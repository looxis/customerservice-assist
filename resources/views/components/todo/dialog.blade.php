{{-- Create or change a ticket ToDo (PROJ-70): one dialog for the page, opened by "new-todo" or "edit-todo". --}}
@props(['ticketNumber' => null])

@php
    $names = app(\App\Staff\StaffDirectory::class)->names();
    $current = app(\App\Staff\StaffDirectory::class)->current(request());
    $blank = ['id' => null, 'description' => '', 'assignee' => $current ?? '', 'due_in' => '', 'due_label' => null, 'kind' => 'direct', 'waiting_for' => '', 'notify_customer' => false, 'notify_hint' => '', 'ticket' => $ticketNumber];
    $create = $ticketNumber ? route('tickets.todos.store', ['number' => $ticketNumber]) : null;
@endphp

@if ($errors->hasAny(['description', 'assignee', 'due_in', 'kind', 'waiting_for', 'notify_hint']))
    <x-alert type="error" role="alert">
        <p class="font-semibold">Das ToDo wurde nicht gespeichert.</p>
        <ul class="mt-1 list-disc pl-5">@foreach (collect($errors->all())->unique() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </x-alert>
@endif

<div x-data="{
        open: false, mode: 'create', todo: @js($blank), names: @js($names), token: '', busy: false,
        start(mode, todo) { this.mode = mode; this.todo = { ...@js($blank), ...todo }; if (! this.names.includes(this.todo.assignee)) { this.todo.assignee = '' } this.token = crypto.randomUUID ? crypto.randomUUID() : String(Date.now()); this.busy = false; this.open = true; this.$nextTick(() => this.$refs.description.focus()) },
        get action() { return this.mode === 'create' ? @js($create) : @js(url('/todos')) + '/' + this.todo.id },
    }"
    x-cloak x-show="open"
    @if ($create) x-on:new-todo.window="start('create', {})" @endif
    x-on:edit-todo.window="start('edit', $event.detail)"
    x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="todo-dialog-heading">
    <form method="POST" x-bind:action="action" x-on:click.outside="open = false" x-on:submit="busy = true"
          class="w-full max-w-lg space-y-4 rounded-lg bg-white p-6 shadow-1 ring-1 ring-slate-200">
        @csrf
        <input type="hidden" name="_method" value="PATCH" x-bind:disabled="mode !== 'edit'">
        <input type="hidden" name="request_token" x-bind:value="token" x-bind:disabled="mode !== 'create'">
        <h2 id="todo-dialog-heading" class="text-base font-semibold text-slate-900" x-text="mode === 'create' ? 'ToDo anlegen' : 'ToDo bearbeiten (Ticket#' + todo.ticket + ')'"></h2>

        <div>
            <label for="todo-description" class="block text-sm font-medium text-slate-900">Beschreibung</label>
            <textarea id="todo-description" name="description" x-ref="description" x-model="todo.description" rows="3" maxlength="1000" required
                      class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"></textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="todo-assignee" class="block text-sm font-medium text-slate-900">Zuständig</label>
                <select id="todo-assignee" name="assignee" x-model="todo.assignee" class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
                    <option value="" disabled>– Name wählen –</option>
                    @foreach ($names as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="todo-due" class="block text-sm font-medium text-slate-900">Fällig</label>
                <select id="todo-due" name="due_in" x-model="todo.due_in" class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
                    <option value="keep" x-show="mode === 'edit'" x-bind:disabled="mode !== 'edit'" x-text="todo.due_label ? 'Unverändert (' + todo.due_label + ')' : 'Unverändert (ohne Frist)'"></option>
                    <option value="">Ohne Frist</option>
                    <option value="1">In 1 Tag</option>
                    <option value="3">In 3 Tagen</option>
                    <option value="7">In 7 Tagen</option>
                </select>
            </div>
        </div>

        <fieldset>
            <legend class="text-sm font-medium text-slate-900">Art</legend>
            <div class="mt-1 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-900">
                <label class="inline-flex items-center gap-2"><input type="radio" name="kind" value="direct" x-model="todo.kind" class="text-brand focus:ring-brand"> Direkt bearbeitbar</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="kind" value="waiting" x-model="todo.kind" class="text-brand focus:ring-brand"> Wartet auf Ereignis</label>
            </div>
        </fieldset>
        <div x-show="todo.kind === 'waiting'" x-cloak>
            <label for="todo-waiting" class="block text-sm font-medium text-slate-900">Worauf wird gewartet?</label>
            <input id="todo-waiting" type="text" name="waiting_for" x-model="todo.waiting_for" maxlength="255" x-bind:required="todo.kind === 'waiting'" placeholder="z. B. Versand der Ersatzbestellung"
                   class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
        </div>

        <div class="space-y-2">
            <label class="inline-flex items-center gap-2 text-sm text-slate-900"><input type="hidden" name="notify_customer" value="0"><input type="checkbox" name="notify_customer" value="1" x-model="todo.notify_customer" class="rounded text-brand focus:ring-brand"> Kunde nach Erledigung informieren</label>
            <div x-show="todo.notify_customer" x-cloak>
                <label for="todo-hint" class="block text-sm font-medium text-slate-900">Hinweis für die Antwort <span class="font-normal text-slate-600">(wird als Entwurf vorbereitet, du prüfst und sendest)</span></label>
                <textarea id="todo-hint" name="notify_hint" x-model="todo.notify_hint" rows="3" maxlength="1000" placeholder="z. B. Sendungsnummer der Ersatzbestellung mitteilen"
                          class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"></textarea>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <x-button variant="secondary" x-on:click="open = false">Abbrechen</x-button>
            <x-button type="submit" x-bind:disabled="busy"><x-icon name="check" size="16" /> <span x-text="busy ? 'Wird gespeichert …' : 'Speichern'">Speichern</span></x-button>
        </div>
    </form>
</div>
