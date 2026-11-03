<?php

namespace App\Http\Controllers;

use App\Analysis\TicketList;
use App\Changelog\Changelog;
use App\Http\Requests\TicketListRequest;
use App\Staff\StaffDirectory;
use Illuminate\Contracts\View\View;

class StartPageController extends Controller
{
    /**
     * The manual ticket lookup with the list of tracked tickets below it (PROJ-67).
     */
    public function __invoke(TicketListRequest $request, TicketList $list, StaffDirectory $staff, Changelog $changelog): View
    {
        $filter = $list->filter($request->validated());
        $staffZammadId = $staff->zammadUserId($staff->current($request));

        return view('tickets.analyze', [
            'ticketFilter' => $filter,
            'ticketRows' => $list->page($filter, $staffZammadId, (int) $request->validated('page', 1))->appends($filter->parameters()),
            'ticketOptions' => $list->options(),
            'ticketSync' => $list->syncState(),
            'lacksStaffUser' => $list->lacksStaffUser($filter, $staffZammadId),
            'recentChanges' => $changelog->recent(),
        ]);
    }
}
