<x-layouts.app title="Wissenslücken" width="5xl">
    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Brauchbarkeit der Vorschläge</h2>
        @if ($success === null)
            <p class="mt-2 text-sm text-slate-600">In den letzten {{ config('analysis.feedback_success_days') }} Tagen wurde noch kein Vorschlag bewertet.</p>
        @else
            <p class="mt-2 text-sm text-slate-900">
                <span class="font-display text-2xl font-semibold">{{ $success['share'] }} %</span>
                „unverändert nutzbar“ oder „leicht angepasst“ – {{ $success['rated'] }} bewertete Vorschläge der letzten {{ config('analysis.feedback_success_days') }} Tage, ohne Testläufe. Ziel laut PRD: mindestens 70 %.
            </p>
        @endif
    </x-card>

    <nav class="flex flex-wrap gap-2" aria-label="Status">
        @foreach (\App\Analysis\KnowledgeGapLog::STATUSES as $key => $label)
            <a href="{{ route('knowledge-gaps.index', ['status' => $key]) }}" @if ($status === $key) aria-current="page" @endif
               @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => $status === $key, 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => $status !== $key])>
                {{ $label }} ({{ $counts[$key] }})
            </a>
        @endforeach
        <a href="{{ route('knowledge-gaps.index', ['status' => 'review']) }}" @if ($status === 'review') aria-current="page" @endif
           @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => $status === 'review', 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => $status !== 'review'])>
            Zur Prüfung ({{ $reviewCount }})
        </a>
    </nav>

    @if ($status === 'open' && $unknownProducts->isNotEmpty())
        <x-card>
            <h2 class="text-base font-semibold text-slate-900">Produkte ohne Katalogangaben und ohne Regeln, nach Häufigkeit</h2>
            <p class="mt-1 text-sm text-slate-600">Automatisch erfasst, wenn ein Vorschlag für ein Produkt erstellt wurde, zu dem weder der Produktkatalog (ProductForge/Content Studio & EOCS) noch die Wissensdatenbank etwas enthält. Gezählt werden Tickets und Fragen, ohne Testläufe.</p>
            <ul class="mt-3 divide-y divide-slate-100">
                @foreach ($unknownProducts as $row)
                    <li class="py-3 text-sm">
                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="font-semibold text-slate-900">{{ $row['product'] }}</span>
                            <x-badge compact tone="warning">{{ $row['cases'] }} {{ $row['cases'] === 1 ? 'Anfrage' : 'Anfragen' }}</x-badge>
                            <span class="text-xs text-slate-600">zuletzt {{ $row['last']->setTimezone('Europe/Berlin')->format('d.m.Y') }}</span>
                        </p>
                        @foreach ($row['notes'] as $note)
                            <p class="mt-1 whitespace-pre-line border-l-2 border-slate-200 pl-3 text-slate-700">{{ \Illuminate\Support\Str::limit($note, 300) }}</p>
                        @endforeach
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    @if ($status === 'open' && $notInProductForge->isNotEmpty())
        <x-card>
            <h2 class="text-base font-semibold text-slate-900">Nicht in ProductForge, nach Häufigkeit</h2>
            <p class="mt-1 text-sm text-slate-600">Automatisch erfasst, wenn für ein Produkt nur Beschreibung und Bullet Points aus EOCS vorlagen, weil es in ProductForge noch nicht gepflegt ist. Gezählt werden Tickets und Fragen, ohne Testläufe.</p>
            <ul class="mt-3 divide-y divide-slate-100">
                @foreach ($notInProductForge as $row)
                    <li class="py-3 text-sm">
                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="font-semibold text-slate-900">{{ $row['product'] }}</span>
                            <span class="font-mono text-xs text-slate-600">SKU {{ $row['sku'] }}</span>
                            <x-badge compact tone="warning">{{ $row['cases'] }} {{ $row['cases'] === 1 ? 'Anfrage' : 'Anfragen' }}</x-badge>
                            <span class="text-xs text-slate-600">zuletzt {{ $row['last']->setTimezone('Europe/Berlin')->format('d.m.Y') }}</span>
                        </p>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    @if ($status === 'review')
        <p class="text-sm text-slate-600">Wissen, das Mitarbeiter als möglicherweise veraltet gemeldet haben. Eine Meldung schaltet nichts ab; ihr entscheidet, ob das Dokument bleibt, überarbeitet wird oder entfällt.</p>
        @forelse ($reviews as $row)
            <x-card>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <span class="font-mono text-slate-600">{{ $row['id'] }}</span>
                    @if ($row['path'])
                        <a href="{{ route('knowledge.show', ['path' => $row['path']]) }}" class="font-semibold text-brand hover:text-brand-hover">{{ $row['title'] ?? $row['path'] }}</a>
                    @else
                        <span class="font-semibold text-slate-900">{{ $row['title'] ?? 'Dokument nicht mehr vorhanden' }}</span>
                    @endif
                    @if ($row['status'])<x-knowledge.status-badge :status="$row['status']" />@endif
                    <x-badge compact tone="warning">{{ $row['flags']->count() }} {{ $row['flags']->count() === 1 ? 'Meldung' : 'Meldungen' }} von {{ $row['staff'] }} {{ $row['staff'] === 1 ? 'Person' : 'Personen' }}</x-badge>
                    @if ($row['many'])<x-badge compact tone="danger">Mehrfach gemeldet</x-badge>@endif
                    @if ($row['changed'])<x-badge compact tone="info">Seit einer Meldung geändert</x-badge>@endif
                </div>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($row['flags'] as $flag)
                        <li class="border-l-2 border-slate-200 pl-3">
                            <span class="font-medium text-slate-900">{{ $flag->staff_name }}</span>
                            <span class="text-slate-600">{{ $flag->created_at->setTimezone('Europe/Berlin')->format('d.m.Y') }} · {{ \App\Knowledge\KnowledgeReviewLog::REASONS[$flag->reason] ?? $flag->reason }}</span>
                            @if ($flag->ticket_number)<a href="{{ route('tickets.show', array_filter(['number' => $flag->ticket_number, 'analyse' => $flag->analysis_uuid])) }}" class="font-mono text-xs text-brand hover:text-brand-hover">Ticket#{{ $flag->ticket_number }}</a>@endif
                            @if ($flag->comment)<p class="mt-1 whitespace-pre-line text-slate-700">{{ $flag->comment }}</p>@endif
                        </li>
                    @endforeach
                </ul>
                <details class="mt-3 text-sm">
                    <summary class="cursor-pointer font-medium text-slate-700 hover:text-slate-900">Belege zum Dokument</summary>
                    <x-knowledge.usage :usage="$reviewUsage($row['id'])" />
                </details>
                <div class="mt-4 flex flex-wrap items-start gap-3" x-data="{ copied: false }">
                    <form method="POST" action="{{ route('knowledge.review.resolve', ['documentId' => $row['id']]) }}">
                        @csrf
                        <x-button type="submit" variant="secondary">Geprüft, bleibt</x-button>
                    </form>
                    <textarea x-ref="text" readonly class="sr-only" aria-hidden="true" tabindex="-1">{{ $reviewChatText($row['id']) }}</textarea>
                    <x-button variant="secondary" x-on:click="navigator.clipboard.writeText($refs.text.value).then(() => { copied = true; setTimeout(() => copied = false, 2500) })">Für den KI-Chat kopieren</x-button>
                    <span x-show="copied" x-cloak role="status" class="self-center text-sm font-medium text-success-700">Kopiert</span>
                    @if ($row['path'])<a href="{{ route('knowledge.show', ['path' => $row['path']]) }}" class="self-center text-sm font-semibold text-brand hover:text-brand-hover">Dokument öffnen (dort: Als veraltet markieren)</a>@endif
                </div>
            </x-card>
        @empty
            <x-card><p class="text-sm text-slate-600">Es liegen keine Meldungen zur Prüfung vor.</p></x-card>
        @endforelse
    @else
    @forelse ($gaps as $gap)
        @php($content = $gap->content ?? [])
        <x-card>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                <span class="font-semibold text-slate-900">{{ $gap->created_at->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr</span>
                <span class="text-slate-600">{{ $gap->staff_name }}</span>
                @if (\App\Analysis\QuestionNumber::isQuestion($gap->ticket_number))
                    <a href="{{ route('questions.show', ['number' => $gap->ticket_number]) }}" class="font-mono text-brand hover:text-brand-hover">Frage {{ $gap->ticket_number }}</a>
                @else
                    <a href="{{ route('tickets.show', ['number' => $gap->ticket_number]) }}" class="font-mono text-brand hover:text-brand-hover">Ticket#{{ $gap->ticket_number }}</a>
                @endif
                <span class="text-slate-600">{{ $groups[$gap->customer_group] ?? $gap->customer_group ?? '–' }} · {{ ($gap->products ?? []) === [] ? 'kein Produktbezug' : implode(', ', $gap->products) }}</span>
                @if ($gap->test_run)<x-badge compact tone="warning">Testlauf</x-badge>@endif
                @if (($gap->content['source'] ?? null) === 'auto')<x-badge compact>automatisch erfasst</x-badge>@endif
            </div>

            @if ($gap->content === null)
                <p class="mt-3 text-sm text-slate-600">Inhalte nach 12 Monaten gelöscht.</p>
            @else
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="font-semibold text-slate-900">Was fehlt?</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $content['missing'] ?? '' }}</dd></div>
                    @if ($content['solution'] ?? null)<div><dt class="font-semibold text-slate-900">So lösen wir das</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $content['solution'] }}</dd></div>@endif
                    @if ($content['notes'] ?? null)<div><dt class="font-semibold text-slate-900">Angaben des Mitarbeiters zum Fall</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $content['notes'] }}</dd></div>@endif
                    @if ($content['comment'] ?? null)<div><dt class="font-semibold text-slate-900">Kommentar</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $content['comment'] }}</dd></div>@endif
                    @if ($content['reason'] ?? null)<div><dt class="font-semibold text-slate-900">Grund fürs Verwerfen</dt><dd class="mt-1 text-slate-900">{{ $content['reason'] }}</dd></div>@endif
                </dl>
            @endif

            @if ($gap->status !== 'open')
                <p class="mt-3 text-xs text-slate-600">
                    {{ \App\Analysis\KnowledgeGapLog::STATUSES[$gap->status] }} von {{ $gap->resolved_by }} am {{ $gap->resolved_at?->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr
                    @if ($gap->knowledge_id) · Knowledge: <span class="font-mono">{{ $gap->knowledge_id }}</span>@endif
                </p>
            @endif

            <div class="mt-4 space-y-3 border-t border-slate-100 pt-3">
                @if ($gap->status === 'open' && $gap->content !== null)
                    <details class="group/chat">
                        <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                            <x-icon name="chevron-right" size="16" class="transition group-open/chat:rotate-90" /> Für den KI-Chat kopieren
                        </summary>
                        <x-copy-field class="mt-2" :text="$chatText($gap)" label="Wissenslücke für den KI-Chat" rows="7" />
                    </details>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    @if ($gap->status === 'open')
                        <form method="POST" action="{{ route('knowledge-gaps.update', ['gap' => $gap->id]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="done">
                            <x-button type="submit"><x-icon name="check" size="16" /> Erledigt</x-button>
                        </form>
                        <form method="POST" action="{{ route('knowledge-gaps.update', ['gap' => $gap->id]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="discarded">
                            <x-button type="submit" variant="secondary"><x-icon name="x" size="16" /> Verwerfen</x-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('knowledge-gaps.update', ['gap' => $gap->id]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="open">
                            <x-button type="submit" variant="secondary"><x-icon name="refresh" size="16" /> Wieder öffnen</x-button>
                        </form>
                    @endif
                </div>
            </div>
        </x-card>
    @empty
        <x-alert>{{ $status === 'open' ? 'Keine offenen Wissenslücken.' : 'Keine Einträge.' }}</x-alert>
    @endforelse
    @endif
</x-layouts.app>
