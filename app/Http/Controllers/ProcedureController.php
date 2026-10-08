<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisPanel;
use App\Knowledge\ProcedureFinder;
use Illuminate\View\View;

class ProcedureController extends Controller
{
    /**
     * The card of one usable procedure, loaded into the ticket page when it
     * is picked by hand (PROJ-30).
     */
    public function show(string $id, ProcedureFinder $finder): View
    {
        $procedure = $finder->find($id);

        abort_if($procedure === null, 404);

        return view('procedures.card', ['procedure' => AnalysisPanel::procedureCard($procedure)]);
    }
}
