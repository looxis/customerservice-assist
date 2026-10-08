@props(['result', 'number'])

@php
    $procedures = $result['procedures'] ?? ['suggested' => [], 'catalogue' => []];
    $suggested = $procedures['suggested'];
    $suggestedIds = array_column($suggested, 'id');
    $actionLabels = config('knowledge.actions');
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
        url: @js(route('procedures.show', ['id' => '__ID__'])),
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

        <div class="mt-4 space-y-3">
            @foreach ($suggested as $position => $procedure)
                <div x-show="shown(@js($procedure['id']))">
                    <x-analysis.procedure-card :procedure="$procedure" :open="$position === 0 || count($suggested) < 3" />
                </div>
            @endforeach

            <template x-for="id in picked" :key="id">
                <div x-html="fragments[id] || '<p class=&quot;text-sm text-slate-600&quot;>Ablauf wird geladen …</p>'"></div>
            </template>

            <div x-show="none" @if ($suggested !== []) x-cloak @endif class="text-sm text-slate-600">
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
