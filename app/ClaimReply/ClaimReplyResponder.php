<?php

namespace App\ClaimReply;

use App\Analysis\AnalysisException;
use App\Analysis\AnalysisProblem;
use App\Analysis\LanguageModel;
use App\Analysis\Prompt;
use App\Analysis\Pseudonymizer;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsProblem;
use App\Models\ClaimReplyRun;
use App\Models\LabelTicket;
use App\Orders\OrderNumberDetector;
use App\Settings\SettingsStore;
use App\Zammad\ArticleKind;
use App\Zammad\OutgoingReply;
use App\Zammad\ReplyException;
use App\Zammad\ReplyTarget;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use App\Zammad\ZammadProblem;
use App\Zammad\ZammadReplySender;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * The automatic first reply to a new complaint (PROJ-85): asks for one or two photos and a short description, or confirms
 * receipt, as the technical Zammad user. The ticket stays open. The order must have been shipped at most 30 days ago;
 * otherwise a note tells the staff why nothing was sent. Every ticket is handled once and logged in a ClaimReplyRun.
 */
class ClaimReplyResponder
{
    public const int MAX_ATTEMPTS = 3;

    public const int RETRY_SECONDS = 120;

    /** A complaint later than this many days after the first shipment is a case for a person (PROJ-79). */
    public const int MAX_SHIPPED_DAYS = 30;

    /** Seconds the language model may take; the job must end before the queue offers it again (retry_after 90). */
    public const int MODEL_TIMEOUT = 40;

    /** Minutes after which a run that is still "working" counts as abandoned (worker killed) and is taken over. */
    public const int STALE_MINUTES = 5;

    private const string NOTE_SUBJECT = 'Reklamation: Vorab-Antwort';

    public function __construct(
        private readonly ZammadClient $zammad,
        private readonly EocsClient $eocs,
        private readonly OrderNumberDetector $detector,
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
        } catch (EocsException|ZammadException|ReplyException|AnalysisException $exception) {
            return $this->problem($run, $label, $exception);
        }

