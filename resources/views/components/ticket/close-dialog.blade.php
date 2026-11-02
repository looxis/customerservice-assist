{{-- Close a ticket without a reply (PROJ-68): one dialog for the page, opened by the event "close-ticket". --}}
@props(['number'])

<div x-data="{ open: false }" x-cloak x-show="open" x-on:close-ticket.window="open = true; $nextTick(() => $refs.reason.focus())" x-on:keydown.escape.window="open = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="close-ticket-heading">
    <form method="POST" action="{{ route('tickets.close', ['number' => $number]) }}" x-on:click.outside="open = false"
          class="w-full max-w-lg space-y-4 rounded-lg bg-white p-6 shadow-1 ring-1 ring-slate-200" x-data="{ busy: false }" x-on:submit="busy = true">
        @csrf
        <h2 id="close-ticket-heading" class="text-base font-semibold text-slate-900">Ticket#{{ $number }} ohne Antwort schließen?</h2>
        <p class="text-sm text-slate-600">Das Ticket wird in Zammad in deinem Namen geschlossen. Der Kunde erhält keine Nachricht.</p>
        <x-textarea name="grund" id="close-reason" label="Grund (optional)" hint="Wird als interne Notiz in Zammad gespeichert, z. B. „Doppelt, beantwortet in Ticket 2140187“." rows="3" x-ref="reason" />
        <div class="flex flex-wrap justify-end gap-3">
            <x-button variant="secondary" x-on:click="open = false">Abbrechen</x-button>
            <x-button type="submit" variant="danger" x-bind:disabled="busy"><x-icon name="x-circle" size="16" /> Ohne Antwort schließen</x-button>
        </div>
    </form>
</div>
