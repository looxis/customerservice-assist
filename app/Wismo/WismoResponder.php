<?php

namespace App\Wismo;

use App\Analysis\AnalysisException;
use App\Analysis\AnalysisProblem;
use App\Analysis\LanguageModel;
use App\Analysis\Prompt;
use App\Analysis\Pseudonymizer;
use App\Analysis\ReplyLanguage;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsOrder;
use App\Eocs\EocsProblem;
use App\Eocs\EocsShipment;
use App\Knowledge\SalesChannels;
use App\Lettermint\DeliveryNotice;
use App\Lettermint\DeliveryNotices;
use App\Models\LabelTicket;
use App\Models\WismoRun;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\Settings\SettingsStore;
use App\Tracking\DeliveryText;
use App\Tracking\ShipmentHistory;
use App\Tracking\ShipmentTracker;
use App\Tracking\TrackingException;
use App\Tracking\TrackingStatus;
use App\Zammad\ArticleKind;
use App\Zammad\OutgoingReply;
use App\Zammad\ReplyException;
use App\Zammad\ReplyTarget;
use App\Zammad\Ticket;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use App\Zammad\ZammadProblem;
use App\Zammad\ZammadReplySender;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * The automatic reply to "Where is my order?" (PROJ-82): finds the order of the ticket, asks the carrier for the state of
 * the shipment, lets the language model write the reply from those facts and sends it as the technical Zammad user, or
 * writes an internal note when the case needs a person. Every ticket is handled once and logged in a WismoRun.
 *
 * Since PROJ-99 the case of each shipment comes from its whole tracking history, and the reply closes the ticket
 * unless a person has to look at it (a special delivery problem, an order stuck without shipment).
 */
class WismoResponder
{
    public const int MAX_ATTEMPTS = 3;

    public const int RETRY_SECONDS = 120;

    public const int MAX_SHIPMENTS = 3;

    /** Seconds the language model may take; the job must end before the queue offers it again (retry_after 90). */
    public const int MODEL_TIMEOUT = 40;

    /** Minutes after which a run that is still "working" counts as abandoned (worker killed) and is taken over. */
    public const int STALE_MINUTES = 5;

    public function __construct(
        private readonly ZammadClient $zammad,
        private readonly EocsClient $eocs,
        private readonly OrderNumberDetector $detector,
        private readonly ShipmentTracker $tracker,
        private readonly LanguageModel $model,
        private readonly ZammadReplySender $sender,
        private readonly SettingsStore $settings,
        private readonly DeliveryNotices $notices,
    ) {}

    /**
     * Handles the ticket once. Returns the seconds after which the job should try again, or null when it is done.
     */
    public function run(LabelTicket $label, ?CarbonImmutable $now = null): ?int
    {
        $now ??= CarbonImmutable::now();
        $run = $this->claim($label);

        if ($run === null) {
            return null;
        }

        try {
            $this->process($run, $label, $now);
        } catch (EocsException|ZammadException|ReplyException|TrackingException|AnalysisException $exception) {
            return $this->problem($run, $label, $exception);
        }

        return null;
    }

    /**
     * Writes a note and the end state when a ticket could not be handled after all attempts; called by the job when it fails.
     */
    public function giveUp(LabelTicket $label, string $reason): void
    {
        $run = WismoRun::query()->where('zammad_id', $label->zammad_id)->first();

        if ($run === null || ! in_array($run->result, [WismoRun::WORKING, WismoRun::RETRY], true)) {
            return;
        }

        $this->finish($run, WismoRun::ERROR, $reason);
        $this->noteQuietly($label, 'WISMO: nicht automatisch beantwortet', "⚠️ WISMO erkannt, aber die automatische Antwort ist fehlgeschlagen: {$reason}\nBitte das Ticket manuell bearbeiten.");
    }

    /**
     * One row per ticket decides who handles it: the creator of the row, or the job that takes a row back from "retry".
     */
    private function claim(LabelTicket $label): ?WismoRun
    {
        $run = WismoRun::query()->where('zammad_id', $label->zammad_id)->first();

        if ($run === null) {
            try {
                return WismoRun::query()->create(['zammad_id' => $label->zammad_id, 'ticket_number' => $label->number, 'mode' => (string) $this->settings->value('wismo_mode'), 'result' => WismoRun::WORKING, 'attempts' => 1]);
            } catch (UniqueConstraintViolationException) {
                return null;
            }
        }

        if ($run->result === WismoRun::RETRY && WismoRun::query()->whereKey($run->id)->where('result', WismoRun::RETRY)->update(['result' => WismoRun::WORKING, 'attempts' => $run->attempts + 1]) === 1) {
            return $run->refresh();
        }

        return $run->result === WismoRun::WORKING ? $this->takeOver($run, $label) : null;
    }

