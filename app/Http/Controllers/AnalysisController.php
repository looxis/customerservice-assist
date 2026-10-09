<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisException;
use App\Analysis\AnalysisProblem;
use App\Analysis\AnalysisRequest;
use App\Analysis\AnalysisStore;
use App\Analysis\CaseAnalyzer;
use App\Analysis\ContextVariant;
use App\Analysis\Summarizer;
use App\Analysis\Summary;
use App\Analysis\TicketContext;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\OrderLookup;
use App\Http\Requests\AnalyzeTicketRequest;
use App\Http\Requests\DeleteAnalysesRequest;
use App\Http\Requests\UpdateReplyRequest;
use App\Http\Requests\UpdateSummaryRequest;
use App\Knowledge\KnowledgeSelector;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    /**
     * Run an analysis (stage 2, creating or refreshing the summary first when
     * that variant is chosen) and show the ticket with the result.
     */
    public function analyze(string $number, AnalyzeTicketRequest $request, ZammadClient $zammad, EocsClient $eocs, OrderNumberDetector $detector, CaseAnalyzer $analyzer, Summarizer $summarizer, AnalysisStore $store, StaffDirectory $staff, KnowledgeSelector $selector, TestMode $testMode): RedirectResponse
    {
        set_time_limit((int) config('analysis.timeout') * 2 + 30);

        $back = fn (string $message, bool $retry = false): RedirectResponse => redirect()
            ->to($this->ticketUrl($number, $request, '#analyse'))
            ->withInput()
            ->with('analysis_error', $message)
            ->with('analysis_retry', $retry);

        try {
            $ticket = $testMode->rewind($zammad->ticket($number), $request, $request->input('stand'))['ticket'];
        } catch (ZammadException $exception) {
            return $back($exception->problem->message($number), $exception->problem->canRetry());
        }

        try {
            $orders = collect($eocs->lookup($this->orderNumbers($request, $detector)))->flatMap(fn (OrderLookup $lookup): array => $lookup->orders)->values()->all();
        } catch (EocsException $exception) {
            return $back($exception->problem->message(), $exception->problem->canRetry());
        }

        $store->putCaseChoice($number, $request->validated('kundengruppe'), array_values($request->validated('produkte', [])), (string) $staff->current($request));

        if ($request->validated('kundengruppe') !== 'unclear' && $ticket->rewoundTo === null) {
            $store->putCustomerGroup($ticket->customerKey(), $request->validated('kundengruppe'));
        }

        $context = new TicketContext($ticket);
        $variant = in_array($request->variant(), $context->variants(), true) ? $request->variant() : ContextVariant::FullThread;
        $summary = $variant === ContextVariant::LastWithSummary ? $store->summary($ticket->summaryKey()) : null;

        if ($variant === ContextVariant::FullThread && $context->offersVariants() && mb_strlen($context->text($variant)) > (int) config('analysis.max_input_characters')) {
            return $back('Der Verlauf ist zu lang für die KI. Bitte „Letzte Kundennachricht + Zusammenfassung“ wählen – die letzte Kundennachricht wird immer vollständig übertragen.');
        }
        try {
            if ($variant === ContextVariant::LastWithSummary && ($summary === null || $summary->isStale($context->earlierFingerprint()))) {
                if ($summary?->edited) {
                    return $back('Die von Hand geänderte Zusammenfassung ist veraltet, weil neue Nachrichten hinzugekommen sind. Bitte neu erstellen oder weiter verwenden.');
                }

                $summary = $summarizer->create($ticket, $context);
            }

            $id = $analyzer->analyze(new AnalysisRequest(
                ticket: $ticket,
                staffName: (string) $staff->current($request),
                customerGroup: $selector->customerGroup($request->validated('kundengruppe')),
                products: array_values($request->validated('produkte', [])),
                orders: $orders,
                manualOrder: $orders === [] ? $request->manualOrder() : [],
                employeeContext: (string) $request->validated('kontext', ''),
                variant: $variant,
                summary: $summary,
                formInput: [...$request->safe()->only(['kundengruppe', 'produkte', 'kontext', ...array_keys(AnalyzeTicketRequest::MANUAL_ORDER_FIELDS)]), 'variante' => $variant->value],
            ));
        } catch (AnalysisException $exception) {
            return $back($exception->problem->message(), $exception->problem !== AnalysisProblem::Misconfigured);
        }

        return redirect()->to($this->ticketUrl($number, $request, '#ergebnis', ['analyse' => $id]))->withInput();
    }

    /**
     * Create the summary of the earlier thread (stage 1), or create it anew.
     */
    public function createSummary(string $number, Request $request, ZammadClient $zammad, Summarizer $summarizer, TestMode $testMode): RedirectResponse
    {
        set_time_limit((int) config('analysis.timeout') + 30);

        try {
            $ticket = $testMode->rewind($zammad->ticket($number), $request, $request->input('stand'))['ticket'];
            $context = new TicketContext($ticket);

            if (! $context->offersVariants()) {
                return redirect()->to($this->ticketUrl($number, $request, '#analyse'));
            }

            $summarizer->create($ticket, $context);
        } catch (ZammadException $exception) {
            return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'))->with('summary_error', $exception->problem->message($number));
        } catch (AnalysisException $exception) {
            return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'))->with('summary_error', 'Die Zusammenfassung konnte nicht erstellt werden. '.$exception->problem->message().' Du kannst auch eine andere Variante wählen.');
        }

        return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'));
    }

    /**
     * Keep the employee's corrected summary. A summary of an older thread can
     * also be kept on purpose ("weiter verwenden").
     */
    public function updateSummary(string $number, UpdateSummaryRequest $request, ZammadClient $zammad, AnalysisStore $store, TestMode $testMode): RedirectResponse
    {
        try {
            $ticket = $testMode->rewind($zammad->ticket($number), $request, $request->input('stand'))['ticket'];
        } catch (ZammadException) {
            $ticket = null;
        }

        $key = $ticket?->summaryKey() ?? $number;
        $summary = $store->summary($key);

        if ($summary === null) {
            return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'))->with('summary_error', 'Es gibt noch keine Zusammenfassung zum Bearbeiten.');
        }

        $updated = $summary->withText($request->validated('zusammenfassung'));

        if ($ticket !== null) {
            $fingerprint = (new TicketContext($ticket))->earlierFingerprint();
            $updated = new Summary($updated->text, $updated->until, $fingerprint, true, $updated->model, $updated->promptVersion, $updated->createdAt);
        }
        // Without the ticket the old fingerprint stays; the summary may show as outdated.

        $store->putSummary($key, $updated);

        return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'))->with('summary_saved', true);
    }

    /**
     * Save the edited reply draft of an analysis in the background (PROJ-10),
     * or go back to the draft of the language model.
     */
    public function updateReply(string $number, string $analysis, UpdateReplyRequest $request, AnalysisStore $store, StaffDirectory $staff): JsonResponse
    {
        $stored = $store->result($analysis);

        if ($stored === null || ($stored['ticket'] ?? null) !== $number) {
            return response()->json(['message' => 'Diese Analyse ist nicht mehr verfügbar.'], 404);
        }

        if ($store->latest((string) $stored['scope']) !== $analysis) {
            return response()->json(['message' => 'Ältere Analysen sind nur lesbar.'], 409);
        }

        $now = CarbonImmutable::now();
        $edit = $request->boolean('original')
            ? null
            : ['text' => (string) $request->validated('text', ''), 'staff' => (string) $staff->current($request), 'at' => $now->toIso8601String()];

        $stored = $store->changeResult($analysis, fn (array $content): array => [...$content, 'reply_edit' => $edit]) ?? $stored;

        return response()->json([
            'saved' => true,
            'edited' => $stored['reply_edit'] === null ? null : 'bearbeitet von '.$stored['reply_edit']['staff'].' am '.$now->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr',
        ]);
    }

    /**
     * Empty all customer content of a ticket's analyses (admin, PROJ-11).
     */
    public function destroyAll(string $number, DeleteAnalysesRequest $request, AnalysisStore $store, StaffDirectory $staff): RedirectResponse
    {
        $store->deleteTicket($number, (string) $staff->current($request));

        return redirect()->route('tickets.show', ['number' => $number])->with('success', 'Alle Analysen dieses Tickets wurden gelöscht.');
    }

    /**
     * @return list<OrderNumber>
     */
    private function orderNumbers(Request $request, OrderNumberDetector $detector): array
    {
        $numbers = [];

        foreach ((array) $request->input('bestellungen', []) as $value) {
            $number = is_string($value) ? $detector->normalize($value) : null;

            if ($number !== null) {
                $numbers[$number->value] ??= $number;
            }
        }

        return array_slice(array_values($numbers), 0, OrderNumberDetector::MAX_SUGGESTIONS);
    }

    /**
     * @param  array<string, string>  $extra
     */
    private function ticketUrl(string $number, Request $request, string $anchor, array $extra = []): string
    {
        $selection = array_values(array_filter((array) $request->input('bestellungen', []), 'is_string'));
        $stand = $request->input('stand');

        return route('tickets.show', ['number' => $number, 'bestellungen' => $selection, ...(is_string($stand) && $stand !== '' ? ['stand' => $stand] : []), ...$extra]).$anchor;
    }
}
