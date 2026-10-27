@props(['order', 'ticket' => null, 'ticketNumber' => null, 'mode' => 'page', 'field' => false, 'seenArticle' => 0, 'label' => null, 'signature' => '', 'hasToken' => false, 'configured' => true, 'returnGroup' => '', 'receiver' => null])

@php
    $address = $order->shippingAddress;
    $isAmazon = $order->channel === 'amazon';
    $config = [
        'storeUrl' => route('dhl-return.store'),
        'sendUrl' => route('dhl-return.send', ['label' => '__LABEL__']),
        'ticketNumber' => $ticketNumber ?? $ticket?->number,
        'attach' => $mode === 'attach',
        'orderNumber' => $order->externalNumber,
        'eocsOrderId' => $order->id,
        'seenArticle' => $seenArticle,
        'signature' => $signature,
        'label' => $label?->payload(true),
        // On the page of a ticket the address is not in the HTML: it is fetched when the option is opened.
        'addressUrl' => $mode === 'attach' ? route('dhl-return.address', ['bestellung' => $order->externalNumber]) : null,
        'addressMissing' => $mode === 'page' && $address === null,
        'address' => $mode === 'attach' ? ['name' => '', 'street' => '', 'house_number' => '', 'postal_code' => '', 'city' => '', 'country' => 'DE'] : [
            'name' => $address?->name() ?? '',
            'street' => $address?->street ?? '',
            'house_number' => $address?->streetNumber ?? '',
            'postal_code' => $address?->postalCode ?? '',
            'city' => $address?->city ?? '',
            'country' => $address?->countryCode !== null && $address->countryCode !== '' ? $address->countryCode : 'DE',
        ],
    ];
    $inputClass = 'mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand';
@endphp

