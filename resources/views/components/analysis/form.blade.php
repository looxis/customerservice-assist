@props(['number', 'analysis', 'selected' => [], 'testMode' => false, 'unloadedOrders' => [], 'advanced' => false, 'lookups' => []])

@php
    $old = fn (string $key, mixed $default = null) => old($key, $default);
    $variant = $old('variante', $analysis['defaultVariant']->value);
    $chosenProducts = $old('produkte', $analysis['suggestedProducts']);
    $offers = count($analysis['variants']) > 1;
    $inputs = $analysis['inputs'] ?? [];
    $earlierReply = (bool) ($analysis['hasEarlierReply'] ?? false);
    $firstPlaceholder = "Was du über diesen Fall weißt, das nicht im Ticket steht, z. B.:\n· Foto geprüft: Motiv ist verschoben gedruckt\n· Kundin am Telefon: braucht Ersatz bis zum 20.12.\n· Produktion bestätigt: Fehldruck in der Charge";
    $followUpPlaceholder = "Es gab schon Kontakt. Was du über den Stand weißt, das nicht im Ticket steht, z. B.:\n· Entscheidung: Wir erstatten die Preisdifferenz, die Versandkosten nicht, weil der Kunde die Bestellung selbst geteilt hat\n· Stand: Der Fehler war aufwendiger zu beheben und ist erst heute gelöst\n· Bitte in der Antwort: für die verspätete Rückmeldung entschuldigen und den Grund nennen";
    $collapsible = ($analysis['result'] ?? null) !== null;
    $startOpen = ! $collapsible || session('analysis_error') || $errors->any();
    $usedGroup = collect($analysis['groups'])->firstWhere('key', $inputs['kundengruppe'] ?? $analysis['suggestedGroup'])?->label;
    $usedProducts = collect($analysis['products'])->whereIn('slug', $inputs['produkte'] ?? [])->pluck('title')->all();
    $knownOrders = $analysis['hasOrders'] || $unloadedOrders !== [] || $selected !== [];
    $orderLine = match (true) {
        $analysis['hasOrders'] => 'Bestellung: aus EOCS geladen und berücksichtigt.',
        $unloadedOrders !== [] => 'Bestellung: '.implode(', ', $unloadedOrders).' – wird beim Erstellen aus EOCS geladen.',
        $selected !== [] => 'Bestellung: '.implode(', ', $selected).' ausgewählt.',
        default => 'Keine Bestellung bekannt – der Vorschlag nutzt nur das Ticket.',
    };
    $oldInput = session()->hasOldInput();
    $unknownName = (string) ($oldInput ? old('produkt_ohne_wissen', '') : ($inputs['produkt_ohne_wissen'] ?? ''));
    $noProduct = $oldInput ? (bool) old('kein_produktbezug') : (bool) ($inputs['kein_produktbezug'] ?? ($inputs !== [] && ($inputs['produkte'] ?? []) === [] && $unknownName === '' && ($inputs['forge_produkte'] ?? []) === [] && ($inputs['eocs_produkte'] ?? []) === []));
    $usedVariant = \App\Analysis\ContextVariant::tryFrom((string) ($inputs['variante'] ?? ''))?->label();
@endphp

