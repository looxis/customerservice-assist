<?php

namespace App\Staff;

use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use Illuminate\Http\Request;

/**
 * Admin tool for testing on answered tickets (PROJ-32): a ticket is rewound
 * to an earlier customer message, so analysis and summary only see the
 * thread up to there. Switched on per browser, only for admins; for others
 * a cut point is ignored without a word.
 */
class TestMode
{
    public function __construct(private readonly StaffDirectory $staff) {}

    /**
     * The chosen name is an admin, so the switch is shown.
     */
    public function isAvailable(Request $request): bool
    {
        return $this->staff->isAdmin($this->staff->current($request));
    }

    public function isActive(Request $request): bool
    {
        return $this->isAvailable($request) && $request->cookie($this->staff->testModeCookieName()) === '1';
    }

    /**
     * The ticket as the request may see it: rewound to the chosen customer
     * message when the test mode is active, otherwise unchanged. A cut point
     * that does not fit is reported and the full thread is used.
     *
     * @return array{ticket: Ticket, later: list<TicketArticle>, problem: string|null}
     */
    public function rewind(Ticket $ticket, Request $request, mixed $stand): array
    {
        if ($stand === null || $stand === '' || ! $this->isAvailable($request)) {
            return ['ticket' => $ticket, 'later' => [], 'problem' => null];
        }

        if (! $this->isActive($request)) {
            return ['ticket' => $ticket, 'later' => [], 'problem' => 'Der Testmodus ist ausgeschaltet, deshalb gilt der ganze Verlauf. Zum Testen oben rechts „Testmodus“ einschalten.'];
        }

        $rewound = is_scalar($stand) && ctype_digit((string) $stand) ? $ticket->rewoundTo((int) $stand) : null;

        return $rewound === null
            ? ['ticket' => $ticket, 'later' => [], 'problem' => 'Stand nicht gefunden: Diese Nachricht gehört nicht zum Ticket oder ist keine Kundennachricht. Es gilt der ganze Verlauf.']
            : ['ticket' => $rewound[0], 'later' => $rewound[1], 'problem' => null];
    }

    public function cookieName(): string
    {
        return $this->staff->testModeCookieName();
    }
}