    /**
     * A run that stayed "working" for minutes was abandoned (the worker was killed): the next job takes it over, unless it
     * was abandoned too often already; then a person gets a note.
     */
    private function takeOver(WismoRun $run, LabelTicket $label): ?WismoRun
    {
        $stale = now()->subMinutes(self::STALE_MINUTES);

        if ($run->updated_at === null || $run->updated_at->greaterThan($stale)) {
            return null;
        }

        if ($run->attempts >= self::MAX_ATTEMPTS) {
            if (WismoRun::query()->whereKey($run->id)->where('result', WismoRun::WORKING)->where('updated_at', $run->updated_at)->update(['result' => WismoRun::ERROR, 'reason' => 'Die Bearbeitung wurde mehrfach abgebrochen.', 'processed_at' => now()]) === 1) {
                $this->noteQuietly($label, 'WISMO: nicht automatisch beantwortet', "⚠️ WISMO erkannt, aber die automatische Bearbeitung wurde mehrfach abgebrochen.\nBitte das Ticket manuell bearbeiten.");
            }

            return null;
        }

        $taken = WismoRun::query()->whereKey($run->id)->where('result', WismoRun::WORKING)->where('updated_at', $run->updated_at)->update(['attempts' => $run->attempts + 1, 'updated_at' => now()]);

        return $taken === 1 ? $run->refresh() : null;
    }

    private function process(WismoRun $run, LabelTicket $label, CarbonImmutable $now): void
    {
        $mode = (string) $this->settings->value('wismo_mode');
        $run->update(['mode' => $mode]);

        if ($mode === 'off') {
            $this->finish($run, WismoRun::SKIPPED, 'Der Schalter „WISMO automatisch beantworten“ steht auf „aus“.');

            return;
        }

        $ticket = $this->zammad->ticket($label->number);
        $skip = $this->skipReason($ticket, $label);

        if ($skip !== null) {
            $this->finish($run, WismoRun::SKIPPED, $skip);

            return;
        }

        $token = (string) config('services.zammad.reply_token');

        if ($token === '') {
            $this->finish($run, WismoRun::ERROR, 'Kein Zugang des technischen Zammad-Nutzers (ZAMMAD_TOKEN).');

            return;
        }

        $numbers = $this->detector->detect($ticket);

        if ($numbers === []) {
            $this->note($run, $ticket, $token, 'WISMO: keine Bestellnummer', '⚠️ WISMO erkannt, aber keine Bestellnummer im Tickettext gefunden. Bitte manuell nachfragen.', 'Keine Bestellnummer im Ticket gefunden.');

            return;
        }

        $lookup = $this->eocs->lookup([$numbers[0]])[0] ?? null;
        $order = $lookup?->orders[0] ?? null;
        $run->update(['order_number' => $numbers[0]->value]);

        if ($order === null) {
            $this->note($run, $ticket, $token, 'WISMO: Bestellung nicht gefunden', "⚠️ WISMO erkannt. Bestellnummer {$numbers[0]->value} wurde in EOCS nicht gefunden. Bitte manuell prüfen.", 'Bestellung in EOCS nicht gefunden.');

            return;
        }

        $this->answer($run, $ticket, $label, $order, $numbers, $token, $mode, $now);
    }

    /**
     * Why the ticket is left alone, or null.
     */
    private function skipReason(Ticket $ticket, LabelTicket $label): ?string
    {
        $customer = collect($ticket->articles)->filter(fn ($article): bool => $article->kind === ArticleKind::Customer && ! $article->automatic)->last();

        return match (true) {
            $ticket->mergedIntoNumber !== null => 'Das Ticket wurde zusammengeführt.',
            $this->channelWithoutProducts($ticket) !== null => 'Übersprungen: Verkaufskanal „'.$this->channelWithoutProducts($ticket).'“ ohne Produkte und Versand.',
            $ticket->closed => 'Das Ticket ist schon geschlossen.',
            collect($ticket->articles)->contains(fn ($article): bool => $article->kind === ArticleKind::Agent && ! $article->automatic) => 'Das Ticket wurde schon beantwortet.',
            $customer === null => 'Keine Nachricht des Kunden im Ticket.',
            $label->article_id !== null && $customer->id !== null && (int) $label->article_id !== (int) $customer->id => 'Es liegt eine neuere Nachricht des Kunden vor.',
            default => null,
        };
    }

