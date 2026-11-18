<?php

namespace App\Http\Controllers;

use App\Analysis\TicketList;
use App\Changelog\Changelog;
use App\Http\Requests\TicketListRequest;
use App\Labeling\CategoryStatus;
use App\Staff\StaffDirectory;
use App\Todo\TodoBoard;
use Illuminate\Contracts\View\View;

class StartPageController extends Controller
{
    /**
     * The list of tracked tickets (PROJ-67) with the manual lookup folded above it; the open ToDos are on their own page.
     */
    public function __invoke(TicketListRequest $request, TicketList $list, StaffDirectory $staff, Changelog $changelog, TodoBoard $todos, CategoryStatus $categoryStatus): View
    {
        $filter = $list->filter($request->validated());
        $name = $staff->current($request);
        $staffZammadId = $staff->zammadUserId($name);
        $rows = $list->page($filter, $staffZammadId, (int) $request->validated('page', 1))->appends($filter->parameters());

        return view('tickets.analyze', [
            'todoTickets' => $todos->ticketsWithOpen($rows->getCollection()->pluck('zammad_id')),
            'categoryStatus' => $categoryStatus->forRows($rows->getCollection()),
            'ticketFilter' => $filter,
            'ticketRows' => $rows,
            'ticketOptions' => $list->options(),
            'ticketSync' => $list->syncState(),
            'lacksStaffUser' => $list->lacksStaffUser($filter, $staffZammadId),
            'recentChanges' => $changelog->recent(),
        ]);
    }
}
