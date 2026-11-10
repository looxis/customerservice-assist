<x-layouts.app title="Ticket-Klassifikation: Tickets" width="6xl">
    <x-labeling.nav />

    <x-card>
        <form method="GET" action="{{ route('labeling.tickets') }}" class="flex flex-wrap items-end gap-4">
            <x-select name="kategorie" label="Vorschlag" class="min-w-52">
                <option value="">Alle</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected($filters['kategorie'] === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </x-select>
            <x-select name="zuordnung" label="Zuordnung" class="min-w-40">
                <option value="">Alle</option>
                <option value="training" @selected($filters['zuordnung'] === 'training')>Training</option>
                <option value="test" @selected($filters['zuordnung'] === 'test')>Testmenge</option>
            </x-select>
            <x-select name="quelle" label="Quelle" class="min-w-36">
                <option value="">Alle</option>
                <option value="pool" @selected($filters['quelle'] === 'pool')>Pool</option>
                <option value="eingang" @selected($filters['quelle'] === 'eingang')>Eingang</option>
                <option value="vorhanden" @selected($filters['quelle'] === 'vorhanden')>vorhanden</option>
            </x-select>
            <x-select name="entscheidung" label="Entscheidung" class="min-w-40">
                <option value="">Alle</option>
                <option value="offen" @selected($filters['entscheidung'] === 'offen')>Offen</option>
                <option value="unsicher" @selected($filters['entscheidung'] === 'unsicher')>Unsicher</option>
                <option value="mitarbeiter" @selected($filters['entscheidung'] === 'mitarbeiter')>Von Mitarbeitern</option>
                <option value="mitarbeiter-unsicher" @selected($filters['entscheidung'] === 'mitarbeiter-unsicher')>Mitarbeiter unsicher</option>
            </x-select>
            <x-button type="submit" variant="secondary">Filtern</x-button>
            @if (array_filter($filters))
                <a href="{{ route('labeling.tickets') }}" class="text-sm text-brand hover:text-brand-hover">Zurücksetzen</a>
            @endif
        </form>
    </x-card>

    <x-card class="p-0">
        @if ($rows->isEmpty())
            <p class="p-6 text-sm text-slate-600">Kein Ticket entspricht diesem Filter.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Ticket</th>
                            <th scope="col" class="px-4 py-3">Erste Kundennachricht</th>
                            <th scope="col" class="px-4 py-3">Vorschlag</th>
                            <th scope="col" class="px-4 py-3">Entscheidung</th>
                            <th scope="col" class="px-4 py-3">Tag</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ rtrim((string) config('services.zammad.url'), '/') }}/#ticket/zoom/{{ $row['zammad_id'] }}" target="_blank" rel="noopener" class="font-mono text-brand hover:text-brand-hover">Ticket#{{ $row['number'] }}</a>
                                    <span class="block text-xs text-slate-600">{{ $row['group'] }} · {{ $row['source'] }}</span>
                                </td>
                                <td class="max-w-md px-4 py-3 align-top text-slate-900">{{ \Illuminate\Support\Str::limit($row['excerpt'], 110) }}</td>
                                <td class="px-4 py-3 align-top">
                                    @if ($row['suggestion'])<x-badge compact tone="info">{{ $row['suggestion']->label() }}</x-badge>@else<x-badge compact>Kein Vorschlag</x-badge>@endif
                                    <span class="block text-xs text-slate-600">Konfidenz {{ (int) round($row['confidence'] * 100) }} %</span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if ($row['decision'])
                                        <x-badge compact :tone="$row['unsure'] ? 'warning' : 'success'">{{ $row['decision'] }}</x-badge>
                                        @if ($row['unsure'])<a href="{{ route('labeling.review', ['warteschlange' => 'unsicher']) }}" class="block text-xs text-brand hover:text-brand-hover">Erneut prüfen</a>@endif
                                        @if ($row['staff'])<span class="block text-xs text-slate-600">von {{ $row['staff'] }} (Mitarbeiter)</span>@endif
                                    @else
                                        <x-badge compact>Offen</x-badge>
                                    @endif
                                    @if ($row['staff_unsure'])<span class="block text-xs text-warning-700">Mitarbeiter unsicher: {{ $row['staff_unsure'] }}</span>@endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if ($row['tag'])
                                        <span class="font-mono text-xs text-slate-900">{{ $row['tag_value'] }}</span>
                                        <span class="block text-xs text-slate-600">{{ $row['tag'] }}</span>
                                    @else
                                        <span class="text-slate-600">–</span>
                                    @endif
                                    @if ($row['tag_problem'])<span class="block text-xs text-danger-700">{{ $row['tag_problem'] }}</span>@endif
                                    @if ($row['test'])<x-badge compact tone="warning">Test</x-badge>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{ $rows->links() }}
</x-layouts.app>
