{{-- One ticket ToDo (PROJ-70): text, ticket, due date, assignee and the actions. --}}
@props(['todo', 'showTicket' => true])

@php
    $listed = $todo->assignee === \App\Todo\TodoRules::EVERYONE || app(\App\Staff\StaffDirectory::class)->isListed($todo->assignee);
    $done = $todo->status === \App\Todo\TodoStatus::Done;
    $pending = $todo->status === \App\Todo\TodoStatus::NotifyPending;
    $overdue = $todo->isOverdue();
    $auto = $todo->auto_state ?? null;
    $edit = [
        'id' => $todo->id,
        'description' => $todo->description,
        'assignee' => $todo->assignee,
        'due_in' => 'keep',
        'due_label' => $todo->due_on?->format('d.m.Y'),
        'kind' => $todo->kind->value,
        'waiting_for' => $todo->waiting_for,
        'notify_customer' => $todo->notify_customer,
        'notify_hint' => $todo->notify_hint,
        'reference' => $todo->external_ref ?? '',
        'auto_send' => (bool) ($todo->auto_send ?? false),
        'german' => str_starts_with(mb_strtolower(trim((string) ($todo->customer_language ?? ''))), 'deutsch'),
        'ticket' => $todo->ticket_number,
    ];
@endphp

<li class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3 px-4 py-3 {{ $overdue ? 'bg-danger-500/5' : '' }}">
    <div class="min-w-0 flex-1 space-y-1">
        <p class="text-sm text-slate-900 wrap-anywhere whitespace-pre-line {{ $done ? 'text-slate-600 line-through' : '' }}">{{ $todo->description }}</p>
        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-600">
            @if ($showTicket)<a href="{{ route('tickets.show', $todo->ticket_number) }}" class="font-mono text-brand hover:text-brand-hover">Ticket#{{ $todo->ticket_number }}</a>@endif
            <span>Zuständig: {{ $todo->assignee ?? '–' }}</span>
            @if ($todo->assignee !== null && ! $listed && ! $done)<x-badge compact tone="warning">Zuständiger nicht mehr in der Liste</x-badge>@endif
            @if ($todo->due_on)
                <x-badge compact :tone="$overdue ? 'danger' : 'neutral'">{{ $overdue ? 'Überfällig seit' : 'Fällig' }} {{ $todo->due_on->format('d.m.Y') }}</x-badge>
            @endif
            @if ($todo->kind === \App\Todo\TodoKind::Waiting)<x-badge compact tone="info">Wartet auf: {{ $todo->waiting_for }}</x-badge>@endif
            @if ($auto === 'watching')<x-badge compact tone="info">Automatische Prüfung{{ ($todo->auto_next_label ?? null) ? ': nächste '.$todo->auto_next_label : '' }}</x-badge>
            @elseif ($auto === 'review')<x-badge compact tone="warning">Bitte prüfen: {{ $todo->auto_reason ?? 'Grund unbekannt' }}</x-badge>
            @elseif ($auto === 'expired')<x-badge compact tone="warning">Sendungsnummer fehlt seit 5 Tagen</x-badge>
            @elseif ($auto === 'sent')<x-badge compact tone="success">Automatisch gesendet{{ ($todo->auto_sent_at ?? null) ? ' am '.$todo->auto_sent_at->setTimezone('Europe/Berlin')->format('d.m. \u\m H:i') : '' }}{{ ($todo->auto_tracking ?? null) ? ' · '.$todo->auto_tracking : '' }}</x-badge>@endif
            @if ($pending)<x-badge compact tone="warning">Benachrichtigung offen</x-badge>
            @elseif ($todo->notify_customer && ! $done)<x-badge compact>Kunde wird informiert</x-badge>@endif
            @if ($done)<span>Erledigt von {{ $todo->completed_by ?? '–' }}{{ $todo->completed_at ? ' am '.$todo->completed_at->setTimezone('Europe/Berlin')->format('d.m.Y') : '' }}{{ $todo->notify_skipped ? ' (Kunde nicht informiert)' : '' }}</span>@endif
        </p>
        @if ($todo->auto_send && $todo->autoChecks->isNotEmpty() || $auto === 'sent')
            <details class="text-xs text-slate-600">
                <summary class="cursor-pointer font-medium text-slate-700">Verlauf der automatischen Prüfung ({{ $todo->auto_checks }})</summary>
                <ul class="mt-1 space-y-0.5">
                    @foreach ($todo->autoChecks->take(20) as $check)
                        <li>{{ $check->checked_at->setTimezone('Europe/Berlin')->format('d.m. H:i') }} · {{ ['none' => 'noch keine Sendungsnummer', 'sent' => 'gesendet', 'review' => 'bitte prüfen', 'error' => 'Fehler', 'expired' => 'beendet', 'note_failed' => 'Notiz fehlgeschlagen'][$check->result] ?? $check->result }}@if ($check->note) · {{ $check->note }}@endif</li>
                    @endforeach
                </ul>
                @if ($auto === 'sent' && $todo->auto_text)<p class="mt-2 font-medium text-slate-700">Gesendeter Text</p><p class="whitespace-pre-line text-slate-900">{{ $todo->auto_text }}</p>@endif
            </details>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2" x-data="{ edit: @js($edit) }">
        @if ($done)
            <form method="POST" action="{{ route('todos.reopen', $todo) }}">@csrf<x-button type="submit" variant="secondary"><x-icon name="refresh" size="16" /> Wieder öffnen</x-button></form>
        @else
            <x-button variant="secondary" x-on:click="$dispatch('edit-todo', edit)">Bearbeiten</x-button>
            @if ($pending)
                <form method="POST" action="{{ route('todos.complete', $todo) }}">@csrf<x-button type="submit"><x-icon name="chat" size="16" /> Antwort öffnen</x-button></form>
            @endif
            @if ($todo->notify_customer)
                <form method="POST" action="{{ route('todos.complete', $todo) }}" class="flex flex-wrap gap-2">
                    @csrf
                    @unless ($pending)<x-button type="submit"><x-icon name="check" size="16" /> Erledigt, Kunde informieren</x-button>@endunless
                    <x-button type="submit" variant="secondary" name="without_notification" value="1"><x-icon name="check" size="16" /> Ohne Benachrichtigung abschließen</x-button>
                </form>
            @else
                <form method="POST" action="{{ route('todos.complete', $todo) }}">@csrf<x-button type="submit"><x-icon name="check" size="16" /> Erledigt</x-button></form>
            @endif
        @endif
    </div>
</li>
