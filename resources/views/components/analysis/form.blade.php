@props(['number', 'analysis', 'selected' => [], 'canClose' => false, 'testMode' => false, 'unloadedOrders' => [], 'advanced' => false, 'lookups' => [], 'complaint' => [], 'known' => []])

@php
    $old = fn (string $key, mixed $default = null) => old($key, $default);
    $introMode = app(\App\Staff\IntroMode::class)->isActive(request());
    $variant = $old('variante', $analysis['defaultVariant']->value);
    $chosenProducts = $old('produkte', $analysis['suggestedProducts']);
    $offers = count($analysis['variants']) > 1;
    $inputs = $analysis['inputs'] ?? [];
    $newMessages = (bool) ($analysis['result']['new_messages'] ?? false);
    // New messages since the proposal: the answers of the form start empty, the scope (group, channel, products) stays.
    $answers = $newMessages ? [] : $inputs;
    $earlierReply = (bool) ($analysis['hasEarlierReply'] ?? false);
    $firstPlaceholder = "Was du über diesen Fall weißt, das nicht im Ticket steht, z. B.:\n· Foto geprüft: Motiv ist verschoben gedruckt\n· Kundin am Telefon: braucht Ersatz bis zum 20.12.\n· Produktion bestätigt: Fehldruck in der Charge";
    $followUpPlaceholder = "Es gab schon Kontakt. Was du über den Stand weißt, das nicht im Ticket steht, z. B.:\n· Entscheidung: Wir erstatten die Preisdifferenz, die Versandkosten nicht, weil der Kunde die Bestellung selbst geteilt hat\n· Stand: Der Fehler war aufwendiger zu beheben und ist erst heute gelöst\n· Bitte in der Antwort: für die verspätete Rückmeldung entschuldigen und den Grund nennen";
    $collapsible = ($analysis['result'] ?? null) !== null;
    $startOpen = ! $collapsible || session('analysis_error') || $errors->any() || $newMessages;
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
    // Guided forms (PROJ-86, PROJ-90): the form of the ticket's category is preselected, every form can be chosen.
    $complaintToggle = (bool) ($complaint['toggle'] ?? false);
    $forms = \App\Analysis\GuidedForms::options();
    // "Diskussion beenden" (PROJ-98) only for some categories and after several customer replies.
    $closingForm = \App\Analysis\ClosingDecision::FORM;
    if (! ($complaint['closing'] ?? false)) {
        unset($forms[$closingForm]);
    }
    $defaultForm = $complaint['form'] ?? (($complaint['default'] ?? false) ? 'reklamation' : 'frei');
    $chosenForm = $oldInput ? old('formular') : ($answers['formular'] ?? null);
    // A channel without products (PROJ-94) offers no forms for goods, no orders and no DHL label; the form follows a change of the channel.
    $goodsForms = \App\Analysis\GuidedForms::forGoods();
    $startChannel = (string) old('verkaufskanal', $analysis['suggestedChannel'] ?? '');
    $digital = (bool) (collect($analysis['channels'] ?? [])->firstWhere('key', $startChannel)['without_products'] ?? false);
    $formMode = ! $complaintToggle ? 'frei' : (is_string($chosenForm) && isset($forms[$chosenForm]) ? $chosenForm : $defaultForm);
    $formMode = $digital && in_array($formMode, $goodsForms, true) ? 'frei' : $formMode;
    $usedForm = \App\Analysis\GuidedForms::guidedLabel($inputs['formular'] ?? null);
    $pickedAnswer = fn (string $key, ?string $default = null) => $oldInput ? old($key, $default) : ($answers[$key] ?? $default);
    $labelOrders = collect($lookups)->flatMap(fn ($lookup) => $lookup->orders)->values();
    // Guided fact-finding (PROJ-103): what was chosen before, after a failed check the values just sent.
    $findings = $oldInput
        ? ['fallmuster' => (array) old('fallmuster', []), 'pruefung' => (array) old('pruefung', []), 'kundenpruefung' => (array) old('kundenpruefung', []), 'kein_fallmuster' => (bool) old('kein_fallmuster')]
        : array_intersect_key($answers, array_flip(['fallmuster', 'pruefung', 'kundenpruefung', 'kein_fallmuster']));
@endphp

