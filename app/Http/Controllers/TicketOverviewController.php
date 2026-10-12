<?php

namespace App\Http\Controllers;

use App\Analysis\TicketOverview;
use App\Staff\TestMode;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketOverviewController extends Controller
{
    /**
     * The tickets opened in the app, for admins (PROJ-35).
     */
    public function index(Request $request, TicketOverview $overview, TestMode $testMode): View
    {
        abort_unless($testMode->isAvailable($request), 403);

        $stage = array_key_exists((string) $request->query('stand'), TicketOverview::STAGES) ? (string) $request->query('stand') : null;

        return view('ticket-overview.index', [
            'stage' => $stage,
            ...$overview->page($stage, max(1, (int) $request->query('page', 1))),
        ]);
    }
}
