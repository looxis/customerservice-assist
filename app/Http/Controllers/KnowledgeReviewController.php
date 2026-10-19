<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveKnowledgeReviewRequest;
use App\Http\Requests\StoreKnowledgeReviewRequest;
use App\Knowledge\KnowledgeReviewLog;
use App\Staff\StaffDirectory;
use Illuminate\Http\RedirectResponse;

class KnowledgeReviewController extends Controller
{
    /**
     * Staff reports a document as possibly outdated (PROJ-56).
     */
    public function store(StoreKnowledgeReviewRequest $request, KnowledgeReviewLog $log, StaffDirectory $staff): RedirectResponse
    {
        $name = $staff->current($request);

        if ($name === null) {
            return back()->with('review_error', 'Bitte zuerst deinen Namen wählen.');
        }

        $flag = $log->report((string) $request->validated('document_id'), (string) $request->validated('reason'), $request->validated('comment'), $name, $request->validated('analysis'), $request->validated('ticket'));

        return $flag === null
            ? back()->with('review_error', 'Dieses Dokument gibt es nicht mehr.')
            : back()->with('review_reported', $flag->document_id);
    }

    /**
     * An admin closes the open reports of a document: checked, it stays.
     */
    public function resolve(string $documentId, ResolveKnowledgeReviewRequest $request, KnowledgeReviewLog $log, StaffDirectory $staff): RedirectResponse
    {
        abort_unless(preg_match('/^[A-Z][A-Z-]*-\d{3}$/', $documentId) === 1, 404);

        $log->markChecked($documentId, (string) $staff->current($request));

        return redirect()->route('knowledge-gaps.index', ['status' => 'review'])->with('success', 'Meldungen zu '.$documentId.' als geprüft abgeschlossen. Das Dokument bleibt.');
    }
}
