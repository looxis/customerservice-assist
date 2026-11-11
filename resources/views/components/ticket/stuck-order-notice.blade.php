@props(['order', 'showNumber' => true])

@php
    $days = $order->stuckWorkdays(\Carbon\CarbonImmutable::now());
@endphp

{{-- An order without shipment that is older than three working days may be stuck (PROJ-82). No external call. --}}
@if ($days !== null)
    <x-alert type="warning" {{ $attributes }}>
        <p class="font-semibold">
            @if ($showNumber)Bestellung <span class="font-mono">{{ $order->externalNumber }}</span>: @endif
            Seit {{ $days }} Werktagen ohne Versand ({{ $order->orderedAt?->setTimezone('Europe/Berlin')->format('d.m.Y') }} bestellt), möglicherweise hängen geblieben.
        </p>
        <p class="mt-1 font-normal">Bitte in EOCS prüfen, warum die Bestellung nicht versendet wurde. Dem Kunden wurde kein Liefertermin genannt.</p>
    </x-alert>
@endif
