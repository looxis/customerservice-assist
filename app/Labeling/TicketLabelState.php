<?php

namespace App\Labeling;

use App\Models\LabelTicket;

/**
 * What the classification bar on the ticket page shows (PROJ-80), on loading the page and after each choice. The
 * proposal of a ticket of the test set stays hidden, so that the choice of the staff is not swayed by it.
 */
class TicketLabelState
{
    /**
     * @return array{editable: bool, test: bool, suggestion: ?string, suggestion_label: ?string, decision: ?string, decision_label: ?string, decided_by: ?string, by_staff: bool, waiting: bool, unsure_by: ?string, unsure_at: ?string}
     */
    public static function of(LabelTicket $ticket): array
    {
        $suggestion = $ticket->isTest() ? null : LabelCategory::tryFrom((string) $ticket->latestPrediction?->category);

        return [
            'editable' => $ticket->staffMayDecide(),
            'test' => $ticket->isTest(),
            'suggestion' => $suggestion?->value,
            'suggestion_label' => $suggestion?->label(),
            'decision' => $ticket->decision,
            'decision_label' => $ticket->decisionLabel(),
            'decided_by' => in_array($ticket->decision_origin, ['vorhanden', 'automatisch'], true) ? null : $ticket->decided_by,
            'by_staff' => $ticket->decision_origin === LabelTicket::STAFF,
            'waiting' => $ticket->decision_origin === LabelTicket::STAFF && $ticket->hasOpenDeviation(),
            'unsure_by' => $ticket->decision === null ? $ticket->staff_unsure_by : null,
            'unsure_at' => $ticket->decision === null ? $ticket->staff_unsure_at?->setTimezone('Europe/Berlin')->format('d.m.Y') : null,
        ];
    }
}
