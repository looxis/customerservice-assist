@props(['result', 'number'])

@php
    $procedures = $result['procedures'] ?? ['all' => [], 'suggested' => [], 'catalogue' => []];
    $suggested = $procedures['suggested'];
    $actionLabels = config('knowledge.actions');
    $missingActions = implode(', ', array_map(fn ($action) => $actionLabels[$action] ?? $action, $result['result']['actions'] ?? []));
@endphp

{{-- Internal procedures for the case (PROJ-30), apart from the reply to the
     customer. Suggestions come from the server; picking and closing happen in
     the browser, the picks are kept per ticket for the session. Ticks are not saved. --}}
<section id="ablaeufe" aria-labelledby="procedures-heading" class="scroll-mt-4"
    x-data="{
        key: @js('procedures.'.$number),
        suggested: @js($suggested),
        picked: [],
        closed: [],
        init() {
            try { const saved = JSON.parse(sessionStorage.getItem(this.key) || '{}'); this.picked = saved.picked || []; this.closed = saved.closed || [] } catch (error) {}
        },
        remember() { try { sessionStorage.setItem(this.key, JSON.stringify({ picked: this.picked, closed: this.closed })) } catch (error) {} },
        shown(id) { return (this.suggested.includes(id) || this.picked.includes(id)) && ! this.closed.includes(id) },
        pick(id) { this.picked = [...new Set([...this.picked, id])]; this.closed = this.closed.filter((other) => other !== id); this.remember() },
        close(id) { this.closed = [...new Set([...this.closed, id])]; this.picked = this.picked.filter((other) => other !== id); this.remember() },
        get none() { return ! @js(array_keys($procedures['all'])).some((id) => this.shown(id)) },
    }">
    <div class="rounded-lg bg-slate-800/[0.03] p-5 ring-1 ring-slate-300 ring-inset md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="procedures-heading" class="text-base font-semibold text-slate-900">Interne Abläufe <span class="font-normal text-slate-600">– intern, nicht an den Kunden</span></h2>
        </div>

        <div class="mt-4 space-y-3">
            @foreach ($procedures['all'] as $id => $procedure)
                @php($position = array_search($id, $suggested, true))
                <details x-show="shown(@js($id))" @if (! in_array($id, $suggested, true)) x-cloak @endif
                         @if ($position === 0 || ($position !== false && count($suggested) < 3)) open @endif
                         class="group/procedure rounded-md bg-white shadow-1 ring-1 ring-slate-200">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 p-4 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                        <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open/procedure:rotate-90" />
                        <span class="font-mono text-xs text-slate-600">{{ $id }}</span>
                        <span class="font-semibold text-slate-900">{{ $procedure['title'] }}</span>
                        @foreach ($procedure['actions'] as $label)<x-badge compact>{{ $label }}</x-badge>@endforeach
                        @if ($procedure['draft'])<x-badge compact tone="warning">Entwurf</x-badge>@endif
                        <button type="button" x-on:click.prevent="close(@js($id))" class="ml-auto text-xs font-semibold text-slate-600 hover:text-slate-900">Schließen</button>
                    </summary>
                    <div class="border-t border-slate-100 p-4">
                        @if ($procedure['draft'])
                            <x-alert type="warning" class="mb-4">Dieser Ablauf ist noch ein Entwurf. Wenn dir etwas falsch vorkommt, melde es als Wissenslücke.</x-alert>
                        @endif
                        <x-knowledge.text :html="$procedure['html']" />
                    </div>
                </details>
            @endforeach

            <div x-show="none" @if ($suggested !== []) x-cloak @endif class="text-sm text-slate-600">
                <p>Kein passender Ablauf hinterlegt.</p>
                <button type="button" class="mt-1 text-sm font-semibold text-brand hover:text-brand-hover"
                        x-on:click="$dispatch('report-gap', { topic: '', missing: @js('Kein Arbeitsablauf für '.($missingActions !== '' ? $missingActions : 'diesen Fall')) })">Wissenslücke melden</button>
            </div>
        </div>

        @if ($procedures['all'] !== [])
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
                                            <span class="font-mono text-xs">{{ $entry['id'] }}</span> {{ $procedures['all'][$entry['id']]['title'] }}
                                        </button>
                                        @if ($procedures['all'][$entry['id']]['draft'])<x-badge compact tone="warning">Entwurf</x-badge>@endif
                                        @unless ($entry['fits'])<span class="text-xs text-warning-700">gilt nicht für diese Kundengruppe</span>@endunless
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
