@props(['inputs' => [], 'orders' => collect()])

@php
    use App\Analysis\WismoUpdateDecision;

    $oldInput = session()->hasOldInput();
    $form = WismoUpdateDecision::FORM;
    $extra = $oldInput ? old('wismo_zusatz', '') : ($inputs['wismo_zusatz'] ?? '');
    $tracked = $orders->map(fn ($order) => ['order' => $order->externalNumber, 'shipments' => collect($order->shipments)->filter(fn ($shipment) => trim((string) $shipment->trackingNumber) !== '')->take(\App\Wismo\ShipmentSnapshot::MAX_SHIPMENTS_PER_ORDER)->values()])
        ->filter(fn ($entry) => $entry['shipments']->isNotEmpty())->values();
@endphp

{{-- The guided form "Aktualisierte WISMO-Infos" (PROJ-101). The app asks the carrier and Lettermint when the proposal
     is made; the draft only reports the whereabouts of the parcel. A disabled fieldset is not sent. --}}
<fieldset x-show="mode === '{{ $form }}'" x-bind:disabled="mode !== '{{ $form }}'" x-cloak class="min-w-0 space-y-5 border-0 p-0">
    <legend class="sr-only">Aktualisierte WISMO-Infos</legend>

    <div>
        <p class="text-sm font-semibold text-slate-900">Sendungen</p>
        @if ($tracked->isEmpty())
            <p class="mt-1 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">Keine Sendung mit Sendungsnummer – bitte die Bestellung laden.</p>
        @else
            <ul class="mt-1 space-y-1 text-sm text-slate-700">
                @foreach ($tracked as $entry)
                    @foreach ($entry['shipments'] as $shipment)
                        <li>Bestellung {{ $entry['order'] }}: {{ trim((string) $shipment->carrier) !== '' ? $shipment->carrier : 'Versanddienstleister' }} {{ $shipment->trackingNumber }}</li>
                    @endforeach
                @endforeach
            </ul>
            <x-intro>
                <p class="mt-1 text-xs text-slate-600">Beim Erstellen fragt die App den aktuellen Verlauf beim Paketdienst und die Versandbenachrichtigungen bei Lettermint ab, wie die automatische WISMO-Antwort. Der Entwurf behandelt nur den Verbleib der Sendung.</p>
            </x-intro>
        @endif
    </div>

    <x-textarea name="wismo_zusatz" id="wismo_zusatz" label="Zusätzliche Informationen" rows="3" maxlength="1500" placeholder="z. B. Kunde fragt, ob er die Sendung an eine Packstation umleiten kann" :value="$extra" />
</fieldset>
