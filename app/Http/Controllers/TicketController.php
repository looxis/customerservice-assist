<?php

namespace App\Http\Controllers;

use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Http\Requests\AddOrderRequest;
use App\Http\Requests\TicketLookupRequest;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
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
    public function lookup(TicketLookupRequest $request): RedirectResponse
    {
        return redirect()->route('tickets.show', ['number' => $request->number()]);
    }

    /**
     * Show a ticket, always fresh from Zammad, with the orders chosen in the
     * address freshly loaded from EOCS.
     */
    public function show(string $number, Request $request, ZammadClient $zammad, EocsClient $eocs, OrderNumberDetector $detector): Response
    {
        try {
            $ticket = $zammad->ticket($number);
        } catch (ZammadException $exception) {
            return response()->view('tickets.show', [
                'number' => $number,
                'problem' => ['message' => $exception->problem->message($number), 'retry' => $exception->problem->canRetry()],
            ], $exception->problem->status());
        }

        $selected = $this->selection($request, $detector);
        $suggestions = $detector->detect($ticket);
        $lookups = [];
        $orderProblem = null;

        try {
            $lookups = $eocs->lookup($selected);
        } catch (EocsException $exception) {
            $orderProblem = ['message' => $exception->problem->message(), 'retry' => $exception->problem->canRetry()];
        }

        return response()->view('tickets.show', [
            'number' => $number,
            'ticket' => $ticket,
            'selected' => array_map(fn (OrderNumber $order): string => $order->value, $selected),
            'suggestions' => array_slice($suggestions, 0, OrderNumberDetector::MAX_SUGGESTIONS),
            'moreSuggestions' => count($suggestions) > OrderNumberDetector::MAX_SUGGESTIONS,
            'lookups' => $lookups,
            'orderProblem' => $orderProblem,
        ]);
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
            $order = $detector->normalize($value);

            if ($order !== null) {
                $orders[$order->value] ??= $order;
            }
        }

        return array_slice(array_values($orders), 0, OrderNumberDetector::MAX_SUGGESTIONS);
    }
}
