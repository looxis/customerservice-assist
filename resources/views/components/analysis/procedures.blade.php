@props(['result', 'number', 'stand' => null, 'question' => false, 'orders' => [], 'lookups' => []])

@php
    $procedures = $result['procedures'] ?? ['suggested' => [], 'catalogue' => []];
    $suggested = $procedures['suggested'];
    $suggestedIds = array_column($suggested, 'id');
    $actionLabels = config('knowledge.actions');
    $decision = $result['result']['decision'] ?? null;
    $steps = $result['steps'] ?? [];
    $awaiting = ($result['result']['next_step'] ?? 'reply') === 'internal_first';
    $canContinue = $awaiting && ($result['is_latest'] ?? true);
    $caseWork = $decision !== null || $steps !== [] || $awaiting;
    $labelOrders = $question ? collect() : collect($lookups)->flatMap(fn ($lookup) => $lookup->orders)->values();
    $missingActions = implode(', ', array_map(fn ($action) => $actionLabels[$action] ?? $action, $result['result']['actions'] ?? []));
@endphp

{{-- Internal procedures for the case (PROJ-30), apart from the reply to the
     customer. Suggestions come with the page; a picked procedure is loaded
     when picked. Picks are kept per ticket, closed suggestions per analysis,
     both for the browser session only. Ticks are not saved. --}}
