<?php

namespace App\Staff;

use Illuminate\Http\Request;

/**
 * The advanced mode (PROJ-42): a switch per browser for everybody that shows the technical parts of
 * the proposal form (context of the ticket, preview of what goes to the AI, loading of order details,
 * Zammad group). Without a login there is nothing to protect; it only keeps the form short.
 */
class AdvancedMode
{
    public function cookieName(): string
    {
        return (string) config('staff.advanced_mode_cookie', 'advanced_mode');
    }

    public function isActive(Request $request): bool
    {
        return $request->cookie($this->cookieName()) === '1';
    }
}
