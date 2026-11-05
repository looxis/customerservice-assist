<?php

namespace App\Http\Controllers;

use App\Http\LocalRedirect;
use App\Http\Requests\SwitchIntroModeRequest;
use App\Staff\IntroMode;
use App\Staff\StaffDirectory;
use Illuminate\Http\RedirectResponse;

class IntroModeController extends Controller
{
    /**
     * Switch the introduction mode (PROJ-74) on or off in this browser.
     */
    public function __invoke(SwitchIntroModeRequest $request, IntroMode $introMode, StaffDirectory $staff): RedirectResponse
    {
        $cookie = $request->boolean('aktiv')
            ? cookie($introMode->cookieName(), '1', $staff->cookieMinutes())
            : cookie()->forget($introMode->cookieName());

        return LocalRedirect::back($request, route('tickets.analyze'))->withCookie($cookie);
    }
}
