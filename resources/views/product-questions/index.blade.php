<x-layouts.app title="Häufige Produktfragen" width="5xl">
    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Was Kunden zu Produkten fragen, ohne dass eine Angabe vorliegt</h2>
        <p class="mt-2 text-sm text-slate-600">
            Die App zählt Einzelheiten, nach denen Kunden fragen und die in ProductForge nicht stehen (zum Beispiel „Ist das vegan?“). Ein Thema erscheint hier ab {{ $threshold }} Tickets oder Fragen in den letzten {{ $months }} Monaten, ohne Testläufe. Es zählt weiter, wenn mehr dazukommen; wer oben steht, wird aktuell am häufigsten gefragt. Mitarbeiter sehen dazu keine Meldung.
        </p>
    </x-card>

    @php($query = array_filter(['produkt' => $productKey === null ? null : ($productKey === '' ? 'ohne' : $productKey), 'alle' => $all ? 1 : null]))
    <nav class="flex flex-wrap gap-2" aria-label="Status">
        @foreach (\App\Analysis\ProductQuestionLog::STATUSES as $key => $label)
            <a href="{{ route('product-questions.index', ['status' => $key, ...$query]) }}" @if ($status === $key) aria-current="page" @endif
               @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => $status === $key, 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => $status !== $key])>
                {{ $label }} ({{ $counts[$key] }})
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('product-questions.index') }}" class="flex flex-wrap items-end gap-x-6 gap-y-3 text-sm">
        <input type="hidden" name="status" value="{{ $status }}">
        <div>
            <label for="produkt" class="block font-medium text-slate-900">Produkt</label>
            <select id="produkt" name="produkt" class="mt-1 rounded-md border-0 py-1.5 pl-3 pr-8 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand">
                <option value="">Alle Produkte</option>
                @foreach ($products as $key => $name)
                    <option value="{{ $key === '' ? 'ohne' : $key }}" @selected($productKey !== null && $productKey === $key)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        @if ($status === 'open')
            <label class="inline-flex items-center gap-2 text-slate-900"><input type="checkbox" name="alle" value="1" @checked($all) class="rounded text-brand focus:ring-brand"> Alle Themen anzeigen (auch unter {{ $threshold }} Tickets)</label>
        @endif
        <x-button type="submit" variant="secondary">Anzeigen</x-button>
    </form>

    @forelse ($topics as $topic)
        @php($listEntries = $entries[\App\Analysis\ProductQuestionLog::topicKey($topic['product_key'], $topic['topic'])] ?? collect())
        <x-card>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                <span class="font-semibold text-slate-900">{{ $topic['topic'] }}</span>
                <span class="text-slate-700">{{ $topic['product_name'] ?? 'ohne Produkt' }}</span>
                <x-badge compact tone="warning">{{ $topic['recent'] }} {{ $topic['recent'] === 1 ? 'Anfrage' : 'Anfragen' }} in {{ $months }} Monaten</x-badge>
                <span class="text-xs text-slate-600">{{ $topic['total'] }} insgesamt · zuletzt {{ $topic['last']->setTimezone('Europe/Berlin')->format('d.m.Y') }}</span>
                @if ($topic['since'] !== null)<x-badge compact tone="info">wieder gefragt seit {{ $topic['since']->setTimezone('Europe/Berlin')->format('d.m.Y') }}</x-badge>@endif
            </div>

            @if ($listEntries->isNotEmpty())
                <details class="group mt-3 text-sm">
                    <summary class="cursor-pointer font-medium text-slate-700 hover:text-slate-900">Wie Kunden gefragt haben ({{ $listEntries->count() }})</summary>
                    <ul class="mt-2 space-y-2">
                        @foreach ($listEntries->take(20) as $entry)
                            <li class="border-l-2 border-slate-200 pl-3">
                                <p class="text-slate-900">{{ $entry->question }}</p>
                                <p class="text-xs text-slate-600">
                                    {{ $entry->created_at->setTimezone('Europe/Berlin')->format('d.m.Y') }} ·
                                    @if (\App\Analysis\QuestionNumber::isQuestion($entry->ticket_number))
                                        <a href="{{ route('questions.show', ['number' => $entry->ticket_number]) }}" class="font-mono text-brand hover:text-brand-hover">Frage {{ $entry->ticket_number }}</a>
                                    @else
                                        <a href="{{ route('tickets.show', ['number' => $entry->ticket_number]) }}" class="font-mono text-brand hover:text-brand-hover">Ticket#{{ $entry->ticket_number }}</a>
                                    @endif
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif

            <div class="mt-4 flex flex-wrap items-start gap-3" x-data="{ copied: false }">
                @foreach (match ($status) { 'open' => ['done' => 'Erledigt: in ProductForge ergänzt', 'ignored' => 'Ignorieren'], default => ['open' => 'Wieder öffnen'] } as $target => $label)
                    <form method="POST" action="{{ route('product-questions.update') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="product_key" value="{{ $topic['product_key'] }}">
                        <input type="hidden" name="topic" value="{{ $topic['topic'] }}">
                        <input type="hidden" name="status" value="{{ $target }}">
                        <input type="hidden" name="back_status" value="{{ $status }}">
                        <input type="hidden" name="back_product" value="{{ $query['produkt'] ?? '' }}">
                        <input type="hidden" name="back_all" value="{{ $query['alle'] ?? '' }}">
                        <x-button type="submit" variant="secondary">{{ $label }}</x-button>
                    </form>
                @endforeach
                <textarea x-ref="text" readonly class="sr-only" aria-hidden="true" tabindex="-1">{{ $chatText($topic, $listEntries) }}</textarea>
                <x-button variant="secondary" x-on:click="navigator.clipboard.writeText($refs.text.value).then(() => { copied = true; setTimeout(() => copied = false, 2500) })">Für den KI-Chat kopieren</x-button>
                <span x-show="copied" x-cloak role="status" class="self-center text-sm font-medium text-success-700">Kopiert</span>
            </div>
        </x-card>
    @empty
        <x-card>
            <p class="text-sm text-slate-600">
                @if ($status === 'open')
                    Noch kein Thema hat {{ $threshold }} Tickets in den letzten {{ $months }} Monaten erreicht.
                    @unless ($all) Mit „Alle Themen anzeigen“ siehst du auch die seltener gefragten. @endunless
                @else
                    Hier steht noch nichts.
                @endif
            </p>
        </x-card>
    @endforelse
</x-layouts.app>
