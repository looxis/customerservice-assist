<?php

namespace App\Http\Controllers;

use App\Http\LocalRedirect;
use App\Http\Requests\SelectStaffRequest;
use App\Staff\StaffDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class StaffSelectionController extends Controller
{
    /**
     * Remember the chosen name in this browser. The picker sends in the
     * background and gets JSON; without JavaScript the page reloads.
     */
    public function __invoke(SelectStaffRequest $request, StaffDirectory $staff): JsonResponse|RedirectResponse
    {
        $name = $request->validated('name');
        $cookie = cookie($staff->cookieName(), $name, $staff->cookieMinutes());

        $response = $request->expectsJson()
            ? response()->json(['name' => $name])
            : LocalRedirect::back($request, route('tickets.analyze'));

        return $response->withCookie($cookie);
    }
}
