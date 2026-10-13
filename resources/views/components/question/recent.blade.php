@props(['recent' => []])

{{-- The own latest questions of the chosen person (PROJ-40), newest first; never the questions of others. --}}
<x-card>
    <h2 class="text-base font-semibold text-slate-900">Meine letzten Fragen</h2>
    @if ($recent === [])
        <p class="mt-2 text-sm text-slate-600">Noch keine Fragen gestellt. Hier erscheinen deine letzten Fragen, damit du sie wieder öffnen kannst.</p>
    @else
        <ul class="mt-3 divide-y divide-slate-100 text-sm">
            @foreach ($recent as $entry)
                <li>
                    <a href="{{ route('questions.show', ['number' => $entry['number']]) }}" class="flex flex-wrap items-baseline gap-x-3 gap-y-1 py-2 hover:text-brand focus-visible:outline-2 focus-visible:outline-brand">
                        <span class="shrink-0 text-xs text-slate-600">{{ $entry['created_at']->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr</span>
                        <span class="min-w-0 truncate font-medium text-slate-900">{{ $entry['excerpt'] }}</span>
                        @if ($entry['has_reply'] ?? false)<x-badge compact tone="info">mit Antwortentwurf</x-badge>@endif
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
