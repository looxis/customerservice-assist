@php
    $hasErrors = $issues->contains->isError();
@endphp

<x-layouts.app :title="$document->id ?? 'Knowledge-Dokument'" width="5xl">
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">
        <x-icon name="chevron-left" size="16" /> Zur Übersicht
    </a>

    @if (session('review_reported') === $document->id)
        <x-alert type="success">Danke, die Meldung ist angekommen. Ein Admin schaut sich das Dokument an.</x-alert>
    @endif
    @if (session('review_error'))
        <x-alert type="error">{{ session('review_error') }}</x-alert>
    @endif
    @if ($document->parsed && $document->id && ($reportedMany ?? false))
        <x-alert type="warning">Mehrfach zur Prüfung gemeldet: Mitarbeiter halten dieses Wissen für möglicherweise veraltet. Ein Admin prüft es.</x-alert>
    @endif

    @if ($document->parsed && $document->isTrial())
        <x-alert :type="$document->trialExpired() ? 'danger' : 'info'">
            @if ($document->trialLapsed())
                Testphase abgelaufen und nicht entschieden: Dieses Vorgehen wurde nur bis {{ $document->trialUntil()->format('d.m.Y') }} getestet und wird seit {{ $document->trialOverdueDays() - (int) config('knowledge.trial.grace_days') }} {{ $document->trialOverdueDays() - (int) config('knowledge.trial.grace_days') === 1 ? 'Tag' : 'Tagen' }} nicht mehr verwendet. Ein Admin entscheidet, ob es bleibt, verlängert wird oder entfällt.
            @elseif ($document->trialExpired())
                Testphase abgelaufen, Überprüfung überfällig seit {{ $document->trialOverdueDays() }} {{ $document->trialOverdueDays() === 1 ? 'Tag' : 'Tagen' }}: Dieses Vorgehen wurde nur bis {{ $document->trialUntil()->format('d.m.Y') }} getestet. Es gilt noch {{ (int) config('knowledge.trial.grace_days') - $document->trialOverdueDays() + 1 }} Tage weiter und wird dann nicht mehr verwendet, wenn niemand entscheidet.
            @else
                Vorläufig: Dieses Vorgehen wird nur bis {{ $document->trialUntil()->format('d.m.Y') }} getestet.
            @endif
            @if ($document->trialGoal()) Ziel: {{ $document->trialGoal() }} @endif
        </x-alert>
    @endif

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
                    <span class="text-sm text-slate-600">Themen:</span>
                    @foreach ($document->topics() as $topic)
                        <x-badge compact>{{ $topic }}</x-badge>
                    @endforeach
                </div>
            @endif

            @if ($document->relatedKnowledge() !== [])
                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <span class="text-slate-600">Verweise:</span>
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
                        <dt class="text-slate-600">Maßnahme</dt>
                        <dd class="mt-1 font-mono text-slate-900">{{ implode(', ', $document->list('action')) ?: '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-600">Kundenservice entscheidet selbst</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            {{ match ($document->frontmatter['agent_allowed'] ?? null) { true => 'Ja', false => 'Nein', default => '–' } }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-600">Wertgrenze</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            @if (is_numeric($limit = $document->frontmatter['max_value_eur'] ?? null))
                                {{ number_format($limit, fmod((float) $limit, 1.0) === 0.0 ? 0 : 2, ',', '.') }} €
                            @else
                                keine Wertgrenze
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-600">Freigabe durch</dt>
                        <dd class="mt-1 font-mono text-slate-900">{{ $document->string('approval_role') ?? '–' }}</dd>
                    </div>
                </dl>
            @endif
        @endif

        <p class="mt-6 border-t border-slate-100 pt-4 font-mono text-xs text-slate-600">
            {{ $document->path }} · Fingerabdruck {{ $document->shortFingerprint() }}
        </p>
    </x-card>

    @if (session('status_changed'))
        <x-alert type="success">Status auf „{{ config('knowledge.statuses_labels.'.session('status_changed'), session('status_changed')) }}“ gesetzt. Die Datei ist geändert, aber noch nicht committet.</x-alert>
    @endif
    @if (session('status_error'))
        <x-alert type="error">{{ session('status_error') }}</x-alert>
    @endif

    @if (session('trial_changed'))
        <x-alert type="success">Entscheidung umgesetzt: {{ \App\Knowledge\TrialChanger::LABELS[session('trial_changed')] ?? session('trial_changed') }}. Die Datei ist geändert, aber noch nicht committet.</x-alert>
    @endif

    @if (($trialPanel ?? null) !== null && $document->parsed)
        <x-card>
            <h2 class="text-base font-semibold text-slate-900">Entscheidung zur Testphase</h2>
            <p class="mt-1 text-sm text-slate-600">Nur für Admins. Die App ändert die Datei, committet wird von Hand.</p>
            @if ($trialPanel['usage'] ?? null)
                <x-knowledge.usage :usage="$trialPanel['usage']" :goal="$document->trialGoal()" />
            @endif
            @unless ($trialPanel['writable'])
                <p class="mt-2 text-sm font-medium text-danger-700">Die Datei kann in dieser Umgebung nicht geschrieben werden.</p>
            @endunless
            <div class="mt-4 flex flex-wrap items-end gap-4" @if (! $trialPanel['writable']) inert @endif>
                <form method="POST" action="{{ route('knowledge.trial', ['path' => $document->path]) }}" onsubmit="return confirm('Das Dokument dauerhaft gültig machen? Die Befristung wird aus der Datei entfernt.')">
                    @csrf
                    <input type="hidden" name="fingerprint" value="{{ $document->fingerprint }}">
                    <input type="hidden" name="decision" value="permanent">
                    <x-button type="submit">Bewährt, dauerhaft gültig</x-button>
                </form>
                <form method="POST" action="{{ route('knowledge.trial', ['path' => $document->path]) }}" class="flex items-end gap-2">
                    @csrf
                    <input type="hidden" name="fingerprint" value="{{ $document->fingerprint }}">
                    <input type="hidden" name="decision" value="extend">
                    <div>
                        <label for="trial-until" class="block text-sm font-medium text-slate-900">Neues Enddatum</label>
                        <input id="trial-until" type="date" name="until" value="{{ old('until', $trialPanel['suggested']) }}" required class="mt-1 block rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
                    </div>
                    <x-button type="submit" variant="secondary">Testphase verlängern</x-button>
                </form>
                <form method="POST" action="{{ route('knowledge.trial', ['path' => $document->path]) }}" onsubmit="return confirm('Das Dokument beenden und auf veraltet setzen? Die App verwendet es dann nicht mehr.')">
                    @csrf
                    <input type="hidden" name="fingerprint" value="{{ $document->fingerprint }}">
                    <input type="hidden" name="decision" value="end">
                    <x-button type="submit" variant="secondary">Beenden (veraltet)</x-button>
                </form>
            </div>
        </x-card>
    @endif

    @if ($document->parsed && $document->id)
        <x-card>
            <h2 class="text-base font-semibold text-slate-900">Stimmt dieses Wissen noch?</h2>
            <x-knowledge.review-form :document-id="$document->id" />
        </x-card>
    @endif

    @if ($statusPanel !== null && $document->parsed)
        @php($evidence = $statusPanel['evidence'])
        <x-card x-data="{ confirm: null }">
            <h2 class="text-base font-semibold text-slate-900">Status</h2>

            @if ($statusPanel['actions'] === [])
                <p class="mt-2 text-sm text-slate-600">Für dieses Dokument ist keine Statusänderung in der App möglich.</p>
            @else
                @if ($evidence !== [])
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                        <div><dt class="text-slate-600">In Vorschlägen abgerufen</dt><dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $evidence['retrieved'] }}</dd></div>
                        <div><dt class="text-slate-600">Von der KI genannt</dt><dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $evidence['named'] }}</dd></div>
                        <div>
                            <dt class="text-slate-600">Bewertung der genannten</dt>
                            <dd class="mt-1 text-slate-900">
                                @foreach (config('analysis.feedback_levels') as $level => $label){{ $evidence['ratings'][$level] }} {{ $label }}@if (! $loop->last) · @endif @endforeach
                                · {{ $evidence['unrated'] }} nicht bewertet
                            </dd>
                        </div>
                    </dl>
                    @if ($evidence['thin'])
                        <p class="mt-2 text-sm text-warning-700">Bisher nur in {{ $evidence['named'] }} {{ $evidence['named'] === 1 ? 'Vorschlag' : 'Vorschlägen' }} von der KI genannt. Das reicht für eine Entscheidung, ist aber eine dünne Grundlage.</p>
                    @endif
                    <p class="mt-1 text-xs text-slate-600">Ohne Testläufe und fehlgeschlagene Vorschläge, alle bisherigen Fassungen des Dokuments.</p>
                @endif

                @if ($statusPanel['related'] !== [])
                    <p class="mt-3 text-sm text-warning-700">Verwandtes Wissen ist noch Entwurf: <span class="font-mono">{{ implode(', ', $statusPanel['related']) }}</span>. Das sperrt die Aktion nicht.</p>
                @endif

                <div class="mt-4 flex flex-wrap items-start gap-3">
                    @foreach ($statusPanel['actions'] as $action)
                        <div>
                            <x-button type="button" :variant="$action['target'] === 'active' ? 'primary' : 'secondary'" :disabled="$action['blockers'] !== []" x-on:click="confirm = '{{ $action['target'] }}'">{{ $action['label'] }}</x-button>
                            @foreach ($action['blockers'] as $blocker)
                                <p class="mt-1 max-w-xs text-xs text-warning-700">{{ $blocker }}</p>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                @foreach ($statusPanel['actions'] as $action)
                    @continue($action['blockers'] !== [])
                    <form x-show="confirm === '{{ $action['target'] }}'" x-cloak method="POST" action="{{ route('knowledge.status', ['path' => $document->path]) }}" class="mt-4 rounded-md bg-slate-50 p-4 ring-1 ring-slate-200 ring-inset">
                        @csrf
                        <input type="hidden" name="status" value="{{ $action['target'] }}">
                        <input type="hidden" name="fingerprint" value="{{ $document->fingerprint }}">
                        <p class="text-sm font-semibold text-slate-900">{{ $action['label'] }}?</p>
                        <p class="mt-1 text-sm text-slate-700">In der Datei wird nur die Zeile „status:“ auf <span class="font-mono">{{ $action['target'] }}</span> gesetzt. Danach bitte die Änderung in VSCode committen.</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <x-button type="submit">Ja, ändern</x-button>
                            <x-button type="button" variant="secondary" x-on:click="confirm = null">Abbrechen</x-button>
                        </div>
                    </form>
                @endforeach
            @endif
        </x-card>
    @endif

    @if ($html !== null && $document->body !== '')
        <x-card>
            <x-knowledge.text :html="$html" />
        </x-card>
    @endif
</x-layouts.app>
