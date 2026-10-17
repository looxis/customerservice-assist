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
    </nav>

    @if ($status === 'open' && $unknownProducts->isNotEmpty())
        <x-card>
            <h2 class="text-base font-semibold text-slate-900">Produkte ohne Wissen, nach Häufigkeit</h2>
            <p class="mt-1 text-sm text-slate-600">Automatisch erfasst, wenn ein Vorschlag für ein Produkt ohne hinterlegtes Wissen erstellt wurde. Gezählt werden Tickets und Fragen, ohne Testläufe.</p>
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
</x-layouts.app>
