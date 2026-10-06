<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisException;
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
use App\Http\Requests\UpdateSummaryRequest;
use App\Knowledge\KnowledgeSelector;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
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

        $back = fn (string $message): RedirectResponse => redirect()
            ->to($this->ticketUrl($number, $request, '#analyse'))
            ->withInput()
            ->with('analysis_error', $message);

        try {
            $ticket = $testMode->rewind($zammad->ticket($number), $request, $request->input('stand'))['ticket'];
        } catch (ZammadException $exception) {
            return $back($exception->problem->message($number));
        }

        try {
            $orders = collect($eocs->lookup($this->orderNumbers($request, $detector)))->flatMap(fn (OrderLookup $lookup): array => $lookup->orders)->values()->all();
        } catch (EocsException $exception) {
            return $back($exception->problem->message());
        }

        $store->putCaseChoice($number, $request->validated('kundengruppe'), array_values($request->validated('produkte', [])), (string) $staff->current($request));

        if ($request->validated('kundengruppe') !== 'unclear' && $ticket->rewoundTo === null) {
            $store->putCustomerGroup($ticket->customerKey(), $request->validated('kundengruppe'));
        }

        $context = new TicketContext($ticket);
        $variant = in_array($request->variant(), $context->variants(), true) ? $request->variant() : ContextVariant::FullThread;
        $summary = $variant === ContextVariant::LastWithSummary ? $store->summary($ticket->summaryKey()) : null;

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
            ));
        } catch (AnalysisException $exception) {
            return $back($exception->problem->message());
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
