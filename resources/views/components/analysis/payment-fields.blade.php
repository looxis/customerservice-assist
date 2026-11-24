@props(['inputs' => [], 'orders' => collect()])

@php
    use App\Analysis\PaymentDecision;

    $oldInput = session()->hasOldInput();
    $pick = fn (string $key, mixed $default = null) => $oldInput ? old($key, $default) : ($inputs[$key] ?? $default);
    $form = PaymentDecision::FORM;
    $methods = [PaymentDecision::MOLLIE => 'Mollie', PaymentDecision::ON_ACCOUNT => 'Rechnung (Überweisung an LOOXIS)'];
    $states = [PaymentDecision::PAID => 'Bezahlt, Bestellung freigegeben', PaymentDecision::OPEN => 'Noch nicht registriert, Frist läuft', PaymentDecision::CANCELLED => 'Nicht bezahlt, Frist abgelaufen bzw. storniert'];
@endphp

{{-- The guided form "Zahlung / Rechnung" (PROJ-100). The Alpine properties anliegen, zahlungsart, zahlungsstand,
     rechnungAnhang and erstattung live in the form. Branches are disabled fieldsets: what is not shown is not sent.
     The payment state is read by hand on the invoice page of the order in EOCS. --}}
<fieldset x-show="mode === '{{ $form }}'" x-bind:disabled="mode !== '{{ $form }}'" x-cloak class="min-w-0 space-y-5 border-0 p-0">
    <legend class="sr-only">Zahlung / Rechnung</legend>

    <x-analysis.choice name="zahlung_anliegen" legend="Anliegen" :required="true" model="anliegen" :value="$pick('zahlung_anliegen')" :options="[
        PaymentDecision::PROOF => 'Zahlungsnachweis / Überweisungsbestätigung',
        PaymentDecision::QUESTION => 'Frage zur Zahlung',
        PaymentDecision::INVOICE => 'Rechnung anfordern oder korrigieren',
        PaymentDecision::REFUND => 'Stand einer Erstattung',
        PaymentDecision::SUPPLIER => 'Mahnung eines Lieferanten an LOOXIS',
    ]" />

    <x-intro>
        <p class="text-sm text-slate-700">Ein Stornowunsch gehört nicht hierher, sondern zu „Storno / Änderung der Bestellung“. Kundenzahlungen über Mollie erkennt Mollie automatisch und gibt die Bestellung dann selbst frei: Ein Zahlungsnachweis beschleunigt nichts, und wir fügen ihn nicht der Bestellung hinzu.</p>
    </x-intro>

    {{-- Proof of payment: method and state are required. Question: both optional. --}}
    <fieldset x-show="['{{ PaymentDecision::PROOF }}', '{{ PaymentDecision::QUESTION }}'].includes(anliegen)" x-bind:disabled="! ['{{ PaymentDecision::PROOF }}', '{{ PaymentDecision::QUESTION }}'].includes(anliegen)" x-cloak class="min-w-0 space-y-5 border-0 p-0">
        <div>
            <x-analysis.choice name="zahlung_art" legend="Zahlungsart der Bestellung" model="zahlungsart" :value="$pick('zahlung_art')" :options="$methods" />
            <x-intro>
                <p class="mt-1 text-sm text-slate-600">Im Beleg erkennbar am Empfänger: Mollie (z. B. „Stichting Mollie Payments“) oder ein Geschäftskonto von LOOXIS.</p>
            </x-intro>
        </div>

        <div>
            <x-analysis.choice name="zahlung_stand" legend="Zahlungsstand laut EOCS" model="zahlungsstand" :value="$pick('zahlung_stand')" :options="$states" />
            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                @forelse ($orders as $order)
                    <a href="{{ rtrim($order->url, '/') }}/invoice/" target="_blank" rel="noopener" class="font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand">Zahlungsstand in EOCS prüfen{{ $orders->count() > 1 ? ' ('.$order->externalNumber.')' : '' }}</a>
                @empty
                    <span class="text-slate-600">Keine Bestellung geladen. Bitte die Bestellnummer angeben, dann steht hier der Link zum Zahlungsstand in EOCS.</span>
                @endforelse
            </p>
            <p class="mt-1 text-xs text-slate-600" x-show="anliegen === '{{ PaymentDecision::PROOF }}'" x-cloak>Beim Zahlungsnachweis Pflicht. Der Stand steht unten auf der Rechnungsseite der Bestellung.</p>
        </div>
    </fieldset>

    <fieldset x-show="anliegen === '{{ PaymentDecision::INVOICE }}'" x-bind:disabled="anliegen !== '{{ PaymentDecision::INVOICE }}'" x-cloak class="min-w-0 border-0 p-0">
        <x-analysis.choice name="zahlung_rechnung_anhang" legend="Liegt die Rechnung der Antwort bei?" model="rechnungAnhang" :value="$pick('zahlung_rechnung_anhang')" :required="true"
                           :options="['ja' => 'Ja, als Anhang', 'nein' => 'Nein, Korrektur wird intern geklärt']" />
    </fieldset>

    <fieldset x-show="anliegen === '{{ PaymentDecision::REFUND }}'" x-bind:disabled="anliegen !== '{{ PaymentDecision::REFUND }}'" x-cloak class="min-w-0 border-0 p-0">
        <x-analysis.choice name="zahlung_erstattung" legend="Ist die Erstattung veranlasst?" model="erstattung" :value="$pick('zahlung_erstattung')" :required="true"
                           :options="['ja' => 'Ja', 'nein' => 'Nein, noch offen']" />
    </fieldset>

    <x-intro>
        <p class="text-xs text-slate-600" x-show="anliegen === '{{ PaymentDecision::SUPPLIER }}'" x-cloak>Der Entwurf bestätigt dem Lieferanten kurz den Eingang und die Weitergabe an die Buchhaltung. Bitte die Mahnung an die Buchhaltung weitergeben.</p>
    </x-intro>

    <x-textarea name="zahlung_zusatz" id="zahlung_zusatz" label="Zusätzliche Informationen" rows="3" maxlength="1500" placeholder="z. B. doppelt bezahlt, falsche Zahlungsreferenz verwendet, an das LOOXIS-Konto statt an Mollie überwiesen" :value="$pick('zahlung_zusatz', '')" />
</fieldset>