<x-card aria-label="Retourenlabel zur Bestellung {{ $order->externalNumber }}">
<div x-data="returnLabel(@js($config))" x-on:dhl-load-address.window="loadAddress()">
    @if ($field)
        {{-- Sent with the form of the proposal (PROJ-64): only a label that exists is part of the request. --}}
        <input type="hidden" x-bind:name="label && label.status === 'created' ? 'return_label' : null" x-bind:value="label ? label.id : ''">
    @endif
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <h2 class="text-base font-semibold text-slate-900">Bestellung <span class="font-mono">{{ $order->externalNumber }}</span></h2>
        @if ($order->channelName)<x-badge>{{ $order->channelName }}</x-badge>@endif
        @if ($order->statusName)<x-badge :tone="\App\Eocs\EocsOrder::tone($order->statusColor)">{{ $order->statusName }}</x-badge>@endif
        <a href="{{ $order->url }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand underline">in EOCS öffnen</a>
    </div>

    {{-- 1. Check the address and order the label --}}
    <section x-show="! label || label.status === 'failed'" class="mt-4 space-y-3" aria-label="Adresse prüfen">
        <div x-show="addressMissing" @if (! ($mode === 'page' && $address === null)) x-cloak @endif>
            <x-alert type="warning">
            @if ($isAmazon)
                Bei Amazon-Bestellungen liegt in EOCS keine Kundenadresse vor. Bitte die Adresse aus dem Ticket von Hand eintragen.
            @else
                In EOCS ist für diese Bestellung keine Lieferadresse hinterlegt. Bitte die Adresse von Hand eintragen.
            @endif
        </x-alert>
        </div>
        <p x-show="addressLoading" x-cloak class="text-sm text-slate-600" role="status">Adresse wird aus EOCS geladen …</p>

        <p class="text-sm font-medium text-slate-900">Absender der Rücksendung (Kunde)</p>
        <div class="grid gap-3 sm:grid-cols-6">
            <div class="sm:col-span-6">
                <label for="n-{{ $order->id }}" class="block text-sm font-medium text-slate-900">Name</label>
                <input id="n-{{ $order->id }}" type="text" x-model="form.name" maxlength="80" autocomplete="off" class="{{ $inputClass }}">
            </div>
            <div class="sm:col-span-4">
                <label for="s-{{ $order->id }}" class="block text-sm font-medium text-slate-900">Straße</label>
                <input id="s-{{ $order->id }}" type="text" x-model="form.street" maxlength="80" autocomplete="off" class="{{ $inputClass }}">
            </div>
            <div class="sm:col-span-2">
                <label for="h-{{ $order->id }}" class="block text-sm font-medium text-slate-900">Hausnummer</label>
                <input id="h-{{ $order->id }}" type="text" x-model="form.house_number" maxlength="20" autocomplete="off" class="{{ $inputClass }}">
            </div>
            <div class="sm:col-span-2">
                <label for="z-{{ $order->id }}" class="block text-sm font-medium text-slate-900">PLZ</label>
                <input id="z-{{ $order->id }}" type="text" x-model="form.postal_code" maxlength="10" inputmode="numeric" autocomplete="off" class="{{ $inputClass }}">
            </div>
            <div class="sm:col-span-3">
                <label for="c-{{ $order->id }}" class="block text-sm font-medium text-slate-900">Ort</label>
                <input id="c-{{ $order->id }}" type="text" x-model="form.city" maxlength="60" autocomplete="off" class="{{ $inputClass }}">
            </div>
            <div class="sm:col-span-1">
                <label for="l-{{ $order->id }}" class="block text-sm font-medium text-slate-900">Land</label>
                <input id="l-{{ $order->id }}" type="text" x-model="form.country" maxlength="2" readonly class="{{ $inputClass }} bg-slate-50">
            </div>
        </div>
        <p x-show="form.country !== 'DE'" x-cloak role="alert" class="text-sm font-medium text-danger-700">Retourenlabel gibt es nur für Rücksendungen aus Deutschland.</p>

        @if ($ticket === null && $ticketNumber === null)
            <div class="sm:max-w-xs">
                <label for="t-{{ $order->id }}" class="block text-sm font-medium text-slate-900">Ticketnummer <span class="font-normal text-slate-600">(optional, kommt auf das Label)</span></label>
                <input id="t-{{ $order->id }}" type="text" x-model="ticketNumber" maxlength="20" inputmode="numeric" autocomplete="off" class="{{ $inputClass }} font-mono">
            </div>
        @endif

        @if ($receiver)
            <p class="text-sm text-slate-700"><span class="font-medium">Rücksendung an:</span> {{ $receiver }}</p>
        @endif

        <label class="flex items-start gap-2 text-sm text-slate-900">
            <input type="checkbox" x-model="confirmed" class="mt-0.5 rounded text-brand focus:ring-brand">
            <span>Adresse geprüft: Das Label wird mit diesen Angaben bei DHL bestellt.</span>
        </label>
        <p x-show="error !== ''" x-cloak role="alert" class="text-sm font-medium text-danger-700"><span x-text="error"></span> <span x-show="detail !== ''" x-text="'(DHL: ' + detail + ')'"></span></p>
        <x-button x-on:click="create(false)" x-bind:disabled="busy || ! confirmed || form.country !== 'DE'" :disabled="! $configured"><x-icon name="doc" size="16" /> <span x-text="busy ? 'Label wird erzeugt …' : 'Label erzeugen'">Label erzeugen</span></x-button>
    </section>

    {{-- 2. Unclear outcome --}}
    <section x-show="unclear" x-cloak class="mt-4 space-y-2" aria-label="Unklarer Ausgang">
        <x-alert type="warning"><span x-text="label && label.message ? label.message : 'Der Ausgang ist unklar.'"></span> Sendungsnummer: <span class="font-mono" x-text="label ? (label.shipment_no || 'unbekannt') : ''"></span></x-alert>
        <x-button variant="secondary" x-on:click="newLabel()">Zurück zur Adresse</x-button>
    </section>

    {{-- 3. The label exists: download and send --}}
    <section x-show="ready" x-cloak class="mt-4 space-y-4" aria-label="Label und Versand">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <x-badge tone="success">Label erzeugt</x-badge>
            <span class="text-sm text-slate-900">Sendungsnummer <span class="font-mono" x-text="label ? label.shipment_no : ''"></span></span>
            <span x-show="label && label.reused" class="text-sm text-slate-600">(bereits vorhanden, es wurde nichts neu bestellt)</span>
            <a x-bind:href="label ? label.download_url : '#'" class="inline-flex items-center gap-1 text-sm font-semibold text-brand underline"><x-icon name="upload" size="16" /> Herunterladen</a>
            <button type="button" x-on:click="if (confirm('Wirklich ein weiteres Label bestellen? Jedes eingelieferte Label wird berechnet.')) { create(true) }" x-show="! send.done" class="text-sm font-medium text-slate-600 underline hover:text-slate-900">Weiteres Label erstellen</button>
        </div>

        @if ($mode === 'attach')
            <p class="text-sm text-slate-700">Das Label hängt an der Antwort, die Schritte für den Kunden stehen unter dem Entwurf.</p>
        @else
        <div x-show="send.done" x-cloak role="status" class="text-sm text-success-700">
            <p class="font-semibold">Gesendet von <span x-text="send.done ? send.done.by : ''"></span> an Ticket <span x-text="send.done ? send.done.ticket_number : ''"></span><span x-show="send.done && send.done.new_ticket"> (neues Ticket)</span>.</p>
            <p class="mt-1" x-show="send.doneUrl"><a x-bind:href="send.doneUrl" target="_blank" rel="noopener" class="font-medium underline">Ticket in Zammad öffnen</a></p>
        </div>

        <div x-show="! send.done" class="space-y-3 rounded-lg border border-slate-200 bg-slate-50/60 p-4">
            <h3 class="text-sm font-semibold text-slate-900">An den Kunden senden</h3>
            @unless ($hasToken)
                <p class="rounded-md bg-slate-50 p-3 text-sm text-slate-700 ring-1 ring-slate-200 ring-inset">Für dich ist kein Zammad-Token hinterlegt. Du kannst das Label herunterladen; der Direktversand ist erst mit Token möglich.</p>
            @else
                <fieldset>
                    <legend class="text-sm font-medium text-slate-900">Wohin soll das Label?</legend>
                    <div class="mt-1 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-900">
                        <label class="inline-flex items-center gap-2"><input type="radio" value="reply" x-model="send.mode" class="text-brand focus:ring-brand"> Antwort auf ein Ticket</label>
                        <label class="inline-flex items-center gap-2"><input type="radio" value="new" x-model="send.mode" class="text-brand focus:ring-brand"> Neues Ticket erstellen</label>
                    </div>
                </fieldset>
                <div x-show="send.mode === 'reply'" class="sm:max-w-xs">
                    <label for="rt-{{ $order->id }}" class="block text-sm font-medium text-slate-900">Ticketnummer</label>
                    <input id="rt-{{ $order->id }}" type="text" x-model="send.ticket" maxlength="20" inputmode="numeric" autocomplete="off" class="{{ $inputClass }} font-mono">
                    <p class="mt-1 text-xs text-slate-600">Die Antwort geht an die Absenderadresse der letzten Kundennachricht.</p>
                </div>
                <div x-show="send.mode === 'new'" x-cloak class="space-y-1 sm:max-w-md">
                    <label for="em-{{ $order->id }}" class="block text-sm font-medium text-slate-900">E-Mail-Adresse des Kunden</label>
                    <input id="em-{{ $order->id }}" type="email" x-model="send.email" maxlength="200" autocomplete="off" class="{{ $inputClass }}">
                    <p class="text-xs text-slate-600">Betreff: <span class="font-medium">{{ \App\Returns\ReturnInstructions::subject($order->externalNumber) }}</span> · Gruppe in Zammad: {{ $returnGroup }}</p>
                </div>
                <div>
                    <label for="tx-{{ $order->id }}" class="block text-sm font-medium text-slate-900">Text der Nachricht <span class="font-normal text-slate-600">(änderbar; das Label hängt als PDF an)</span></label>
                    <textarea id="tx-{{ $order->id }}" x-model="send.text" rows="14" class="{{ $inputClass }} field-sizing-content"></textarea>
                </div>
                <div>
                    <label for="sg-{{ $order->id }}" class="block text-sm font-medium text-slate-900">Signatur</label>
                    <textarea id="sg-{{ $order->id }}" x-model="send.signature" rows="3" maxlength="600" class="{{ $inputClass }} field-sizing-content"></textarea>
                </div>
                <fieldset>
                    <legend class="text-sm font-medium text-slate-900">Danach</legend>
                    <div class="mt-1 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-900">
                        <label class="inline-flex items-center gap-2"><input type="radio" value="close" x-model="send.after" class="text-brand focus:ring-brand"> Ticket schließen</label>
                        <label class="inline-flex items-center gap-2"><input type="radio" value="reminder" x-model="send.after" class="text-brand focus:ring-brand"> Erinnerung in
                            <select x-model.number="send.days" x-bind:disabled="send.after !== 'reminder'" aria-label="Tage bis zur Erinnerung" class="rounded-md border-0 py-1 pl-2 pr-7 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand"><option value="1">1</option><option value="3">3</option><option value="7">7</option></select> Tagen</label>
                    </div>
                </fieldset>
                <div x-show="send.newer !== ''" x-cloak role="alert" class="rounded-md bg-warning-500/10 p-3 text-sm text-slate-900 ring-1 ring-warning-500/30 ring-inset">
                    <p class="font-semibold text-warning-700" x-text="send.newer"></p>
                    <x-button variant="secondary" class="mt-2" x-on:click="submit(true)" x-bind:disabled="send.busy">Trotzdem senden</x-button>
                </div>
                <p x-show="send.error !== ''" x-cloak role="alert" class="text-sm font-medium text-danger-700" x-text="send.error"></p>
                <x-button x-on:click="submit(false)" x-bind:disabled="send.busy || send.text.trim() === '' || (send.mode === 'reply' && send.ticket.trim() === '') || (send.mode === 'new' && send.email.trim() === '')"><x-icon name="chat" size="16" /> <span x-text="send.busy ? 'Wird gesendet …' : 'Senden'">Senden</span></x-button>
                <p class="text-xs text-slate-600">Die Nachricht geht über Zammad im Namen des gewählten Mitarbeiters raus, erst nach diesem Klick.</p>
            @endunless
        </div>
        @endif
    </section>
</div>
</x-card>
