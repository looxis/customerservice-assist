<?php

namespace App\Http\Controllers;

use App\Models\WismoRun;
use App\Staff\TestMode;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The runs of the automatic WISMO reply, for admins (PROJ-82): one row per ticket with the result and, on request, the
 * texts that were sent.
 */
class WismoRunController extends Controller
{
    public function index(Request $request, TestMode $testMode): View
    {
        abort_unless($testMode->isAvailable($request), 403);

        $result = array_key_exists((string) $request->query('ergebnis'), WismoRun::results()) ? (string) $request->query('ergebnis') : null;
        $counts = WismoRun::query()->selectRaw('result, count(*) as total')->groupBy('result')->pluck('total', 'result');

        return view('wismo.index', [
            'result' => $result,
            'counts' => $counts,
            'total' => (int) $counts->sum(),
            'runs' => WismoRun::query()->when($result !== null, fn ($query) => $query->where('result', $result))->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function show(Request $request, TestMode $testMode, WismoRun $run): View
    {
        abort_unless($testMode->isAvailable($request), 403);

        return view('wismo.show', ['run' => $run]);
    }
}
