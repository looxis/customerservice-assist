@props(['row', 'minCases' => 50, 'minRate' => 95, 'target' => 100, 'testTarget' => 50, 'model' => null])

@php
    $category = $row['category'];
    $done = $row['confirmed'] + $row['corrected'] + $row['automatic'];
    $rate = $row['sampled'] > 0 ? round($row['hits'] / $row['sampled'] * 100) : null;
    $enough = $row['sampled'] >= $minCases;
    $reaches = $rate !== null && $rate >= $minRate;
    $progress = min(100, (int) round($done / $target * 100));
    $canRelease = ! $row['released'] && $enough && $reaches;
@endphp

<x-card class="space-y-4 p-5">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
            <h2 class="text-base font-semibold text-slate-900">{{ $category->label() }}</h2>
            <p class="font-mono text-xs text-slate-600">{{ $category->tag() }}</p>
        </div>
        @if ($row['locked'])
            <x-badge tone="danger">Gesperrt</x-badge>
        @elseif ($row['released'])
            <x-badge tone="success">Freigegeben</x-badge>
        @else
            <x-badge>Nicht freigegeben</x-badge>
        @endif
    </div>

    <div>
        <div class="flex items-baseline justify-between text-sm">
            <span class="text-slate-600">Getaggt oder bestätigt</span>
            <span class="font-semibold text-slate-900">{{ $done }} / {{ $target }}</span>
        </div>
        <progress class="mt-1 block h-2 w-full accent-brand" max="{{ $target }}" value="{{ min($done, $target) }}" aria-label="Zielwert {{ $category->label() }}">{{ $progress }} %</progress>
    </div>

    <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
        <dt class="text-slate-600">Bestätigt</dt><dd class="text-right text-slate-900">{{ $row['confirmed'] }}</dd>
        <dt class="text-slate-600">Korrigiert</dt><dd class="text-right text-slate-900">{{ $row['corrected'] }}</dd>
        <dt class="text-slate-600">Automatisch</dt><dd class="text-right text-slate-900">{{ $row['automatic'] }}</dd>
        <dt class="text-slate-600">Offen</dt><dd class="text-right text-slate-900">{{ $row['open'] }}</dd>
        <dt class="text-slate-600" title="Von dir als unsicher markiert, nach dem Vorschlag der KI">Unsicher</dt><dd class="text-right text-slate-900">{{ $row['unsure'] }}</dd>
        <dt class="text-slate-600" title="Entschiedene Testtickets; ab dem Testziel erscheinen Testtickets mit diesem Vorschlag nicht mehr unter „Prüfen“">Testmenge</dt><dd @class(['text-right', 'text-success-700' => $row['tested'] >= $testTarget, 'text-slate-900' => $row['tested'] < $testTarget])>{{ $row['tested'] }} / {{ $testTarget }}</dd>
    </dl>

    <p class="text-sm text-slate-600">
        @if ($rate === null)
            Noch keine Stichprobe{{ $model ? ' mit '.$model : '' }} geprüft.
        @else
            Trefferquote der Stichprobe{{ $model ? ' mit '.$model : '' }}: <span @class(['font-semibold', 'text-success-700' => $reaches, 'text-danger-700' => ! $reaches])>{{ $rate }} %</span> bei {{ $row['sampled'] }} Fällen (nötig: {{ $minRate }} % bei mindestens {{ $minCases }}).
        @endif
    </p>

    @if ($row['locked'])
        <x-alert type="error">Die Quote der letzten Stichproben lag unter {{ $minRate }} %. Es werden keine Tags mehr automatisch geschrieben, bis neue Stichproben wieder reichen.</x-alert>
    @endif

    @if (! $row['released'])
        <form method="POST" action="{{ route('labeling.categories.release', ['category' => $category->value]) }}">
            @csrf
            <x-button type="submit" :disabled="! $canRelease">Freigeben</x-button>
            @unless ($canRelease)
                <p class="mt-1 text-xs text-slate-600">{{ $enough ? 'Die Trefferquote reicht noch nicht.' : 'Es fehlen geprüfte Stichprobenfälle.' }}</p>
            @endunless
        </form>
    @endif
</x-card>
