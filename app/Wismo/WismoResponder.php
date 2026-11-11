<?php

namespace App\Wismo;

use App\Analysis\AnalysisException;
use App\Analysis\AnalysisProblem;
use App\Analysis\LanguageModel;
use App\Analysis\Prompt;
use App\Analysis\Pseudonymizer;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsOrder;
use App\Eocs\EocsProblem;
use App\Eocs\EocsShipment;
use App\Models\LabelTicket;
use App\Models\WismoRun;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\Settings\SettingsStore;
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
 */
class WismoResponder
{
    public const int MAX_ATTEMPTS = 3;

    public const int RETRY_SECONDS = 120;

    public const int MAX_SHIPMENTS = 3;

    public function __construct(
        private readonly ZammadClient $zammad,
        private readonly EocsClient $eocs,
        private readonly OrderNumberDetector $detector,
        private readonly ShipmentTracker $tracker,
        private readonly LanguageModel $model,
        private readonly ZammadReplySender $sender,
        private readonly SettingsStore $settings,
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

        return null;
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
        $run->update([
            'carrier' => collect($statuses)->pluck('carrier')->unique()->implode(', ') ?: null,
            'tracking_number' => collect($statuses)->pluck('trackingNumber')->implode(', ') ?: null,
            'delivery_problem' => $problem !== null,
            'delivery_problem_reason' => $problem?->problem === null ? null : mb_substr($problem->problem, 0, 250),
            'pickup_address_used' => collect($statuses)->contains(fn (TrackingStatus $status): bool => $status->problem === null && $status->pickupLocation !== null),
            'stuck_workdays' => $stuck,
        ]);

        $facts = $this->facts($order, $statuses, $stuck, $problem !== null);
        $reply = $this->write($run, $ticket, $facts, $statuses);

        if (is_string($reply)) {
            $this->note($run, $ticket, $token, 'WISMO: nicht automatisch beantwortet', "⚠️ WISMO erkannt, aber die Antwort wurde nicht gesendet: {$reply}\nBitte manuell bearbeiten (Bestellung {$order->externalNumber}).".$this->warnings($problem, $stuck), $reply);

            return;
        }

        $body = $reply['text']."\n\n".trim((string) $this->settings->value('wismo_signature'))."\n\n".trim((string) $this->settings->value('wismo_notice'));
        $run->update(['reply_text' => $body, 'reply_text_de' => $reply['de']]);
        $summary = collect($statuses)->map(fn (TrackingStatus $status): string => "{$status->carrier} {$status->trackingNumber}: {$status->status}")->implode("\n");

        if ($mode === 'dry') {
            $warnings = $this->warnings($problem, $stuck);
            $this->noteQuietly($ticket, 'WISMO Trockenlauf', "🧪 Trockenlauf: so würde die App antworten (nicht gesendet).{$warnings}\n\nBestellung {$order->externalNumber}\n{$summary}\n\n--- Antwort ---\n{$body}\n\n--- Deutsche Fassung ---\n{$reply['de']}", $token);
            $this->finish($run, WismoRun::DRY_RUN, null);

            return;
        }

        $warnings = $this->warnings($problem, $stuck);

        if (! $this->send($run, $ticket, $target, $body, $token)) {
            $this->noteQuietly($ticket, 'WISMO: Versand unklar', "⚠️ WISMO: Der Versand der Antwort an Zammad blieb unklar. Bitte im Ticket prüfen, ob die Antwort angekommen ist; die App sendet nicht erneut.{$warnings}", $token);

            return;
        }

        $when = $now->setTimezone('Europe/Berlin')->format('d.m.Y \u\m H:i');
        $this->noteQuietly($ticket, 'WISMO automatisch beantwortet', "🤖 Automatisch beantwortet (WISMO) am {$when} Uhr.{$warnings}\n\nBestellung {$order->externalNumber}\n{$summary}\n\n--- Deutsche Fassung der Antwort ---\n{$reply['de']}", $token);
    }