{{-- Analysis form (PROJ-9). Posts normally; Alpine only for variant preview, counter and the double-submit lock. --}}
<section id="analyse" aria-labelledby="analysis-heading" class="scroll-mt-4">
    <x-card>
        @if ($collapsible)
            <details id="analyse-details" class="group/form" @if ($startOpen) open @endif x-data x-on:open-analysis-form.window="$el.open = true; $nextTick(() => $el.scrollIntoView({ block: 'start' }))">
                <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open/form:rotate-90" />
                    <h2 id="analysis-heading" class="text-base font-semibold text-slate-900">Ticket bearbeiten</h2>
                    <span class="text-sm text-slate-600">
                        Erstellt mit: {{ implode(' · ', array_filter([$usedForm, $analysis['usedChannelLabel'] ?? null, $usedGroup, in_array($inputs['formular'] ?? null, [\App\Analysis\ReshipmentDecision::FORM, $closingForm, \App\Analysis\PaymentDecision::FORM, \App\Analysis\WismoUpdateDecision::FORM, \App\Analysis\CancellationDecision::FORM], true) ? null : ($usedProducts === [] ? 'kein Produktbezug' : implode(', ', $usedProducts)), $usedVariant])) }}
                        – <span class="font-semibold text-brand">Eingaben ändern und neuen Antwortvorschlag erstellen</span>
                        @if ($complaintToggle && ! $digital)
                            · <button type="button" class="font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand" x-on:click.prevent.stop="$dispatch('open-analysis-form', { mode: 'reklamation' })">Als Reklamation abwickeln</button>
                        @endif
                    </span>
                </summary>
        @else
            <h2 id="analysis-heading" class="text-base font-semibold text-slate-900">Ticket bearbeiten</h2>
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
              x-data="{
                  variant: @js($variant), busy: false, length: @js(mb_strlen((string) $old('kontext', $inputs['kontext'] ?? ''))), labelOn: false, labelNote: '',
                  mode: @js($formMode), digital: @js($digital), goodsForms: @js($goodsForms), loesung: @js($pickedAnswer('support_loesung')), kostenpflichtig: @js($pickedAnswer('neuversand_kostenpflichtig')), bezahlt: @js($pickedAnswer('neuversand_bezahlt')), berechtigt: @js($pickedAnswer('reklamation_berechtigt')), retoure: @js($pickedAnswer('retoure', 'nein')), vorErsatz: @js($pickedAnswer('retoure_vor_ersatz', 'ja')), kulanz: @js($pickedAnswer('kulanz', 'ja')), versand: @js($pickedAnswer('versand', 'zeitnah')), angebot: @js($pickedAnswer('abschluss_angebot')), anliegen: @js($pickedAnswer('zahlung_anliegen')), zahlungsart: @js($pickedAnswer('zahlung_art')), zahlungsstand: @js($pickedAnswer('zahlung_stand')), rechnungAnhang: @js($pickedAnswer('zahlung_rechnung_anhang')), erstattung: @js($pickedAnswer('zahlung_erstattung')), stornoErgebnis: @js($pickedAnswer('storno_ergebnis')), stornoArt: @js($pickedAnswer('storno_art')),
                  syncLabel() { this.labelOn = this.mode === 'reklamation' && ['ja', 'unsicher'].includes(this.berechtigt) && this.retoure === 'ja' },
                  init() {
                      ['mode', 'berechtigt', 'retoure'].forEach((property) => this.$watch(property, () => this.syncLabel()))
                      this.$watch('labelOn', (on) => { if (on) { this.$dispatch('dhl-load-address') } })
                      this.syncLabel()
                  },
              }"
              x-on:return-label-ready="labelNote = ''"
              x-on:open-analysis-form.window="if ($event.detail?.mode && ! (digital && goodsForms.includes($event.detail.mode))) { mode = $event.detail.mode }"
              x-on:channel-changed="digital = $event.detail.withoutProducts; if (digital) { labelOn = false; if (goodsForms.includes(mode)) { mode = 'frei' } }"
              x-on:submit="if (busy) { $event.preventDefault(); return } if (labelOn && ! $el.querySelector('input[name=return_label]:not(:disabled)')) { $event.preventDefault(); labelNote = 'Bitte zuerst das DHL-Retourenlabel erzeugen oder die Option abwählen.'; $nextTick(() => $el.querySelector('#dhl-option')?.scrollIntoView({ block: 'center' })); return } busy = true; $dispatch('loading-start', { title: 'Antwortvorschlag wird erstellt …', text: 'Das dauert in der Regel 10 bis 30 Sekunden.' })"
              x-on:pageshow.window="busy = false">
            @csrf
            @if ($analysis['stand']) <input type="hidden" name="stand" value="{{ $analysis['stand'] }}"> @endif
            @foreach ($selected as $value)
                <input type="hidden" name="bestellungen[]" value="{{ $value }}" x-bind:disabled="digital">
            @endforeach

            @if ($analysis['related']['enabled'] ?? false)
                <x-analysis.related-tickets :related="$analysis['related']" />
            @endif

            <x-analysis.scope-fields :analysis="$analysis" :chosen-products="$chosenProducts" :no-product="$noProduct" :unknown-name="$unknownName" :advanced="$advanced" :optional-product-for="$complaintToggle ? 'support' : null" :hidden-product-for="$complaintToggle ? ['neuversand', $closingForm, \App\Analysis\PaymentDecision::FORM, \App\Analysis\WismoUpdateDecision::FORM, \App\Analysis\CancellationDecision::FORM] : null" />

            <fieldset x-show="! digital" x-bind:disabled="digital" @if ($digital) x-cloak @endif class="min-w-0 space-y-5 border-0 p-0">
            <legend class="sr-only">Bestellung</legend>
            @unless ($analysis['hasOrders'])
                <p class="text-sm text-slate-700" id="bestellung-status">{{ $orderLine }}</p>
            @endunless

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
            </fieldset>

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

            {{-- PROJ-103: three functions in one area, no modes. The direct analysis is the usual way; the guided fact-finding is optional and closed; the guided processing holds the forms as before. --}}
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="zap" size="18" class="text-brand" /> Direkte Analyse</h3>

                <x-analysis.case-patterns :known="$known" :category="$complaint['category'] ?? null" :initial="$findings" />

                <input type="hidden" name="formular" x-bind:value="mode" value="{{ $formMode }}">
                @if ($complaintToggle)
                    <section aria-labelledby="guided-processing-heading" class="space-y-5 rounded-lg bg-slate-50/60 p-4 ring-1 ring-slate-200 ring-inset">
                        <h3 id="guided-processing-heading" class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="list-todo" size="18" class="text-brand" /> Geführte Bearbeitung</h3>
                        {{-- Which form the proposal is made with: preselected from the category of the ticket. The free text is no guided processing and stands apart. --}}
                        <fieldset>
                            <legend class="block text-sm font-medium text-slate-900">Formular</legend>
                            <div class="mt-1 flex flex-wrap items-center gap-x-6 gap-y-1">
                                @foreach ($forms as $formKey => $formLabel)
                                    @if ($formKey === \App\Analysis\ComplaintDecision::FREE)
                                        <span class="text-sm text-slate-500" aria-hidden="true">oder:</span>
                                    @endif
                                    <label class="flex min-h-9 items-center gap-2 text-sm text-slate-900" @if (in_array($formKey, $goodsForms, true)) x-show="! digital" @if ($digital) x-cloak @endif @endif>
                                        <input type="radio" name="formularwahl" value="{{ $formKey }}" x-model="mode" @checked($formMode === $formKey) class="size-4 border-slate-300 text-brand focus:ring-brand">
                                        {{ $formLabel }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <x-analysis.customer-check-hint />
                        <x-analysis.support-fields :inputs="$answers" />
                        <x-analysis.complaint-fields :inputs="$answers" :complaint="$complaint" :label-orders="$labelOrders" :number="$number" />
                        <x-analysis.payment-fields :inputs="$answers" :orders="$labelOrders" />
                        <x-analysis.wismo-update-fields :inputs="$answers" :orders="$labelOrders" />
                        <x-analysis.cancellation-fields :inputs="$answers" :orders="$labelOrders" />
                        <x-analysis.reshipment-fields :inputs="$answers" />
                        @if (isset($forms[$closingForm]))
                            <x-analysis.closing-fields :inputs="$answers" />
                        @endif
                    </section>
                @else
                    <x-analysis.customer-check-hint />
                @endif
            </div>

            @if ($testMode)
                <x-alert type="warning">
                    <span class="font-semibold">Testmodus:</span> Hier keine Regeln oder Antworten eintragen („das geht nicht“, „das machen wir immer so“) – sonst bleiben Lücken in der Wissensdatenbank unentdeckt. Fakten zum Fall (z. B. Ergebnis einer Fotoprüfung) sind erlaubt.
                </x-alert>
            @endif

            <fieldset x-show="mode === 'frei'" x-bind:disabled="mode !== 'frei'" @if ($formMode !== 'frei') x-cloak @endif class="min-w-0 space-y-5 border-0 p-0">
            <legend class="sr-only">Zusätzliche Informationen</legend>
            <x-field id="kontext" label="Zusätzliche Informationen / eigene Einschätzung" :hint="$introMode ? 'Optional. Fakten, Entscheidungen und Wünsche an die Antwort gelten für die KI als geprüft. Allgemeine Regeln („das machen wir immer so“) gehören nicht hierher, sondern in die Wissensdatenbank – bitte nach der Analyse mit „Wissenslücke melden“ melden.' : null">
                <textarea name="kontext" id="kontext" rows="5" placeholder="{{ $earlierReply ? $followUpPlaceholder : $firstPlaceholder }}" maxlength="{{ config('analysis.max_context_length') }}" x-on:input="length = $el.value.length"
                          class="block w-full rounded-md border-0 bg-white px-3 py-2 text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-brand sm:text-sm">{{ $old('kontext', '') }}</textarea>
                <p class="mt-1 text-right text-xs text-slate-600"><span x-text="length">0</span> / {{ number_format(config('analysis.max_context_length'), 0, ',', '.') }}</p>
            </x-field>

            @if ($labelOrders->isNotEmpty())
                {{-- DHL return label before the proposal (PROJ-64): the model learns that a label goes with the reply, the app adds the steps. --}}
                <div id="dhl-option" class="rounded-lg border border-slate-200 bg-slate-50/60 p-4" x-show="! digital" @if ($digital) x-cloak @endif>
                    <label class="flex items-start gap-2 text-sm font-medium text-slate-900">
                        <input type="checkbox" x-model="labelOn" class="mt-0.5 rounded text-brand focus:ring-brand">
                        <span>DHL-Retourenlabel erzeugen und im Antwortvorschlag berücksichtigen</span>
                    </label>
                    <x-intro>
                        <p class="mt-1 pl-6 text-xs text-slate-600">Das Label wird jetzt bei DHL bestellt, hängt als PDF an der Antwort, und die Schritte für den Kunden stehen unter dem Entwurf. Möglich für Rücksendungen aus {{ \App\Returns\ReturnCountry::sentence() }}.</p>
                    </x-intro>
                    <p x-show="labelNote !== ''" x-cloak role="alert" class="mt-2 pl-6 text-sm font-medium text-danger-700" x-text="labelNote"></p>
                    <div x-show="labelOn" x-cloak class="mt-3 space-y-4">
                        @foreach ($labelOrders as $labelOrder)
                            <x-dhl.return-label :order="$labelOrder" :ticket-number="$number" mode="attach" :field="true" :configured="app(\App\Dhl\DhlClient::class)->isConfigured()" />
                        @endforeach
                    </div>
                </div>
            @endif

            <x-intro>
            <details class="-mt-2 text-sm text-slate-700">
                <summary class="cursor-pointer font-medium text-slate-700 hover:text-slate-900">So schreibst du es</summary>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li><span class="font-medium">Fakten</span> in einfachen Sätzen: „Foto geprüft: Das Motiv ist verschoben gedruckt.“</li>
                    <li><span class="font-medium">Entscheidungen</span> mit „Wir …“: „Wir erstatten die Differenz, die Versandkosten nicht, weil …“</li>
                    <li><span class="font-medium">Wünsche an die Antwort</span> mit „Bitte erkläre …“ oder „Bitte entschuldige dich für …“: „Bitte erkläre, warum es länger gedauert hat.“</li>
                    <li>Keine allgemeinen Regeln („das machen wir immer so“): Die gehören in die Wissensdatenbank.</li>
                </ul>
            </details>
            </x-intro>
            </fieldset>

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

            <div class="flex flex-wrap items-center justify-end gap-3">
                <x-button type="submit" class="min-h-11" x-bind:disabled="busy"><x-icon name="sparkle" size="16" /> Antwortvorschlag erstellen</x-button>
                @if ($canClose)
                    <span class="text-sm text-slate-600">oder</span>
                    <x-button type="submit" form="direkt-antworten-formular" variant="secondary" class="min-h-11" title="Ohne Vorschlag direkt eine Antwort schreiben und senden (kein KI-Aufruf)."><x-icon name="edit" size="16" /> Direkt antworten</x-button>
                    <x-button variant="danger" class="min-h-11" x-on:click="$dispatch('close-ticket')"><x-icon name="x-circle" size="16" /> Ticket ohne Antwort schließen</x-button>
                @endif
            </div>
        </form>
        @if ($canClose)
            <form id="direkt-antworten-formular" method="POST" action="{{ route('tickets.manual-reply', ['number' => $number]) }}" class="hidden">@csrf</form>
        @endif
        @if ($collapsible)
            </details>
        @endif
    </x-card>
</section>
