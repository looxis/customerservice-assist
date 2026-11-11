{{-- Assign a ticket or take the assignment back (PROJ-81): one dialog for the page, opened by the events "assign-ticket" and "unassign-ticket". --}}
@props(['number', 'assignees' => [], 'assignee' => null, 'currentName' => null])

<div x-data="{ open: false, mode: 'assign', busy: false }" x-cloak x-show="open"
     x-on:assign-ticket.window="mode = 'assign'; open = true; busy = false; $nextTick(() => $refs.target?.focus())"
     x-on:unassign-ticket.window="mode = 'unassign'; open = true; busy = false; $nextTick(() => $refs.reason?.focus())"
     x-on:keydown.escape.window="open = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="assign-ticket-heading">
    <div x-on:click.outside="open = false" class="w-full max-w-lg rounded-lg bg-white p-6 shadow-1 ring-1 ring-slate-200">
        <form x-show="mode === 'assign'" method="POST" action="{{ route('tickets.assign', ['number' => $number]) }}" class="space-y-4" x-on:submit="busy = true">
            @csrf
            <h2 id="assign-ticket-heading" class="text-base font-semibold text-slate-900">Ticket#{{ $number }} zuweisen</h2>
            <p class="text-sm text-slate-600">Die Zuweisung wird in Zammad in deinem Namen gespeichert. Der Kunde erhält keine Nachricht.</p>
            <x-select name="mitarbeiter" id="assign-target" label="Zuweisen an" required x-ref="target">
                @foreach ($assignees as $name)
                    <option value="{{ $name }}" @selected($name === ($assignee ?? $currentName))>{{ $name }}{{ $name === $currentName ? ' (ich)' : '' }}</option>
                @endforeach
            </x-select>
            <x-textarea name="notiz" id="assign-note" label="Notiz (optional)" hint="Wird als interne Notiz in Zammad gespeichert, z. B. „Bitte Reklamationsfoto prüfen“." rows="3" />
            <div class="flex flex-wrap justify-end gap-3">
                <x-button variant="secondary" x-on:click="open = false">Abbrechen</x-button>
                <x-button type="submit" x-bind:disabled="busy"><x-icon name="check" size="16" /> Zuweisen</x-button>
            </div>
        </form>

        <form x-show="mode === 'unassign'" method="POST" action="{{ route('tickets.unassign', ['number' => $number]) }}" class="space-y-4" x-on:submit="busy = true">
            @csrf
            <h2 class="text-base font-semibold text-slate-900">Zuweisung zurücknehmen?</h2>
            <p class="text-sm text-slate-600">Das Ticket{{ $assignee ? ' ist derzeit '.$assignee.' zugewiesen und' : '' }} ist danach wieder allgemein offen. Der Status bleibt unverändert.</p>
            <x-textarea name="notiz" id="unassign-note" label="Notiz (optional)" hint="Wird als interne Notiz in Zammad gespeichert, z. B. „Bin ab morgen im Urlaub“." rows="3" x-ref="reason" />
            <div class="flex flex-wrap justify-end gap-3">
                <x-button variant="secondary" x-on:click="open = false">Abbrechen</x-button>
                <x-button type="submit" x-bind:disabled="busy"><x-icon name="x-circle" size="16" /> Zuweisung zurücknehmen</x-button>
            </div>
        </form>
    </div>
</div>
