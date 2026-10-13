<?php

namespace App\Http\Controllers;

use App\Http\LocalRedirect;
use App\Http\Requests\SwitchAdvancedModeRequest;
use App\Staff\AdvancedMode;
use App\Staff\StaffDirectory;
use Illuminate\Http\RedirectResponse;

class AdvancedModeController extends Controller
{
    /**
     * Switch the advanced mode (PROJ-42) on or off in this browser.
     */
    public function __invoke(SwitchAdvancedModeRequest $request, AdvancedMode $advancedMode, StaffDirectory $staff): RedirectResponse
    {
        $cookie = $request->boolean('aktiv')
            ? cookie($advancedMode->cookieName(), '1', $staff->cookieMinutes())
            : cookie()->forget($advancedMode->cookieName());

        return LocalRedirect::back($request, route('tickets.analyze'))->withCookie($cookie);
    }
}
