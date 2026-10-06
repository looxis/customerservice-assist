@props(['result'])

@php
    $r = $result['result'];
    $meta = $result['meta'];
    $drafts = $result['draft_ids'] ?? [];
    $text = fn ($value) => \App\Analysis\AnalysisPanel::text($value);
    $assessment = ['berechtigt' => 'success', 'unberechtigt' => 'danger', 'unklar' => 'warning'];
    $confidence = ['HOCH' => 'success', 'MITTEL' => 'warning', 'NIEDRIG' => 'danger'];
@endphp

{{-- Analysis result (PROJ-9): every part, plainly. The working view follows with PROJ-10. --}}
<section id="ergebnis" aria-labelledby="result-heading" class="scroll-mt-4">
    <x-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="result-heading" class="text-base font-semibold text-slate-900">Ergebnis der Analyse</h2>
            <div class="flex flex-wrap gap-2">
                @if (($r['knowledge_gaps'] ?? []) !== [])<x-badge tone="warning">Wissenslücke</x-badge>@endif
                @if ($r['assessment'] ?? null)<x-badge :tone="$assessment[$r['assessment']] ?? 'neutral'">{{ ucfirst($r['assessment']) }}</x-badge>@endif
                <x-badge :tone="$confidence[$r['confidence']['level']] ?? 'neutral'">Confidence {{ $r['confidence']['level'] }}</x-badge>
            </div>
        </div>

        @if ($meta['test_until'] ?? null)
            <p class="mt-2 inline-flex rounded-md bg-warning-500/10 px-2 py-1 text-xs font-semibold text-warning-700">Testlauf (Stand bis Nachricht vom {{ \Carbon\CarbonImmutable::parse($meta['test_until'])->setTimezone(config('services.zammad.timezone', 'Europe/Berlin'))->format('d.m.Y, H:i') }})</p>
        @endif

        @if (($result['notes'] ?? []) !== [])
            <x-alert type="warning" class="mt-4">
                <p class="font-semibold">Hinweise zur Prüfung des Ergebnisses</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($result['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul>
            </x-alert>
        @endif

        @if (($meta['knowledge_fingerprints'] ?? null) === [])
            <x-alert type="warning" class="mt-4">
                <p class="font-semibold">Kein Wissen für diesen Fall</p>
                <p class="mt-1">Für die gewählte Kundengruppe und die Produkte gibt es kein passendes Wissen. Der Vorschlag beruht nur auf dem Ticket und ist entsprechend vorsichtig. Stimmen Kundengruppe und Produkte?</p>
            </x-alert>
        @endif

        @if (($meta['knowledge_warnings'] ?? []) !== [])
            <x-alert type="warning" class="mt-4">
                <p class="font-semibold">Hinweise zur Wissensauswahl</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($meta['knowledge_warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul>
            </x-alert>
        @endif

        @if (($r['knowledge_gaps'] ?? []) !== [])
            <x-alert type="warning" class="mt-4">
                <p class="font-semibold">Fehlendes Wissen</p>
                <p class="mt-1">Für diesen Fall fehlt eine Regel in der Wissensdatenbank. Der Vorschlag sagt dazu bewusst nichts zu. Bitte nicht nach Gefühl entscheiden, sondern nachfragen und die Lücke an Etienne melden.</p>
                <ul class="mt-2 space-y-2">
                    @foreach ($r['knowledge_gaps'] as $gap)
                        <li><span class="font-medium">{{ $gap['topic'] }}</span>@if ($gap['question'] ?? null)<br><span>Offene Frage: {{ $gap['question'] }}</span>@endif</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <dl class="mt-4 space-y-4 text-sm">
            <div><dt class="font-semibold text-slate-900">Was ist passiert?</dt><dd class="mt-1 text-slate-900">{{ $text($r['summary']['incident'] ?? '') }}</dd></div>
            <div><dt class="font-semibold text-slate-900">Was möchte der Kunde?</dt><dd class="mt-1 text-slate-900">{{ $text($r['summary']['customer_wish'] ?? '') }}</dd></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><dt class="font-semibold text-slate-900">Kategorie</dt><dd class="mt-1 text-slate-900">{{ $r['category'] ?? '–' }}</dd></div>
                <div><dt class="font-semibold text-slate-900">Fallmuster</dt><dd class="mt-1 text-slate-900">{{ $r['case_pattern'] ?? '–' }}</dd></div>
            </div>
            <div>
                <dt class="font-semibold text-slate-900">Empfohlene Maßnahme</dt>
                <dd class="mt-1 text-slate-900">{{ $text($r['recommendation']) }}</dd>
                @if ($r['actions'] !== [])
                    <dd class="mt-2 flex flex-wrap gap-2">@foreach ($r['actions'] as $action)<x-badge compact>{{ config("knowledge.actions.{$action}", $action) }}</x-badge>@endforeach</dd>
                @endif
            </div>
            <div>
                <dt class="font-semibold text-slate-900">Befugnis</dt>
                <dd class="mt-1 text-slate-900">
                    {{ ($r['authority']['agent_may_decide'] ?? false) ? 'Der Kundenservice darf selbst entscheiden.' : 'Freigabe erforderlich'.(($r['authority']['approval_by'] ?? null) ? ': '.$r['authority']['approval_by'] : '.') }}
                    @if ($r['authority']['permission_id'] ?? null)<span class="font-mono text-xs text-slate-600">({{ $r['authority']['permission_id'] }})</span>@endif
                </dd>
            </div>
            <div><dt class="font-semibold text-slate-900">Begründung</dt><dd class="mt-1 text-slate-900">{{ $text($r['reasoning']) }}</dd></div>
            @if ($r['missing_information'] !== [])
                <div>
                    <dt class="font-semibold text-slate-900">Fehlende Informationen</dt>
                    <dd class="mt-1">
                        <ul class="space-y-2">
                            @foreach ($r['missing_information'] as $missing)
                                <li class="rounded-md bg-warning-500/5 p-3 ring-1 ring-warning-500/20 ring-inset">
                                    <p class="text-slate-900">{{ $missing['what'] }} <span class="text-slate-600">– von {{ $missing['from'] }}</span></p>
                                    <p class="mt-1 text-slate-900"><span class="font-medium">Rückfrage:</span> {{ $missing['question'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </dd>
                </div>
            @endif
            <div>
                <dt class="font-semibold text-slate-900">Confidence {{ $r['confidence']['level'] }} – Gründe</dt>
                <dd class="mt-1"><ul class="list-disc pl-5 text-slate-900">@foreach ($r['confidence']['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul></dd>
            </div>
            @if ($r['internal_todos'] !== [])
                <div><dt class="font-semibold text-slate-900">Interne To-dos</dt><dd class="mt-1"><ul class="list-disc pl-5 text-slate-900">@foreach ($r['internal_todos'] as $todo)<li>{{ $todo }}</li>@endforeach</ul></dd></div>
            @endif
            <div>
                <dt class="font-semibold text-slate-900">Verwendetes Wissen</dt>
                <dd class="mt-1 flex flex-wrap gap-2">
                    @forelse ($r['knowledge_ids'] as $id)
                        <x-badge compact :tone="in_array($id, $drafts, true) ? 'warning' : 'neutral'">{{ $id }}@if (in_array($id, $drafts, true)) · Entwurf @endif</x-badge>
                    @empty
                        <span class="text-slate-600">keines</span>
                    @endforelse
                </dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-900">Antwortentwurf <span class="font-normal text-slate-600">({{ $r['reply']['language'] ?? '–' }})</span></dt>
                <dd class="mail-text mt-2 rounded-md bg-slate-50 p-4 ring-1 ring-slate-200">{{ $result['reply_html'] }}</dd>
            </div>
        </dl>

        <p class="mt-6 border-t border-slate-100 pt-3 font-mono text-xs text-slate-600">
            {{ $meta['staff'] }} · {{ \Carbon\CarbonImmutable::parse($meta['created_at'])->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr · {{ $meta['model'] }} · Prompt {{ $meta['prompt_version'] }} · Wissensstand {{ $meta['knowledge_state'] }} · {{ number_format($meta['duration_ms'] / 1000, 1, ',', '.') }} s
        </p>
    </x-card>
</section>
