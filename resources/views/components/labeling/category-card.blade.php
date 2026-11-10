@props(['row', 'minCases' => 50, 'minRate' => 95, 'target' => 100, 'testTarget' => 50, 'model' => null])

@php
    $category = $row['category'];
    $done = $row['confirmed'] + $row['corrected'] + $row['automatic'];
    $rate = $row['sampled'] > 0 ? round($row['hits'] / $row['sampled'] * 100) : null;
    $enough = $row['sampled'] >= $minCases;
    $reaches = $rate !== null && $rate >= $minRate;
    $progress = min(100, (int) round($done / $target * 100));
    $canRelease = ! $row['released'] && $enough && $reaches;
    // Outside a release the samples only fill the missing training tickets, so they are no need of their own.
    $amount = fn (int $count, string $one, string $many): string => $count.' '.($count === 1 ? $one : $many);
    $missing = array_filter([
        $row['needs']['training'] > 0 ? $amount($row['needs']['training'], 'Trainingsticket', 'Trainingstickets') : null,
        $row['needs']['test'] > 0 ? $amount($row['needs']['test'], 'Testticket', 'Testtickets') : null,
        $row['released'] && $row['needs']['samples'] > 0 ? $amount($row['needs']['samples'], 'Stichprobe', 'Stichproben') : null,
    ]);
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
            <x-help-tip class="text-slate-600" text="Summe aus Bestätigt, Korrigiert und Automatisch, gemessen am Zielwert je Kategorie aus den Einstellungen. Offene und unsichere Tickets zählen nicht mit. Ist der Zielwert erreicht, hat die Kategorie genug Trainingsdaten.">Getaggt oder bestätigt</x-help-tip>
            <span class="font-semibold text-slate-900">{{ $done }} / {{ $target }}</span>
        </div>
        <progress class="mt-1 block h-2 w-full accent-brand" max="{{ $target }}" value="{{ min($done, $target) }}" aria-label="Zielwert {{ $category->label() }}">{{ $progress }} %</progress>
    </div>

    <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
        <dt class="text-slate-600"><x-help-tip text="Du hast diese Tickets unter „Prüfen“ dieser Kategorie zugeordnet, und die KI hatte dieselbe Kategorie vorgeschlagen. Beim Lauf „Tags schreiben“ erhalten sie das Tag der Kategorie. Geprüfte Trainingstickets nutzt die App außerdem als Beispiele im Prompt. Tickets, die schon vor dem Abruf ein train:-Tag hatten, zählen hier nicht mit, sondern als Referenz.">Bestätigt</x-help-tip></dt><dd class="text-right text-slate-900">{{ $row['confirmed'] }}</dd>
        <dt class="text-slate-600"><x-help-tip text="Du hast diese Tickets dieser Kategorie zugeordnet, obwohl die KI eine andere vorgeschlagen hatte. Sie zählen bei der Kategorie, die du gewählt hast, und erhalten deren Tag. Korrekturen zeigen, wo der Prompt noch nicht trifft, und kommen bei den Beispielen im Prompt zuerst dran.">Korrigiert</x-help-tip></dt><dd class="text-right text-slate-900">{{ $row['corrected'] }}</dd>
        <dt class="text-slate-600"><x-help-tip text="Ohne deine Prüfung getaggt: Die Kategorie war freigegeben und die KI hatte das Ticket eindeutig hier eingeordnet. Das geschieht erst beim Lauf „Tags schreiben“, nicht im Trockenlauf. Testtickets werden nie automatisch getaggt.">Automatisch</x-help-tip></dt><dd class="text-right text-slate-900">{{ $row['automatic'] }}</dd>
        <dt class="text-slate-600"><x-help-tip text="Die KI hat diese Tickets hier eingeordnet, es gibt aber noch keine Entscheidung. Zweifelsfälle, Stichproben und Testtickets warten unter „Prüfen“. Eindeutige Trainingstickets bleiben offen, bis die Kategorie freigegeben ist; dann taggt sie der nächste Lauf „Tags schreiben“ automatisch.">Offen</x-help-tip></dt><dd class="text-right text-slate-900">{{ $row['open'] }}</dd>
        <dt class="text-slate-600"><x-help-tip text="Du hast diese Tickets unter „Prüfen“ als „Unsicher, nicht verwenden“ markiert. Sie zählen bei der Kategorie, die die KI vorgeschlagen hatte. Sie erhalten kein Tag und gehen weder in die Trainings- noch in die Testdaten; ein schon geschriebenes Tag entfernt der nächste Lauf „Tags schreiben“. Wiederfinden kannst du sie unter „Prüfen“ in „Unsicher markiert“.">Unsicher</x-help-tip></dt><dd class="text-right text-slate-900">{{ $row['unsure'] }}</dd>
        <dt class="text-slate-600"><x-help-tip text="Entschiedene Testtickets dieser Kategorie im Verhältnis zum Testziel. Rund 20 % der Tickets kommen beim Abruf fest in die Testmenge, damit sich die Qualität später unabhängig vom Training messen lässt. Du entscheidest sie ohne sichtbaren Vorschlag, sie erhalten das Tag test: statt train: und werden nie automatisch getaggt oder als Beispiel genutzt. Ist das Testziel erreicht, erscheinen Testtickets mit diesem Vorschlag nicht mehr unter „Prüfen“.">Testmenge</x-help-tip></dt><dd @class(['text-right', 'text-success-700' => $row['tested'] >= $testTarget, 'text-slate-900' => $row['tested'] < $testTarget])>{{ $row['tested'] }} / {{ $testTarget }}</dd>
    </dl>

    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-md bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200">
        <div class="flex flex-wrap items-center gap-x-2">
            <x-help-tip class="text-slate-600" text="Was dieser Kategorie noch fehlt: Trainingstickets bis zum Zielwert, entschiedene Testtickets bis zum Testziel und, wenn sie freigegeben ist, Stichproben mit dem aktuellen Modell bis zur nötigen Zahl. Bei freigegebenen Kategorien zählen die eindeutigen Vorschläge schon mit, die der nächste Lauf „Tags schreiben“ taggt. Sonst füllen Stichproben die fehlenden Trainingstickets. „Prüfen“ zeigt nur die nötigen Tickets dieser Kategorie.">Noch nötig</x-help-tip>
            <span @class(['font-semibold', 'text-slate-900' => $missing !== [], 'text-success-700' => $missing === []])>{{ $missing === [] ? 'nichts' : implode(' · ', $missing) }}</span>
        </div>
        <a href="{{ route('labeling.review', array_filter(['kategorie' => $category->value, 'bereich' => $missing === [] ? \App\Labeling\ReviewQueue::OPTIONAL : null])) }}" class="font-medium text-brand hover:text-brand-hover">{{ $missing === [] ? 'Bei Bedarf prüfen' : 'Prüfen' }}</a>
    </div>

    <p class="text-sm text-slate-600">
        @if ($rate === null)
            Noch keine Stichprobe{{ $model ? ' mit '.$model : '' }} geprüft.
        @else
            Trefferquote der Stichprobe{{ $model ? ' mit '.$model : '' }}: <span @class(['font-semibold', 'text-success-700' => $reaches, 'text-danger-700' => ! $reaches])>{{ $rate }} %</span> bei {{ $row['sampled'] }} Fällen (nötig: {{ $minRate }} % bei mindestens {{ $minCases }}).
        @endif
    </p>

    @if ($row['locked'] && $canRelease)
        <x-alert type="info">Die Stichproben nach der Sperre reichen wieder. Die Sperre bleibt, bis du sie mit „Freigeben“ aufhebst.</x-alert>
    @elseif ($row['locked'])
        <x-alert type="error">Die Quote der letzten Stichproben lag unter {{ $minRate }} %. Es werden keine Tags mehr automatisch geschrieben, bis neue Stichproben wieder reichen. Es zählen nur Vorschläge, die nach der Sperre entstanden sind, etwa nach „Neu klassifizieren“ dieser Kategorie mit geschärftem Prompt.</x-alert>
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