    /**
     * @param  list<OrderNumber>  $numbers
     */
    private function answer(WismoRun $run, Ticket $ticket, LabelTicket $label, EocsOrder $order, array $numbers, string $token, string $mode, CarbonImmutable $now): void
    {
        $target = ReplyTarget::for($ticket);

        if ($target === null) {
            $this->note($run, $ticket, $token, 'WISMO: keine Antwortadresse', "⚠️ WISMO erkannt, aber das Ticket hat keine eindeutige Antwortadresse. Bitte manuell prüfen (Bestellung {$order->externalNumber}).", 'Keine eindeutige Antwortadresse im Ticket.');

            return;
        }

        $postal = $order->shippingAddress?->postalCode;
        $shipments = collect($order->shipments)->filter(fn (EocsShipment $shipment): bool => trim((string) $shipment->trackingNumber) !== '')->take(self::MAX_SHIPMENTS)->values();
        $statuses = $shipments->map(fn (EocsShipment $shipment): TrackingStatus => $this->tracker->track($shipment, $postal) ?? new TrackingStatus(trim((string) $shipment->carrier) !== '' ? trim((string) $shipment->carrier) : 'Versanddienstleister', trim((string) $shipment->trackingNumber), false, 'Der aktuelle Status liegt uns nicht vor.'))->all();

        $problem = collect($statuses)->first(fn (TrackingStatus $status): bool => $status->problem !== null);
        $stuck = $shipments->isEmpty() ? $order->stuckWorkdays($now) : null;
        $cases = array_map(fn (TrackingStatus $status): string => $status->case(), $statuses);
        $case = $statuses === [] ? ($stuck !== null ? WismoRun::STUCK : WismoRun::NOT_SHIPPED) : ShipmentHistory::worst($cases);
        $close = $stuck === null && collect($cases)->every(fn (string $one): bool => ShipmentHistory::closable($one));
        $run->update([
            'shipment_case' => $case,
            'shipment_history' => $statuses === [] ? null : array_map(fn (TrackingStatus $status): array => ['tracking_number' => $status->trackingNumber, 'case' => $status->case(), 'milestones' => $status->history === null ? [] : $status->history->milestones], $statuses),
            'carrier' => collect($statuses)->pluck('carrier')->unique()->implode(', ') ?: null,
            'tracking_number' => collect($statuses)->pluck('trackingNumber')->implode(', ') ?: null,
            'delivery_problem' => $problem !== null,
            'delivery_problem_reason' => $problem?->problem === null ? null : mb_substr($problem->problem, 0, 250),
            'pickup_address_used' => collect($statuses)->contains(fn (TrackingStatus $status): bool => $status->problem === null && $status->pickupLocation !== null),
            'stuck_workdays' => $stuck,
        ]);

        $facts = $this->facts($order, $statuses, $stuck, $problem !== null, $this->notifications($order, $cases, $target->to, $ticket->number));
        $reply = $this->write($run, $ticket, $facts, $statuses);
        $warnings = $this->warnings($problem, $stuck, $case);

        if (is_string($reply)) {
            $this->note($run, $ticket, $token, 'WISMO: nicht automatisch beantwortet', "⚠️ WISMO erkannt, aber die Antwort wurde nicht gesendet: {$reply}\nBitte manuell bearbeiten (Bestellung {$order->externalNumber}).".$warnings, $reply);

            return;
        }

        $body = $reply['text']."\n\n".$reply['signature']."\n\n".$reply['notice'];
        $run->update(['reply_text' => $body, 'reply_text_de' => $reply['de']]);
        $summary = collect($statuses)->map(fn (TrackingStatus $status): string => "{$status->carrier} {$status->trackingNumber}: {$status->status}")->implode("\n");

        if ($mode === 'dry') {
            $closing = $close ? 'Das Ticket würde automatisch geschlossen.' : 'Das Ticket bliebe offen.';
            $this->noteQuietly($ticket, 'WISMO Trockenlauf', "🧪 Trockenlauf: so würde die App antworten (nicht gesendet). {$closing}{$warnings}\n\nBestellung {$order->externalNumber}\n{$summary}\n\n--- Antwort ---\n{$body}\n\n--- Deutsche Fassung ---\n{$reply['de']}", $token);
            $this->finish($run, WismoRun::DRY_RUN, null);

            return;
        }

        if (! $this->send($run, $ticket, $target, $body, $token, $close ? 'closed' : 'open')) {
            $this->noteQuietly($ticket, 'WISMO: Versand unklar', "⚠️ WISMO: Der Versand der Antwort an Zammad blieb unklar. Bitte im Ticket prüfen, ob die Antwort angekommen ist; die App sendet nicht erneut.{$warnings}", $token);

            return;
        }

        $when = $now->setTimezone('Europe/Berlin')->format('d.m.Y \u\m H:i');
        $note = "🤖 Automatisch beantwortet (WISMO) am {$when} Uhr.".($close ? ' Ticket automatisch geschlossen.' : '').$warnings;

        if (! $reply['german']) {
            $note .= "\n\nBestellung {$order->externalNumber}\n{$summary}\n\n--- Deutsche Fassung der Antwort ---\n{$reply['de']}";
        }

        $this->noteQuietly($ticket, 'WISMO automatisch beantwortet', $note, $token);
    }

