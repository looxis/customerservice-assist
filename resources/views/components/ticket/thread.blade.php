@props(['articles', 'later' => [], 'rewindUrl' => null])

@php
    $count = count($articles);
    $collapse = $count > 10;
    $hidden = $collapse ? range(1, $count - 6) : [];
    $lastCustomer = collect($articles)->keys()->filter(fn ($index) => $articles[$index]->kind === \App\Zammad\ArticleKind::Customer)->last();
@endphp

{{-- Ticket thread, oldest first (PROJ-6). Above 10 messages the middle part is collapsed. --}}
<section aria-label="Verlauf" class="space-y-4">
    <h2 class="text-base font-semibold text-slate-900">Verlauf</h2>

    @if ($count === 0)
        <x-alert>Dieses Ticket enthält noch keine Nachrichten.</x-alert>
    @else
        @foreach ($articles as $index => $article)
            @if ($collapse && $index === 1)
                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 p-3 text-sm font-medium text-slate-600 hover:bg-white hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand group-open:mb-4 [&::-webkit-details-marker]:hidden">
                        <x-icon name="chevron-down" size="16" class="transition group-open:rotate-180" />
                        <span class="group-open:hidden">{{ count($hidden) }} weitere Nachrichten anzeigen</span>
                        <span class="hidden group-open:inline">{{ count($hidden) }} Nachrichten ausblenden</span>
                    </summary>
                    <div class="space-y-4">
                        @foreach ($hidden as $hiddenIndex)
                            <x-ticket.article :article="$articles[$hiddenIndex]" />
                        @endforeach
                    </div>
                </details>
            @endif

            @continue(in_array($index, $hidden, true))

            @if ($index === $lastCustomer && isset($summary))
                {{ $summary }}
            @endif

            <x-ticket.article :article="$article" :latest="$index === $count - 1" :rewind-url="$rewindUrl && $article->kind === \App\Zammad\ArticleKind::Customer && $article->id !== null ? $rewindUrl($article->id) : null" />
        @endforeach
    @endif

    @if ($later !== [])
        <details class="group">
            <summary class="flex cursor-pointer list-none items-center justify-center gap-2 rounded-lg border border-dashed border-warning-500/40 bg-warning-500/5 p-3 text-sm font-medium text-slate-600 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand group-open:mb-4 [&::-webkit-details-marker]:hidden">
                <x-icon name="chevron-down" size="16" class="transition group-open:rotate-180" />
                <span>{{ count($later) }} {{ count($later) === 1 ? 'spätere Nachricht' : 'spätere Nachrichten' }} (nicht an die KI)</span>
            </summary>
            <div class="space-y-4 opacity-60">
                @foreach ($later as $article)
                    <x-ticket.article :article="$article" />
                @endforeach
            </div>
        </details>
    @endif
</section>
