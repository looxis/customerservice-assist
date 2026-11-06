<?php

namespace App\Http\Controllers;

use App\Labeling\LabelCategory;
use App\Labeling\PreviewData;
use App\Staff\TestMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelingController extends Controller
{
    /**
     * Pages of the ticket tagging for admins (PROJ-78). The data is still example data
     * from PreviewData; the actions are wired up by the backend.
     */
    public function index(Request $request, TestMode $testMode, PreviewData $preview): View
    {
        $this->authorizeAdmin($request, $testMode);

        return view('labeling.index', [
            'categories' => $preview->categories(),
            'runs' => $preview->runs(),
            'preview' => true,
        ]);
    }

    public function review(Request $request, TestMode $testMode, PreviewData $preview): View
    {
        $this->authorizeAdmin($request, $testMode);

        $queues = $preview->queues();
        $queue = array_key_exists((string) $request->query('warteschlange'), $queues) ? (string) $request->query('warteschlange') : array_key_first($queues);

        return view('labeling.review', [
            'queues' => $queues,
            'queue' => $queue,
            'ticket' => $preview->reviewTicket($queue),
            'categories' => LabelCategory::cases(),
            'preview' => true,
        ]);
    }

    public function tickets(Request $request, TestMode $testMode, PreviewData $preview): View
    {
        $this->authorizeAdmin($request, $testMode);

        $filters = [
            'kategorie' => (string) $request->query('kategorie'),
            'zuordnung' => (string) $request->query('zuordnung'),
        ];

        return view('labeling.tickets', [
            'filters' => $filters,
            'rows' => $preview->tickets($filters, max(1, (int) $request->query('page', 1))),
            'categories' => LabelCategory::cases(),
            'preview' => true,
        ]);
    }

    /**
     * Stand-in for the actions the backend adds.
     */
    public function pending(Request $request, TestMode $testMode): RedirectResponse
    {
        $this->authorizeAdmin($request, $testMode);

        return back()->with('error', 'Diese Aktion ist noch nicht angeschlossen (Backend folgt).');
    }

    private function authorizeAdmin(Request $request, TestMode $testMode): void
    {
        abort_unless($testMode->isAvailable($request), 403);
    }
}