<section id="ablaeufe" aria-labelledby="procedures-heading" class="scroll-mt-4"
    x-data="{
        pickedKey: @js('procedures.picked.'.$number),
        closedKey: @js('procedures.closed.'.$result['id']),
        url: @js(route('procedures.show', array_filter(['id' => '__ID__', 'ticket' => $question ? null : $number, 'bestellungen' => $question || $orders === [] ? null : array_values($orders)]))),
        suggested: @js($suggestedIds),
        picked: [],
        closed: [],
        fragments: {},
        init() {
            try { this.picked = JSON.parse(sessionStorage.getItem(this.pickedKey) || '[]').filter((id) => ! this.suggested.includes(id)); this.closed = JSON.parse(sessionStorage.getItem(this.closedKey) || '[]') } catch (error) {}
            this.picked.forEach((id) => this.load(id));
        },
        remember() { try { sessionStorage.setItem(this.pickedKey, JSON.stringify(this.picked)); sessionStorage.setItem(this.closedKey, JSON.stringify(this.closed)) } catch (error) {} },
        async load(id) {
            if (this.fragments[id]) { return }
            try {
                const response = await fetch(this.url.replace('__ID__', encodeURIComponent(id)), { headers: { 'Accept': 'text/html' } });
                if (! response.ok) { throw new Error(response.status) }
                this.fragments[id] = await response.text();
            } catch (error) {
                this.picked = this.picked.filter((other) => other !== id);
                this.remember();
            }
        },
        shown(id) { return this.suggested.includes(id) ? ! this.closed.includes(id) : this.picked.includes(id) },
        pick(id) {
            this.closed = this.closed.filter((other) => other !== id);
            if (! this.suggested.includes(id) && ! this.picked.includes(id)) { this.picked = [...this.picked, id]; this.load(id) }
            this.remember();
        },
        close(id) { if (this.suggested.includes(id)) { this.closed = [...new Set([...this.closed, id])] } else { this.picked = this.picked.filter((other) => other !== id) } this.remember() },
        get none() { return ! this.suggested.some((id) => this.shown(id)) && this.picked.length === 0 },
    }"
    x-on:click="if ($event.target.dataset.closeProcedure) { $event.preventDefault(); close($event.target.dataset.closeProcedure) }">
    <div class="rounded-lg bg-slate-800/[0.03] p-5 ring-1 ring-slate-300 ring-inset md:p-6">
        <h2 id="procedures-heading" class="text-base font-semibold text-slate-900">Interne Abläufe <span class="font-normal text-slate-600">– intern, nicht an den Kunden</span></h2>

        @if ($labelOrders->isNotEmpty() && ($result['has_reply'] ?? false))
            {{-- DHL return label for a proposal that is already there (PROJ-64): the steps go under the draft, the PDF becomes an attachment. --}}
            <div class="mt-3" x-data="{ labelOpen: false }">
                <button type="button" x-on:click="labelOpen = ! labelOpen; if (labelOpen) { $dispatch('dhl-load-address') }" x-bind:aria-expanded="labelOpen ? 'true' : 'false'" aria-controls="dhl-label-{{ $result['id'] }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand focus-visible:outline-2 focus-visible:outline-brand">
                    <x-icon name="doc" size="16" /> DHL-Retourenlabel erstellen
                </button>
                <div id="dhl-label-{{ $result['id'] }}" x-show="labelOpen" x-cloak class="mt-3 space-y-4">
                    @foreach ($labelOrders as $labelOrder)
                        <x-dhl.return-label :order="$labelOrder" :ticket-number="$number" mode="attach" :configured="app(\App\Dhl\DhlClient::class)->isConfigured()" />
                    @endforeach
                </div>
            </div>
        @endif

        @if ($caseWork)
            <div class="mt-4 space-y-4">
                @if ($canContinue)
                    <form method="POST" action="{{ $question ? route('questions.follow-up', ['number' => $number]) : route('tickets.analysis.run', ['number' => $number]) }}" class="space-y-4" x-data="{ busy: false, choice: @js((string) old('klaerung_option', '')), note: @js((string) old('klaerung_text', '')) }" x-on:submit="if (busy) { $event.preventDefault(); return } busy = true; $dispatch('loading-start', { title: 'Vorschlag wird erstellt …', text: 'Das dauert in der Regel 10 bis 30 Sekunden.' })" x-on:pageshow.window="busy = false">
                        @csrf
                        <input type="hidden" name="klaerung_analyse" value="{{ $result['id'] }}">
                        @if ($stand)<input type="hidden" name="stand" value="{{ $stand }}">@endif
                        @unless ($question)
                            @foreach ($result['meta']['orders'] ?? [] as $order)<input type="hidden" name="bestellungen[]" value="{{ $order }}">@endforeach
                        @endunless
                @endif

                @if ($decision)
                    <div class="rounded-lg bg-white p-4 ring-1 ring-brand/30 ring-inset" id="klaerung">
                        <h3 class="text-sm font-semibold text-brand-700">Zuerst zu klären</h3>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ $decision['question'] }}</p>
                        @if ($decision['why'] !== '')<p class="mt-1 text-sm text-slate-600">{{ $decision['why'] }}</p>@endif
                        @if ($canContinue)<p class="mt-3 text-sm text-slate-700">Wähle das Ergebnis deiner Klärung oder trage unten ein eigenes ein, und bestätige mit „Mit diesem Ergebnis fortsetzen“.</p>@endif
                        <ul class="mt-2 space-y-2">
                            @foreach ($decision['options'] as $option)
                                <li>
                                    @if ($canContinue)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-md px-3 py-2 text-sm ring-1 ring-slate-300 ring-inset transition hover:bg-brand/5 hover:ring-brand has-[:checked]:bg-brand/5 has-[:checked]:ring-2 has-[:checked]:ring-brand has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-brand">
                                            <input type="radio" name="klaerung_option" value="{{ $option['key'] }}" x-model="choice" class="mt-1 shrink-0 cursor-pointer border-slate-300 text-brand focus:ring-brand">
                                            <span>
                                                <span class="font-medium text-slate-900">{{ $option['label'] }}</span>
                                                @if ($option['consequence'] !== '')<span class="mt-0.5 block text-xs text-slate-600">{{ $option['consequence'] }}</span>@endif
                                            </span>
                                        </label>
                                    @else
                                        <p class="rounded-md px-3 py-2 text-sm ring-1 ring-slate-200 ring-inset"><span class="font-medium text-slate-900">{{ $option['label'] }}</span>@if ($option['consequence'] !== '')<span class="mt-0.5 block text-xs text-slate-600">{{ $option['consequence'] }}</span>@endif</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($canContinue)
                            <x-analysis.clarification-input :decision="true" />
                        @endif
                        @if (($decision['knowledge_ids'] ?? []) !== [])
                            <p class="mt-3 text-xs text-slate-600">Grundlage: <span class="font-mono">{{ implode(', ', $decision['knowledge_ids']) }}</span></p>
                        @endif
                    </div>
                @endif

                @if ($steps !== [])
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Für diesen Fall <span class="font-normal text-slate-600">– von der KI aus dem Fall abgeleitet</span></h3>
                        <p class="mt-1 text-xs text-slate-600">Nur Hinweise zum Abhaken für dich. Die Kästchen wirken sich nicht auf den Vorschlag aus und werden nicht gespeichert.</p>
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($steps as $step)
                                <li><label class="flex items-start gap-2"><input type="checkbox" class="mt-1 rounded border-slate-300 text-brand focus:ring-brand"><span>{{ $step }}</span></label></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($canContinue && ! $decision)
                    <x-analysis.clarification-input />
                @endif

                @if ($canContinue)
                    </form>
                @endif
            </div>
        @endif

        @if ($caseWork && $suggested !== [])
            <p class="mt-4 text-sm font-semibold text-slate-900">Ergänzend aus der Wissensbasis</p>
        @endif

        <div class="mt-4 space-y-3">
            @foreach ($suggested as $position => $procedure)
                <div x-show="shown(@js($procedure['id']))">
                    <x-analysis.procedure-card :procedure="$procedure" :open="! $caseWork && ($position === 0 || count($suggested) < 3)" />
                </div>
            @endforeach

            <template x-for="id in picked" :key="id">
                <div x-html="fragments[id] || '<p class=&quot;text-sm text-slate-600&quot;>Ablauf wird geladen …</p>'"></div>
            </template>

            <div x-show="none" @if ($suggested !== [] || $caseWork) x-cloak @endif class="text-sm text-slate-600">
                <p>Kein passender Ablauf hinterlegt.</p>
                <button type="button" class="mt-1 text-sm font-semibold text-brand hover:text-brand-hover"
                        x-on:click="$dispatch('report-gap', { topic: '', missing: @js('Kein Arbeitsablauf für '.($missingActions !== '' ? $missingActions : 'diesen Fall')) })">Wissenslücke melden</button>
            </div>
        </div>

        @if ($procedures['catalogue'] !== [])
            <details class="group/pick mt-4">
                <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-right" size="16" class="transition group-open/pick:rotate-90" /> Ablauf auswählen
                </summary>
                <div class="mt-3 space-y-3 text-sm">
                    @foreach ($procedures['catalogue'] as $action => $entries)
                        <div>
                            <p class="font-semibold text-slate-900">{{ $actionLabels[$action] ?? $action }}</p>
                            <ul class="mt-1 space-y-1">
                                @foreach ($entries as $entry)
                                    <li class="flex flex-wrap items-center gap-x-2">
                                        <button type="button" x-on:click="pick(@js($entry['id'])); $nextTick(() => document.getElementById('ablaeufe').scrollIntoView({ block: 'start' }))"
                                                class="text-left font-medium text-brand hover:text-brand-hover">
                                            <span class="font-mono text-xs">{{ $entry['id'] }}</span> {{ $entry['title'] }}
                                        </button>
                                        @if ($entry['draft'])<x-badge compact tone="warning">Entwurf</x-badge>@endif
                                        @if ($entry['note'])<span class="text-xs text-warning-700">{{ $entry['note'] }}</span>@endif
                                        <span x-show="shown(@js($entry['id']))" x-cloak class="text-xs text-slate-600">angezeigt</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </div>
</section>