    /**
     * @param  list<TrackingStatus>  $statuses
     * @return array<string, mixed>
     */
    private function facts(EocsOrder $order, array $statuses, ?int $stuck, bool $problem): array
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
        ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== false);
    }

    /**
     * The reply as text and German version, or the reason why none could be made.
     *
     * @param  array<string, mixed>  $facts
     * @param  list<TrackingStatus>  $statuses
     * @return array{text: string, de: string}|string
     *
     * @throws AnalysisException
     */
    private function write(WismoRun $run, Ticket $ticket, array $facts, array $statuses): array|string
    {
        $prompt = Prompt::load('wismo');
        $model = (string) $this->settings->value('model_wismo');
        $pseudonymizer = app(Pseudonymizer::class);
        $message = $this->customerText($ticket);

        $input = "Nachricht des Kunden:\n".$pseudonymizer->apply($message)."\n\nAngaben zur Bestellung und Sendung (JSON):\n".json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $answer = $this->model->ask(new WismoAgent($prompt->text), $input, $model, 'wismo', $ticket->number);
        $run->update(['prompt_version' => $prompt->version, 'model' => $model, 'input_tokens' => $answer['input_tokens'], 'output_tokens' => $answer['output_tokens'], 'duration_ms' => $answer['duration_ms']]);

        $text = trim($pseudonymizer->restorePlain((string) ($answer['data']['reply'] ?? '')));
        $de = trim($pseudonymizer->restorePlain((string) ($answer['data']['reply_de'] ?? '')));

        if ($text === '') {
            return 'Das Sprachmodell hat keinen Antworttext geliefert.';
        }

        if (preg_match('/\[[A-ZÄÖÜ][A-ZÄÖÜ\-]*(?:_\d+)?\]/u', $text.$de) === 1) {
            return 'Im Antworttext ist ein Platzhalter nicht gefüllt.';
        }

        foreach ($statuses as $status) {
            if (! str_contains($text, $status->trackingNumber)) {
                return "Die Sendungsnummer {$status->trackingNumber} fehlt im Antworttext.";
            }

            if ($status->link !== null && ! str_contains($text, $status->link)) {
                return 'Der Trackinglink fehlt im Antworttext.';
            }
        }

        return ['text' => $text, 'de' => $de !== '' ? $de : $text];
    }

    private function customerText(Ticket $ticket): string
    {
        $last = collect($ticket->articles)->filter(fn ($article): bool => $article->kind === ArticleKind::Customer && ! $article->automatic)->last();
        $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $last?->body->toHtml() ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return trim('Betreff: '.$ticket->title."\n\n".mb_substr($text, 0, 4000));
    }

    /**
     * Sends the reply, in a way that never sends twice: the row says "sending" first, and an unclear outcome stays that way
     * (false). A reply that certainly was not sent is thrown again for a new attempt.
     *
     * @throws ReplyException
     */
    private function send(WismoRun $run, Ticket $ticket, ReplyTarget $target, string $body, string $token): bool
    {
        $run->update(['result' => WismoRun::SENDING, 'sending_at' => now()]);

        try {
            $this->sender->send($token, new OutgoingReply($ticket->id, $target->to, $target->subject, $body, $target->inReplyTo, 'open', null));
        } catch (ReplyException $exception) {
            if ($exception->problem->notSent()) {
                $run->update(['result' => WismoRun::WORKING, 'sending_at' => null]);

                throw $exception;
            }

            $this->finish($run, WismoRun::SENDING, 'Der Versand an Zammad blieb unklar: '.$exception->problem->message('customerserviceassist'));

            return false;
        }

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

    private function warnings(?TrackingStatus $problem, ?int $stuck): string
    {
        return ($problem === null ? '' : "\n⚠️ Zustellproblem, bitte prüfen: ".$problem->problem)
            .($stuck === null ? '' : "\n⚠️ Die Bestellung ist seit {$stuck} Werktagen ohne Versand und möglicherweise hängen geblieben.");
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
}
