<?php

namespace App\Http\Controllers;

use App\Staff\StaffDirectory;
use App\Tickets\TicketLocks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TicketLockController extends Controller
{
    /**
     * The heartbeat of the ticket page (PROJ-96): with "claim" it takes or extends the lock after activity,
     * without it only tells a locked page whether the ticket is free again.
     */
    public function heartbeat(string $number, Request $request, TicketLocks $locks, StaffDirectory $staff): JsonResponse
    {
        $me = $staff->current($request);
        $lock = $request->boolean('claim') && $me !== null ? $locks->touch($number, $me) : $locks->holder($number, $me);

        return response()->json(['locked_by' => $lock?->staff_name]);
    }

    /**
     * Frees the ticket when its page is left (sent with navigator.sendBeacon).
     */
    public function release(string $number, Request $request, TicketLocks $locks, StaffDirectory $staff): Response
    {
        $me = $staff->current($request);

        if ($me !== null) {
            $locks->release($number, $me);
        }

        return response()->noContent();
    }
}
