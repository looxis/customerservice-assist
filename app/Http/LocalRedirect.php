<?php

namespace App\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Back" for form submissions without trusting the Referer header: only an
 * address of this app is used, anything else falls back to a fixed page.
 */
class LocalRedirect
{
    public static function back(Request $request, string $fallback): RedirectResponse
    {
        return redirect()->to(self::previous($request, $fallback));
    }

    public static function previous(Request $request, string $fallback): string
    {
        $previous = url()->previous($fallback);
        $root = $request->getSchemeAndHttpHost();

        return $previous === $root || str_starts_with($previous, $root.'/') ? $previous : $fallback;
    }
}
