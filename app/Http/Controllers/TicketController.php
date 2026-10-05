<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketLookupRequest;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Http\RedirectResponse;
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
     * Show a ticket, always fresh from Zammad.
     */
    public function show(string $number, ZammadClient $zammad): Response
    {
        try {
            return response()->view('tickets.show', ['number' => $number, 'ticket' => $zammad->ticket($number)]);
        } catch (ZammadException $exception) {
            return response()->view('tickets.show', [
                'number' => $number,
                'problem' => ['message' => $exception->problem->message($number), 'retry' => $exception->problem->canRetry()],
            ], $exception->problem->status());
        }
    }
}
