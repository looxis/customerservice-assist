@props(['result', 'number'])

@php
    $r = $result['result'];
    $meta = $result['meta'];
    $text = fn ($value) => \App\Analysis\AnalysisPanel::text($value);
    $assessment = ['berechtigt' => 'success', 'unberechtigt' => 'danger', 'unklar' => 'warning'];
    $confidence = ['HOCH' => 'success', 'MITTEL' => 'warning', 'NIEDRIG' => 'danger'];
    $time = fn (string $value): string => \Carbon\CarbonImmutable::parse($value)->setTimezone('Europe/Berlin')->format('d.m.Y, H:i');
    $usedDrafts = collect($result['sources'])->where('draft', true)->pluck('id')->all();
    $gaps = $r['knowledge_gaps'] ?? [];
    $missing = $r['missing_information'] ?? [];
    $todos = $r['internal_todos'] ?? [];
    $notes = $result['notes'] ?? [];
    $mayDecide = $r['authority']['agent_may_decide'] ?? false;
@endphp

{{-- Analysis result (PROJ-10): what to do and the reply first, the reasoning
     in collapsible sections below. --}}
<section id="ergebnis" aria-labelledby="result-heading" class="scroll-mt-4">
    <x-card>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="result-heading" class="text-base font-semibold text-slate-900">Ergebnis der Analyse</h2>
                <p class="mt-1 text-sm text-slate-600">Analyse von {{ $meta['staff'] }}, {{ $time($meta['created_at']) }} Uhr</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($gaps !== [])<x-badge tone="warning">Wissenslücke</x-badge>@endif
                @if ($r['assessment'] ?? null)<x-badge :tone="$assessment[$r['assessment']] ?? 'neutral'">{{ ucfirst($r['assessment']) }}</x-badge>@endif
                <x-badge :tone="$confidence[$r['confidence']['level']] ?? 'neutral'">Confidence {{ $r['confidence']['level'] }}</x-badge>
            </div>
        </div>

        @if ($meta['test_until'] ?? null)
            <p class="mt-2 inline-flex rounded-md bg-warning-500/10 px-2 py-1 text-xs font-semibold text-warning-700">Testlauf (Stand bis Nachricht vom {{ $time($meta['test_until']) }})</p>
        @endif

        @if ($result['new_messages'])
            <x-alert type="warning" class="mt-4">
                <p>Seit dieser Analyse sind neue Nachrichten eingegangen.</p>
                <p class="mt-3">
                    <x-button variant="secondary" x-data x-on:click="$dispatch('open-analysis-form')"><x-icon name="refresh" size="16" /> Neu analysieren</x-button>
                </p>
            </x-alert>
        @endif

        {{-- What to do --}}
        <div class="mt-4 space-y-4 rounded-lg bg-slate-50 p-4 text-sm ring-1 ring-slate-200 ring-inset">
            <h3 class="font-semibold text-slate-900">Was ist zu tun?</h3>

            <div>
                <p class="text-slate-900">{{ $text($r['recommendation']) }}</p>
                @if (($r['actions'] ?? []) !== [])
                    <p class="mt-2 flex flex-wrap gap-2">@foreach ($r['actions'] as $action)<x-badge compact>{{ config("knowledge.actions.{$action}", $action) }}</x-badge>@endforeach</p>
                @endif
            </div>

            <p @class(['flex items-start gap-2 font-medium', 'text-success-700' => $mayDecide, 'text-warning-700' => ! $mayDecide])>
                <x-icon :name="$mayDecide ? 'check' : 'warning'" size="16" class="mt-0.5 shrink-0" />
                <span>
                    {{ $mayDecide ? 'Du darfst das selbst entscheiden.' : 'Freigabe nötig'.(($r['authority']['approval_by'] ?? null) ? ' durch '.$r['authority']['approval_by'] : '').'.' }}
                    @if ($r['authority']['permission_id'] ?? null)<span class="font-mono text-xs font-normal text-slate-600">({{ $r['authority']['permission_id'] }})</span>@endif
                </span>
            </p>

            @if ($missing !== [])
                <div>
                    <p class="font-semibold text-slate-900">Fehlende Informationen</p>
                    <ul class="mt-2 space-y-2">
                        @foreach ($missing as $item)
                            <li class="rounded-md bg-white p-3 ring-1 ring-warning-500/20 ring-inset">
                                <p class="text-slate-900">{{ $item['what'] }} <span class="text-slate-600">– von {{ $item['from'] }}</span></p>
                                <p class="mt-1 text-slate-900"><span class="font-medium">Rückfrage:</span> {{ $item['question'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($gaps !== [])
                <x-alert type="warning">
                    <p class="font-semibold">Fehlendes Wissen</p>
                    <p class="mt-1">Für diesen Fall fehlt eine Regel in der Wissensdatenbank. Der Vorschlag sagt dazu bewusst nichts zu. Bitte nicht nach Gefühl entscheiden, sondern nachfragen und die Lücke an Etienne melden.</p>
                    <ul class="mt-2 space-y-2">
                        @foreach ($gaps as $gap)
                            <li><span class="font-medium">{{ $gap['topic'] }}</span>@if ($gap['question'] ?? null)<br><span>Offene Frage: {{ $gap['question'] }}</span>@endif</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            @if (($meta['knowledge_fingerprints'] ?? null) === [])
                <x-alert type="warning">
                    <p class="font-semibold">Kein Wissen für diesen Fall</p>
                    <p class="mt-1">Für die gewählte Kundengruppe und die Produkte gibt es kein passendes Wissen. Der Vorschlag beruht nur auf dem Ticket und ist entsprechend vorsichtig. Stimmen Kundengruppe und Produkte?</p>
                </x-alert>
            @endif

            @if ($usedDrafts !== [])
                <p class="flex items-start gap-2 text-warning-700">
                    <x-icon name="warning" size="16" class="mt-0.5 shrink-0" />
                    <span>Beruht teilweise auf Entwurfs-Wissen – bitte kritisch prüfen: <span class="font-mono text-xs">{{ implode(', ', $usedDrafts) }}</span></span>
                </p>
            @endif

            @if ($notes !== [] || ($meta['knowledge_warnings'] ?? []) !== [])
                <p class="text-slate-600">Prüfhinweise vorhanden – siehe unten.</p>
            @endif
        </div>

        <x-analysis.reply-editor :result="$result" :number="$number" />

        {{-- Reasoning and details --}}
        <div class="mt-6">
            <x-analysis.section title="Begründung">
                <p>{{ $text($r['reasoning']) }}</p>
            </x-analysis.section>

            <x-analysis.section title="Quellen" :count="count($result['sources'])">
                @forelse ($result['sources'] as $source)
                    <details class="group/source border-b border-slate-100 last:border-b-0">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center gap-2 py-2 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                            <x-icon name="chevron-right" size="14" class="text-slate-400 transition group-open/source:rotate-90" />
                            <span class="font-mono text-xs text-slate-600">{{ $source['id'] }}</span>
                            <span class="font-medium">{{ $source['title'] ?? '–' }}</span>
                            @if ($source['type'])<x-badge compact>{{ $source['type'] }}</x-badge>@endif
                            @if ($source['draft'])<x-badge compact tone="warning">Entwurf</x-badge>@endif
                            @if ($source['state'] === 'changed')<x-badge compact tone="info">Seit der Analyse geändert</x-badge>@endif
                            @if ($source['state'] === 'removed')<x-badge compact tone="danger">nicht mehr vorhanden</x-badge>@endif
                        </summary>
                        <div class="pb-4 pl-6">
                            @if ($source['html'])
                                <x-knowledge.text :html="$source['html']" class="text-sm" />
                            @else
                                <p class="text-slate-600">Der Text aus der Zeit der Analyse ist nicht gespeichert.</p>
                            @endif
                            @if ($source['url'])
                                <p class="mt-3"><a href="{{ $source['url'] }}" class="font-semibold text-brand hover:text-brand-hover">In der Knowledge-Übersicht öffnen</a></p>
                            @endif
                        </div>
                    </details>
                @empty
                    <p class="text-slate-600">Der Vorschlag nennt keine Quellen.</p>
                @endforelse
            </x-analysis.section>

            <x-analysis.section title="Kurzfassung">
                <dl class="space-y-3">
                    <div><dt class="font-semibold">Was ist passiert?</dt><dd class="mt-1">{{ $text($r['summary']['incident'] ?? '') }}</dd></div>
                    <div><dt class="font-semibold">Was möchte der Kunde?</dt><dd class="mt-1">{{ $text($r['summary']['customer_wish'] ?? '') }}</dd></div>
                    <div><dt class="font-semibold">Kategorie</dt><dd class="mt-1">{{ $r['category'] ?? '–' }}</dd></div>
                    <div><dt class="font-semibold">Fallmuster</dt><dd class="mt-1">{{ $r['case_pattern'] ?? '–' }}</dd></div>
                </dl>
            </x-analysis.section>

            <x-analysis.section :title="'Confidence '.$r['confidence']['level'].' – Gründe'" :count="count($r['confidence']['reasons'] ?? [])">
                <ul class="list-disc pl-5">@foreach ($r['confidence']['reasons'] ?? [] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
            </x-analysis.section>

            <x-analysis.section title="Interne To-dos" :count="count($todos)">
                @if ($todos === [])
                    <p class="text-slate-600">Keine.</p>
                @else
                    <ul class="list-disc pl-5">@foreach ($todos as $todo)<li>{{ $todo }}</li>@endforeach</ul>
                @endif
            </x-analysis.section>

            @if ($notes !== [] || ($meta['knowledge_warnings'] ?? []) !== [])
                <x-analysis.section title="Prüfhinweise" :count="count($notes) + count($meta['knowledge_warnings'] ?? [])">
                    <ul class="list-disc pl-5">
                        @foreach ($notes as $note)<li>{{ $note }}</li>@endforeach
                        @foreach ($meta['knowledge_warnings'] ?? [] as $warning)<li>{{ $warning }}</li>@endforeach
                    </ul>
                </x-analysis.section>
            @endif

            <x-analysis.section title="Details zur Analyse">
                <p class="font-mono text-xs text-slate-600">
                    {{ $meta['staff'] }} · {{ $time($meta['created_at']) }} Uhr · {{ $meta['model'] }} · Prompt {{ $meta['prompt_version'] }} · Wissensstand {{ $meta['knowledge_state'] }} · {{ number_format($meta['duration_ms'] / 1000, 1, ',', '.') }} s
                </p>
            </x-analysis.section>
        </div>
    </x-card>
</section>
