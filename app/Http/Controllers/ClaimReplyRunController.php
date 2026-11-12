<?php

namespace App\Http\Controllers;

use App\Models\ClaimReplyRun;
use App\Staff\TestMode;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The runs of the automatic first reply to complaints, for admins (PROJ-85): one row per ticket with the result and, on
 * request, the texts that were sent.
 */
class ClaimReplyRunController extends Controller
{
    public function index(Request $request, TestMode $testMode): View
    {
        abort_unless($testMode->isAvailable($request), 403);

        $result = array_key_exists((string) $request->query('ergebnis'), ClaimReplyRun::results()) ? (string) $request->query('ergebnis') : null;
        $counts = ClaimReplyRun::query()->selectRaw('result, count(*) as total')->groupBy('result')->pluck('total', 'result');

        return view('claim-reply.index', [
            'result' => $result,
            'counts' => $counts,
            'total' => (int) $counts->sum(),
            'runs' => ClaimReplyRun::query()->when($result !== null, fn ($query) => $query->where('result', $result))->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function show(Request $request, TestMode $testMode, ClaimReplyRun $run): View
    {
        abort_unless($testMode->isAvailable($request), 403);

        return view('claim-reply.show', ['run' => $run]);
    }
}
