<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisPanel;
use App\Analysis\AnalysisStore;
use App\Analysis\ReplyDelivery;
use App\Analysis\TicketActivityLog;
use App\Analysis\Translator;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\OrderLookup;
use App\Http\Requests\AddOrderRequest;
use App\Http\Requests\TicketLookupRequest;
use App\Labeling\LabelCategory;
use App\Labeling\TicketLabelState;
use App\Models\LabelTicket;
use App\Models\WismoRun;
use App\Orders\OrderFormats;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use App\Todo\TodoCaseContext;
use App\Zammad\Ticket;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TicketController extends Controller
{
    /**
     * The input form: clean "Ticket#…" and go to the ticket's own address.
     */
    public function lookup(TicketLookupRequest $request, ZammadClient $zammad): RedirectResponse
    {
        if ($request->zammadId() === null) {
            return redirect()->route('tickets.show', ['number' => $request->number()]);
        }

        try {
            $number = $zammad->numberForId($request->zammadId());
        } catch (ZammadException $exception) {
            return redirect()->route('tickets.analyze')
                ->withInput()
                ->withErrors(['ticket' => $exception->problem->messageForAddress()]);
        }

        return redirect()->route('tickets.show', ['number' => $number]);
    }

    /**
     * Show a ticket, always fresh from Zammad, with the orders chosen in the
     * address freshly loaded from EOCS.
     */
    public function show(string $number, Request $request, ZammadClient $zammad, EocsClient $eocs, OrderNumberDetector $detector, AnalysisPanel $panel, TestMode $testMode, Translator $translator, TicketActivityLog $activity, StaffDirectory $staff, ReplyDelivery $delivery, AnalysisStore $store): Response
    {
        try {
            $ticket = $zammad->ticket($number);
        } catch (ZammadException $exception) {
            return response()->view('tickets.show', [
                'number' => $number,
                'problem' => ['message' => $exception->problem->message($number), 'retry' => $exception->problem->canRetry()],
            ], $exception->problem->status());
        }

        $full = $ticket;
        ['ticket' => $ticket, 'later' => $later, 'problem' => $rewindProblem] = $testMode->rewind($ticket, $request, $request->query('stand'));
        $selected = $this->selection($request, $detector);
        $activity->recordVisit($ticket, $staff->current($request));
        $suggestions = $detector->detect($ticket);

        // Order numbers the app recognises by their format are loaded at once, so the product can be
        // proposed from the positions. Six-digit EOCS ids could be anything and take several seconds:
        // those are only loaded when chosen. A selection in the address (even an empty one) wins.
        // Numbers typed in by hand earlier and found in EOCS come back with the ticket (also after the analyses were deleted).
        if (! $request->has('bestellungen')) {
            $selected = $this->normalized([...array_map(fn (OrderNumber $order): string => $order->value, array_filter($suggestions, fn (OrderNumber $order): bool => ! $order->isEocsId())), ...$store->rememberedOrders($number)], $detector);
        }
        $lookups = [];
        $orderProblem = null;

        try {
            $lookups = $eocs->lookup($selected);
        } catch (EocsException $exception) {
            $orderProblem = ['message' => $exception->problem->message(), 'retry' => $exception->problem->canRetry()];
        }

        if ($orderProblem === null) {
            $recognised = array_map(fn (OrderNumber $order): string => $order->value, $suggestions);
            $store->rememberOrders($number, array_values(array_map(fn (OrderLookup $lookup): string => $lookup->number->value, array_filter($lookups, fn (OrderLookup $lookup): bool => $lookup->found() && ! in_array($lookup->number->value, $recognised, true)))), $staff->current($request));
        }

        $analysis = $panel->build($ticket, $lookups, is_string($request->query('analyse')) ? $request->query('analyse') : null);

        if (is_array($analysis['result'] ?? null)) {
            $analysis['result']['send'] = $delivery->offer($analysis['result'], $ticket, $staff->current($request));
            $analysis['result']['todo_context'] = app(TodoCaseContext::class)->build($ticket, $selected, $lookups, $this->claimReferences($suggestions), (string) ($analysis['result']['reply_text'] ?? ''), (array) ($analysis['inputs'] ?? []));
        }

        $labelState = $this->labelState($ticket);

        return response()->view('tickets.show', [
            'number' => $number,
            'ticket' => $ticket,
            'later' => $later,
            'translations' => $translator->translations($full),
            'untranslated' => count($translator->pending($ticket)),
            'rewindProblem' => $rewindProblem,
            'testMode' => $testMode->isActive($request),
            'selected' => array_map(fn (OrderNumber $order): string => $order->value, $selected),
            'suggestions' => array_slice($suggestions, 0, OrderNumberDetector::MAX_SUGGESTIONS),
            'moreSuggestions' => count($suggestions) > OrderNumberDetector::MAX_SUGGESTIONS,
            'lookups' => $lookups,
            'claimReferences' => $this->claimReferences($suggestions),
            'orderProblem' => $orderProblem,
            'deliveryProblem' => $this->deliveryProblem($ticket),
            'analysis' => $analysis,
            'relatedThreads' => $this->relatedThreads($analysis, $ticket, $zammad),
            'labelState' => $labelState,
            'complaintForm' => $this->complaintForm($labelState, $this->claimReferences($suggestions), $lookups),
        ]);
    }

    /**
     * What the complaint form in "Vorschlag erstellen" needs (PROJ-86): whether it is the default form (category
     * complaint, confirmed or else proposed), whether the switch to it is offered, and a claim number to prefill.
     *
     * @param  array<string, mixed>|null  $labelState
     * @param  list<string>  $claimReferences
     * @param  list<OrderLookup>  $lookups
     * @return array{default: bool, toggle: bool, claimNumber: ?string}
     */
    private function complaintForm(?array $labelState, array $claimReferences, array $lookups): array
    {
        $category = $labelState['decision'] ?? $labelState['suggestion'] ?? null;
        $default = $category === LabelCategory::Reklamation->value;
        $undecided = $labelState !== null && ($labelState['decision'] ?? null) === null && (($labelState['suggestion'] ?? null) === null || ($labelState['unsure_by'] ?? null) !== null);

        $claims = $claimReferences;

        foreach ($lookups as $lookup) {
            foreach ($lookup->orders as $order) {
                foreach ($order->claims as $claim) {
                    $claims[] = $claim->externalNumber;
                }
            }
        }

        return ['default' => $default, 'toggle' => $default || $undecided, 'claimNumber' => $claims[0] ?? null];
    }

    /**
     * The delivery problem the automatic WISMO reply found (PROJ-82), from the stored run; no carrier is asked here.
     *
     * @return array{reason: ?string, at: ?string}|null
     */
    private function deliveryProblem(Ticket $ticket): ?array
    {
        $run = WismoRun::query()->where('zammad_id', $ticket->id)->where('delivery_problem', true)->first();

        return $run === null ? null : ['reason' => $run->delivery_problem_reason, 'at' => $run->processed_at?->toIso8601String()];
    }

    /**
     * What the classification bar shows (PROJ-80), null while the ticket is not classified (older, removed, still
     * in progress).
     *
     * @return array<string, mixed>|null
     */
    private function labelState(Ticket $ticket): ?array
    {
        $labelTicket = LabelTicket::query()->where('zammad_id', $ticket->id)->with(['latestPrediction', 'reviews'])->first();

        if ($labelTicket === null || $labelTicket->state !== 'classified' || blank($labelTicket->text)) {
            return null;
        }

        return TicketLabelState::of($labelTicket);
    }

    /**
     * The tickets the shown suggestion used as earlier history (PROJ-52), read fresh from Zammad for the
     * collapsed display below the thread. A ticket that cannot be read is left out.
     *
     * @param  array<string, mixed>  $analysis
     * @return list<Ticket>
     */
    private function relatedThreads(array $analysis, Ticket $current, ZammadClient $zammad): array
    {
        $threads = [];

        foreach (array_slice((array) ($analysis['result']['related']['tickets'] ?? []), 0, (int) config('analysis.related_tickets.max')) as $related) {
            $number = (string) ($related['number'] ?? '');

            if ($number === '' || $number === $current->number) {
                continue;
            }

            try {
                $threads[] = $zammad->ticket($number);
            } catch (ZammadException) {
                continue;
            }
        }

        return $threads;
    }

    /**
     * Add a typed-in order number to the selection.
     */
    public function addOrder(string $number, AddOrderRequest $request, OrderNumberDetector $detector): RedirectResponse
    {
        $selection = array_map(
            fn (OrderNumber $order): string => $order->value,
            $this->normalized([...$request->selection(), $request->orderNumber()], $detector),
        );

        return redirect()->route('tickets.show', ['number' => $number, 'bestellungen' => $selection]);
    }

    /**
     * @return list<OrderNumber>
     */
    private function selection(Request $request, OrderNumberDetector $detector): array
    {
        $raw = $request->query('bestellungen', []);

        return $this->normalized(is_array($raw) ? array_filter($raw, 'is_string') : [], $detector);
    }

    /**
     * Valid, distinct order numbers, at most the configured number.
     *
     * @param  iterable<string>  $values
     * @return list<OrderNumber>
     */
    private function normalized(iterable $values, OrderNumberDetector $detector): array
    {
        $orders = [];

        foreach ($values as $value) {
            $order = $detector->normalize($value, true);

            if ($order !== null) {
                $orders[$order->value] ??= $order;
            }
        }

        return array_slice(array_values($orders), 0, OrderNumberDetector::MAX_SUGGESTIONS);
    }

    /**
     * The claim numbers the ticket names, as written ("R1-302-…"), for the order block (PROJ-53).
     *
     * @param  list<OrderNumber>  $numbers
     * @return list<string>
     */
    private function claimReferences(array $numbers): array
    {
        $prefix = app(OrderFormats::class)->claimPrefix();

        return array_values(array_merge(...array_map(fn (OrderNumber $number): array => array_map(fn (int $claim): string => $prefix.$claim.'-'.$number->value, $number->claims), $numbers) ?: [[]]));
    }
}
