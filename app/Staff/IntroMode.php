<?php

namespace App\Staff;

use Illuminate\Http\Request;

/**
 * The introduction mode (PROJ-74): a switch per browser for everybody that shows the explanatory texts
 * and examples (help for the fields, hints on empty states). Off by default so that daily users get
 * a short page; warnings that need action never depend on it. Independent of the advanced mode.
 */
class IntroMode
{
    public function cookieName(): string
    {
        return (string) config('staff.intro_mode_cookie', 'intro_mode');
    }

    public function isActive(Request $request): bool
    {
        return $request->cookie($this->cookieName()) === '1';
    }
}
