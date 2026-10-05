@props(['order', 'mention' => null, 'removeUrl'])

{{-- One order loaded from EOCS (PROJ-7); products from an Amazon notice (PROJ-6) are merged in. --}}
<article class="rounded-lg bg-slate-50 p-4 ring-1 ring-slate-200" aria-label="Bestellung {{ $order->externalNumber }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="font-mono text-sm font-semibold text-slate-900">{{ $order->externalNumber }}</p>
            <p class="mt-0.5 text-xs text-slate-600">{{ $order->channelName ?? 'Kanal unbekannt' }} · EOCS-ID {{ $order->id }}</p>
        </div>
        @if ($order->statusName)
            <x-badge :tone="\App\Eocs\EocsOrder::tone($order->statusColor)">{{ $order->statusName }}</x-badge>
        @endif
    </div>

    <dl class="mt-3 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-3">
        <div>
            <dt class="text-slate-600">Bestellt</dt>
            <dd class="text-slate-900">{{ $order->orderedAt?->format('d.m.Y') ?? '–' }}</dd>
        </div>
        <div>
            <dt class="text-slate-600">Rechnungsnummer</dt>
            <dd class="font-mono text-slate-900">{{ $order->invoiceNumber ?? '–' }}</dd>
        </div>
        <div>
            <dt class="text-slate-600">Versand</dt>
            <dd class="text-slate-900">
                @forelse ($order->shipments as $shipment)
                    <span class="block">
                        {{ $shipment->carrier ?? 'Versand' }}
                        @if ($shipment->trackingNumber) · <span class="font-mono">{{ $shipment->trackingNumber }}</span> @endif
                        @if ($shipment->shippedAt) · {{ $shipment->shippedAt->format('d.m.Y') }} @endif
                        @if ($shipment->delivered) · zugestellt @endif
                    </span>
                @empty
                    noch nicht versandt
                @endforelse
            </dd>
        </div>
    </dl>

    @if ($mention && $mention->items !== [])
        <ul class="mt-3 space-y-1 border-t border-slate-200 pt-3 text-sm" aria-label="Produkte laut Amazon-Nachricht">
            @foreach ($mention->items as $item)
                <li class="text-slate-900">{{ $item->name }} @if ($item->asin)<span class="font-mono text-xs text-slate-600">· ASIN {{ $item->asin }}</span>@endif</li>
            @endforeach
        </ul>
    @endif

    @if ($order->claims !== [])
        <div class="mt-3 border-t border-slate-200 pt-3">
            <p class="text-sm font-semibold text-slate-900">Reklamationsaufträge</p>
            <ul class="mt-1 space-y-1 text-sm">
                @foreach ($order->claims as $claim)
                    <li class="flex flex-wrap items-center gap-x-2">
                        <span class="font-mono text-slate-900">{{ $claim->externalNumber }}</span>
                        @if ($claim->orderedAt)<span class="text-slate-600">vom {{ $claim->orderedAt->format('d.m.Y') }}</span>@endif
                        @if ($claim->statusName)<x-badge compact :tone="\App\Eocs\EocsOrder::tone($claim->statusColor)">{{ $claim->statusName }}</x-badge>@endif
                        <a href="{{ $claim->url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-brand hover:text-brand-hover">In EOCS öffnen</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-3 flex flex-wrap gap-3 border-t border-slate-200 pt-3 text-sm">
        <a href="{{ $order->url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand hover:text-brand-hover">In EOCS öffnen</a>
        <a href="{{ $removeUrl }}" class="font-semibold text-slate-600 hover:text-slate-900">Entfernen</a>
    </div>
</article>