    /**
     * @param  list<TrackingStatus>  $statuses
     * @param  list<array{mail: string, sent_at: ?string}>  $notifications
     * @return array<string, mixed>
     */
    private function facts(EocsOrder $order, array $statuses, ?int $stuck, bool $problem, array $notifications = []): array
    {
        $shipped = collect($order->shipments)->pluck('shippedAt')->filter()->sort()->first();

        return array_filter([
            'order_number' => $order->externalNumber,
            'order_status' => $order->statusName,
            'order_date' => $order->orderedAt?->setTimezone('Europe/Berlin')->format('d.m.Y'),
            'shipped_on' => $shipped?->setTimezone('Europe/Berlin')->format('d.m.Y'),
            'shipments' => array_map(fn (TrackingStatus $status): array => $status->facts(), $statuses),
            'delivery_problem' => $problem,
            'no_shipment_for_workdays' => $stuck,
            'notifications' => $notifications,
            'reply_rules' => self::replyRules($statuses, $notifications),
        ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== false);
    }

    /**
     * The few rules of the case that the model must not miss (PROJ-99); the long prompt alone was not enough for the
     * smaller models. Decided in code from the cases of the shipments.
     *
     * @param  list<TrackingStatus>  $statuses
     * @param  list<array{mail: string, sent_at: ?string}>  $notifications
     * @return list<string>
     */
    public static function replyRules(array $statuses, array $notifications): array
    {
        $cases = array_map(fn (TrackingStatus $status): string => $status->case(), $statuses);
        $returned = array_intersect($cases, [ShipmentHistory::RETURNED_NOT_COLLECTED, ShipmentHistory::RETURNED_ADDRESS, ShipmentHistory::RETURNED_REFUSED]) !== [];
        $attempted = collect($statuses)->contains(fn (TrackingStatus $status): bool => collect($status->history?->milestones ?? [])->contains(fn (array $milestone): bool => array_intersect(DeliveryText::kinds($milestone['text']), ['attempt', 'absent', 'pickup', 'not_collected']) !== []));
        $rules = [];

        if (collect($statuses)->contains(fn (TrackingStatus $status): bool => $status->link !== null)) {
            $rules[] = 'Nenne für jede Sendung den `tracking_link` unverändert als reinen Text in einer eigenen Zeile, zusammen mit der Sendungsnummer und dem Hinweis auf die Webseite des Paketdienstes; ohne Link wird die Antwort nicht gesendet.';
        }

        if ($returned || in_array(ShipmentHistory::PICKUP_READY, $cases, true) || $attempted) {
            $rules[] = $notifications === []
                ? 'Sage ausdrücklich, dass der Kunde über die Zustellung benachrichtigt wurde und der Paketdienst in der Regel zusätzlich eine Benachrichtigungskarte im Briefkasten hinterlässt.'
                : 'Sage ausdrücklich, dass wir den Kunden per E-Mail benachrichtigt haben, mit Art und Zeitpunkt aus `notifications` (Datum und Uhrzeit aus `sent_at`).';
        }

        if (in_array(ShipmentHistory::RETURNED_NOT_COLLECTED, $cases, true)) {
            $days = collect($statuses)->map(fn (TrackingStatus $status): ?int => $status->history?->storageDays)->filter()->first();
            $rules[] = $days !== null ? "Nenne die Lagerfrist von {$days} Tagen." : 'Nenne die Lagerfrist mit „mindestens 5 Tage“.';
        }

        if (array_diff($cases, [ShipmentHistory::IN_TRANSIT, ShipmentHistory::PICKUP_READY]) !== []) {
            $rules[] = 'Erwähne keine Zustelloptionen, die sich beim Paketdienst ändern lassen.';
        }

        $address = array_intersect($cases, [ShipmentHistory::RETURNED_ADDRESS, ShipmentHistory::ADDRESS_HOLD]) !== [];
        $receive = array_intersect($cases, [ShipmentHistory::RETURNED_NOT_COLLECTED, ShipmentHistory::RETURNED_REFUSED]) !== [];

        if ($returned) {
            $rules[] = 'Weise vor der Bitte am Ende freundlich darauf hin, dass ein erneuter Versand Kosten verursacht, die wir leider nicht übernehmen können, '
                .'und dass auch nach dem Eingang des Pakets bei uns keine Erstattung möglich ist, weil das Produkt nach dem Wunsch des Kunden personalisiert wurde und nicht anderweitig verwendet werden kann. '
                .'Nenne keinen Betrag und verwende nicht die Wörter „kostenlos“ oder „gratis“.';
        }

        if ($address || $receive) {
            $rules[] = 'Der letzte Absatz ist die Bitte an den Kunden: die Details in der Sendungsverfolgung prüfen, '
                .($address ? 'seine Lieferadresse sowie den Namen an Klingel und Briefkasten prüfen, sich zur Klärung bei uns melden und uns seine korrekte Lieferadresse bestätigen' : 'sich zur Klärung bei uns melden und uns bestätigen, dass er das Paket auch entgegennehmen kann (zum Beispiel bei Abwesenheit oder Urlaub)')
                .'. Versprich keinen erneuten Versand.';
        }

        return $rules;
    }

    /**
     * The reply as text and German version, or the reason why none could be made.
     *
     * @param  array<string, mixed>  $facts
     * @param  list<TrackingStatus>  $statuses
     * @return array{text: string, de: string, german: bool, signature: string, notice: string}|string
     *
     * @throws AnalysisException
     */
    private function write(WismoRun $run, Ticket $ticket, array $facts, array $statuses): array|string
    {
        $prompt = Prompt::load('wismo');
        $model = (string) $this->settings->value('model_wismo');
        $pseudonymizer = app(Pseudonymizer::class);
        $message = $this->customerText($ticket);

        $signature = trim((string) $this->settings->value('wismo_signature'));
        $notice = trim((string) $this->settings->value('wismo_notice'));
        $input = "Nachricht des Kunden:\n".$pseudonymizer->apply($message)."\n\nSignatur (deutsch):\n{$signature}\n\nHinweiszeile (deutsch):\n{$notice}\n\nAngaben zur Bestellung und Sendung (JSON):\n".json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $answer = $this->model->ask(new WismoAgent($prompt->text), $input, $model, 'wismo', $ticket->number, self::MODEL_TIMEOUT);
        $run->update(['prompt_version' => $prompt->version, 'model' => $model, 'input_tokens' => $answer['input_tokens'], 'output_tokens' => $answer['output_tokens'], 'duration_ms' => $answer['duration_ms']]);

        $text = trim($pseudonymizer->restorePlain((string) ($answer['data']['reply'] ?? '')));
        $de = trim($pseudonymizer->restorePlain((string) ($answer['data']['reply_de'] ?? '')));
        $closing = trim($pseudonymizer->restorePlain((string) ($answer['data']['signature'] ?? '')));
        $footer = trim($pseudonymizer->restorePlain((string) ($answer['data']['notice'] ?? '')));

        if ($text === '') {
            return 'Das Sprachmodell hat keinen Antworttext geliefert.';
        }

        if (preg_match('/\[[A-ZÄÖÜ][A-ZÄÖÜ\-]*(?:_\d+)?\]/u', $text.$de) === 1) {
            return 'Im Antworttext ist ein Platzhalter nicht gefüllt.';
        }

        $guard = ReplyGuard::violation($text."\n".$closing."\n".$footer, array_values(array_filter(array_map(fn (TrackingStatus $status): ?string => $status->link, $statuses))));

        if ($guard !== null) {
            return $guard;
        }

        foreach ($statuses as $status) {
            if (! str_contains($text, $status->trackingNumber)) {
                return "Die Sendungsnummer {$status->trackingNumber} fehlt im Antworttext.";
            }

            if ($status->link !== null && ! str_contains($text, $status->link)) {
                return 'Der Trackinglink fehlt im Antworttext.';
            }
        }

        $language = mb_strtolower(trim((string) ($answer['data']['language'] ?? '')));
        $german = $language !== '' ? preg_match('/^(de\b|deutsch|german)/u', $language) === 1 : ($de === '' || $de === $text);
        $closing = $german || $closing === '' ? $signature : $closing;
        $footer = $german || $footer === '' ? $notice : $footer;
        $mismatch = ReplyLanguage::problem($this->customerBody($ticket), $text."\n".$closing."\n".$footer, $de, $language);

        if ($mismatch !== null) {
            return $mismatch;
        }

        return [
            'text' => $text,
            'de' => $de !== '' ? $de : $text,
            'german' => $german,
            'signature' => $closing,
            'notice' => $footer,
        ];
    }

    private function customerText(Ticket $ticket): string
    {
        return trim('Betreff: '.$ticket->title."\n\n".$this->customerBody($ticket));
    }

    private function customerBody(Ticket $ticket): string
    {
        $last = collect($ticket->articles)->filter(fn ($article): bool => $article->kind === ArticleKind::Customer && ! $article->automatic)->last();

        return mb_substr(trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $last?->body->toHtml() ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 4000);
    }

    /**
     * Sends the reply, in a way that never sends twice: the row says "sending" first, and an unclear outcome stays that way
     * (false). A reply that certainly was not sent is thrown again for a new attempt.
     *
     * @throws ReplyException
     */
    private function send(WismoRun $run, Ticket $ticket, ReplyTarget $target, string $body, string $token, string $state): bool
    {
        $run->update(['result' => WismoRun::SENDING, 'sending_at' => now()]);

        try {
            $this->sender->send($token, new OutgoingReply($ticket->id, $target->to, $target->subject, $body, $target->inReplyTo, $state, null));
        } catch (ReplyException $exception) {
            if ($exception->problem->notSent()) {
                $run->update(['result' => WismoRun::WORKING, 'sending_at' => null]);

                throw $exception;
            }

            $this->finish($run, WismoRun::SENDING, 'Der Versand an Zammad blieb unklar: '.$exception->problem->message('customerserviceassist'));

            return false;
        }

        $run->update(['auto_closed' => $state === 'closed']);
        $this->finish($run, WismoRun::SENT, null);

        return true;
    }

    private function note(WismoRun $run, Ticket $ticket, string $token, string $subject, string $body, string $reason): void
    {
        if ((string) $this->settings->value('wismo_mode') === 'on') {
            $this->noteQuietly($ticket, $subject, $body, $token);
        } else {
            $this->noteQuietly($ticket, $subject, "🧪 Trockenlauf (die Notiz wäre so geschrieben worden):\n".$body, $token);
        }

        $this->finish($run, WismoRun::NOTE, $reason);
    }

    private function noteQuietly(Ticket|LabelTicket $ticket, string $subject, string $body, ?string $token = null): void
    {
        $token ??= (string) config('services.zammad.reply_token');

        if ($token === '') {
            return;
        }

        try {
            $this->sender->addNote($token, $ticket instanceof Ticket ? $ticket->id : (int) $ticket->zammad_id, $subject, trim($body));
        } catch (ReplyException $exception) {
            Log::warning('WISMO note not written', ['ticket' => $ticket->number, 'problem' => $exception->problem->value]);
        }
    }

    /**
     * The lines of the internal note about the case: a special delivery problem needs a person, a return or an address
     * problem is with the customer now.
     */
    private function warnings(?TrackingStatus $problem, ?int $stuck, ?string $case): string
    {
        $line = match (true) {
            $problem === null => '',
            $case === ShipmentHistory::SPECIAL => "\n⚠️ Zustellproblem, bitte prüfen: ".$problem->problem,
            default => "\nℹ️ ".(WismoRun::cases()[$case] ?? 'Zustellproblem').': '.$problem->problem.' Der Kunde wurde gebeten, sich bei uns zu melden.',
        };

        return $line.($stuck === null ? '' : "\n⚠️ Die Bestellung ist seit {$stuck} Werktagen ohne Versand und möglicherweise hängen geblieben.");
    }

    /**
     * The notification mails LOOXIS sent for the order (PROJ-99, from Lettermint as in PROJ-97): "Paket kommt heute" and
     * the pickup notice, with the time they were sent. Asked only when the parcel was not simply delivered; a problem at
     * Lettermint never stops the reply.
     *
     * @param  list<string>  $cases
     * @return list<array{mail: string, sent_at: ?string}>
     */
    private function notifications(EocsOrder $order, array $cases, string $email, string $ticketNumber): array
    {
        $worth = array_diff($cases, [ShipmentHistory::DELIVERED, ShipmentHistory::UNKNOWN]) !== [];

        if (! $worth || ! $this->notices->enabled()) {
            return [];
        }

        try {
            $found = $this->notices->forOrder($order->externalNumber, $email, $ticketNumber);
        } catch (\Throwable $exception) {
            Log::warning('WISMO notifications not read', ['ticket' => $ticketNumber, 'problem' => $exception->getMessage()]);

            return [];
        }

        return collect($found['notices'])
            ->filter(fn (DeliveryNotice $notice): bool => $notice->kind !== 'other' && $notice->sent())
            ->map(fn (DeliveryNotice $notice): array => ['mail' => DeliveryNotice::KIND_LABELS[$notice->kind], 'sent_at' => $notice->sentAt])
            ->reverse()
            ->values()
            ->all();
    }

    private function finish(WismoRun $run, string $result, ?string $reason): void
    {
        $run->update(['result' => $result, 'reason' => $reason === null ? null : mb_substr($reason, 0, 250), 'processed_at' => now()]);
    }

    /**
     * A problem on the way: a permanent one ends the run with a note, a temporary one asks the job to come back (up to three attempts).
     */
    private function problem(WismoRun $run, LabelTicket $label, \Throwable $exception): ?int
    {
        [$message, $temporary] = match (true) {
            $exception instanceof EocsException => ['EOCS: '.$exception->problem->message(), $exception->problem !== EocsProblem::Misconfigured],
            $exception instanceof ZammadException => ['Zammad: '.$exception->problem->message($label->number), $exception->problem !== ZammadProblem::Misconfigured && $exception->problem !== ZammadProblem::NotFound],
            $exception instanceof ReplyException => ['Zammad: '.$exception->problem->message('customerserviceassist'), $exception->problem->notSent() && in_array($exception->problem->value, ['unavailable'], true)],
            $exception instanceof TrackingException => [$exception->getMessage(), $exception->temporary],
            $exception instanceof AnalysisException => ['Sprachmodell: '.$exception->problem->message(), $exception->problem !== AnalysisProblem::Misconfigured],
            default => [$exception->getMessage(), false],
        };

        Log::warning('WISMO problem', ['ticket' => $label->number, 'attempt' => $run->attempts, 'problem' => $message]);

        if (! $temporary || $run->attempts >= self::MAX_ATTEMPTS) {
            $this->finish($run, WismoRun::ERROR, $message);
            $this->noteQuietly($label, 'WISMO: nicht automatisch beantwortet', "⚠️ WISMO erkannt, aber die automatische Antwort ist fehlgeschlagen: {$message}\nBitte das Ticket manuell bearbeiten.");

            return null;
        }

        $run->update(['result' => WismoRun::RETRY, 'reason' => mb_substr($message, 0, 250)]);

        return self::RETRY_SECONDS * $run->attempts;
    }

    /**
     * The name of the channel when the ticket subject names a channel without products (PROJ-94, e.g. WeCreateYour);
     * its texts about orders and shipping do not fit there.
     */
    private function channelWithoutProducts(Ticket $ticket): ?string
    {
        $channels = app(SalesChannels::class);
        $channel = $channels->fromSubject($ticket->title);

        return $channel !== null && $channels->withoutProducts($channel) ? $channels->label($channel) : null;
    }
}
