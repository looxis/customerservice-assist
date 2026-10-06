@props(['ticket'])

{{-- Header of a loaded ticket (PROJ-6). --}}
<x-card>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="font-mono text-sm text-slate-600">Ticket#{{ $ticket->number }}</p>
            <h2 class="mt-1 text-base font-semibold text-slate-900">{{ $ticket->title }}</h2>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($ticket->closed)
                <x-badge>Geschlossen</x-badge>
            @else
                <x-badge tone="info">{{ ucfirst($ticket->state) }}</x-badge>
            @endif
        </div>
    </div>

    @if ($ticket->mergedIntoNumber)
        <x-alert type="warning" class="mt-4">
            Dieses Ticket wurde in Zammad mit
            <a href="{{ route('tickets.show', ['number' => $ticket->mergedIntoNumber]) }}" class="font-semibold underline">Ticket#{{ $ticket->mergedIntoNumber }}</a>
            zusammengeführt. Der aktuelle Verlauf steht dort.
        </x-alert>
    @elseif ($ticket->rewoundTo !== null)
        <x-alert type="warning" class="mt-4">
            <p><span class="font-semibold">Testlauf:</span> Stand bis zur Kundennachricht vom {{ $ticket->rewoundAt()->format('d.m.Y, H:i') }} Uhr. Status und spätere Antworten werden ignoriert.</p>
            <p class="mt-2"><a href="{{ route('tickets.show', ['number' => $ticket->number]) }}" class="font-semibold underline">Ganzen Verlauf zeigen</a></p>
        </x-alert>
    @elseif ($ticket->closed)
        <x-alert class="mt-4">Dieses Ticket ist in Zammad geschlossen. Du kannst es trotzdem ansehen.</x-alert>
    @endif

    <dl class="mt-6 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2 md:grid-cols-3">
        <div>
            <dt class="text-slate-600">Kunde</dt>
            <dd class="mt-1 text-slate-900">
                {{ $ticket->customerName ?? '–' }}
                @if ($ticket->customerEmail)
                    <span class="block font-mono text-xs text-slate-600">{{ $ticket->customerEmail }}</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-slate-600">Gruppe</dt>
            <dd class="mt-1 text-slate-900">{{ $ticket->group ?? '–' }}</dd>
        </div>
        <div>
            <dt class="text-slate-600">Nachrichten</dt>
            <dd class="mt-1 text-slate-900">
                {{ count($ticket->articles) }}
                @if ($ticket->attachmentCount() > 0)
                    · <a href="{{ $ticket->zammadUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-brand hover:text-brand-hover">{{ $ticket->attachmentCount() }} {{ $ticket->attachmentCount() === 1 ? 'Anhang' : 'Anhänge' }} – in Zammad ansehen</a>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-slate-600">Erstellt</dt>
            <dd class="mt-1 text-slate-900">{{ $ticket->createdAt->format('d.m.Y, H:i') }} Uhr</dd>
        </div>
        <div>
            <dt class="text-slate-600">Letzte Nachricht</dt>
            <dd class="mt-1 text-slate-900">{{ $ticket->lastArticleAt()?->format('d.m.Y, H:i').' Uhr' ?? '–' }}</dd>
        </div>
    </dl>

    {{ $orders ?? '' }}

    <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-4">
        <x-button :href="$ticket->zammadUrl" variant="secondary" target="_blank" rel="noopener noreferrer">
            In Zammad öffnen <x-icon name="chevron-right" size="16" />
        </x-button>
        <x-button :href="route('tickets.show', array_filter(['number' => $ticket->number, 'bestellungen' => request()->query('bestellungen')]))" variant="secondary" x-data x-on:click="$dispatch('loading-start', { title: 'Ticket wird aktualisiert …' })">
            <x-icon name="refresh" size="16" /> Aktualisieren
        </x-button>
    </div>
</x-card>
