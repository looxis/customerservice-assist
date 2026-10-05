@php
    $hasErrors = $issues->contains->isError();
@endphp

<x-layouts.app :title="$document->id ?? 'Knowledge-Dokument'" width="5xl">
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">
        <x-icon name="chevron-left" size="16" /> Zur Übersicht
    </a>

    @if ($document->parsed && ! $hasErrors && $document->status === 'draft')
        <x-alert type="warning">
            Entwurfs-Wissen: Dieses Dokument ist noch nicht fachlich bestätigt. Die App verwendet es bereits und kennzeichnet es im Ergebnis. Wenn dir etwas unstimmig vorkommt, gib bitte Bescheid.
        </x-alert>
    @elseif ($document->status === 'deprecated')
        <x-alert>Veraltet: Dieses Dokument gilt nicht mehr und wird von der App nicht verwendet.</x-alert>
    @endif

    @if ($issues->isNotEmpty())
        <div class="space-y-2">
            @if ($hasErrors)
                <p class="text-sm font-semibold text-slate-900">Dieses Dokument wird von der App nicht verwendet, bis die Fehler behoben sind.</p>
            @endif
            @foreach ($issues as $issue)
                <x-alert :type="$issue->isError() ? 'error' : 'warning'">{{ $issue->severity->label() }}: {{ $issue->message }}</x-alert>
            @endforeach
        </div>
    @endif

    <x-card>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                @if ($document->id)
                    <p class="font-mono text-sm text-slate-600">{{ $document->id }}@if ($typeLabel) · {{ $typeLabel }}@endif</p>
                @endif
                <h2 class="mt-1 text-base font-semibold text-slate-900">{{ $document->title ?? 'Titel nicht lesbar' }}</h2>
            </div>
            @if ($document->parsed)
                <x-knowledge.status-badge :status="$document->status" />
            @endif
        </div>

        @if ($document->parsed)
            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                <x-knowledge.scope label="Kundenart" :values="$document->customerTypes()" />
                <x-knowledge.scope label="Kanal" :values="$document->salesChannels()" />
                <x-knowledge.scope label="Kategorie" :values="$document->categories()" />
                <x-knowledge.scope label="Produkt" :values="$document->products()" />
            </div>

            @if ($document->topics() !== [])
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-sm text-slate-400">Themen:</span>
                    @foreach ($document->topics() as $topic)
                        <x-badge compact>{{ $topic }}</x-badge>
                    @endforeach
                </div>
            @endif

            @if ($document->relatedKnowledge() !== [])
                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <span class="text-slate-400">Verweise:</span>
                    @foreach ($document->relatedKnowledge() as $reference)
                        @isset($links[$reference])
                            <a href="{{ $links[$reference] }}" class="font-mono font-medium text-brand hover:text-brand-hover">{{ $reference }}</a>
                        @else
                            <span class="font-mono text-slate-700">{{ $reference }}</span>
                        @endisset
                    @endforeach
                </div>
            @endif

            @if ($document->type === 'permission')
                <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-4 text-sm sm:grid-cols-2 md:grid-cols-4">
                    <div>
                        <dt class="text-slate-400">Maßnahme</dt>
                        <dd class="mt-1 font-mono text-slate-900">{{ implode(', ', $document->list('action')) ?: '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Kundenservice entscheidet selbst</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            {{ match ($document->frontmatter['agent_allowed'] ?? null) { true => 'Ja', false => 'Nein', default => '–' } }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Wertgrenze</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            @if (is_numeric($limit = $document->frontmatter['max_value_eur'] ?? null))
                                {{ number_format($limit, fmod((float) $limit, 1.0) === 0.0 ? 0 : 2, ',', '.') }} €
                            @else
                                keine Wertgrenze
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Freigabe durch</dt>
                        <dd class="mt-1 font-mono text-slate-900">{{ $document->string('approval_role') ?? '–' }}</dd>
                    </div>
                </dl>
            @endif
        @endif

        <p class="mt-6 border-t border-slate-100 pt-4 font-mono text-xs text-slate-600">
            {{ $document->path }} · Fingerabdruck {{ $document->shortFingerprint() }}
        </p>
    </x-card>

    @if ($html !== null && $document->body !== '')
        <x-card>
            <x-knowledge.text :html="$html" />
        </x-card>
    @endif
</x-layouts.app>
