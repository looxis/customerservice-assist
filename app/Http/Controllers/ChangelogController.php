<?php

namespace App\Http\Controllers;

use App\Changelog\Changelog;
use Illuminate\Contracts\View\View;

class ChangelogController extends Controller
{
    /**
     * The page "Änderungen": every entry of docs/CHANGELOG.md, newest first.
     */
    public function __invoke(Changelog $changelog): View
    {
        return view('changes', ['days' => $changelog->all()]);
    }
}
