<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisStore;
use App\Analysis\KnowledgeGapLog;
use App\Http\Requests\ResolveKnowledgeGapRequest;
use App\Http\Requests\StoreKnowledgeGapRequest;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelector;
use App\Models\KnowledgeGap;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeGapController extends Controller
{
    /**
     * Report a gap at an analysis (PROJ-12).
     */
    public function store(string $number, string $analysis, StoreKnowledgeGapRequest $request, KnowledgeGapLog $gaps, StaffDirectory $staff): RedirectResponse
    {
        $gap = $gaps->report($number, $analysis, $request->validated(), $request->validated('topic'), (string) $staff->current($request));
        $back = redirect()->to(route('tickets.show', array_filter(['number' => $number, 'analyse' => $analysis, 'stand' => $request->input('stand')])).'#ergebnis');

        if ($gap === null) {
            return $back->with('gap_error', 'Diese Analyse ist nicht mehr verfügbar.');
        }

        return $back->with('gap_reported', true);
    }

    /**
     * The reported gaps for admins, with the success figure of the feedback.
     */
    public function index(Request $request, KnowledgeGapLog $gaps, AnalysisStore $store, KnowledgeSelector $selector, TestMode $testMode): View
    {
        abort_unless($testMode->isAvailable($request), 403);

        $status = array_key_exists((string) $request->query('status'), KnowledgeGapLog::STATUSES) ? (string) $request->query('status') : 'open';
        $groups = collect($selector->customerGroups())->mapWithKeys(fn (CustomerGroup $group): array => [$group->key => $group->label])->all();

        return view('knowledge-gaps.index', [
            'status' => $status,
            'gaps' => $gaps->list($status),
            'counts' => collect(KnowledgeGapLog::STATUSES)->map(fn (string $label, string $key): int => KnowledgeGap::query()->where('status', $key)->count())->all(),
            'success' => $store->successRate(),
            'groups' => $groups,
            'chatText' => fn (KnowledgeGap $gap): string => $gaps->chatText($gap, $groups),
        ]);
    }

    /**
     * Mark a gap as done, discard it or open it again.
     */
    public function update(KnowledgeGap $gap, ResolveKnowledgeGapRequest $request, KnowledgeGapLog $gaps, StaffDirectory $staff): RedirectResponse
    {
        $gaps->resolve($gap, $request->validated('status'), (string) $staff->current($request));

        return redirect()->route('knowledge-gaps.index')->with('success', match ($request->validated('status')) {
            'done' => 'Wissenslücke als erledigt markiert.',
            'discarded' => 'Wissenslücke verworfen.',
            default => 'Wissenslücke wieder geöffnet.',
        });
    }
}
