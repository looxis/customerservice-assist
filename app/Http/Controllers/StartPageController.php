<?php

namespace App\Http\Controllers;

use App\Analysis\TicketList;
use App\Changelog\Changelog;
use App\Http\Requests\TicketListRequest;
use App\Staff\StaffDirectory;
use App\Todo\TodoBoard;
use Illuminate\Contracts\View\View;

class StartPageController extends Controller
{
    /**
     * The manual ticket lookup with the list of tracked tickets below it (PROJ-67).
     */
    public function __invoke(TicketListRequest $request, TicketList $list, StaffDirectory $staff, Changelog $changelog, TodoBoard $todos): View
    {
        $filter = $list->filter($request->validated());
        $name = $staff->current($request);
        $staffZammadId = $staff->zammadUserId($name);
        $rows = $list->page($filter, $staffZammadId, (int) $request->validated('page', 1))->appends($filter->parameters());
        $todoView = TodoBoard::view($request->query('todos'));

        return view('tickets.analyze', [
            'todoView' => $todoView,
            'todoCounts' => $todos->counts($name),
            'todoList' => $todos->start($todoView, $name),
            'todoTickets' => $todos->ticketsWithOpen($rows->getCollection()->pluck('zammad_id')),
            'ticketFilter' => $filter,
            'ticketRows' => $rows,
            'ticketOptions' => $list->options(),
            'ticketSync' => $list->syncState(),
            'lacksStaffUser' => $list->lacksStaffUser($filter, $staffZammadId),
            'recentChanges' => $changelog->recent(),
        ]);
    }
}
