<?php

namespace App\Analysis;

use App\Settings\SettingsStore;
use App\Staff\StaffDirectory;
use App\Zammad\ReplyTarget;
use App\Zammad\Ticket;
use Carbon\CarbonImmutable;

/**
 * What the block "Antwort versenden" shows for a suggestion (PROJ-19): whether sending is offered, to whom,
 * with which subject and signature. The sending itself is done by AnalysisController::sendReply.
 */
class ReplyDelivery
{
    public function __construct(private readonly StaffDirectory $staff, private readonly SettingsStore $settings, private readonly AnalysisStore $store) {}

    /**
     * The signature of the settings with the full name of the employee.
     */
    public function signature(string $staffName): string
    {
        return str_replace('{Name}', $this->staff->fullName($staffName), (string) $this->settings->value('reply_signature'));
    }

    /**
     * @param  array<string, mixed>  $result  A stored suggestion prepared by AnalysisPanel.
     * @return array{state: string, reason?: string, to?: string, subject?: string, signature?: string, ticket_url?: string, sent?: array{by: string, at: string}}
     */
    public function offer(array $result, Ticket $ticket, ?string $staffName): array
    {
        $sending = $this->store->replySending((string) $result['id']);

        if (! ($result['has_reply'] ?? false) || $ticket->id === 0 || $ticket->rewoundTo !== null || $sending === null || $sending['test']) {
            return ['state' => 'hidden'];
        }

        if ($sending['sent_at'] !== null) {
            return ['state' => 'sent', 'ticket_url' => $ticket->zammadUrl, 'sent' => ['by' => (string) $sending['sent_by'], 'at' => $sending['sent_at']->setTimezone('Europe/Berlin')->format('d.m.Y \u\m H:i')]];
        }

        if (! ($result['is_latest'] ?? true) || $staffName === null) {
            return ['state' => 'hidden'];
        }

        if ($this->staff->zammadToken($staffName) === null) {
            return ['state' => 'no_token'];
        }

        $rule = $result['rule_check'] ?? [];

        if (($rule['status'] ?? null) === 'incomplete' && ($rule['at'] ?? null) === null) {
            return ['state' => 'blocked', 'reason' => 'Erst senden, wenn die Regelprüfung bestätigt ist (siehe oben).'];
        }

        $target = ReplyTarget::for($ticket);

        if ($target === null) {
            return ['state' => 'blocked', 'reason' => 'Zu diesem Ticket gibt es keine Kundennachricht mit eindeutiger Antwortadresse. Bitte in Zammad antworten.'];
        }

        return ['state' => 'ready', 'to' => $target->to, 'subject' => $target->subject, 'signature' => $this->signature($staffName), 'ticket_url' => $ticket->zammadUrl, 'started_unclear' => $sending['started'] !== null];
    }

    /**
     * The reminder date: the given number of days from now, at 8 o'clock local time.
     */
    public static function reminderDate(int $days): CarbonImmutable
    {
        return CarbonImmutable::now('Europe/Berlin')->addDays($days)->setTime(8, 0);
    }
}
