@php
    $errors_ = $library->errors();
    $warnings = $library->warnings();
    $state = $library->state();
    $filtered = $filters !== [];
@endphp

<x-layouts.app title="Knowledge" width="6xl">
    @if ($errors->any())
        <x-alert type="warning">{{ $errors->first() }} Die Filter wurden zurückgesetzt.</x-alert>
    @endif

    <x-card>
        <dl class="grid grid-cols-2 gap-6 md:grid-cols-4">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Dokumente</dt>
                <dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $counts['total'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Verwendbar</dt>
                <dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $counts['usable'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Entwurf</dt>
                <dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $counts['draft'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Aktiv</dt>
                <dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $counts['active'] }}</dd>
            </div>
        </dl>
        <p class="mt-6 border-t border-slate-100 pt-4 text-sm text-slate-600">
            Wissensstand: <span class="font-mono text-slate-900">{{ $state->label() }}</span>
        </p>
    </x-card>

    @if ($counts['total'] === 0)
        <x-alert type="warning">
            Es ist kein Unternehmenswissen verfügbar: {{ $library->issues()->first()?->message ?? 'Der Knowledge-Ordner enthält keine Dokumente.' }}
        </x-alert>
    @else
        <x-card>
            <form method="GET" action="{{ route('knowledge.index') }}" class="grid gap-4 md:grid-cols-[2fr_1fr_1fr_auto] md:items-end">
                <x-input name="q" type="search" label="Suche" placeholder="ID oder Titel" :value="$filters['q'] ?? ''" maxlength="100" />

                <x-select name="type" label="Typ">
                    <option value="">Alle Typen</option>
                    @foreach ($types as $type => $definition)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ $definition['label'] }}</option>
                    @endforeach
                </x-select>

                <x-select name="status" label="Status">
                    <option value="">Jeder Status</option>
                    <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Entwurf</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktiv</option>
                    <option value="deprecated" @selected(($filters['status'] ?? '') === 'deprecated')>Veraltet</option>
                </x-select>

                <div class="flex items-center gap-3">
                    <x-button type="submit"><x-icon name="search" /> Filtern</x-button>
                    @if ($filtered)
                        <a href="{{ route('knowledge.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Zurücksetzen</a>
                    @endif
                </div>

                <label class="flex items-center gap-2 text-sm font-medium text-slate-900 md:col-span-4">
                    <input type="checkbox" name="issues" value="1" @checked(isset($filters['issues'])) class="size-4 rounded-sm accent-brand focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    Nur Dokumente mit Meldungen
                </label>
            </form>
        </x-card>

        @forelse ($groups as $type => $documents)
            <section class="overflow-hidden rounded-lg bg-white shadow-1 ring-1 ring-slate-200" aria-labelledby="group-{{ $type ?: 'ohne' }}">
                <h2 id="group-{{ $type ?: 'ohne' }}" class="flex items-center gap-3 border-b border-slate-100 px-6 py-4 text-base font-semibold text-slate-900">
                    {{ $types[$type]['label'] ?? 'Nicht zugeordnet' }}
                    <x-badge compact>{{ $documents->count() }}</x-badge>
                </h2>

                <ul class="divide-y divide-slate-100">
                    @foreach ($documents as $document)
                        @php
                            $issues = $issuesByPath[$document->path] ?? collect();
                            $errorCount = $issues->filter->isError()->count();
                            $warningCount = $issues->count() - $errorCount;
                        @endphp

                        <li>
                            <a
                                href="{{ route('knowledge.show', ['path' => $document->path]) }}"
                                @class([
                                    'block px-6 py-4 hover:bg-slate-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand',
                                    'opacity-60' => $document->status === 'deprecated',
                                ])
                            >
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                    @if ($document->id)
                                        <span class="font-mono text-sm text-slate-600">{{ $document->id }}</span>
                                    @endif

                                    <span class="min-w-0 flex-1 truncate text-sm font-semibold text-brand">
                                        {{ $document->title ?? $document->path }}
                                    </span>

                                    @if ($errorCount > 0)
                                        <x-badge tone="danger">Wird nicht verwendet</x-badge>
                                    @elseif ($document->status === 'deprecated')
                                        <x-badge>Wird nicht verwendet</x-badge>
                                    @endif

                                    @if ($warningCount > 0)
                                        <x-badge tone="warning">{{ $warningCount }} {{ $warningCount === 1 ? 'Warnung' : 'Warnungen' }}</x-badge>
                                    @endif

                                    @if ($document->parsed)
                                        <x-knowledge.status-badge :status="$document->status" />
                                    @endif
                                </div>

                                @if ($document->parsed)
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                        <x-knowledge.scope label="Kundenart" :values="$document->customerTypes()" />
                                        <x-knowledge.scope label="Kanal" :values="$document->salesChannels()" />
                                        <x-knowledge.scope label="Kategorie" :values="$document->categories()" />
                                        <x-knowledge.scope label="Produkt" :values="$document->products()" />
                                    </div>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <x-card class="text-center">
                <p class="text-sm font-semibold text-slate-900">Keine Dokumente gefunden</p>
                <p class="mt-1 text-sm text-slate-600">Zu Suche und Filter passt kein Dokument.</p>
                <div class="mt-4">
                    <x-button variant="secondary" :href="route('knowledge.index')">Filter zurücksetzen</x-button>
                </div>
            </x-card>
        @endforelse
    @endif

    <p class="pt-2 text-xs font-medium uppercase tracking-wide text-slate-400">Für die Pflege der Knowledge Base</p>

    <x-collapsible title="Prüfergebnis" :open="$errors_->isNotEmpty()">
        <x-slot:meta>
            @if ($errors_->isEmpty() && $warnings->isEmpty())
                <x-badge tone="success">Keine Fehler, keine Warnungen</x-badge>
            @else
                @if ($errors_->isNotEmpty())
                    <x-badge tone="danger">{{ $errors_->count() }} Fehler</x-badge>
                @endif
                @if ($warnings->isNotEmpty())
                    <x-badge tone="warning">{{ $warnings->count() }} {{ $warnings->count() === 1 ? 'Warnung' : 'Warnungen' }}</x-badge>
                @endif
            @endif
        </x-slot:meta>

        @if ($errors_->isEmpty() && $warnings->isEmpty())
            <p class="text-sm text-slate-600">Alle Knowledge-Dateien sind korrekt aufgebaut.</p>
        @else
            @if ($errors_->isNotEmpty())
                <p class="text-sm text-slate-600">Dokumente mit Fehlern werden von der App nicht verwendet.</p>
            @endif

            <ul class="mt-4 space-y-4">
                @foreach ($issuesByPath->sortKeys() as $path => $issues)
                    <li>
                        @if (in_array($path, $documentPaths, true))
                            <a href="{{ route('knowledge.show', ['path' => $path]) }}" class="font-mono text-sm font-medium text-brand hover:text-brand-hover">{{ $path }}</a>
                        @else
                            <span class="font-mono text-sm font-medium text-slate-900">{{ $path === '.' ? 'Knowledge Base' : $path }}</span>
                        @endif

                        <ul class="mt-2 space-y-2">
                            @foreach ($library->issuesFor($path) as $issue)
                                <li><x-alert :type="$issue->isError() ? 'error' : 'warning'" class="p-3">{{ $issue->severity->label() }}: {{ $issue->message }}</x-alert></li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-collapsible>

    <x-collapsible title="Für den KI-Chat">
        <p class="text-sm text-slate-600">
            Vergebene IDs, nächste freie ID je Typ und verwendete Schlagwörter. Diesen Block zu Beginn einer Sitzung in den Chat einfügen, zusammen mit dem Authoring Guide.
        </p>
        <x-copy-field class="mt-4" :text="$library->overview()" label="ID-Übersicht für den KI-Chat" />
    </x-collapsible>
</x-layouts.app>
