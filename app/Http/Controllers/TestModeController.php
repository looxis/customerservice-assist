<?php

namespace App\Http\Controllers;

use App\Http\LocalRedirect;
use App\Http\Requests\SwitchTestModeRequest;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use Illuminate\Http\RedirectResponse;

class TestModeController extends Controller
{
    /**
     * Switch the test mode (PROJ-32) on or off in this browser.
     */
    public function __invoke(SwitchTestModeRequest $request, TestMode $testMode, StaffDirectory $staff): RedirectResponse
    {
        $cookie = $request->boolean('aktiv')
            ? cookie($testMode->cookieName(), '1', $staff->cookieMinutes())
            : cookie()->forget($testMode->cookieName());

        return LocalRedirect::back($request, route('tickets.analyze'))->withCookie($cookie);
    }
}
