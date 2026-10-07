@props(['number', 'analysis', 'selected' => [], 'testMode' => false])

@php
    $old = fn (string $key, mixed $default = null) => old($key, $default);
    $variant = $old('variante', $analysis['defaultVariant']->value);
    $chosenProducts = $old('produkte', $analysis['suggestedProducts']);
    $offers = count($analysis['variants']) > 1;
    $inputs = $analysis['inputs'] ?? [];
    $collapsible = ($analysis['result'] ?? null) !== null;
    $startOpen = ! $collapsible || session('analysis_error') || $errors->any();
    $usedGroup = collect($analysis['groups'])->firstWhere('key', $inputs['kundengruppe'] ?? $analysis['suggestedGroup'])?->label;
    $usedProducts = collect($analysis['products'])->whereIn('slug', $inputs['produkte'] ?? [])->pluck('title')->all();
    $usedVariant = \App\Analysis\ContextVariant::tryFrom((string) ($inputs['variante'] ?? ''))?->label();
@endphp

{{-- Analysis form (PROJ-9). Posts normally; Alpine only for variant preview, counter and the double-submit lock. --}}
<section id="analyse" aria-labelledby="analysis-heading" class="scroll-mt-4">
    <x-card>
        @if ($collapsible)
            <details id="analyse-details" class="group/form" @if ($startOpen) open @endif x-data x-on:open-analysis-form.window="$el.open = true; $nextTick(() => $el.scrollIntoView({ block: 'start' }))">
                <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open/form:rotate-90" />
                    <h2 id="analysis-heading" class="text-base font-semibold text-slate-900">Analyse</h2>
                    <span class="text-sm text-slate-600">
                        Analysiert mit: {{ implode(' · ', array_filter([$usedGroup, $usedProducts === [] ? 'kein Produktbezug' : implode(', ', $usedProducts), $usedVariant])) }}
                        – <span class="font-semibold text-brand">Eingaben ändern und neu analysieren</span>
                    </span>
                </summary>
        @else
            <h2 id="analysis-heading" class="text-base font-semibold text-slate-900">Analyse</h2>
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

        <form id="analyse-formular" method="POST" action="{{ route('tickets.analysis.run', ['number' => $number]) }}" class="mt-4 space-y-5"
              x-data="{ variant: @js($variant), busy: false, length: @js(mb_strlen((string) $old('kontext', $inputs['kontext'] ?? ''))) }"
              x-on:submit="if (busy) { $event.preventDefault(); return } busy = true; $dispatch('loading-start', { title: 'Ticket wird analysiert …', text: 'Das dauert in der Regel 10 bis 30 Sekunden.' })"
              x-on:pageshow.window="busy = false">
            @csrf
            @if ($analysis['stand']) <input type="hidden" name="stand" value="{{ $analysis['stand'] }}"> @endif
            @foreach ($selected as $value)
                <input type="hidden" name="bestellungen[]" value="{{ $value }}">
            @endforeach

            <div class="grid gap-4 md:grid-cols-2">
                <x-select name="kundengruppe" label="Kundengruppe" :hint="$analysis['groupSource'] ? 'Vorbelegt: '.$analysis['groupSource'] : null" required>
                    @foreach ($analysis['groups'] as $group)
                        <option value="{{ $group->key }}" @selected($old('kundengruppe', $analysis['suggestedGroup']) === $group->key)>{{ $group->label }}</option>
                    @endforeach
                </x-select>

                <fieldset>
                    <legend class="block text-sm font-medium text-slate-900">Produkt(e)</legend>
                    @if ($analysis['products'] === [])
                        <p class="mt-1 text-sm text-slate-600">Noch kein Produktwissen hinterlegt – Analyse ohne Produktbezug.</p>
                    @else
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-2">
                            @foreach ($analysis['products'] as $product)
                                <label class="inline-flex min-h-9 items-center gap-2 text-sm text-slate-900">
                                    <input type="checkbox" name="produkte[]" value="{{ $product['slug'] }}" @checked(in_array($product['slug'], (array) $chosenProducts, true)) class="size-4 rounded border-slate-300 text-brand focus:ring-brand">
                                    {{ $product['title'] }}
                                    @if (($analysis['productReasons'][$product['slug']] ?? []) !== [])
                                        <span class="text-xs text-slate-600">({{ implode(' · ', $analysis['productReasons'][$product['slug']]) }})</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @foreach ($analysis['mentionedProducts'] ?? [] as $mentioned)
                            <p class="mt-1 text-xs font-medium text-warning-700">Im Ticket erwähnt: {{ collect($analysis['products'])->firstWhere('slug', $mentioned)['title'] ?? $mentioned }} ({{ implode(' · ', $analysis['productReasons'][$mentioned] ?? []) }}) – nicht ausgewählt</p>
                        @endforeach
                        <p class="mt-1 text-xs text-slate-600">Nichts angehakt = kein Produktbezug / unklar.</p>
                    @endif
                </fieldset>
            </div>

            @unless ($analysis['hasOrders'])
                <details class="group rounded-md ring-1 ring-slate-200 ring-inset" @if (collect(array_keys(\App\Http\Requests\AnalyzeTicketRequest::MANUAL_ORDER_FIELDS))->contains(fn ($field) => filled(old($field)))) open @endif>
                    <summary class="cursor-pointer list-none p-3 text-sm font-medium text-slate-900 [&::-webkit-details-marker]:hidden">
                        <x-icon name="chevron-down" size="16" class="inline transition group-open:rotate-180" /> Bestelldaten von Hand (keine EOCS-Bestellung geladen)
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

            @if ($offers)
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
                <input type="hidden" name="variante" value="{{ $analysis['defaultVariant']->value }}">
            @endif

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

            @if ($testMode)
                <x-alert type="warning">
                    <span class="font-semibold">Testmodus:</span> Hier keine Regeln oder Antworten eintragen („das geht nicht“, „das machen wir immer so“) – sonst bleiben Lücken in der Wissensdatenbank unentdeckt. Fakten zum Fall (z. B. Ergebnis einer Fotoprüfung) sind erlaubt.
                </x-alert>
            @endif

            <x-field id="kontext" label="Zusätzliche Informationen / eigene Einschätzung" hint="Optional. Gilt für die KI als geprüfter Fakt. Allgemeine Regeln („das machen wir immer so“) gehören nicht hierher, sondern in die Wissensdatenbank – bitte als Wissenslücke an Etienne melden.">
                <textarea name="kontext" id="kontext" rows="5" placeholder="Was du über diesen Fall weißt, das nicht im Ticket steht, z. B.:&#10;· Foto geprüft: Motiv ist verschoben gedruckt&#10;· Kundin am Telefon: braucht Ersatz bis zum 20.12.&#10;· Produktion bestätigt: Fehldruck in der Charge" maxlength="{{ config('analysis.max_context_length') }}" x-on:input="length = $el.value.length"
                          class="block w-full rounded-md border-0 bg-white px-3 py-2 text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-brand sm:text-sm">{{ $old('kontext', $inputs['kontext'] ?? '') }}</textarea>
                <p class="mt-1 text-right text-xs text-slate-600"><span x-text="length">0</span> / {{ number_format(config('analysis.max_context_length'), 0, ',', '.') }}</p>
            </x-field>

            @error('kundengruppe') <p class="text-sm text-danger-700">{{ $message }}</p> @enderror
            @error('produkte.*') <p class="text-sm text-danger-700">{{ $message }}</p> @enderror
            @error('variante') <p class="text-sm text-danger-700">{{ $message }}</p> @enderror

            <div class="flex justify-end">
                <x-button type="submit" class="min-h-11" x-bind:disabled="busy"><x-icon name="sparkle" size="16" /> Analysieren</x-button>
            </div>
        </form>
        @if ($collapsible)
            </details>
        @endif
    </x-card>
</section>
