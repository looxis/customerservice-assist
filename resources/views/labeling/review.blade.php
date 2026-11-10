<x-layouts.app title="Ticket-Klassifikation: Prüfen" width="5xl">
    <x-labeling.nav />

    <x-card class="space-y-4">
        <form method="GET" action="{{ route('labeling.review') }}" class="flex flex-wrap items-end gap-4">
            @if ($part !== \App\Labeling\ReviewQueue::NEEDED)
                <input type="hidden" name="bereich" value="{{ $part }}">
            @endif
            <x-select name="kategorie" label="Nur Vorschläge" class="min-w-52">
                <option value="">Alle Kategorien</option>
                @foreach ($categories as $option)
                    <option value="{{ $option->value }}" @selected($category === $option)>{{ $option->label() }}</option>
                @endforeach
            </x-select>
            <x-button type="submit" variant="secondary">Filtern</x-button>
            @if ($category)
                <a href="{{ route('labeling.review', array_filter(['bereich' => $filters['bereich'] ?? null])) }}" class="text-sm text-brand hover:text-brand-hover">Zurücksetzen</a>
            @endif
        </form>

        @foreach ($queues as $partKey => $partQueues)
            @php
                $visible = collect($partQueues)->filter(fn (array $info, string $key): bool => $info['count'] > 0 || ($part === $partKey && $queue === $key));
            @endphp
            <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4">
                <x-help-tip class="w-28 shrink-0 text-sm font-semibold text-slate-900" :text="$partKey === \App\Labeling\ReviewQueue::NEEDED
                    ? 'Tickets, deren vorgeschlagene Kategorie noch etwas braucht: Trainingstickets bis zum Zielwert, Testtickets bis zum Testziel, Stichproben bis zur nötigen Zahl. Automatische Tags freigegebener Kategorien zählen schon mit. Fehlen Stichproben, zieht die App sie zufällig aus den eindeutigen Vorschlägen. Ist der Bedarf gedeckt, wandern die übrigen Tickets von selbst nach „Bei Bedarf“.'
                    : 'Tickets, deren vorgeschlagene Kategorie schon genug hat. Prüfen schadet nicht, bringt aber kaum noch etwas. Hier stehen auch die als unsicher markierten Tickets.'">{{ \App\Labeling\ReviewQueue::PARTS[$partKey] }}</x-help-tip>
                <nav class="flex flex-wrap gap-2" aria-label="Warteschlangen {{ \App\Labeling\ReviewQueue::PARTS[$partKey] }}">
                    @forelse ($visible as $key => $info)
                        @php
                            $current = $part === $partKey && $queue === $key;
                        @endphp
                        <a href="{{ route('labeling.review', array_filter(['warteschlange' => $key, 'bereich' => $partKey === \App\Labeling\ReviewQueue::NEEDED ? null : $partKey, 'kategorie' => $category?->value])) }}" @if ($current) aria-current="page" @endif
                           @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => $current, 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => ! $current])>
                            {{ $info['label'] }} ({{ $info['count'] }})
                        </a>
                    @empty
                        <span class="text-sm text-slate-600">{{ $partKey === \App\Labeling\ReviewQueue::NEEDED ? 'Nichts mehr nötig.' : 'Nichts.' }}</span>
                    @endforelse
                </nav>
            </div>
        @endforeach
    </x-card>

    @if ($ticket === null)
        <x-card class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-600">In dieser Warteschlange ist nichts mehr zu prüfen.</p>
            @if ($recent > 0)
                <x-button variant="secondary" :href="route('labeling.review', $filters + ['zurueck' => 1])"><x-icon name="chevron-left" :size="16" /> Zurück zur letzten Entscheidung</x-button>
            @endif
        </x-card>
    @else
        @php
            $parts = $ticket['evidence'] !== '' && str_contains($ticket['text'], $ticket['evidence']) ? explode($ticket['evidence'], $ticket['text'], 2) : null;
        @endphp

        <form method="POST" action="{{ $revisit ? route('labeling.reviews.amend', ['review' => $revisit->id]) : route('labeling.reviews.store', ['ticket' => $ticket['id']]) }}"
              x-data="{ busy: false, pick: null }"
              x-on:submit="if (busy) { $event.preventDefault(); return } busy = true"
              x-on:keydown.window="
                  if ($event.target.closest('input, textarea, select') || $event.ctrlKey || $event.metaKey || $event.altKey) return;
                  const button = $el.querySelector('[data-key=\'' + $event.key + '\']');
                  if (button && ! busy) { $event.preventDefault(); button.click() }
              ">
            @csrf
            @if ($revisit)
                @method('PATCH')
            @endif
            <input type="hidden" name="queue" value="{{ $queue }}">
            <input type="hidden" name="bereich" value="{{ $part }}">
            @if ($category)
                <input type="hidden" name="kategorie" value="{{ $category->value }}">
            @endif
            @if ($errors->any())
                <x-alert type="error" class="mb-4">Die Entscheidung konnte nicht gespeichert werden: {{ $errors->first() }}</x-alert>
            @endif
            <x-card class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ rtrim((string) config('services.zammad.url'), '/') }}/#ticket/zoom/{{ $ticket['zammad_id'] }}" target="_blank" rel="noopener" class="font-mono text-brand hover:text-brand-hover">Ticket#{{ $ticket['number'] }}</a>
                        <span class="text-sm text-slate-600">{{ $ticket['created'] }} · {{ $ticket['group'] }}</span>
                        <x-badge compact :tone="$ticket['test'] ? 'warning' : 'neutral'">{{ $ticket['test'] ? 'Testmenge' : 'Training' }}</x-badge>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($revisit)
                            <x-badge tone="info">{{ $step === 1 ? 'Deine letzte Entscheidung' : 'Deine vorletzte Entscheidung' }}</x-badge>
                        @else
                            <span class="text-sm text-slate-600">Noch {{ $ticket['remaining'] }} in dieser Warteschlange</span>
                        @endif
                        @if ($step < $recent)
                            <x-button variant="secondary" :href="route('labeling.review', $filters + ['zurueck' => $step + 1])" data-key="z"><x-icon name="chevron-left" :size="16" /> Zurück <span class="font-normal opacity-80">(z)</span></x-button>
                        @endif
                        @if ($revisit)
                            <x-button variant="secondary" :href="route('labeling.review', $filters + array_filter(['zurueck' => $step - 1]))">Weiter <x-icon name="chevron-right" :size="16" /></x-button>
                        @endif
                    </div>
                </div>

                <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
                    <dt class="text-slate-600">Betreff</dt>
                    <dd class="break-words font-medium text-slate-900">{{ $ticket['subject'] ?? '–' }}</dd>
                    <dt class="text-slate-600">Absender</dt>
                    <dd class="break-all text-slate-900">{{ $ticket['sender'] ?? '–' }}</dd>
                </dl>

                <blockquote class="whitespace-pre-line rounded-md bg-slate-50 p-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200">
                    @if ($parts && ! $ticket['test']){{ $parts[0] }}<mark class="rounded bg-warning-500/20 px-0.5 text-slate-900">{{ $ticket['evidence'] }}</mark>{{ $parts[1] }}@else{{ $ticket['text'] }}@endif
                </blockquote>

                @if ($revisit)
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-slate-600">Deine Entscheidung:</span>
                        <x-badge tone="neutral">{{ $ticket['previous'] }}</x-badge>
                        <span class="text-slate-600">Wähle die Kategorie, die stattdessen gilt. Sie ersetzt diese Entscheidung, auch in der Trefferquote und beim nächsten Tag.</span>
                    </div>
                @elseif ($ticket['previous'] !== null)
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-slate-600">Bisherige Entscheidung:</span>
                        <x-badge tone="neutral">{{ $ticket['previous'] }}</x-badge>
                        <span class="text-slate-600">{{ $queue === 'unsicher' ? 'Entscheide jetzt oder lass es bei „Unsicher“.' : 'Der neue Vorschlag weicht davon ab. Wähle die Kategorie, die gilt.' }}</span>
                    </div>
                @endif

                @if ($ticket['test'])
                    <x-alert type="warning">Testmenge: Der Vorschlag ist verborgen. Entscheide selbst, es gibt kein „Bestätigen“.</x-alert>
                @else
                    <div class="space-y-2 rounded-md bg-slate-50 p-4 text-sm ring-1 ring-inset ring-slate-200">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-slate-600">Vorschlag:</span>
                            <x-badge tone="info">{{ $ticket['suggestion']?->label() ?? 'Kein Vorschlag' }}</x-badge>
                            <span class="text-slate-600">Konfidenz {{ (int) round($ticket['confidence'] * 100) }} %</span>
                            @if ($ticket['unclear'])<x-badge compact tone="warning">Unklar</x-badge>@endif
                            @if ($ticket['multiple'])<x-badge compact tone="warning">Mehrere Anliegen</x-badge>@endif
                        </div>
                        <p class="text-slate-900">{{ $ticket['reason'] }}</p>
                    </div>
                @endif

                <fieldset class="space-y-3" x-bind:class="busy && 'pointer-events-none opacity-60'" x-bind:aria-busy="busy">
                    <legend class="text-sm font-medium text-slate-900">Entscheidung</legend>

                    @if (! $ticket['test'] && $ticket['suggestion'])
                        <x-button type="submit" name="category" :value="$ticket['suggestion']->value" data-key="Enter">Bestätigen <span class="font-normal opacity-80">(Enter)</span></x-button>
                    @endif

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($categories as $category)
                            <x-button type="submit" variant="secondary" name="category" :value="$category->value" :data-key="$category->key()" class="justify-start" :title="$category->description()">
                                <span class="rounded bg-slate-100 px-1.5 font-mono text-xs text-slate-600">{{ $category->key() }}</span>
                                {{ $category->label() }}
                                @if ($revisit && $ticket['decision'] === $category->value)<x-badge compact tone="info">bisher</x-badge>@endif
                            </x-button>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-3">
                        <x-button type="submit" variant="secondary" name="category" value="{{ \App\Models\LabelTicket::UNSURE }}" data-key="u" class="justify-start">
                            <span class="rounded bg-slate-100 px-1.5 font-mono text-xs text-slate-600">u</span>
                            Unsicher, nicht verwenden
                            @if ($revisit && $ticket['decision'] === \App\Models\LabelTicket::UNSURE)<x-badge compact tone="info">bisher</x-badge>@endif
                        </x-button>
                        <span class="text-sm text-slate-600">Kein Tag, kein Beispiel, zählt nicht in die Trefferquote. Später unter „Unsicher markiert“ wieder aufrufbar.</span>
                    </div>
                    <p x-show="busy" x-cloak role="status" class="text-sm text-slate-600">Wird gespeichert …</p>
                </fieldset>
            </x-card>
        </form>

        <x-card class="space-y-3">
            <h2 class="text-sm font-medium text-slate-900">Gehört nicht in die Trainingsdaten?</h2>
            <p class="text-sm text-slate-600">Zum Beispiel ein Testticket. Das Ticket wird aus der App entfernt (Text, Vorschlag und Entscheidungen werden gelöscht) und bei späteren Abrufen übersprungen.</p>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('labeling.tickets.exclude', ['ticket' => $ticket['id']]) }}"
                      x-data x-on:submit="if (! confirm('Ticket#{{ $ticket['number'] }} aus der App entfernen und künftig nicht mehr abrufen? In Zammad bleibt es unverändert.')) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="queue" value="{{ $queue }}">
                    <input type="hidden" name="bereich" value="{{ $part }}">
                    @if ($category)
                        <input type="hidden" name="kategorie" value="{{ $category->value }}">
                    @endif
                    <x-button type="submit" variant="secondary">Entfernen und nicht mehr abrufen</x-button>
                </form>
                <form method="POST" action="{{ route('labeling.tickets.exclude', ['ticket' => $ticket['id']]) }}"
                      x-data x-on:submit="if (! confirm('Ticket#{{ $ticket['number'] }} auch in Zammad endgültig löschen? Das lässt sich nicht rückgängig machen.')) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="queue" value="{{ $queue }}">
                    <input type="hidden" name="bereich" value="{{ $part }}">
                    @if ($category)
                        <input type="hidden" name="kategorie" value="{{ $category->value }}">
                    @endif
                    <input type="hidden" name="delete_in_zammad" value="1">
                    <x-button type="submit" variant="danger">Entfernen und in Zammad löschen</x-button>
                </form>
            </div>
        </x-card>
    @endif
</x-layouts.app>
