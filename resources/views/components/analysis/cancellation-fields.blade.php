@props(['inputs' => [], 'orders' => collect()])

@php
    use App\Analysis\CancellationDecision;

    $oldInput = session()->hasOldInput();
    $pick = fn (string $key, mixed $default = null) => $oldInput ? old($key, $default) : ($inputs[$key] ?? $default);
    $form = CancellationDecision::FORM;
    $withKind = "['".CancellationDecision::DONE."', '".CancellationDecision::NOT_POSSIBLE."'].includes(stornoErgebnis)";
    $addressOrder = $orders->first()?->externalNumber;
    $tracked = $orders->flatMap(fn ($order) => collect($order->shipments)->filter(fn ($shipment) => trim((string) $shipment->trackingNumber) !== '')
        ->map(fn ($shipment) => ['order' => $order->externalNumber, 'shipment' => $shipment]))->values();
@endphp

{{-- The guided form "Storno / Änderung" (PROJ-102). The Alpine properties stornoErgebnis and stornoArt live in the
     form. Branches are disabled fieldsets: what is not shown is not sent. The new address reaches the model only as a
     placeholder; tracking numbers and links come from EOCS when the proposal is made. --}}
<fieldset x-show="mode === '{{ $form }}'" x-bind:disabled="mode !== '{{ $form }}'" x-cloak class="min-w-0 space-y-5 border-0 p-0"
          x-data="{
              addressBusy: false, addressNote: '',
              async loadAddress(force = false) {
                  const number = @js($addressOrder)
                  const field = this.$refs.stornoAdresse
                  if (! number || this.addressBusy || (! force && field.value.trim() !== '')) { return }
                  this.addressBusy = true
                  this.addressNote = ''
                  try {
                      const response = await fetch(@js(route('dhl-return.address')) + '?bestellung=' + encodeURIComponent(number), { headers: { Accept: 'application/json' } })
                      const data = await response.json()
                      if (! response.ok) { this.addressNote = data.message ?? 'Die Adresse konnte nicht geladen werden.'; return }
                      if (! data.found) { this.addressNote = 'Zu dieser Bestellung hat EOCS keine Lieferadresse.'; return }
                      const a = data.address
                      field.value = [a.name, (a.street + ' ' + a.house_number).trim(), (a.postal_code + ' ' + a.city).trim(), a.country === 'DE' ? '' : a.country].filter((line) => line !== '').join('\n')
                      this.addressNote = 'Adresse aus EOCS übernommen. Bitte prüfen.'
                  } catch (error) {
                      this.addressNote = 'Die Adresse konnte nicht geladen werden.'
                  } finally {
                      this.addressBusy = false
                  }
              },
          }"
          x-init="$watch('stornoErgebnis', (value) => { if (value === '{{ CancellationDecision::ADDRESS_CHANGED }}') { loadAddress() } })">
    <legend class="sr-only">Storno / Änderung</legend>

    <x-analysis.choice name="storno_ergebnis" legend="Ergebnis" :required="true" model="stornoErgebnis" :value="$pick('storno_ergebnis')" :options="[
        CancellationDecision::DONE => 'Storno / Änderung erledigt (Ausnahme)',
        CancellationDecision::NOT_POSSIBLE => 'Storno / Änderung nicht möglich, Bestellung bereits in Bearbeitung',
        CancellationDecision::ADDRESS_CHANGED => 'Adressänderung vorgenommen',
        CancellationDecision::ADDRESS_SHIPPED => 'Adressänderung nicht mehr möglich, bereits verschickt',
    ]" />

    <x-intro>
        <p class="text-sm text-slate-700">Ein Storno oder eine Änderung ist nach der Freigabe in der Regel nicht möglich. „Erledigt“ nur wählen, wenn es hier ausnahmsweise geklappt hat, z. B. weil die Bestellung noch nicht bezahlt und freigegeben war oder die Bearbeitung noch gestoppt werden konnte.</p>
    </x-intro>

    <fieldset x-show="{{ $withKind }}" x-bind:disabled="! ({{ $withKind }})" x-cloak class="min-w-0 space-y-5 border-0 p-0">
        <x-analysis.choice name="storno_art" legend="Storno oder Änderung?" :required="true" model="stornoArt" :value="$pick('storno_art')"
                           :options="[CancellationDecision::CANCELLATION => 'Storno', CancellationDecision::CHANGE => 'Änderung (Motiv, Artikel, Menge)']" />

        <fieldset x-show="stornoErgebnis === '{{ CancellationDecision::DONE }}' && stornoArt === '{{ CancellationDecision::CHANGE }}'" x-bind:disabled="! (stornoErgebnis === '{{ CancellationDecision::DONE }}' && stornoArt === '{{ CancellationDecision::CHANGE }}')" x-cloak class="min-w-0 border-0 p-0">
            <x-input name="storno_aenderung" label="Was wurde geändert?" :value="$pick('storno_aenderung')" maxlength="300" autocomplete="off" hint="z. B. „Motiv gegen das zweite Foto getauscht“ oder „Menge auf 2 Stück erhöht“." />
        </fieldset>
    </fieldset>

    <fieldset x-show="stornoErgebnis === '{{ CancellationDecision::ADDRESS_CHANGED }}'" x-bind:disabled="stornoErgebnis !== '{{ CancellationDecision::ADDRESS_CHANGED }}'" x-cloak class="min-w-0 border-0 p-0">
        <x-textarea name="storno_adresse" id="storno_adresse" label="Neue Adresse" rows="4" maxlength="500" x-ref="stornoAdresse" placeholder="Name&#10;Straße Hausnummer&#10;PLZ Ort" :value="$pick('storno_adresse', '')"
                    hint="Wird aus EOCS übernommen. Die Adresse geht nicht an die KI, die App setzt sie zur Bestätigung in den Entwurf ein." />
        <p class="mt-1 flex flex-wrap items-center gap-3 text-sm">
            @if ($addressOrder !== null)
                <button type="button" class="font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand disabled:opacity-50" x-on:click="loadAddress(true)" x-bind:disabled="addressBusy">Adresse aus EOCS laden</button>
            @else
                <span class="text-slate-600">Keine Bestellung geladen. Bitte die Adresse selbst eintragen.</span>
            @endif
            <span class="text-slate-600" x-show="addressBusy" x-cloak>Wird geladen …</span>
            <span class="text-slate-600" aria-live="polite" x-show="addressNote !== ''" x-cloak x-text="addressNote"></span>
        </p>
    </fieldset>

    <div x-show="stornoErgebnis === '{{ CancellationDecision::ADDRESS_SHIPPED }}'" x-cloak>
        <p class="text-sm font-semibold text-slate-900">Sendungen</p>
        @if ($tracked->isEmpty())
            <p class="mt-1 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">Keine Sendung mit Sendungsnummer geladen. Im Entwurf steht dann ein Platzhalter für die Sendungsnummer.</p>
        @else
            <ul class="mt-1 space-y-1 text-sm text-slate-700">
                @foreach ($tracked as $entry)
                    <li>Bestellung {{ $entry['order'] }}: {{ trim((string) $entry['shipment']->carrier) !== '' ? $entry['shipment']->carrier : 'Versanddienstleister' }} {{ $entry['shipment']->trackingNumber }}</li>
                @endforeach
            </ul>
            <x-intro>
                <p class="mt-1 text-xs text-slate-600">Der Entwurf nennt Sendungsnummer und Link zur Sendungsverfolgung und weist darauf hin, dass der Kunde beim Paketdienst ggf. noch Zustelloptionen anpassen kann.</p>
            </x-intro>
        @endif
    </div>

    <x-textarea name="storno_zusatz" id="storno_zusatz" label="Zusätzliche Informationen" rows="3" maxlength="1500" placeholder="z. B. Grund, warum das Storno ausnahmsweise möglich war, oder Erstattung bereits veranlasst" :value="$pick('storno_zusatz', '')" />
</fieldset>
