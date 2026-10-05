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

    @foreach ($ticket->orders as $order)
        <section class="mt-6 border-t border-slate-100 pt-4" aria-label="Bestellung {{ $order->number }}">
            <h3 class="text-sm font-semibold text-slate-900">Bestellung <span class="font-normal text-slate-600">· aus {{ $order->source }}</span></h3>
            <dl class="mt-3 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-600">Bestellnummer</dt>
                    <dd class="mt-1 font-mono text-slate-900">{{ $order->number }}</dd>
                </div>
                <div>
                    <dt class="text-slate-600">Rechnungsnummer</dt>
                    <dd class="mt-1 font-mono text-slate-900">{{ $order->invoiceNumber ?? '–' }}</dd>
                </div>
            </dl>
            @if ($order->items !== [])
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-600">
                                <th scope="col" class="py-2 pr-4 font-medium">Produkt</th>
                                <th scope="col" class="py-2 pr-4 font-medium">ASIN</th>
                                <th scope="col" class="py-2 pr-4 font-medium">SKU</th>
                                <th scope="col" class="py-2 font-medium">Anzahl</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr class="border-b border-slate-100 align-top">
                                    <td class="py-2 pr-4 text-slate-900">{{ $item->name }}</td>
                                    <td class="py-2 pr-4 font-mono text-slate-900">{{ $item->asin ?? '–' }}</td>
                                    <td class="py-2 pr-4 font-mono text-slate-900">{{ $item->sku ?? '–' }}</td>
                                    <td class="py-2 text-slate-900">{{ $item->quantity ?? '–' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endforeach

    <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-4">
        <x-button :href="$ticket->zammadUrl" variant="secondary" target="_blank" rel="noopener noreferrer">
            In Zammad öffnen <x-icon name="chevron-right" size="16" />
        </x-button>
        <x-button :href="route('tickets.show', ['number' => $ticket->number])" variant="secondary" x-data x-on:click="$dispatch('loading-start', { title: 'Ticket wird aktualisiert …' })">
            <x-icon name="refresh" size="16" /> Aktualisieren
        </x-button>
    </div>
</x-card>
