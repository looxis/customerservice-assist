<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTicketRequest;
use App\Staff\StaffDirectory;
use App\Zammad\ReplyException;
use App\Zammad\TicketSynchronizer;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use App\Zammad\ZammadReplySender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class TicketAssignController extends Controller
{
    /**
     * Assign an open ticket to an employee in Zammad (PROJ-81), with an optional internal note.
     */
    public function assign(string $number, AssignTicketRequest $request, ZammadClient $zammad, ZammadReplySender $sender, StaffDirectory $staff, TicketSynchronizer $synchronizer): RedirectResponse
    {
        $target = (string) $request->validated('mitarbeiter');

        return $this->change($number, $request, $zammad, $sender, $staff, $synchronizer, $staff->zammadUserId($target), "Ticket#{$number} wurde {$target} zugewiesen.", "Zugewiesen an {$target}");
    }

    /**
     * Take the assignment back: the ticket has no owner again, its status stays (PROJ-81).
     */
    public function unassign(string $number, AssignTicketRequest $request, ZammadClient $zammad, ZammadReplySender $sender, StaffDirectory $staff, TicketSynchronizer $synchronizer): RedirectResponse
    {
        return $this->change($number, $request, $zammad, $sender, $staff, $synchronizer, null, "Die Zuweisung von Ticket#{$number} wurde zurückgenommen.", 'Zuweisung zurückgenommen');
    }

    private function change(string $number, AssignTicketRequest $request, ZammadClient $zammad, ZammadReplySender $sender, StaffDirectory $staff, TicketSynchronizer $synchronizer, ?int $ownerId, string $success, string $subject): RedirectResponse
    {
        $back = fn (string $message): RedirectResponse => redirect()->route('tickets.show', ['number' => $number])->with('error', $message);
        $name = (string) $staff->current($request);

        try {
            $ticket = $zammad->ticket($number);
        } catch (ZammadException $exception) {
            return $back($exception->problem->message($number));
        }

        if ($ticket->closed) {
            return $back("Ticket#{$number} ist geschlossen. Die Zuweisung wurde nicht geändert.");
        }

        try {
            $sender->assign((string) $staff->zammadToken($name), $ticket->id, $ownerId, $request->validated('notiz'), $subject);
        } catch (ReplyException $exception) {
            return $back($exception->problem->assignMessage($name));
        }

        Log::info('Ticket assignment changed', ['ticket' => $number, 'staff' => $name, 'owner' => $ownerId]);

        rescue(fn () => $synchronizer->sync($ticket->id));

        return redirect()->route('tickets.show', ['number' => $number])->with('success', $success);
    }
}
