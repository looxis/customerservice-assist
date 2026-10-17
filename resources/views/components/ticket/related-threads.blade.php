@props(['tickets' => []])

{{-- Earlier history of the tickets the suggestion used (PROJ-52): read-only, collapsed, apart from the thread above. --}}
@if ($tickets !== [])
    <section id="zusammengehoerige-tickets" aria-label="Zusammengehörige Tickets" class="scroll-mt-4 space-y-3 border-t border-slate-200 pt-6">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Zusammengehörige Tickets</h2>
            <p class="text-sm text-slate-600">Der Verlauf dieser Tickets ging als früherer Verlauf in den Vorschlag ein. Hier nur zum Nachlesen.</p>
        </div>

        @foreach ($tickets as $related)
            <details class="group rounded-lg border border-slate-200 bg-slate-50/60">
                <summary class="flex cursor-pointer list-none items-center gap-3 p-3 text-sm focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-down" size="16" class="shrink-0 transition group-open:rotate-180" />
                    <span class="min-w-0 flex-1">
                        <span class="font-mono font-semibold text-slate-900">{{ $related->number }}</span>
                        <span class="text-slate-900">{{ $related->title }}</span>
                        <span class="block text-xs text-slate-600">{{ $related->state }} · {{ $related->createdAt->setTimezone('Europe/Berlin')->format('d.m.Y') }} · {{ count($related->articles) }} {{ count($related->articles) === 1 ? 'Nachricht' : 'Nachrichten' }}</span>
                    </span>
                    <a href="{{ route('tickets.show', ['number' => $related->number]) }}" class="shrink-0 font-medium text-brand hover:text-brand-hover" x-on:click.stop>Ticket öffnen</a>
                </summary>
                <div class="space-y-4 p-3 pt-1">
                    @foreach ($related->articles as $article)
                        <x-ticket.article :article="$article" />
                    @endforeach
                </div>
            </details>
        @endforeach
    </section>
@endif
