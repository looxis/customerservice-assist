<?php

namespace App\Http\Middleware;

use App\Http\LocalRedirect;
use App\Staff\StaffDirectory;
use App\Tickets\TicketLocks;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns away changes to a ticket someone else is working on right now (PROJ-96). Question numbers
 * (PROJ-40) belong to nobody else and pass.
 */
class EnsureTicketNotLocked
{
    public function __construct(private readonly StaffDirectory $staff, private readonly TicketLocks $locks) {}

    public static function message(string $name): string
    {
        return "Ticket wird gerade von {$name} bearbeitet.";
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $number = (string) $request->route('number');

        if (! ctype_digit($number) || ($lock = $this->locks->holder($number, $this->staff->current($request))) === null) {
            return $next($request);
        }

        $message = self::message($lock->staff_name);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'locked_by' => $lock->staff_name], 409);
        }

        return LocalRedirect::back($request, route('tickets.show', ['number' => $number]))->withInput()->with('error', $message);
    }
}
