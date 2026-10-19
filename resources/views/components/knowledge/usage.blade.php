@props(['usage', 'goal' => null])

{{-- What the document did in practice (PROJ-56): figures only, test runs and failed proposals left out. --}}
@php
    $outcomes = $usage['outcomes'];
    $taken = $outcomes['sent_unchanged'] + $outcomes['sent_changed'] + $outcomes['copied_unchanged'] + $outcomes['copied_changed'];
    $labels = ['sent_unchanged' => 'gesendet, unverändert', 'sent_changed' => 'gesendet, angepasst', 'copied_unchanged' => 'kopiert, unverändert', 'copied_changed' => 'kopiert, angepasst', 'edited' => 'nur bearbeitet', 'none' => 'nicht übernommen (oder ohne Antwortentwurf)'];
@endphp
<div class="mt-3 space-y-3 text-sm">
    @if ($goal)<p class="text-slate-700"><span class="font-medium text-slate-900">Ziel der Testphase:</span> {{ $goal }}</p>@endif
    <dl class="grid gap-3 sm:grid-cols-3">
        <div><dt class="text-slate-600">Von der KI genannt</dt><dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $usage['named'] }} <span class="text-sm font-normal text-slate-600">(davon {{ $usage['recent'] }} in den letzten 30 Tagen)</span></dd></div>
        <div>
            <dt class="text-slate-600">Bewertung: unverändert oder leicht angepasst</dt>
            <dd class="mt-1 font-display text-lg font-semibold text-slate-900">
                @if ($usage['usable_share'] === null) noch nicht bewertet @else {{ $usage['usable_share'] }} % <span class="text-sm font-normal text-slate-600">von {{ $usage['rated'] }} bewerteten</span> @endif
            </dd>
            @if ($usage['overall_share'] !== null)<dd class="text-xs text-slate-600">Alle Vorschläge zusammen: {{ $usage['overall_share'] }} %</dd>@endif
        </div>
        <div><dt class="text-slate-600">Meldungen „bitte prüfen“</dt><dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $usage['reports']['open'] }} <span class="text-sm font-normal text-slate-600">offen, {{ $usage['reports']['checked'] }} geprüft</span></dd></div>
    </dl>
    <p class="text-slate-700">
        <span class="font-medium text-slate-900">Was aus den Antwortentwürfen wurde:</span>
        {{ $taken }} übernommen
        @foreach ($labels as $key => $label)@if ($outcomes[$key] > 0)· {{ $outcomes[$key] }} {{ $label }} @endif @endforeach
    </p>
    @if ($usage['reports']['reasons'] !== [])
        <p class="text-slate-700"><span class="font-medium text-slate-900">Offene Meldungen:</span>
            @foreach ($usage['reports']['reasons'] as $reason => $count){{ $count }} × {{ \App\Knowledge\KnowledgeReviewLog::REASONS[$reason] ?? $reason }}@if (! $loop->last) · @endif @endforeach
        </p>
    @endif
    @if ($usage['named'] < \App\Knowledge\DocumentEvidence::THIN)
        <p class="text-warning-700">Bisher nur in {{ $usage['named'] }} {{ $usage['named'] === 1 ? 'Vorschlag' : 'Vorschlägen' }} genannt: eine dünne Grundlage für die Entscheidung.</p>
    @endif
    <p class="text-xs text-slate-600">Alle bisherigen Fassungen des Dokuments; ohne Testläufe und fehlgeschlagene Vorschläge. Ob ein Entwurf eine Antwort war, die der Kunde bekam, zeigt nur das Senden.</p>
</div>