{{-- Analysis form (PROJ-9). Posts normally; Alpine only for variant preview, counter and the double-submit lock. --}}
<section id="analyse" aria-labelledby="analysis-heading" class="scroll-mt-4">
    <x-card>
        @if ($collapsible)
            <details id="analyse-details" class="group/form" @if ($startOpen) open @endif x-data x-on:open-analysis-form.window="$el.open = true; $nextTick(() => $el.scrollIntoView({ block: 'start' }))">
                <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open/form:rotate-90" />
                    <h2 id="analysis-heading" class="text-base font-semibold text-slate-900">Vorschlag erstellen</h2>
                    <span class="text-sm text-slate-600">
                        Erstellt mit: {{ implode(' · ', array_filter([$usedGroup, $usedProducts === [] ? 'kein Produktbezug' : implode(', ', $usedProducts), $usedVariant])) }}
                        – <span class="font-semibold text-brand">Eingaben ändern und neuen Vorschlag erstellen</span>
                    </span>
                </summary>
        @else
            <h2 id="analysis-heading" class="text-base font-semibold text-slate-900">Vorschlag erstellen</h2>
        @endif

        @if (session('analysis_error'))
            <x-alert type="error" class="mt-4" role="alert">
                <p>{{ session('analysis_error') }}</p>
                @if (session('analysis_retry'))
                    <p class="mt-3">
                        <x-button type="submit" form="analyse-formular" variant="secondary"><x-icon name="refresh" size="16" /> Erneut versuchen</x-button>
                    </p>
                @endif
            </x-alert>
        @endif

        @if (($analysis['ignoredReplies'] ?? 0) > 0)
            <p class="mt-3 text-sm text-slate-600">{{ $analysis['ignoredReplies'] === 1 ? 'Unsere Antwort nach der letzten Kundennachricht geht' : 'Unsere '.$analysis['ignoredReplies'].' Antworten nach der letzten Kundennachricht gehen' }} nicht in den Vorschlag ein: Er beantwortet die letzte Kundennachricht.</p>
        @endif

        <form id="analyse-formular" method="POST" action="{{ route('tickets.analysis.run', ['number' => $number]) }}" class="mt-4 space-y-5"
              x-data="{ variant: @js($variant), busy: false, length: @js(mb_strlen((string) $old('kontext', $inputs['kontext'] ?? ''))) }"
              x-on:submit="if (busy) { $event.preventDefault(); return } busy = true; $dispatch('loading-start', { title: 'Vorschlag wird erstellt …', text: 'Das dauert in der Regel 10 bis 30 Sekunden.' })"
              x-on:pageshow.window="busy = false">
            @csrf
            @if ($analysis['stand']) <input type="hidden" name="stand" value="{{ $analysis['stand'] }}"> @endif
            @foreach ($selected as $value)
                <input type="hidden" name="bestellungen[]" value="{{ $value }}">
            @endforeach

            @if ($analysis['related']['enabled'] ?? false)
                <x-analysis.related-tickets :related="$analysis['related']" />
            @endif

            <x-analysis.scope-fields :analysis="$analysis" :chosen-products="$chosenProducts" :no-product="$noProduct" :unknown-name="$unknownName" :advanced="$advanced" />

            <p class="text-sm text-slate-700" id="bestellung-status">{{ $orderLine }}
                @if ($unloadedOrders !== [] || $selected !== [] || $analysis['hasOrders']) <span class="text-slate-600">Auswahl und Laden stehen oben unter „Bestellungen“.</span>@endif
            </p>

            @unless ($knownOrders)
                <details class="group rounded-md ring-1 ring-slate-200 ring-inset" @if (collect(array_keys(\App\Http\Requests\AnalyzeTicketRequest::MANUAL_ORDER_FIELDS))->contains(fn ($field) => filled(old($field)))) open @endif>
                    <summary class="cursor-pointer list-none p-3 text-sm font-medium text-slate-900 [&::-webkit-details-marker]:hidden">
                        <x-icon name="chevron-down" size="16" class="inline transition group-open:rotate-180" /> Bestelldaten von Hand (keine Bestellnummer bekannt)
                    </summary>
                    <div class="grid gap-3 px-3 pb-3 md:grid-cols-2">
                        <x-input name="bestellung_nummer" label="Bestellnummer" :value="old('bestellung_nummer', $inputs['bestellung_nummer'] ?? null)" maxlength="60" />
                        <x-input name="bestellung_kanal" label="Kanal" :value="old('bestellung_kanal', $inputs['bestellung_kanal'] ?? null)" maxlength="60" />
                        <x-input name="bestellung_datum" label="Bestelldatum" :value="old('bestellung_datum', $inputs['bestellung_datum'] ?? null)" maxlength="30" />
                        <x-input name="bestellung_produkt" label="Produkt" :value="old('bestellung_produkt', $inputs['bestellung_produkt'] ?? null)" maxlength="200" />
                        <x-textarea name="bestellung_personalisierung" label="Personalisierung" class="md:col-span-2" rows="2" maxlength="2000">{{ old('bestellung_personalisierung', $inputs['bestellung_personalisierung'] ?? '') }}</x-textarea>
                    </div>
                </details>
            @endunless

            @if ($advanced && $offers)
                <fieldset>
                    <legend class="block text-sm font-medium text-slate-900">Was soll an die KI gehen?</legend>
                    <div class="mt-1 space-y-1">
                        @foreach ($analysis['variants'] as $option)
                            <label class="flex min-h-9 items-center gap-2 text-sm text-slate-900">
                                <input type="radio" name="variante" value="{{ $option->value }}" x-model="variant" @checked($variant === $option->value) class="size-4 border-slate-300 text-brand focus:ring-brand">
                                {{ $option->label() }}
                                @if ($option === $analysis['defaultVariant'])<span class="text-xs text-slate-600">(empfohlen)</span>@endif
                            </label>
                        @endforeach
                    </div>
                    @if ($analysis['suggestsSummary'] && ! $analysis['summary'])
                        <p class="mt-2 text-sm text-slate-600" x-show="variant === 'zusammenfassung'">Der Verlauf ist lang: Erstelle zuerst die <a href="#zusammenfassung" class="font-medium text-brand hover:text-brand-hover">Zusammenfassung</a> und prüfe sie. Sonst wird sie bei der Analyse automatisch erstellt.</p>
                    @endif
                </fieldset>
            @else
                <input type="hidden" name="variante" value="{{ $variant }}">
            @endif

            @if ($advanced)
            <details class="group rounded-md ring-1 ring-slate-200 ring-inset">
                <summary class="cursor-pointer list-none p-3 text-sm font-medium text-slate-900 [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-down" size="16" class="inline transition group-open:rotate-180" /> Was an die KI geht (Ticketteil, Kontaktdaten ersetzt)
                </summary>
                <div class="px-3 pb-3">
                    @foreach ($analysis['previews'] as $key => $preview)
                        <div x-show="variant === @js($key)" @if ($key !== $variant) x-cloak @endif>
                            @if ($preview === null)
                                <p class="text-sm text-slate-600">Die Zusammenfassung ist noch nicht erstellt.</p>
                            @else
                                <pre class="max-h-96 overflow-y-auto whitespace-pre-wrap wrap-anywhere rounded-md bg-slate-50 p-3 font-mono text-xs text-slate-900">{{ $preview }}</pre>
                            @endif
                        </div>
                    @endforeach
                    <p class="mt-2 text-xs text-slate-600">Dazu kommen Bestelldaten (ohne Adressen und Zahlungsdaten), Kundengruppe, Produkte, deine zusätzlichen Informationen und das passende Wissen.</p>
                </div>
            </details>
            @endif

            @if ($testMode)
                <x-alert type="warning">
                    <span class="font-semibold">Testmodus:</span> Hier keine Regeln oder Antworten eintragen („das geht nicht“, „das machen wir immer so“) – sonst bleiben Lücken in der Wissensdatenbank unentdeckt. Fakten zum Fall (z. B. Ergebnis einer Fotoprüfung) sind erlaubt.
                </x-alert>
            @endif

            <x-field id="kontext" label="Zusätzliche Informationen / eigene Einschätzung" hint="Optional. Fakten, Entscheidungen und Wünsche an die Antwort gelten für die KI als geprüft. Allgemeine Regeln („das machen wir immer so“) gehören nicht hierher, sondern in die Wissensdatenbank – bitte nach der Analyse mit „Wissenslücke melden“ melden.">
                <textarea name="kontext" id="kontext" rows="5" placeholder="{{ $earlierReply ? $followUpPlaceholder : $firstPlaceholder }}" maxlength="{{ config('analysis.max_context_length') }}" x-on:input="length = $el.value.length"
                          class="block w-full rounded-md border-0 bg-white px-3 py-2 text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-brand sm:text-sm">{{ $old('kontext', $inputs['kontext'] ?? '') }}</textarea>
                <p class="mt-1 text-right text-xs text-slate-600"><span x-text="length">0</span> / {{ number_format(config('analysis.max_context_length'), 0, ',', '.') }}</p>
            </x-field>

            @php($labelOrders = collect($lookups)->flatMap(fn ($lookup) => $lookup->orders)->values())
            @if ($labelOrders->isNotEmpty())
                {{-- DHL return label before the proposal (PROJ-64): the model learns that a label goes with the reply, the app adds the steps. --}}
                <div x-data="{ labelOn: false }" class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <label class="flex items-start gap-2 text-sm font-medium text-slate-900">
                        <input type="checkbox" x-model="labelOn" x-on:change="if (labelOn) { $dispatch('dhl-load-address') }" class="mt-0.5 rounded text-brand focus:ring-brand">
                        <span>DHL-Retourenlabel erzeugen und im Antwortvorschlag berücksichtigen</span>
                    </label>
                    <p class="mt-1 pl-6 text-xs text-slate-600">Das Label wird jetzt bei DHL bestellt, hängt als PDF an der Antwort, und die Schritte für den Kunden stehen unter dem Entwurf. Nur für Rücksendungen aus Deutschland.</p>
                    <div x-show="labelOn" x-cloak class="mt-3 space-y-4">
                        @foreach ($labelOrders as $labelOrder)
                            <x-dhl.return-label :order="$labelOrder" :ticket-number="$number" mode="attach" :field="true" :configured="app(\App\Dhl\DhlClient::class)->isConfigured()" />
                        @endforeach
                    </div>
                </div>
            @endif

            <details class="-mt-2 text-sm text-slate-700">
                <summary class="cursor-pointer font-medium text-slate-700 hover:text-slate-900">So schreibst du es</summary>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li><span class="font-medium">Fakten</span> in einfachen Sätzen: „Foto geprüft: Das Motiv ist verschoben gedruckt.“</li>
                    <li><span class="font-medium">Entscheidungen</span> mit „Wir …“: „Wir erstatten die Differenz, die Versandkosten nicht, weil …“</li>
                    <li><span class="font-medium">Wünsche an die Antwort</span> mit „Bitte erkläre …“ oder „Bitte entschuldige dich für …“: „Bitte erkläre, warum es länger gedauert hat.“</li>
                    <li>Keine allgemeinen Regeln („das machen wir immer so“): Die gehören in die Wissensdatenbank.</li>
                </ul>
            </details>

            @error('kundengruppe') <p class="text-sm text-danger-700">{{ $message }}</p> @enderror
            @error('produkte.*') <p class="text-sm text-danger-700">{{ $message }}</p> @enderror
            @error('variante') <p class="text-sm text-danger-700">{{ $message }}</p> @enderror

            @if ($advanced && $unloadedOrders !== [])
                <div class="rounded-md bg-slate-50 p-3 text-sm ring-1 ring-slate-200 ring-inset">
                    <p class="text-slate-900">Erweitert: Die App ruft die Bestelldetails aus EOCS ab: <span class="font-mono">{{ implode(', ', $unloadedOrders) }}</span></p>
                    <label class="mt-2 inline-flex items-center gap-2 text-slate-700">
                        <input type="checkbox" name="ohne_bestelldetails" value="1" @checked(old('ohne_bestelldetails')) class="size-4 rounded border-slate-300 text-brand focus:ring-brand">
                        Bestelldetails nicht abrufen (der Vorschlag kennt dann nur die Bestellnummer)
                    </label>
                </div>
            @endif

            <div class="flex justify-end">
                <x-button type="submit" class="min-h-11" x-bind:disabled="busy"><x-icon name="sparkle" size="16" /> Vorschlag erstellen</x-button>
            </div>
        </form>
        @if ($collapsible)
            </details>
        @endif
    </x-card>
</section>
