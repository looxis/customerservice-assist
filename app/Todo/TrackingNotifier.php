<?php

namespace App\Todo;

use App\Analysis\ReplyDelivery;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsShipment;
use App\Models\TicketTodo;
use App\Models\TodoAutoCheck;
use App\Orders\OrderFormats;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\Settings\TodoReplyTexts;
use App\Staff\StaffDirectory;
use App\Zammad\OutgoingReply;
use App\Zammad\ReplyException;
use App\Zammad\ReplyTarget;
use App\Zammad\Ticket;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use App\Zammad\ZammadReplySender;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;

/**
 * The automatic reply with the tracking number (PROJ-77). Called at the fixed check times: looks in EOCS whether the
 * claim of a ToDo was shipped and, if so and nothing speaks against it, sends the prepared reply in the name of the
 * employee who created the ToDo, adds an internal note, closes the ticket and finishes the ToDo. Where something
 * speaks against it, the ToDo says "Bitte prüfen" and keeps the reply prepared. Everything is written to the check log.
 */
class TrackingNotifier
{
    public const int MAX_ERRORS = 3;

    private const int STUCK_MINUTES = 10;

    private const int MERGE_HOPS = 5;

    public function __construct(
        private readonly EocsClient $eocs,
        private readonly ZammadClient $zammad,
        private readonly ZammadReplySender $sender,
        private readonly StaffDirectory $staff,
        private readonly ReplyDelivery $delivery,
        private readonly OrderFormats $formats,
        private readonly OrderNumberDetector $detector,
    ) {}

    /**
     * @return array{checked: int, sent: int}
     */
    public function run(CarbonImmutable $now, ?string $slot = null): array
    {
        $slot ??= TrackingSchedule::slotAt($now);
        $figures = ['checked' => 0, 'sent' => 0];

        if ($slot === null) {
            return $figures;
        }

        $this->recoverStuck($now);

        $todos = TicketTodo::query()->where('status', TodoStatus::Open->value)->where('auto_send', true)->where('auto_state', 'watching')->orderBy('id')->get();

        foreach ($todos as $todo) {
            if (TrackingSchedule::expired($todo, $now)) {
                $todo->update(['auto_state' => 'expired', 'auto_reason' => null]);
                $this->log($todo, $now, 'expired', 'Keine Sendungsnummer nach 5 Tagen.');

                continue;
            }

            if (! TrackingSchedule::isDue($todo, $now, $slot)) {
                continue;
            }

            $figures['checked']++;
            $figures['sent'] += $this->check($todo, $now) ? 1 : 0;
        }

        return $figures;
    }

    /**
     * Checks one ToDo; true when the reply was sent.
     */
    public function check(TicketTodo $todo, CarbonImmutable $now): bool
    {
        $todo->update(['auto_checks' => $todo->auto_checks + 1, 'auto_last_check_at' => $now]);

        try {
            $shipments = $this->shipments($todo);
            $numbers = $this->trackingNumbers($shipments);

            if ($numbers === []) {
                $todo->update(['auto_errors' => 0]);
                $this->log($todo, $now, 'none', 'Noch keine Sendungsnummer.');

                return false;
            }

            $carriers = $this->carriers($shipments);
            $ticket = $this->ticket($todo->ticket_number);
            $text = $this->text($todo, $ticket, $numbers, $carriers);
            $reason = $this->objection($todo, $shipments, $ticket, $text);

            if ($reason !== null) {
                $this->review($todo, $now, $reason, $text);

                return false;
            }

            return $this->send($todo, $now, $ticket, $numbers, $text);
        } catch (EocsException $exception) {
            $this->failed($todo, $now, 'EOCS: '.$exception->problem->message());
        } catch (ZammadException $exception) {
            $this->failed($todo, $now, 'Zammad: '.$exception->problem->message($todo->ticket_number));
        } catch (ReplyException $exception) {
            $this->failed($todo, $now, $exception->problem->message((string) $todo->created_by));
        }

        return false;
    }

    /**
     * The sending was started but not finished (the server stopped, Zammad did not answer): if the reply is in Zammad,
     * the ToDo is done; otherwise it goes back to watching.
     */
    private function recoverStuck(CarbonImmutable $now): void
    {
        $stuck = TicketTodo::query()->where('auto_state', 'sending')->where('auto_sending_at', '<', $now->subMinutes(self::STUCK_MINUTES))->get();

        foreach ($stuck as $todo) {
            $token = $this->staff->zammadToken($todo->created_by);

            try {
                $ticket = $this->ticket($todo->ticket_number);
                $sent = $token === null || $todo->auto_sending_at === null ? null : $this->sender->sentSince($token, $ticket->id, CarbonImmutable::instance($todo->auto_sending_at));
            } catch (ZammadException|ReplyException) {
                continue;
            }

            if ($sent !== null) {
                $this->finish($todo, $now, $todo->auto_tracking ?? '', $todo->auto_text ?? '');
                $this->log($todo, $now, 'sent', 'Antwort war bereits in Zammad angekommen.');
            } else {
                $todo->update(['auto_state' => 'watching', 'auto_sending_at' => null, 'auto_tracking' => null, 'auto_text' => null]);
            }
        }
    }

