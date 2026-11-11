@props(['inputs' => [], 'complaint' => [], 'labelOrders', 'number'])

@php
    $oldInput = session()->hasOldInput();
    $pick = fn (string $key, mixed $default = null) => $oldInput ? old($key, $default) : ($inputs[$key] ?? $default);
    $yesNo = ['ja' => 'Ja', 'nein' => 'Nein'];
    $shipping = ['heute' => 'heute', 'morgen' => 'morgen', 'zeitnah' => 'zeitnah'];
    $todoOn = $oldInput ? (bool) old('sendungsnummer_todo') : (bool) ($inputs['sendungsnummer_todo'] ?? true);
@endphp

{{-- The guided form for a complaint (PROJ-86). The Alpine properties berechtigt and retoure live in the form. The
     branches are disabled fieldsets: what is not shown is not sent, but typed values stay when the choice changes. --}}
<fieldset x-show="mode === 'reklamation'" x-bind:disabled="mode !== 'reklamation'" x-cloak class="min-w-0 space-y-5 border-0 p-0">
    <legend class="sr-only">Reklamation</legend>

    <x-analysis.choice name="reklamation_berechtigt" legend="Wird die Reklamation nach Prüfung als berechtigt anerkannt?" :options="$yesNo" model="berechtigt" :value="$pick('reklamation_berechtigt')" :required="true" />

    {{-- Not justified: the reason and whether a goodwill offer is made. --}}
    <fieldset x-show="berechtigt === 'nein'" x-bind:disabled="berechtigt !== 'nein'" x-cloak class="min-w-0 space-y-4 rounded-lg border border-slate-200 bg-slate-50/60 p-4">
        <legend class="sr-only">Reklamation nicht berechtigt</legend>
        <x-textarea name="reklamation_grund" id="reklamation_grund" label="Grund" rows="3" maxlength="1000" :required="true" placeholder="Hier den Grund eingeben" :value="$pick('reklamation_grund', '')" />
        <x-analysis.choice name="kulanz" legend="Kulanzangebot unterbreiten?" :options="$yesNo" model="kulanz" :value="$pick('kulanz', 'nein')" />
    </fieldset>

    {{-- Justified: return, claim number, shipping and the follow-up ToDo. --}}
    <fieldset x-show="berechtigt === 'ja'" x-bind:disabled="berechtigt !== 'ja'" x-cloak class="min-w-0 space-y-5">
        <legend class="sr-only">Reklamation berechtigt</legend>

        <div>
            <x-analysis.choice name="retoure" legend="Soll das defekte Produkt retourniert werden?" :options="$yesNo" model="retoure" :value="$pick('retoure', 'nein')" />
            <x-intro>
                <p class="mt-1 text-sm text-slate-600">Höherwertige Produkte, die in Teilen noch (weiter-)verwendet werden können, sollten retourniert werden.</p>
            </x-intro>
            <p x-show="labelNote !== ''" x-cloak role="alert" class="mt-2 text-sm font-medium text-danger-700" x-text="labelNote"></p>
            <div x-show="berechtigt === 'ja' && retoure === 'ja'" x-cloak class="mt-3 space-y-4">
                @forelse ($labelOrders as $labelOrder)
                    <x-dhl.return-label :order="$labelOrder" :ticket-number="$number" mode="attach" :field="true" :configured="app(\App\Dhl\DhlClient::class)->isConfigured()" />
                @empty
                    <x-alert type="warning">Zu diesem Ticket ist keine Bestellung geladen, daher kann hier kein Retourenlabel erstellt werden. Das Label lässt sich auch später über „DHL Retoure“ erstellen.</x-alert>
                @endforelse
            </div>
        </div>

        <x-input name="reklamationsnummer" label="Reklamationsnummer" :value="$pick('reklamationsnummer', $complaint['claimNumber'] ?? null)" maxlength="60" autocomplete="off"
                 hint="Bitte Reklamation in EOCS erstellen und die Reklamationsnummer hier einfügen. Ohne Nummer steht im Entwurf ein Platzhalter." />

        <x-analysis.choice name="versand" legend="Versand voraussichtlich" :options="$shipping" model="versand" :value="$pick('versand', 'zeitnah')" />

        <label class="flex items-start gap-2 text-sm font-medium text-slate-900">
            <input type="checkbox" name="sendungsnummer_todo" value="1" @checked($todoOn) class="mt-0.5 size-4 rounded border-slate-300 text-brand focus:ring-brand">
            <span>ToDo „Sendungsnummer mitteilen“ anlegen
                <x-intro><span class="block text-xs font-normal text-slate-600">Die App reicht die Sendungsnummer nach dem Versand automatisch nach (nur bei deutscher Antwort und mit Reklamationsnummer).</span></x-intro>
            </span>
        </label>
    </fieldset>
</fieldset>
