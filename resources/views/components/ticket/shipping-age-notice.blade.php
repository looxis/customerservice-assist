@props(['order', 'showNumber' => true])

@php
    $now = \Carbon\CarbonImmutable::now();
    $level = $order->shippingAgeLevel($now);
    $days = $order->daysSinceFirstShipment($now);
    $shippedAt = collect($order->shipments)->pluck('shippedAt')->filter()->sort()->first();
@endphp

{{-- How long ago the first shipment left (PROJ-79): visible damage is an open defect and must be reported immediately. No AI call. --}}
@if ($level !== null)
    <x-alert :type="$level === 'strong' ? 'error' : 'warning'" {{ $attributes }}>
        <p class="font-semibold">
            @if ($showNumber)Bestellung <span class="font-mono">{{ $order->externalNumber }}</span>: @endif
            Der Versand liegt {{ $days }} Tage zurück ({{ $shippedAt?->setTimezone('Europe/Berlin')->format('d.m.Y') }}).
        </p>
        <p class="mt-1 font-normal">
            @if ($level === 'strong')
                Sichtbare Beschädigungen sind offene Mängel und wären sofort aufgefallen. Eine Meldung so lange nach dem Versand ist kaum noch als Mangel anzuerkennen; vor einer Zusage Datum der Entdeckung und Art des Schadens prüfen.
            @else
                Sichtbare Beschädigungen sind offene Mängel und sofort zu melden. Bei einer Beschädigungsmeldung auf das Datum der Entdeckung achten.
            @endif
        </p>
    </x-alert>
@endif
