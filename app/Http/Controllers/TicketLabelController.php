<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketLabelRequest;
use App\Labeling\ReviewRecorder;
use App\Labeling\TicketLabelState;
use App\Models\LabelTicket;
use App\Staff\StaffDirectory;
use Illuminate\Http\JsonResponse;

class TicketLabelController extends Controller
{
    /**
     * Files the category a member of the staff chose for a new ticket on the ticket page (PROJ-80), sent in the
     * background so that a started reply stays.
     */
    public function __invoke(string $number, StoreTicketLabelRequest $request, ReviewRecorder $recorder, StaffDirectory $staff): JsonResponse
    {
        $ticket = LabelTicket::query()->where('number', $number)->with('latestPrediction')->first();

        if ($ticket === null || ! $ticket->staffMayDecide()) {
            return response()->json(['message' => 'Die Einordnung dieses Tickets lässt sich hier nicht mehr ändern. Lade die Seite neu.'], 409);
        }

        $recorder->recordFromTicket($ticket, $request->validated('entscheidung'), (string) $staff->current($request));

        return response()->json(TicketLabelState::of($ticket->fresh(['latestPrediction', 'reviews'])));
    }
}
