{{-- Create or change a ticket ToDo (PROJ-70): one dialog for the page, opened by "new-todo" or "edit-todo". --}}
@props(['ticketNumber' => null])

@php
    $names = \App\Todo\TodoRules::assignees();
    $presets = \App\Todo\TodoRules::presets();
    $blank = ['id' => null, 'preset' => '', 'previous' => '', 'description' => '', 'assignee' => \App\Todo\TodoRules::EVERYONE, 'due_in' => '', 'due_label' => null, 'waiting_for' => '', 'notify_customer' => false, 'notify_hint' => '', 'reference' => '', 'auto_send' => false, 'german' => false, 'ticket' => $ticketNumber];
    $create = $ticketNumber ? route('tickets.todos.store', ['number' => $ticketNumber]) : null;
@endphp

@if ($errors->hasAny(['description', 'assignee', 'due_in', 'kind', 'waiting_for', 'notify_hint', 'reference']))
    <x-alert type="error" role="alert">
        <p class="font-semibold">Das ToDo wurde nicht gespeichert.</p>
        <ul class="mt-1 list-disc pl-5">@foreach (collect($errors->all())->unique() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </x-alert>
@endif

<div x-data="{
        open: false, mode: 'create', todo: @js($blank), names: @js($names), presets: @js($presets), token: '', busy: false,
        start(mode, todo) { this.mode = mode; this.todo = { ...@js($blank), ...todo }; if (this.todo.due_in === 'keep' && this.todo.kind === 'waiting') { this.todo.due_in = 'waiting' } if (! this.names.includes(this.todo.assignee)) { this.todo.assignee = '' } this.token = crypto.randomUUID ? crypto.randomUUID() : String(Date.now()); this.busy = false; this.open = true; this.$nextTick(() => this.$refs.description.focus()) },
        get text() { return [this.todo.preset, this.todo.description.trim()].filter((part) => part !== '').join(' – ') },
        get action() { return this.mode === 'create' ? @js($create) : @js(url('/todos')) + '/' + this.todo.id },
    }"
    x-cloak x-show="open"
    @if ($create) x-on:new-todo.window="start('create', {})" @endif
    x-on:edit-todo.window="start('edit', $event.detail)"
    x-on:keydown.escape.window="open = false" x-on:click.self="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="todo-dialog-heading">
    <form method="POST" x-bind:action="action" x-on:submit="busy = true"
          class="w-full max-w-lg space-y-4 rounded-lg bg-white p-6 shadow-1 ring-1 ring-slate-200">
        @csrf
        <input type="hidden" name="_method" value="PATCH" x-bind:disabled="mode !== 'edit'">
        <input type="hidden" name="request_token" x-bind:value="token" x-bind:disabled="mode !== 'create'">
        <h2 id="todo-dialog-heading" class="text-base font-semibold text-slate-900" x-text="mode === 'create' ? 'ToDo anlegen' : 'ToDo bearbeiten (Ticket#' + todo.ticket + ')'"></h2>

        <div class="space-y-2">
            <div x-show="mode === 'create'">
                <label for="todo-preset" class="block text-sm font-medium text-slate-900">Häufiges ToDo</label>
                <select id="todo-preset" x-model="todo.preset" x-on:change="$todoPreset(todo, presets, todo.previous); todo.previous = todo.preset" class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
                    <option value="">Etwas anderes (Freitext)</option>
                    @foreach ($presets as $preset)<option value="{{ $preset['label'] }}">{{ $preset['label'] }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="todo-description" class="block text-sm font-medium text-slate-900" x-text="todo.preset ? 'Zusätzliche Angaben (optional)' : 'Beschreibung'">Beschreibung</label>
                <textarea id="todo-description" x-ref="description" x-model="todo.description" rows="3" maxlength="900" x-bind:required="! todo.preset"
                          class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"></textarea>
                <input type="hidden" name="description" x-bind:value="text">
            </div>
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
                <select id="todo-due" x-model="todo.due_in" class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
                    <option value="keep" x-show="mode === 'edit'" x-bind:disabled="mode !== 'edit'" x-text="todo.due_label ? 'Unverändert (' + todo.due_label + ')' : 'Unverändert (ohne Frist)'"></option>
                    <option value="">Ohne Frist</option>
                    <option value="waiting">Wartet auf Ereignis</option>
                    <option value="1">In 1 Tag</option>
                    <option value="3">In 3 Tagen</option>
                    <option value="7">In 7 Tagen</option>
                </select>
            </div>
        </div>

        <input type="hidden" name="due_in" x-bind:value="todo.due_in === 'waiting' ? '' : todo.due_in">
        <input type="hidden" name="kind" x-bind:value="todo.due_in === 'waiting' ? 'waiting' : 'direct'">
        <div x-show="todo.due_in === 'waiting'" x-cloak>
            <label for="todo-waiting" class="block text-sm font-medium text-slate-900">Worauf wird gewartet?</label>
            <input id="todo-waiting" type="text" name="waiting_for" x-model="todo.waiting_for" maxlength="255" x-bind:required="todo.due_in === 'waiting'" placeholder="z. B. Versand der Ersatzbestellung"
                   class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
        </div>

        <div class="space-y-2">
            <label class="inline-flex items-center gap-2 text-sm text-slate-900"><input type="hidden" name="notify_customer" value="0"><input type="checkbox" name="notify_customer" value="1" x-model="todo.notify_customer" class="rounded text-brand focus:ring-brand"> Kunde nach Erledigung informieren</label>
            <div x-show="todo.notify_customer" x-cloak>
                <label for="todo-hint" class="block text-sm font-medium text-slate-900">Entwurf der späteren Antwort <span class="font-normal text-slate-600">(wird vorbereitet, du prüfst und sendest)</span></label>
                <textarea id="todo-hint" name="notify_hint" x-model="todo.notify_hint" rows="6" maxlength="1000" placeholder="Text der Antwort an den Kunden"
                          class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"></textarea>
            </div>
        </div>

        <div class="space-y-2">
            <div>
                <label for="todo-reference" class="block text-sm font-medium text-slate-900">Reklamations- oder Bestellnummer <span class="font-normal text-slate-600">(optional)</span></label>
                <input id="todo-reference" type="text" name="reference" x-model="todo.reference" maxlength="120" placeholder="z. B. R1-7KA-0QH8-6AF8"
                       class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 font-mono text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
            </div>
            <div x-show="todo.reference.trim() !== ''" x-cloak class="space-y-1 text-sm text-slate-900">
                <input type="hidden" name="auto_send" x-bind:value="todo.auto_send && todo.german ? '1' : '0'">
                <label class="inline-flex items-center gap-2"><input type="checkbox" x-model="todo.auto_send" x-bind:disabled="! todo.german" class="rounded text-brand focus:ring-brand"> Mail bei Verfügbarkeit der Sendungsnummer automatisiert versenden</label>
                <p x-show="! todo.german" class="text-slate-600">Die Sprache des Kunden ist hier nicht bekannt oder nicht Deutsch: Die App sendet nicht von selbst. Am Antwortvorschlag im Ticket lässt sich das ToDo mit Automatik anlegen.</p>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <x-button variant="secondary" x-on:click="open = false">Abbrechen</x-button>
            <x-button type="submit" x-bind:disabled="busy"><x-icon name="check" size="16" /> <span x-text="busy ? 'Wird gespeichert …' : 'Speichern'">Speichern</span></x-button>
        </div>
    </form>
</div>