        return null;
    }

    /**
     * Writes a note and the end state when a ticket could not be handled after all attempts; called by the job when it fails.
     */
    public function giveUp(LabelTicket $label, string $reason): void
    {
        $run = ClaimReplyRun::query()->where('zammad_id', $label->zammad_id)->first();

        if ($run === null || ! in_array($run->result, [ClaimReplyRun::WORKING, ClaimReplyRun::RETRY], true)) {
            return;
        }

        $this->finish($run, ClaimReplyRun::ERROR, $reason);
        $this->noteQuietly($label, self::NOTE_SUBJECT, "⚠️ Reklamation erkannt, aber die automatische Vorab-Antwort ist fehlgeschlagen: {$reason}\nBitte das Ticket manuell bearbeiten.");
    }

    /**
     * One row per ticket decides who handles it: the creator of the row, or the job that takes a row back from "retry".
     */
    private function claim(LabelTicket $label): ?ClaimReplyRun
    {
        $run = ClaimReplyRun::query()->where('zammad_id', $label->zammad_id)->first();

        if ($run === null) {
            try {
                return ClaimReplyRun::query()->create(['zammad_id' => $label->zammad_id, 'ticket_number' => $label->number, 'mode' => (string) $this->settings->value('claim_reply_mode'), 'result' => ClaimReplyRun::WORKING, 'attempts' => 1]);
            } catch (UniqueConstraintViolationException) {
                return null;
            }
        }

        if ($run->result === ClaimReplyRun::RETRY && ClaimReplyRun::query()->whereKey($run->id)->where('result', ClaimReplyRun::RETRY)->update(['result' => ClaimReplyRun::WORKING, 'attempts' => $run->attempts + 1]) === 1) {
            return $run->refresh();
        }

        return $run->result === ClaimReplyRun::WORKING ? $this->takeOver($run, $label) : null;
    }

    /**
     * A run that stayed "working" for minutes was abandoned (the worker was killed): the next job takes it over, unless it
     * was abandoned too often already; then a person gets a note.
     */
    private function takeOver(ClaimReplyRun $run, LabelTicket $label): ?ClaimReplyRun
    {
        $stale = now()->subMinutes(self::STALE_MINUTES);

        if ($run->updated_at === null || $run->updated_at->greaterThan($stale)) {
            return null;
        }

        if ($run->attempts >= self::MAX_ATTEMPTS) {
            if (ClaimReplyRun::query()->whereKey($run->id)->where('result', ClaimReplyRun::WORKING)->where('updated_at', $run->updated_at)->update(['result' => ClaimReplyRun::ERROR, 'reason' => 'Die Bearbeitung wurde mehrfach abgebrochen.', 'processed_at' => now()]) === 1) {
                $this->noteQuietly($label, self::NOTE_SUBJECT, "⚠️ Reklamation erkannt, aber die automatische Bearbeitung wurde mehrfach abgebrochen.\nBitte das Ticket manuell bearbeiten.");
            }

            return null;
        }

        $taken = ClaimReplyRun::query()->whereKey($run->id)->where('result', ClaimReplyRun::WORKING)->where('updated_at', $run->updated_at)->update(['attempts' => $run->attempts + 1, 'updated_at' => now()]);

        return $taken === 1 ? $run->refresh() : null;
    }

    private function process(ClaimReplyRun $run, LabelTicket $label, CarbonImmutable $now): void
    {
        $mode = (string) $this->settings->value('claim_reply_mode');
        $run->update(['mode' => $mode]);

        if ($mode === 'off') {
            $this->finish($run, ClaimReplyRun::SKIPPED, 'Der Schalter „Reklamationen automatisch beantworten“ steht auf „aus“.');

            return;
        }

        $ticket = $this->zammad->ticket($label->number);
        $skip = $this->skipReason($ticket, $label);

        if ($skip !== null) {
            $this->finish($run, ClaimReplyRun::SKIPPED, $skip);

            return;
        }

        $token = (string) config('services.zammad.reply_token');

        if ($token === '') {
            $this->finish($run, ClaimReplyRun::ERROR, 'Kein Zugang des technischen Zammad-Nutzers (ZAMMAD_TOKEN).');

            return;
        }

        $numbers = $this->detector->detect($ticket);

        if ($numbers === []) {
            $this->note($run, $ticket, $token, '⚠️ Reklamation erkannt, aber keine Bestellnummer im Tickettext gefunden: keine Vorab-Antwort gesendet.', 'Keine Bestellnummer im Ticket gefunden.');

            return;
        }

        $lookup = $this->eocs->lookup([$numbers[0]])[0] ?? null;
        $order = $lookup?->orders[0] ?? null;
        $run->update(['order_number' => $numbers[0]->value]);

        if ($order === null) {
            $this->note($run, $ticket, $token, "⚠️ Reklamation erkannt, Bestellnummer {$numbers[0]->value} wurde in EOCS nicht gefunden: keine Vorab-Antwort gesendet.", 'Bestellung in EOCS nicht gefunden.');

            return;
        }

        $days = $order->daysSinceFirstShipment($now);
        $run->update(['days_since_shipped' => $days]);

        if ($days === null) {
            $this->note($run, $ticket, $token, "⚠️ Reklamation erkannt, aber die Bestellung {$order->externalNumber} ist noch nicht versendet: keine Vorab-Antwort gesendet.", 'Die Bestellung hat kein Versanddatum.');

            return;
        }

        if ($days > self::MAX_SHIPPED_DAYS) {
            $this->note($run, $ticket, $token, "Keine Vorab-Antwort: Der erste Versand der Bestellung {$order->externalNumber} liegt {$days} Tage zurück (Grenze: ".self::MAX_SHIPPED_DAYS.' Tage).', "Der Versand liegt {$days} Tage zurück (mehr als ".self::MAX_SHIPPED_DAYS.').');

            return;
        }

        $target = ReplyTarget::for($ticket);

        if ($target === null) {
            $this->note($run, $ticket, $token, '⚠️ Reklamation erkannt, aber das Ticket hat keine eindeutige Antwortadresse: keine Vorab-Antwort gesendet.', 'Keine eindeutige Antwortadresse im Ticket.');

            return;
        }

        $this->answer($run, $ticket, $target, $order->externalNumber, $days, $token, $mode, $now);
    }

    /**
     * Why the ticket is left alone, or null.
     */
    private function skipReason(Ticket $ticket, LabelTicket $label): ?string
    {
        $customer = $this->lastCustomerArticle($ticket);

        return match (true) {
            $ticket->mergedIntoNumber !== null => 'Das Ticket wurde zusammengeführt.',
            $ticket->closed => 'Das Ticket ist schon geschlossen.',
            collect($ticket->articles)->contains(fn ($article): bool => $article->kind === ArticleKind::Agent && ! $article->automatic) => 'Das Ticket wurde schon beantwortet.',
            $customer === null => 'Keine Nachricht des Kunden im Ticket.',
            $label->article_id !== null && $customer->id !== null && (int) $label->article_id !== (int) $customer->id => 'Es liegt eine neuere Nachricht des Kunden vor.',
            default => null,
        };
    }

    private function lastCustomerArticle(Ticket $ticket): ?TicketArticle
    {
        return collect($ticket->articles)->filter(fn (TicketArticle $article): bool => $article->kind === ArticleKind::Customer && ! $article->automatic)->last();
    }

    /**
     * Photos in the sense of this function: images the viewer can show (no logos or signature images, PROJ-60).
     */
    private function photoCount(Ticket $ticket): int
    {
        return count(array_filter($this->lastCustomerArticle($ticket)?->attachments ?? [], fn ($attachment): bool => $attachment->isViewable()));
    }

    private function answer(ClaimReplyRun $run, Ticket $ticket, ReplyTarget $target, string $orderNumber, int $days, string $token, string $mode, CarbonImmutable $now): void
    {
        $photos = $this->photoCount($ticket);
        $run->update(['photo_count' => $photos]);

        $reply = $this->write($run, $ticket, $photos);

        if (is_string($reply)) {
            $this->note($run, $ticket, $token, "⚠️ Reklamation erkannt, aber die Vorab-Antwort wurde nicht gesendet: {$reply}\nBitte manuell bearbeiten (Bestellung {$orderNumber}).", $reply);

            return;
        }

        $body = $reply['text']."\n\n".trim((string) $this->settings->value('claim_reply_signature'));
        $run->update(['reply_text' => $body, 'reply_text_de' => $reply['de'], 'scenario' => $reply['scenario']]);
        $scenario = ClaimReplyRun::scenarios()[$reply['scenario']];
        $facts = "Bestellung {$orderNumber}, Versand vor {$days} Tagen, {$photos} ".($photos === 1 ? 'Foto' : 'Fotos');

        if ($mode === 'dry') {
            $this->noteQuietly($ticket, 'Vorab-Antwort Trockenlauf', "🧪 Vorab-Antwort Trockenlauf ({$scenario}): so würde die App antworten (nicht gesendet).\n\n{$facts}\n\n--- Antwort ---\n{$body}\n\n--- Deutsche Fassung ---\n{$reply['de']}", $token);
            $this->finish($run, ClaimReplyRun::DRY_RUN, null);

            return;
        }

        if (! $this->send($run, $ticket, $target, $body, $token)) {
            $this->noteQuietly($ticket, 'Vorab-Antwort: Versand unklar', '⚠️ Vorab-Antwort: Der Versand an Zammad blieb unklar. Bitte im Ticket prüfen, ob die Antwort angekommen ist; die App sendet nicht erneut.', $token);

            return;
        }

        $when = $now->setTimezone('Europe/Berlin')->format('d.m.Y \u\m H:i');
        $this->noteQuietly($ticket, 'Vorab-Antwort automatisch gesendet', "🤖 Vorab-Antwort automatisch gesendet ({$scenario}) am {$when} Uhr.\n\n{$facts}\n\n--- Deutsche Fassung der Antwort ---\n{$reply['de']}", $token);
    }

    /**
     * The reply as text and German version, or the reason why none could be made.
     *
     * @return array{text: string, de: string, scenario: string}|string
     *
     * @throws AnalysisException
     */
    private function write(ClaimReplyRun $run, Ticket $ticket, int $photos): array|string
    {
        $prompt = Prompt::load('claim-reply');
        $model = (string) $this->settings->value('model_claim_reply');
        $pseudonymizer = app(Pseudonymizer::class);
        $facts = ['photo_count' => $photos, 'order_number_known' => true];

        $input = "Nachricht des Kunden:\n".$pseudonymizer->apply($this->customerText($ticket))."\n\nAngaben (JSON):\n".json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $answer = $this->model->ask(new ClaimReplyAgent($prompt->text), $input, $model, 'claim-reply', $ticket->number, self::MODEL_TIMEOUT);
        $run->update(['prompt_version' => $prompt->version, 'model' => $model, 'input_tokens' => $answer['input_tokens'], 'output_tokens' => $answer['output_tokens'], 'duration_ms' => $answer['duration_ms']]);

        $text = trim($pseudonymizer->restorePlain((string) ($answer['data']['reply'] ?? '')));
        $de = trim($pseudonymizer->restorePlain((string) ($answer['data']['reply_de'] ?? '')));
        $scenario = (string) ($answer['data']['scenario'] ?? '');

        if ($text === '') {
            return 'Das Sprachmodell hat keinen Antworttext geliefert.';
        }

        if (! array_key_exists($scenario, ClaimReplyRun::scenarios())) {
            return 'Das Sprachmodell hat keinen gültigen Fall genannt.';
        }

        if (($photos === 0) !== ($scenario === ClaimReplyRun::NO_PHOTO)) {
            $run->update(['scenario' => $scenario]);

            return "Der Fall der Antwort ({$scenario}) passt nicht zur Zahl der Fotos ({$photos}).";
        }

        if (preg_match('/\[[A-ZÄÖÜ][A-ZÄÖÜ\-]*(?:_\d+)?\]/u', $text.$de) === 1) {
            return 'Im Antworttext ist ein Platzhalter nicht gefüllt.';
        }

        $guard = ClaimReplyGuard::violation($text);

        if ($guard !== null) {
            return $guard;
        }

        return ['text' => $text, 'de' => $de !== '' ? $de : $text, 'scenario' => $scenario];
    }

    private function customerText(Ticket $ticket): string
    {
        $last = $this->lastCustomerArticle($ticket);
        $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $last?->body->toHtml() ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return trim('Betreff: '.$ticket->title."\n\n".mb_substr($text, 0, 4000));
    }

    /**
     * Sends the reply, in a way that never sends twice: the row says "sending" first, and an unclear outcome stays that way
     * (false). A reply that certainly was not sent is thrown again for a new attempt. The ticket stays open.
     *
     * @throws ReplyException
     */
    private function send(ClaimReplyRun $run, Ticket $ticket, ReplyTarget $target, string $body, string $token): bool
    {
        $run->update(['result' => ClaimReplyRun::SENDING, 'sending_at' => now()]);

        try {
            $this->sender->send($token, new OutgoingReply($ticket->id, $target->to, $target->subject, $body, $target->inReplyTo, 'open', null));
        } catch (ReplyException $exception) {
            if ($exception->problem->notSent()) {
                $run->update(['result' => ClaimReplyRun::WORKING, 'sending_at' => null]);

                throw $exception;
            }

            $this->finish($run, ClaimReplyRun::SENDING, 'Der Versand an Zammad blieb unklar: '.$exception->problem->message('customerserviceassist'));

            return false;
        }

        $this->finish($run, ClaimReplyRun::SENT, null);

        return true;
    }

    private function note(ClaimReplyRun $run, Ticket $ticket, string $token, string $body, string $reason): void
    {
        if ((string) $this->settings->value('claim_reply_mode') === 'on') {
            $this->noteQuietly($ticket, self::NOTE_SUBJECT, $body, $token);
        } else {
            $this->noteQuietly($ticket, self::NOTE_SUBJECT, "🧪 Trockenlauf (die Notiz wäre so geschrieben worden):\n".$body, $token);
        }

        $this->finish($run, ClaimReplyRun::NOTE, $reason);
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
            Log::warning('Claim reply note not written', ['ticket' => $ticket->number, 'problem' => $exception->problem->value]);
        }
    }

    private function finish(ClaimReplyRun $run, string $result, ?string $reason): void
    {
        $run->update(['result' => $result, 'reason' => $reason === null ? null : mb_substr($reason, 0, 250), 'processed_at' => now()]);
    }

    /**
     * A problem on the way: a permanent one ends the run with a note, a temporary one asks the job to come back (up to three attempts).
     */
    private function problem(ClaimReplyRun $run, LabelTicket $label, \Throwable $exception): ?int
    {
        [$message, $temporary] = match (true) {
            $exception instanceof EocsException => ['EOCS: '.$exception->problem->message(), $exception->problem !== EocsProblem::Misconfigured],
            $exception instanceof ZammadException => ['Zammad: '.$exception->problem->message($label->number), $exception->problem !== ZammadProblem::Misconfigured && $exception->problem !== ZammadProblem::NotFound],
            $exception instanceof ReplyException => ['Zammad: '.$exception->problem->message('customerserviceassist'), $exception->problem->notSent() && in_array($exception->problem->value, ['unavailable'], true)],
            $exception instanceof AnalysisException => ['Sprachmodell: '.$exception->problem->message(), $exception->problem !== AnalysisProblem::Misconfigured],
            default => [$exception->getMessage(), false],
        };

        Log::warning('Claim reply problem', ['ticket' => $label->number, 'attempt' => $run->attempts, 'problem' => $message]);

        if (! $temporary || $run->attempts >= self::MAX_ATTEMPTS) {
            $this->finish($run, ClaimReplyRun::ERROR, $message);
            $this->noteQuietly($label, self::NOTE_SUBJECT, "⚠️ Reklamation erkannt, aber die automatische Vorab-Antwort ist fehlgeschlagen: {$message}\nBitte das Ticket manuell bearbeiten.");

            return null;
        }

        $run->update(['result' => ClaimReplyRun::RETRY, 'reason' => mb_substr($message, 0, 250)]);

        return self::RETRY_SECONDS * $run->attempts;
    }
}
