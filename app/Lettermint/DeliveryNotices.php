<?php

namespace App\Lettermint;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finds the notification mails the n8n workflows sent through Lettermint for an order (PROJ-97): "Paket kommt heute"
 * and the pickup notice. Searched by the order number in the subject; a message counts only when the number stands
 * there as a whole. The time is the one of the `delivered` event, never `created_at` or `status_changed_at`.
 */
class DeliveryNotices
{
    public const int MAX_PER_ORDER = 5;

    private const array FAILURES = ['soft_bounced', 'hard_bounced', 'failed', 'suppressed'];

    private const array FAILURE_LABELS = ['soft_bounced' => 'vorübergehend abgewiesen (Soft Bounce)', 'hard_bounced' => 'abgewiesen (Hard Bounce)', 'failed' => 'Versand fehlgeschlagen', 'suppressed' => 'Versand unterdrückt (Adresse gesperrt)'];

    public function __construct(private readonly LettermintClient $client) {}

    public function enabled(): bool
    {
        return $this->client->configured();
    }

    /**
     * @return array{status: 'ok'|'unavailable', notices: list<DeliveryNotice>, reason?: string}
     */
    public function forOrder(string $orderNumber, ?string $ticketEmail, string $ticketNumber = ''): array
    {
        $orderNumber = trim($orderNumber);

        try {
            $messages = collect($this->client->messages($orderNumber))
                ->filter(fn (array $message): bool => self::mentions((string) ($message['subject'] ?? ''), $orderNumber))
                ->sortByDesc(fn (array $message): string => (string) ($message['created_at'] ?? ''))
                ->take(self::MAX_PER_ORDER)
                ->values();

            $notices = $messages->map(fn (array $message): DeliveryNotice => $this->notice($message, $ticketEmail))->all();
        } catch (LettermintException $exception) {
            Log::info('Benachrichtigungen nicht abrufbar.', ['ticket' => $ticketNumber, 'reason' => $exception->getMessage()]);

            return ['status' => 'unavailable', 'notices' => [], 'reason' => $exception->getMessage()];
        }

        Log::info('Benachrichtigungen bei Lettermint abgefragt', ['ticket' => $ticketNumber, 'found' => count($notices)]);

        return ['status' => 'ok', 'notices' => $notices];
    }

    /**
     * Whether the subject contains the order number as a whole (12345 does not match 123456 or 12345-2).
     */
    public static function mentions(string $subject, string $orderNumber): bool
    {
        return $orderNumber !== '' && preg_match('/(?<![0-9A-Za-z-])'.preg_quote($orderNumber, '/').'(?![0-9A-Za-z])(?!-[0-9A-Za-z])/u', $subject) === 1;
    }

    public static function kind(string $subject): string
    {
        $subject = mb_strtolower($subject);

        foreach (['pickup', 'today'] as $kind) {
            foreach ((array) config("services.lettermint.subjects.{$kind}", []) as $keyword) {
                if ($keyword !== '' && str_contains($subject, mb_strtolower((string) $keyword))) {
                    return $kind;
                }
            }
        }

        return 'other';
    }

    /**
     * @param  array<string, mixed>  $message
     *
     * @throws LettermintException
     */
    private function notice(array $message, ?string $ticketEmail): DeliveryNotice
    {
        $subject = (string) ($message['subject'] ?? '');
        $id = (string) ($message['id'] ?? '');
        $events = $id === '' ? [] : $this->safeEvents($id);

        [$outcome, $at, $failure] = self::evaluate($events, (string) ($message['status'] ?? ''));

        return new DeliveryNotice(self::kind($subject), $subject, $outcome, $at, $failure, self::toSender($message, $ticketEmail));
    }

    /**
     * A missing history (404) leaves the evidence open; any other failure stops the whole check.
     *
     * @return list<array<string, mixed>>
     *
     * @throws LettermintException
     */
    private function safeEvents(string $id): array
    {
        try {
            return $this->client->events($id);
        } catch (LettermintException $exception) {
            if (str_contains($exception->getMessage(), 'HTTP 404')) {
                return [];
            }

            throw $exception;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return array{0: string, 1: ?string, 2: ?string} Outcome, time in German time, failure description.
     */
    public static function evaluate(array $events, string $status = ''): array
    {
        $timed = collect($events)
            ->map(fn (array $event): array => ['event' => strtolower((string) ($event['event'] ?? '')), 'at' => self::parse($event['timestamp'] ?? null)])
            ->filter(fn (array $event): bool => $event['event'] !== '')
            ->sortBy(fn (array $event): int => $event['at']?->getTimestamp() ?? 0)
            ->values();

        $delivered = $timed->last(fn (array $event): bool => $event['event'] === 'delivered');
        $failure = $timed->last(fn (array $event): bool => in_array($event['event'], self::FAILURES, true));

        if ($failure !== null && ($delivered === null || ($failure['at'] !== null && $delivered['at'] !== null && $failure['at']->greaterThan($delivered['at'])))) {
            return ['failed', self::format($failure['at']), self::FAILURE_LABELS[$failure['event']]];
        }

        if ($delivered !== null) {
            return ['accepted', self::format($delivered['at']), null];
        }

        if (strtolower($status) === 'delivered') {
            return ['accepted', null, null];
        }

        $processed = $timed->last(fn (array $event): bool => $event['event'] === 'processed');

        if ($processed !== null) {
            return ['handed_over', self::format($processed['at']), null];
        }

        $pending = $timed->last(fn (array $event): bool => in_array($event['event'], ['queued', 'scheduled'], true));

        return $pending !== null || in_array(strtolower($status), ['queued', 'scheduled', 'pending'], true)
            ? ['pending', self::format($pending['at'] ?? null), null]
            : ['unknown', null, null];
    }

    /**
     * Whether the ticket's sender is among the recipients; null when the ticket has no sender address.
     *
     * @param  array<string, mixed>  $message
     */
    private static function toSender(array $message, ?string $ticketEmail): ?bool
    {
        $ticketEmail = mb_strtolower(trim((string) $ticketEmail));

        if ($ticketEmail === '') {
            return null;
        }

        foreach (['to', 'cc', 'bcc'] as $field) {
            foreach ((array) ($message[$field] ?? []) as $recipient) {
                $address = is_array($recipient) ? ($recipient['email'] ?? '') : $recipient;

                if (mb_strtolower(trim((string) $address)) === $ticketEmail) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function parse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    private static function format(?CarbonImmutable $at): ?string
    {
        return $at?->setTimezone('Europe/Berlin')->format('d.m.Y, H:i:s').' Uhr';
    }
}
