<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisPanel;
use App\Knowledge\CaseLinksBuilder;
use App\Knowledge\ProcedureFinder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcedureController extends Controller
{
    /**
     * The card of one usable procedure, loaded into the ticket page when it
     * is picked by hand (PROJ-30). The page gives the ticket number and the
     * order numbers so the links to the tools fit the case (PROJ-58).
     */
    public function show(string $id, Request $request, ProcedureFinder $finder, CaseLinksBuilder $links): View
    {
        $procedure = $finder->find($id);

        abort_if($procedure === null, 404);

        $ticket = $request->query('ticket');
        $orders = $request->query('bestellungen', []);
        $case = $links->build(is_string($ticket) ? $ticket : null, is_array($orders) ? array_values(array_filter($orders, 'is_string')) : []);

        return view('procedures.card', ['procedure' => AnalysisPanel::procedureCard($procedure, $case)]);
    }
}