    /**
     * The shipments of the claim named at the ToDo; of the newest claim when only the order is named. Never those of
     * the original order.
     *
     * @return list<EocsShipment>
     *
     * @throws EocsException
     */
    private function shipments(TicketTodo $todo): array
    {
        $reference = trim((string) $todo->external_ref);
        $prefix = preg_quote($this->formats->claimPrefix(), '/');

        if (preg_match('/^'.$prefix.'\d+-/i', $reference) === 1) {
            $orders = $this->eocs->lookup([new OrderNumber($reference, $this->formats->amazon())])[0]->orders ?? [];

            return array_values(array_merge(...array_map(fn ($order): array => $order->shipments, $orders ?: [])));
        }

        $number = $this->detector->normalize($reference, true);
        $orders = $number === null ? [] : ($this->eocs->lookup([$number])[0]->orders ?? []);
        $claims = array_merge(...array_map(fn ($order): array => $order->claims, $orders ?: []));

        if ($claims === []) {
            return [];
        }

        $count = fn (string $claim): int => preg_match('/^[A-Za-z]+(\d+)-/', $claim, $match) === 1 ? (int) $match[1] : 0;
        usort($claims, fn ($a, $b): int => $count($b->externalNumber) <=> $count($a->externalNumber));

        return $claims[0]->shipments;
    }

    /**
     * @param  list<EocsShipment>  $shipments
     * @return list<string>
     */
    private function trackingNumbers(array $shipments): array
    {
        return array_values(array_unique(array_filter(array_map(fn (EocsShipment $shipment): string => trim((string) $shipment->trackingNumber), $shipments), fn (string $number): bool => $number !== '')));
    }

    /**
     * @param  list<EocsShipment>  $shipments
     * @return list<string>
     */
    private function carriers(array $shipments): array
    {
        return array_values(array_unique(array_filter(array_map(fn (EocsShipment $shipment): string => trim((string) $shipment->carrier), array_filter($shipments, fn (EocsShipment $shipment): bool => trim((string) $shipment->trackingNumber) !== '')), fn (string $carrier): bool => $carrier !== '')));
    }

    /**
     * The prepared reply of the ToDo with the tracking numbers and carriers filled in.
     *
     * @param  list<string>  $numbers
     * @param  list<string>  $carriers
     */
    private function text(TicketTodo $todo, Ticket $ticket, array $numbers, array $carriers): string
    {
        $claim = preg_match('/^'.preg_quote($this->formats->claimPrefix(), '/').'\d+-/i', (string) $todo->external_ref) === 1;
        $text = TodoReplyTexts::fill((string) $todo->notify_hint, ['name' => $this->customerName($ticket), 'kind' => $claim ? 'claim' : 'order', 'number' => $todo->external_ref]);

        return strtr($text, ['[Sendungsnummer]' => implode(', ', $numbers), '[Carrier]' => implode(', ', $carriers)]);
    }

    private function customerName(Ticket $ticket): string
    {
        $name = trim((string) $ticket->customerName);

        return $name === '' || str_contains($name, '@') || mb_strlen($name) > 80 ? '' : $name;
    }

    /**
     * What speaks against sending, or null.
     *
     * @param  list<EocsShipment>  $shipments
     */
    private function objection(TicketTodo $todo, array $shipments, Ticket $ticket, string $text): ?string
    {
        $german = str_starts_with(mb_strtolower(trim((string) $todo->customer_language)), 'deutsch');

        return match (true) {
            ! $german => 'Der Kunde schreibt nicht auf Deutsch oder die Sprache ist unbekannt.',
            collect($shipments)->contains(fn (EocsShipment $shipment): bool => trim((string) $shipment->trackingNumber) !== '' && trim((string) $shipment->carrier) === '') => 'Sendungsnummer ohne Versanddienstleister.',
            $this->staff->zammadToken($todo->created_by) === null => 'Kein Zugang des Mitarbeiters '.($todo->created_by ?? '–').' zu Zammad.',
            trim((string) $todo->notify_hint) === '' => 'Kein Text für die Antwort vorbereitet.',
            preg_match('/\[[^\[\]]+\]/u', $text, $open) === 1 => 'Platzhalter im Text nicht gefüllt: '.$open[0].'.',
            ReplyTarget::for($ticket) === null => 'Keine eindeutige Antwortadresse im Ticket.',
            default => null,
        };
    }

