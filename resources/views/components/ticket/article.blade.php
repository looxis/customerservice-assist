@props(['article', 'latest' => false])

@php
    $tone = match ($article->kind) {
        \App\Zammad\ArticleKind::Customer => ['border' => 'border-l-slate-400', 'badge' => 'neutral'],
        \App\Zammad\ArticleKind::Agent => ['border' => 'border-l-brand', 'badge' => 'info'],
        \App\Zammad\ArticleKind::Internal => ['border' => 'border-l-warning-500', 'badge' => 'warning'],
    };
@endphp

{{-- One message of the ticket thread (PROJ-6). --}}
<article
    @if ($latest) id="neueste-nachricht" x-data x-init="$el.scrollIntoView({ block: 'start' })" @endif
    @class([
        'scroll-mt-4 rounded-lg border-l-4 p-5 shadow-1 ring-1 wrap-anywhere',
        $tone['border'],
        'bg-white ring-slate-200' => $article->kind !== \App\Zammad\ArticleKind::Internal,
        'bg-warning-500/5 ring-warning-500/20' => $article->kind === \App\Zammad\ArticleKind::Internal,
        'ring-2 ring-brand' => $latest,
    ])
    aria-label="{{ $article->kind->label() }}, {{ $article->senderName }}, {{ $article->createdAt->format('d.m.Y, H:i') }} Uhr"
>
    <header class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <x-badge :tone="$tone['badge']">{{ $article->kind->label() }}</x-badge>
        @if ($article->automatic)
            <x-badge compact>automatisch</x-badge>
        @endif
        <span class="text-sm font-semibold text-slate-900">{{ $article->senderName }}</span>
        <span class="text-sm text-slate-600">{{ $article->createdAt->format('d.m.Y, H:i') }} Uhr</span>
        @if ($article->channel)
            <span class="text-xs text-slate-600">· {{ $article->channel }}</span>
        @endif
        @if ($latest)
            <span class="ml-auto text-xs font-semibold text-brand-700">Neueste Nachricht</span>
        @endif
    </header>

    <div class="mt-3">
        @if ($article->hasText())
            {{-- body and quote are sanitized by App\Zammad (symfony/html-sanitizer). --}}
            <div class="knowledge-text">{{ $article->body }}</div>
        @else
            <p class="text-sm text-slate-600">(kein Text, nur Anhang)</p>
        @endif

        @if ($article->quote)
            <details class="group mt-3">
                <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-down" size="16" class="transition group-open:rotate-180" />
                    <span class="group-open:hidden">Zitat anzeigen</span>
                    <span class="hidden group-open:inline">Zitat ausblenden</span>
                </summary>
                <div class="knowledge-text mt-2 border-l-2 border-slate-200 pl-4 text-slate-600">{{ $article->quote }}</div>
            </details>
        @endif
    </div>

    @if ($article->attachments !== [])
        <ul class="mt-4 space-y-1 border-t border-slate-100 pt-3 text-sm" aria-label="Anhänge">
            @foreach ($article->attachments as $attachment)
                <li class="flex flex-wrap items-center gap-x-2">
                    <x-icon :name="$attachment->kindLabel() === 'Bild' ? 'image' : 'file'" size="16" class="text-slate-400" />
                    <span class="font-mono text-slate-900">{{ $attachment->filename }}</span>
                    <span class="text-slate-600">{{ $attachment->kindLabel() }}, {{ $attachment->sizeLabel() }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</article>
