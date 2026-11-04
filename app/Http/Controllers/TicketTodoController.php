<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisStore;
use App\Http\Requests\CompleteTicketTodoRequest;
use App\Http\Requests\StoreTicketTodoRequest;
use App\Http\Requests\TodoListRequest;
use App\Http\Requests\UpdateTicketTodoRequest;
use App\Models\TicketTodo;
use App\Models\TrackedTicket;
use App\Staff\StaffDirectory;
use App\Todo\TodoBoard;
use App\Todo\TodoService;
use App\Todo\TodoStatus;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketTodoController extends Controller
{
    /**
     * All ToDos: open ones and those finished within the last 12 months, 100 per page (PROJ-70).
     */
    public function index(TodoListRequest $request, TodoBoard $board): View
    {
        return view('todos.index', [
            'rows' => $board->page((string) $request->validated('zeige', 'alle'), $request->validated('zustaendig')),
            'show' => (string) $request->validated('zeige', 'alle'),
            'assignee' => (string) $request->validated('zustaendig', ''),
        ]);
    }

    public function store(string $number, StoreTicketTodoRequest $request, ZammadClient $zammad, TodoService $todos, StaffDirectory $staff): RedirectResponse
    {
        $zammadId = TrackedTicket::query()->where('number', $number)->value('zammad_id');

        try {
            $zammadId ??= $zammad->ticket($number)->id;
        } catch (ZammadException $exception) {
            return back()->withInput()->with('error', $exception->problem->message($number).' Das ToDo wurde nicht angelegt.');
        }

        $token = (string) $request->validated('request_token', '');
        $todos->create((int) $zammadId, $number, $request->validated(), (string) $staff->current($request), $token === '' ? null : "manual:{$token}");

        return redirect()->to(route('tickets.show', ['number' => $number]).'#todos')->with('success', 'ToDo angelegt.');
    }

    public function update(TicketTodo $todo, UpdateTicketTodoRequest $request, TodoService $todos, StaffDirectory $staff): RedirectResponse
    {
        if ($todo->status === TodoStatus::Done) {
            return back()->with('error', 'Dieses ToDo ist bereits erledigt. Bitte erst wieder öffnen.');
        }

        $todos->update($todo, $request->validated(), (string) $staff->current($request));

        return back()->with('success', 'ToDo gespeichert.');
    }

    /**
     * Finish a ToDo. With "inform the customer" this prepares the reply in the ticket; the ToDo is done once it is sent.
     */
    public function complete(TicketTodo $todo, CompleteTicketTodoRequest $request, TodoService $todos, ZammadClient $zammad, AnalysisStore $store, StaffDirectory $staff): RedirectResponse
    {
        $name = (string) $staff->current($request);
        $skip = $request->boolean('without_notification');

        if ($todo->status === TodoStatus::Done) {
            return back()->with('error', 'Dieses ToDo wurde bereits erledigt'.($todo->completed_by === null ? '.' : " von {$todo->completed_by}."));
        }

        if (! $todo->notify_customer || $skip) {
            return $todos->finish($todo, $name, $skip)
                ? back()->with('success', 'ToDo erledigt.')
                : back()->with('error', 'Dieses ToDo wurde gerade von jemand anderem erledigt.');
        }

        // The prepared reply is reused as long as it can be sent; otherwise (a newer proposal, deleted proposals) a new one is prepared.
        $resume = $todo->status === TodoStatus::NotifyPending;

        if ($resume && $todos->draftUsable($todo, $store)) {
            return $this->draft($todo);
        }

        if (! $resume && ! $todos->claimNotification($todo, $name)) {
            return back()->with('error', 'Dieses ToDo wird gerade von jemand anderem abgeschlossen.');
        }

        try {
            $todos->prepareNotification($todo->refresh(), $name, $zammad, $store);
        } catch (ZammadException $exception) {
            $todos->releaseNotification($todo);

            return back()->with('error', $exception->problem->message($todo->ticket_number).($resume ? ' Die Antwort wurde nicht vorbereitet.' : ' Das ToDo ist noch offen.'));
        }

        return $this->draft($todo->refresh());
    }

    public function reopen(TicketTodo $todo, StaffDirectory $staff, Request $request): RedirectResponse
    {
        $todo->update(['status' => TodoStatus::Open, 'completed_by' => null, 'completed_at' => null, 'notify_skipped' => false, 'notify_analysis_uuid' => null, 'updated_by' => (string) $staff->current($request)]);

        return back()->with('success', 'ToDo wieder geöffnet.');
    }

    private function draft(TicketTodo $todo): RedirectResponse
    {
        return redirect()->to(route('tickets.show', ['number' => $todo->ticket_number, 'analyse' => $todo->notify_analysis_uuid]).'#antwort-versenden')
            ->with('success', 'Die Antwort an den Kunden ist vorbereitet. Bitte prüfen und senden; danach ist das ToDo erledigt.');
    }
}
