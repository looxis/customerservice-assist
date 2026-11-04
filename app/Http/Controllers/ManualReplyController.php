<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisStore;
use App\Http\Requests\StartManualReplyRequest;
use App\Staff\StaffDirectory;
use App\Zammad\ArticleKind;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Http\RedirectResponse;

class ManualReplyController extends Controller
{
    /**
     * Open the reply view of a ticket without a language model call (PROJ-69): an empty draft the employee writes
     * and sends like a proposal.
     */
    public function __invoke(string $number, StartManualReplyRequest $request, ZammadClient $zammad, AnalysisStore $store, StaffDirectory $staff): RedirectResponse
    {
        try {
            $ticket = $zammad->ticket($number);
        } catch (ZammadException $exception) {
            return redirect()->route('tickets.show', ['number' => $number])->with('error', $exception->problem->message($number));
        }

        if ($ticket->closed) {
            return redirect()->route('tickets.show', ['number' => $number])->with('error', "Ticket#{$number} ist geschlossen.");
        }

        $newest = collect($ticket->articles)->reject(fn ($article): bool => $article->kind === ArticleKind::Internal)->last()?->id;
        $id = $store->putManualReply($number, $ticket->summaryKey(), (string) $staff->current($request), $newest, $ticket->lastCustomerArticleId());

        return redirect()->to(route('tickets.show', ['number' => $number, 'analyse' => $id]).'#antwort-versenden');
    }
}