    /**
     * @param  list<string>  $numbers
     */
    private function send(TicketTodo $todo, CarbonImmutable $now, Ticket $ticket, array $numbers, string $text): bool
    {
        $name = (string) $todo->created_by;
        $token = (string) $this->staff->zammadToken($name);
        $target = ReplyTarget::for($ticket);
        $signature = trim($this->delivery->signature($name));
        $body = $signature === '' ? $text : $text."\n\n".$signature;

        $claimed = TicketTodo::query()->whereKey($todo->id)->where('status', TodoStatus::Open->value)->where('auto_state', 'watching')->update([
            'auto_state' => 'sending', 'auto_sending_at' => $now, 'auto_tracking' => Crypt::encryptString(implode(', ', $numbers)), 'auto_text' => Crypt::encryptString($body),
        ]);

        if ($claimed !== 1) {
            return false;
        }

        try {
            $this->sender->send($token, new OutgoingReply($ticket->id, (string) $target?->to, (string) $target?->subject, $body, $target?->inReplyTo, 'closed', null));
        } catch (ReplyException $exception) {
            if ($exception->problem->notSent()) {
                $todo->update(['auto_state' => 'watching', 'auto_sending_at' => null, 'auto_tracking' => null, 'auto_text' => null]);
            }

            $this->failed($todo->refresh(), $now, $exception->problem->message($name));

            return false;
        }

        $this->finish($todo->refresh(), $now, implode(', ', $numbers), $body);
        $this->log($todo, $now, 'sent', 'Sendungsnummer '.implode(', ', $numbers));

        try {
            $this->sender->addNote($token, $ticket->id, 'Automatisch gesendet', 'Automatisch gesendet am '.$now->setTimezone(TrackingSchedule::ZONE)->format('d.m.Y \u\m H:i').' Uhr: Sendungsnummer '.implode(', ', $numbers).' laut EOCS ('.$todo->external_ref."). ToDo #{$todo->id}.");
        } catch (ReplyException) {
            $this->log($todo, $now, 'note_failed', 'Die interne Notiz konnte nicht angelegt werden.');
        }

        return true;
    }

    private function finish(TicketTodo $todo, CarbonImmutable $now, string $tracking, string $text): void
    {
        $todo->update([
            'status' => TodoStatus::Done, 'completed_by' => 'Automatik', 'completed_at' => $now,
            'auto_state' => 'sent', 'auto_sent_at' => $now, 'auto_tracking' => $tracking, 'auto_text' => $text,
            'auto_reason' => null, 'auto_errors' => 0, 'auto_sending_at' => null,
        ]);
    }

    private function review(TicketTodo $todo, CarbonImmutable $now, string $reason, string $text): void
    {
        $changes = ['auto_state' => 'review', 'auto_reason' => mb_substr($reason, 0, 250), 'auto_errors' => 0];

        if (trim($text) !== '') {
            $changes['notify_hint'] = mb_substr($text, 0, 1000);
        }

        $todo->update($changes);
        $this->log($todo, $now, 'review', $reason);
    }

    /**
     * A technical problem: counted, and after the third in a row the ToDo asks for a look.
     */
    private function failed(TicketTodo $todo, CarbonImmutable $now, string $message): void
    {
        $errors = $todo->auto_errors + 1;
        $changes = ['auto_errors' => $errors];

        if ($errors >= self::MAX_ERRORS && $todo->auto_state !== 'sending') {
            $changes += ['auto_state' => 'review', 'auto_reason' => mb_substr('Technischer Fehler: '.$message, 0, 250)];
        }

        $todo->update($changes);
        $this->log($todo, $now, 'error', $message);
    }

    private function log(TicketTodo $todo, CarbonImmutable $now, string $result, ?string $note): void
    {
        TodoAutoCheck::query()->create(['ticket_todo_id' => $todo->id, 'checked_at' => $now, 'result' => $result, 'note' => $note === null ? null : mb_substr($note, 0, 250)]);
    }

    /**
     * The ticket, or the one it was merged into.
     *
     * @throws ZammadException
     */
    private function ticket(string $number): Ticket
    {
        $ticket = $this->zammad->ticket($number);

        for ($hop = 0; $ticket->mergedIntoNumber !== null && $hop < self::MERGE_HOPS; $hop++) {
            $ticket = $this->zammad->ticket($ticket->mergedIntoNumber);
        }

        return $ticket;
    }
}
