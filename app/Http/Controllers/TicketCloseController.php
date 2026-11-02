<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseTicketRequest;
use App\Staff\StaffDirectory;
use App\Zammad\ReplyException;
use App\Zammad\TicketSynchronizer;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use App\Zammad\ZammadReplySender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class TicketCloseController extends Controller
{
    /**
     * Close a ticket in Zammad without a reply to the customer (PROJ-68) and go back to the ticket list.
     */
    public function __invoke(string $number, CloseTicketRequest $request, ZammadClient $zammad, ZammadReplySender $sender, StaffDirectory $staff, TicketSynchronizer $synchronizer): RedirectResponse
    {
        $back = fn (string $message): RedirectResponse => redirect()->route('tickets.show', ['number' => $number])->with('error', $message);
        $name = (string) $staff->current($request);

        try {
            $ticket = $zammad->ticket($number);
        } catch (ZammadException $exception) {
            return $back($exception->problem->message($number));
        }

        if ($ticket->closed) {
            return redirect()->route('tickets.analyze')->with('success', "Ticket#{$number} war bereits geschlossen.");
        }

        try {
            $sender->close((string) $staff->zammadToken($name), $ticket->id, $request->validated('grund'));
        } catch (ReplyException $exception) {
            return $back($exception->problem->closeMessage($name));
        }

        Log::info('Ticket closed without reply', ['ticket' => $number, 'staff' => $name]);

        // The list shows the new state at once, without waiting for the next sync run.
        rescue(fn () => $synchronizer->sync($ticket->id));

        return redirect()->route('tickets.analyze')->with('success', "Ticket#{$number} wurde ohne Antwort geschlossen.");
    }
}
