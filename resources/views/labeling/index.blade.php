<x-layouts.app title="Beschriftung" width="6xl">
    @if (collect($runs)->contains('active', true))
        <div x-data x-init="setInterval(() => { if (! document.activeElement?.closest('input, select, textarea')) { location.reload() } }, 10000)"></div>
    @endif
    <x-labeling.nav />

    <x-collapsible title="Ablauf und Hinweise" :open="$captured === 0" id="ablauf-hinweis">
        <div class="space-y-2 text-sm text-slate-900">
            <p>Du musst vorab nichts in Zammad klassifizieren. Bereits vorhandene <span class="font-mono">train:</span>-Tags werden beim Abruf automatisch als Referenz übernommen.</p>
            <ol class="list-decimal space-y-1 pl-5">
                <li><strong>Abruf aller Tickets</strong> starten (unten unter „Läufe“). Das kostet nur Zammad-Aufrufe, keine KI.</li>
                <li><strong>Klassifikation</strong> starten. Das Modell schlägt je Ticket eine Kategorie vor. Das kostet KI-Aufrufe und lässt sich mit „Abbrechen“ stoppen.</li>
                <li>Unter <strong>Prüfen</strong> Vorschläge bestätigen oder korrigieren, zuerst die Zweifelsfälle, dazwischen Stichproben. Bestätigte Tickets werden automatisch als Beispiele im Prompt genutzt.</li>
                <li>Zuletzt <strong>Tags schreiben</strong>, zuerst als Trockenlauf, danach wirklich nach Zammad.</li>
            </ol>
            <p>Der Abruf holt alle Tickets ab dem Stichtag <span class="font-mono">LABELING_EARLIEST</span> (Standard 2019-01-01). Für einen kleinen Test setze den Stichtag auf die letzten Wochen.</p>
        </div>
    </x-collapsible>

    @if ($captured === 0)
        <x-card>
            <p class="text-sm text-slate-600">Noch keine Tickets erfasst. Starte unten den Abruf, danach die Klassifikation.</p>
        </x-card>
    @else
        <section aria-label="Kategorien" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($categories as $row)
                <x-labeling.category-card :$row :min-cases="$settings['min_cases']" :min-rate="$settings['min_rate']" :target="$settings['target']" />
            @endforeach
        </section>

        @if ($references['total'] > 0)
            <p class="text-sm text-slate-600">Referenz: {{ $references['hits'] }} von {{ $references['total'] }} Tickets, die schon vor der Beschriftung ein <span class="font-mono">train:</span>-Tag hatten, hat das Modell richtig eingeordnet.</p>
        @endif
    @endif

    <x-card class="space-y-5">
        <h2 class="text-base font-semibold text-slate-900">Läufe</h2>

        <form method="POST" action="{{ route('labeling.runs.store') }}" x-data="{ kind: 'fetch', dry: true, busy: false }" x-on:submit="busy = true" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select name="kind" label="Lauf" x-model="kind">
                    <option value="fetch">Abruf aller Tickets</option>
                    <option value="classify">Klassifikation</option>
                    <option value="reclassify">Neu klassifizieren (Tickets ohne Tag)</option>
                    <option value="tags">Tags schreiben</option>
                </x-select>

                <x-input name="excluded_groups" label="Gruppen vom Pool ausnehmen" hint="Kommagetrennt, z. B. Spam. Leer heißt: alle Gruppen." :value="$settings['excluded_groups']" x-show="kind === 'fetch'" />
            </div>

            <label x-show="kind === 'tags'" x-cloak class="flex items-center gap-2 text-sm text-slate-900">
                <input type="checkbox" name="dry_run" value="1" x-model="dry" x-bind:disabled="kind !== 'tags'" class="size-4 rounded border-slate-300 text-brand focus:ring-brand">
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
                            <th scope="col" class="py-2 pr-4">Admin</th>
                            <th scope="col" class="py-2"><span class="sr-only">Aktion</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($runs as $run)
                            <tr>
                                <td class="py-2 pr-4 text-slate-900">{{ $run['kind'] }}</td>
                                <td class="py-2 pr-4 text-slate-900">{{ $run['started'] }}</td>
                                <td class="py-2 pr-4"><x-badge compact :tone="match ($run['state']) { 'läuft' => 'info', 'fertig' => 'success', 'abgebrochen' => 'neutral', default => 'danger' }">{{ ucfirst($run['state']) }}</x-badge></td>
                                <td class="py-2 pr-4 text-slate-900">{{ $run['counter'] }}</td>
                                <td class="py-2 pr-4 text-slate-900">{{ $run['admin'] }}</td>
                                <td class="py-2 text-right">
                                    @if ($run['active'])
                                        <form method="POST" action="{{ route('labeling.runs.cancel', ['run' => $run['id']]) }}">
                                            @csrf
                                            <x-button type="submit" variant="secondary">Abbrechen</x-button>
                                        </form>
                                    @endif
                                </td>
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
            <x-input name="target" type="number" label="Zielwert je Kategorie" hint="Bestätigte oder getaggte Tickets" :value="$settings['target']" min="1" />
            <x-input name="min_cases" type="number" label="Stichprobenfälle für die Freigabe" :value="$settings['min_cases']" min="1" />
            <x-input name="min_rate" type="number" label="Nötige Trefferquote (%)" :value="$settings['min_rate']" min="50" max="100" />
            <x-input name="sample_share" type="number" label="Stichprobenanteil (%)" hint="Anteil der eindeutigen Vorschläge, den du prüfst" :value="$settings['sample_share']" min="1" max="100" />
            <x-input name="confidence" type="number" step="0.01" label="Konfidenz-Schwelle" hint="Darunter gilt ein Vorschlag nicht als eindeutig" :value="$settings['confidence']" min="0" max="1" />
            <x-input name="examples" type="number" label="Beispiele je Kategorie im Prompt" :value="$settings['examples']" min="0" max="20" />
            <input type="hidden" name="excluded_groups" value="{{ $settings['excluded_groups'] }}">
            <div class="sm:col-span-2">
                <x-button type="submit" variant="secondary">Speichern</x-button>
            </div>
        </form>
    </x-collapsible>
</x-layouts.app>
