<?php

namespace App\Http\Middleware;

use App\Http\LocalRedirect;
use App\Staff\StaffDirectory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards actions that are attributed to a person (analysis, feedback,
 * knowledge gap): without a chosen name nothing is done and the input is kept.
 */
class EnsureStaffSelected
{
    public const string MESSAGE = 'Bitte wähle zuerst deinen Namen.';

    public function __construct(private readonly StaffDirectory $staff) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->staff->current($request) !== null) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => self::MESSAGE], 409);
        }

        return LocalRedirect::back($request, route('tickets.analyze'))->withInput()->with('error', self::MESSAGE);
    }
}
