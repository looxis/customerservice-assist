@props(['known' => [], 'category' => null, 'initial' => [], 'scope' => []])

@php
    // What was chosen for the last proposal (PROJ-103); a form reopened after new customer messages starts empty.
    $config = [
        'url' => route('case-patterns'),
        'category' => $category,
        'known' => (object) $known,
        'selected' => array_values((array) ($initial['fallmuster'] ?? [])),
        'answers' => (object) ($initial['pruefung'] ?? []),
        'adopted' => array_values((array) ($initial['kundenpruefung'] ?? [])),
        'none' => (bool) ($initial['kein_fallmuster'] ?? false),
        'group' => $scope['group'] ?? '',
        'channel' => $scope['channel'] ?? '',
        'products' => array_values((array) ($scope['products'] ?? [])),
    ];
    $answers = ['ja' => 'ja', 'nein' => 'nein', 'offen' => 'nicht beurteilbar'];
@endphp

{{-- Guided fact-finding (PROJ-103): known case patterns of the knowledge base, with what to look at and what to check.
     Optional and closed by default; a chosen pattern is a hypothesis, never a decision. Knows no ticket. --}}
<details id="sachverhaltsermittlung" class="group/patterns rounded-lg bg-white ring-1 ring-slate-200 ring-inset"
         x-data="casePatterns(@js($config))"
         x-on:scope-changed.window="rescope($event.detail)"
         x-on:open-case-patterns.window="$el.open = true; $nextTick(() => $el.scrollIntoView({ block: 'center' }))"
         @if (! empty($initial['fallmuster']) || ! empty($initial['kein_fallmuster'])) open @endif>
    <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-2 gap-y-1 p-3 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
        <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open/patterns:rotate-90" />
        <x-icon name="list-checks" size="18" class="text-brand" />
        <span class="text-sm font-semibold text-slate-900">Sachverhaltsermittlung</span>
        {{-- The click on the question mark must not open or close the area. --}}
        <span x-on:click.prevent class="inline-flex"><x-help-tip :size="19" text="Unklar, was hinter der Anfrage steckt oder wie der Fall zu lösen ist? Hier findest du bekannte Fallmuster aus dem Wissen: woran man sie erkennt und was zu prüfen ist. So erarbeitest du den Sachverhalt und einen möglichen Lösungsweg und machst danach mit der geführten Bearbeitung oder der direkten Analyse weiter." /></span>
        <span class="text-sm text-slate-600">· Unsicher, was zu prüfen ist?</span>
        <span class="text-sm" x-bind:class="empty ? 'italic text-slate-600' : 'font-medium text-slate-700'" x-text="summary"></span>
        <template x-if="$store.caseFindings.selected.length > 0">
            <x-badge compact tone="info"><span x-text="$store.caseFindings.selected.length + ' gewählt'"></span></x-badge>
        </template>
    </summary>

    <div class="space-y-4 border-t border-slate-200 px-3 pt-3 pb-4">
        <x-intro>
            <p class="text-sm text-slate-600">Fallmuster beschreiben, was du auf Fotos oder im Text beobachten kannst, und welche Fragen den Fall klären. Ein gewähltes Fallmuster ist eine Vermutung, keine Entscheidung: Die KI prüft andere Ursachen weiter und sagt, wenn etwas nicht passt.</p>
        </x-intro>

        <template x-for="notice in $store.caseFindings.notices" :key="notice">
            <x-alert type="warning"><span x-text="notice"></span></x-alert>
        </template>

        <p x-show="loading && ! loaded" class="text-sm text-slate-600" role="status">Fallmuster werden geladen …</p>
        <div x-show="failed" x-cloak class="flex flex-wrap items-center gap-3 text-sm text-danger-700" role="alert">
            Die Fallmuster konnten nicht geladen werden.
            <x-button type="button" variant="secondary" class="px-2! py-1! text-xs!" x-on:click="load()"><x-icon name="refresh" size="14" /> Neu laden</x-button>
        </div>
        <p x-show="empty" x-cloak class="text-sm text-slate-600">Für diese Angaben sind im Wissen keine Fallmuster hinterlegt.</p>

        @foreach (['product' => 'Fallmuster zum Produkt', 'general' => 'Allgemeine Fallmuster'] as $group => $heading)
            <fieldset x-show="{{ $group === 'product' ? 'productPatterns' : 'generalPatterns' }}.length > 0" x-cloak class="min-w-0 border-0 p-0">
                <legend class="text-sm font-medium text-slate-900">
                    @if ($group === 'general')
                        <button type="button" class="inline-flex items-center gap-1 focus-visible:outline-2 focus-visible:outline-brand" x-show="productPatterns.length > 0 && ! showGeneral" x-on:click="showGeneral = true">
                            <x-icon name="chevron-down" size="14" /> {{ $heading }} (<span x-text="generalPatterns.length"></span>) anzeigen
                        </button>
                        <span x-show="productPatterns.length === 0 || showGeneral">{{ $heading }}</span>
                    @else
                        {{ $heading }}
                    @endif
                </legend>
                <ul class="mt-2 space-y-2" @if ($group === 'general') x-show="productPatterns.length === 0 || showGeneral" @endif>
                    <template x-for="pattern in {{ $group === 'product' ? 'productPatterns' : 'generalPatterns' }}" :key="pattern.id">
                        <li class="rounded-md p-3 ring-1 ring-inset" x-bind:class="$store.caseFindings.isSelected(pattern.id) ? 'bg-brand-tint ring-brand/30' : 'ring-slate-200'">
                            <label class="flex cursor-pointer items-start gap-2">
                                <input type="checkbox" class="mt-0.5 size-4 rounded border-slate-300 text-brand focus:ring-brand" x-bind:checked="$store.caseFindings.isSelected(pattern.id)" x-on:change="toggle(pattern)">
                                <span>
                                    <span class="block text-sm font-medium text-slate-900" x-text="pattern.title"></span>
                                    <span class="block text-sm text-slate-700" x-text="pattern.recognize"></span>
                                </span>
                            </label>

                            <div x-show="$store.caseFindings.isSelected(pattern.id) && pattern.checks.length > 0" x-cloak class="mt-3 space-y-3 pl-6">
                                <template x-for="check in pattern.checks" :key="check.key">
                                    <div>
                                        <p class="text-sm text-slate-900" x-text="check.question"></p>
                                        <template x-if="knowledgeOf(check).state === 'ask'">
                                            <div class="mt-1 flex flex-wrap gap-2" role="group" x-bind:aria-label="check.question">
                                                @foreach ($answers as $answerValue => $answerLabel)
                                                    <button type="button" class="min-h-8 rounded-pill px-3 text-xs font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand"
                                                            x-bind:class="answer(pattern, check) === @js($answerValue) ? 'bg-brand text-white ring-brand' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'"
                                                            x-bind:aria-pressed="(answer(pattern, check) === @js($answerValue)).toString()"
                                                            x-on:click="setAnswer(pattern, check, @js($answerValue))">{{ $answerLabel }}</button>
                                                @endforeach
                                            </div>
                                        </template>
                                        <p x-show="knowledgeOf(check).text !== ''" class="mt-1 text-xs" x-bind:class="knowledgeOf(check).state === 'ask' ? 'text-slate-600' : 'font-medium text-success-700'" x-text="knowledgeOf(check).text"></p>
                                    </div>
                                </template>
                            </div>
                        </li>
                    </template>
                </ul>
            </fieldset>
        @endforeach

        <label x-show="loaded" class="flex cursor-pointer items-center gap-2 text-sm text-slate-900">
            <input type="checkbox" class="size-4 rounded border-slate-300 text-brand focus:ring-brand" x-bind:checked="$store.caseFindings.none" x-on:change="toggleNone()">
            Kein Fallmuster passt
        </label>
    </div>

    {{-- What goes with the form. A question the app answered itself is not sent: the server knows the value. --}}
    <template x-for="id in $store.caseFindings.selected" :key="'f-' + id"><input type="hidden" name="fallmuster[]" x-bind:value="id"></template>
    <template x-for="field in answerFields" :key="field.name"><input type="hidden" x-bind:name="field.name" x-bind:value="field.value"></template>
    <template x-for="id in $store.caseFindings.adopted" :key="'k-' + id"><input type="hidden" name="kundenpruefung[]" x-bind:value="id"></template>
    <template x-if="$store.caseFindings.none"><input type="hidden" name="kein_fallmuster" value="1"></template>
</details>
