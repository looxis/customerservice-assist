@props(['inputs' => []])

@php
    $oldInput = session()->hasOldInput();
    $pick = fn (string $key, mixed $default = null) => $oldInput ? old($key, $default) : ($inputs[$key] ?? $default);
    $todoOn = $oldInput ? (bool) old('neuversand_todo') : (bool) ($inputs['neuversand_todo'] ?? true);
@endphp

{{-- The guided form "Neuversand" (PROJ-93). The Alpine properties kostenpflichtig and bezahlt live in the form. The
     branches are disabled fieldsets: what is not shown is not sent, but typed values stay when the choice changes.
     The address can be loaded from EOCS by the order number of the new shipment; it reaches the model only as a placeholder. --}}
<fieldset x-show="mode === 'neuversand'" x-bind:disabled="mode !== 'neuversand'" x-cloak class="min-w-0 space-y-5 border-0 p-0"
          x-data="{
              addressBusy: false, addressNote: '',
              async loadAddress(force = false) {
                  const number = this.$refs.auftrag.value.trim()
                  const field = this.$refs.adresse
                  if (number === '' || this.addressBusy || (! force && field.value.trim() !== '')) { return }
                  this.addressBusy = true
                  this.addressNote = ''
                  try {
                      const response = await fetch(@js(route('dhl-return.address')) + '?bestellung=' + encodeURIComponent(number), { headers: { Accept: 'application/json' } })
                      const data = await response.json()
                      if (! response.ok) { this.addressNote = data.message ?? 'Die Adresse konnte nicht geladen werden.'; return }
                      if (! data.found) { this.addressNote = 'Zu dieser Auftragsnummer hat EOCS keine Lieferadresse.'; return }
                      const a = data.address
                      field.value = [a.name, (a.street + ' ' + a.house_number).trim(), (a.postal_code + ' ' + a.city).trim(), a.country === 'DE' ? '' : a.country].filter((line) => line !== '').join('\n')
                      this.addressNote = 'Adresse aus EOCS übernommen. Bitte prüfen.'
                  } catch (error) {
                      this.addressNote = 'Die Adresse konnte nicht geladen werden.'
                  } finally {
                      this.addressBusy = false
                  }
              },
          }">
    <legend class="sr-only">Neuversand</legend>

    <x-analysis.choice name="neuversand_kostenpflichtig" legend="Ist der Neuversand kostenpflichtig?" :options="['ja' => 'Ja', 'nein' => 'Nein']" model="kostenpflichtig" :value="$pick('neuversand_kostenpflichtig')" :required="true" />

    <fieldset x-show="kostenpflichtig === 'ja'" x-bind:disabled="kostenpflichtig !== 'ja'" x-cloak class="min-w-0 border-0 p-0">
        <x-analysis.choice name="neuversand_bezahlt" legend="Bereits bezahlt?" :options="['ja' => 'Ja', 'rechnung' => 'Nein, Rechnung wird dem Paket beigelegt']" model="bezahlt" :value="$pick('neuversand_bezahlt')" :required="true" />
    </fieldset>

    <x-input name="neuversand_auftrag" label="Auftragsnummer des Neuversands" :value="$pick('neuversand_auftrag')" maxlength="60" autocomplete="off" x-ref="auftrag" x-on:change="loadAddress()"
             hint="Meist die Reklamationsnummer (z. B. R1-…), sonst die Bestellnummer aus EOCS. Ohne Nummer steht im Entwurf ein Platzhalter." />

    <div>
        <x-textarea name="neuversand_adresse" id="neuversand_adresse" label="Aktualisierte Adresse" rows="4" maxlength="500" x-ref="adresse" placeholder="Name&#10;Straße Hausnummer&#10;PLZ Ort" :value="$pick('neuversand_adresse', '')"
                    hint="Wird aus EOCS übernommen, sobald die Auftragsnummer eingetragen ist. Die Adresse geht nicht an die KI, die App setzt sie in den Entwurf ein." />
        <p class="mt-1 flex flex-wrap items-center gap-3 text-sm">
            <button type="button" class="font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand disabled:opacity-50" x-on:click="loadAddress(true)" x-bind:disabled="addressBusy">Adresse aus EOCS laden</button>
            <span class="text-slate-600" x-show="addressBusy" x-cloak>Wird geladen …</span>
            <span class="text-slate-600" aria-live="polite" x-show="addressNote !== ''" x-cloak x-text="addressNote"></span>
        </p>
    </div>

    <x-textarea name="neuversand_zusatz" id="neuversand_zusatz" label="Zusätzliche Informationen" rows="3" maxlength="1500" placeholder="z. B. Antworten auf Fragen des Kunden oder ein Hinweis für die Antwort" :value="$pick('neuversand_zusatz', '')" />

    <label class="flex items-start gap-2 text-sm font-medium text-slate-900">
        <input type="hidden" name="neuversand_todo" value="0">
        <input type="checkbox" name="neuversand_todo" value="1" @checked($todoOn) class="mt-0.5 size-4 rounded border-slate-300 text-brand focus:ring-brand">
        <span>ToDo „Sendungsnummer mitteilen“ anlegen
            <x-intro><span class="block text-xs font-normal text-slate-600">Die App reicht die Sendungsnummer nach dem Versand automatisch nach (nur bei deutscher Antwort und mit Reklamations- oder Bestellnummer).</span></x-intro>
        </span>
    </label>
</fieldset>
