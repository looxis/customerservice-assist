<x-layouts.app title="Beschriftung" width="6xl">
    <x-labeling.nav :$preview />

    @if ($categories->sum(fn (array $row): int => $row['open'] + $row['confirmed'] + $row['corrected'] + $row['automatic']) === 0)
        <x-card>
            <p class="text-sm text-slate-600">Noch keine Tickets erfasst. Starte unten den Abruf, danach die Klassifikation.</p>
        </x-card>
    @else
        <section aria-label="Kategorien" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($categories as $row)
                <x-labeling.category-card :$row />
            @endforeach
        </section>
    @endif

    <x-card class="space-y-5">
        <h2 class="text-base font-semibold text-slate-900">Läufe</h2>

        <form method="POST" action="{{ route('labeling.runs.store') }}" x-data="{ kind: 'abruf', dry: true, busy: false }" x-on:submit="busy = true" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select name="kind" label="Lauf" x-model="kind">
                    <option value="abruf">Abruf aller Tickets</option>
                    <option value="klassifikation">Klassifikation</option>
                    <option value="neu-klassifizieren">Neu klassifizieren (Tickets ohne Tag)</option>
                    <option value="tags">Tags schreiben</option>
                </x-select>

                <x-input name="excluded_groups" label="Gruppen vom Pool ausnehmen" hint="Kommagetrennt, z. B. Spam. Leer heißt: alle Gruppen." x-show="kind === 'abruf'" />
            </div>

            <label x-show="kind === 'tags'" x-cloak class="flex items-center gap-2 text-sm text-slate-900">
                <input type="checkbox" name="dry_run" value="1" x-model="dry" class="size-4 rounded border-slate-300 text-brand focus:ring-brand">
                Trockenlauf: zeigt nur, welche Tags geschrieben würden
            </label>
            <p x-show="kind === 'tags' && ! dry" x-cloak class="text-sm text-warning-700">Schreibt die Tags wirklich nach Zammad, mit deinem Zammad-Token.</p>

            <div class="flex flex-wrap items-center gap-3">
                <x-button type="submit" x-bind:disabled="busy">Starten</x-button>
                <span x-show="busy" x-cloak role="status" class="text-sm text-slate-600">Wird gestartet …</span>
            </div>
        </form>

        @if (count($runs) === 0)
            <p class="text-sm text-slate-600">Noch kein Lauf gestartet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th scope="col" class="py-2 pr-4">Lauf</th>
                            <th scope="col" class="py-2 pr-4">Start</th>
                            <th scope="col" class="py-2 pr-4">Stand</th>
                            <th scope="col" class="py-2 pr-4">Zähler</th>
                            <th scope="col" class="py-2">Admin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($runs as $run)
                            <tr>
                                <td class="py-2 pr-4 text-slate-900">{{ $run['kind'] }}</td>
                                <td class="py-2 pr-4 text-slate-900">{{ $run['started'] }}</td>
                                <td class="py-2 pr-4"><x-badge compact :tone="$run['state'] === 'läuft' ? 'info' : 'success'">{{ ucfirst($run['state']) }}</x-badge></td>
                                <td class="py-2 pr-4 text-slate-900">{{ $run['counter'] }}</td>
                                <td class="py-2 text-slate-900">{{ $run['admin'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-collapsible title="Einstellungen">
        <form method="POST" action="{{ route('labeling.settings.update') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            @method('PATCH')
            <x-input name="target" type="number" label="Zielwert je Kategorie" hint="Bestätigte oder getaggte Tickets" value="100" min="1" />
            <x-input name="min_cases" type="number" label="Stichprobenfälle für die Freigabe" value="50" min="1" />
            <x-input name="min_rate" type="number" label="Nötige Trefferquote (%)" value="95" min="50" max="100" />
            <x-input name="sample_share" type="number" label="Stichprobenanteil (%)" hint="Anteil der eindeutigen Vorschläge, den du prüfst" value="15" min="1" max="100" />
            <x-input name="confidence" type="number" step="0.01" label="Konfidenz-Schwelle" hint="Darunter gilt ein Vorschlag nicht als eindeutig" value="0.85" min="0" max="1" />
            <x-input name="examples" type="number" label="Beispiele je Kategorie im Prompt" value="8" min="0" max="20" />
            <div class="sm:col-span-2">
                <x-button type="submit" variant="secondary">Speichern</x-button>
            </div>
        </form>
    </x-collapsible>
</x-layouts.app>
