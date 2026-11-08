<x-layouts.app title="Ticket-Klassifikation: Prüfen" width="5xl">
    <x-labeling.nav />

    <nav class="flex flex-wrap gap-2" aria-label="Warteschlangen">
        @foreach ($queues as $key => $info)
            <a href="{{ route('labeling.review', ['warteschlange' => $key]) }}" @if ($queue === $key) aria-current="page" @endif
               @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => $queue === $key, 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => $queue !== $key])>
                {{ $info['label'] }} ({{ $info['count'] }})
            </a>
        @endforeach
    </nav>

    @if ($ticket === null)
        <x-card>
            <p class="text-sm text-slate-600">In dieser Warteschlange ist nichts mehr zu prüfen.</p>
        </x-card>
    @else
        @php
            $parts = $ticket['evidence'] !== '' && str_contains($ticket['text'], $ticket['evidence']) ? explode($ticket['evidence'], $ticket['text'], 2) : null;
        @endphp

        <form method="POST" action="{{ route('labeling.reviews.store', ['ticket' => $ticket['id']]) }}"
              x-data="{ busy: false, pick: null }"
              x-on:submit="if (busy) { $event.preventDefault(); return } busy = true"
              x-on:keydown.window="
                  if ($event.target.closest('input, textarea, select') || $event.ctrlKey || $event.metaKey || $event.altKey) return;
                  const button = $el.querySelector('[data-key=\'' + $event.key + '\']');
                  if (button && ! busy) { $event.preventDefault(); button.click() }
              ">
            @csrf
            <input type="hidden" name="queue" value="{{ $queue }}">
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
                    <span class="text-sm text-slate-600">Noch {{ $ticket['remaining'] }} in dieser Warteschlange</span>
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

                @if ($ticket['previous'] !== null)
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
                            </x-button>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-3">
                        <x-button type="submit" variant="secondary" name="category" value="{{ \App\Models\LabelTicket::UNSURE }}" data-key="u" class="justify-start">
                            <span class="rounded bg-slate-100 px-1.5 font-mono text-xs text-slate-600">u</span>
                            Unsicher, nicht verwenden
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
                    <x-button type="submit" variant="secondary">Entfernen und nicht mehr abrufen</x-button>
                </form>
                <form method="POST" action="{{ route('labeling.tickets.exclude', ['ticket' => $ticket['id']]) }}"
                      x-data x-on:submit="if (! confirm('Ticket#{{ $ticket['number'] }} auch in Zammad endgültig löschen? Das lässt sich nicht rückgängig machen.')) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="queue" value="{{ $queue }}">
                    <input type="hidden" name="delete_in_zammad" value="1">
                    <x-button type="submit" variant="danger">Entfernen und in Zammad löschen</x-button>
                </form>
            </div>
        </x-card>
    @endif
</x-layouts.app>
