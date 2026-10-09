@props(['article', 'latest' => false, 'latestLabel' => 'Neueste Nachricht', 'rewindUrl' => null, 'translation' => null, 'retranslate' => null])

@php
    $tone = match ($article->kind) {
        \App\Zammad\ArticleKind::Customer => ['border' => 'border-l-brand', 'badge' => 'info', 'side' => 'md:mr-auto'],
        \App\Zammad\ArticleKind::Agent => ['border' => 'border-l-slate-400', 'badge' => 'neutral', 'side' => 'md:ml-auto'],
        \App\Zammad\ArticleKind::Internal => ['border' => 'border-l-warning-500', 'badge' => 'warning', 'side' => 'md:ml-auto'],
    };
@endphp

{{-- One message of the ticket thread (PROJ-6). --}}
<article
    @if ($latest) id="neueste-nachricht" x-data x-init="$el.scrollIntoView({ block: 'start' })" @endif
    @class([
        'scroll-mt-4 rounded-lg border-l-4 p-5 shadow-1 ring-1 wrap-anywhere md:w-4/5',
        $tone['border'],
        $tone['side'],
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
            <span class="ml-auto text-xs font-semibold text-brand-700">{{ $latestLabel }}</span>
        @endif
        @if ($rewindUrl)
            <a href="{{ $rewindUrl }}" @class(['inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold text-warning-700 ring-1 ring-warning-500/30 ring-inset hover:bg-warning-500/10 focus-visible:outline-2 focus-visible:outline-brand', 'ml-auto' => ! $latest])>Bis hierher testen</a>
        @endif
    </header>

    <div class="mt-3">
        @if (($translation['status'] ?? null) === 'translated' && $article->hasText())
            {{-- German translation first, original one click away (PROJ-28); "Original zuerst" is kept per browser. --}}
            <div x-data="{ originalFirst: (() => { try { return localStorage.getItem('translation.originalFirst') === '1' } catch (error) { return false } })() }"
                 x-on:translation-order.window="originalFirst = $event.detail">
                <div x-show="! originalFirst">
                    <p class="mb-2 inline-flex items-center gap-1 rounded-md bg-trust-500/10 px-2 py-0.5 text-xs font-medium text-trust-500">Übersetzt aus {{ $translation['language'] ?: 'einer anderen Sprache' }} · KI-Übersetzung</p>
                    <div class="mail-text whitespace-pre-line">{{ $translation['text'] }}</div>
                    <details class="group mt-2">
                        <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                            <x-icon name="chevron-down" size="16" class="transition group-open:rotate-180" />
                            <span class="group-open:hidden">Original anzeigen</span>
                            <span class="hidden group-open:inline">Original ausblenden</span>
                        </summary>
                        <div class="mt-2 border-l-2 border-slate-200 pl-4"><x-ticket.article-body :article="$article" /></div>
                    </details>
                </div>
                <div x-show="originalFirst" x-cloak>
                    <x-ticket.article-body :article="$article" />
                    <details class="group mt-2">
                        <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                            <x-icon name="chevron-down" size="16" class="transition group-open:rotate-180" />
                            <span class="group-open:hidden">Übersetzung anzeigen ({{ $translation['language'] ?: 'KI' }} → Deutsch)</span>
                            <span class="hidden group-open:inline">Übersetzung ausblenden</span>
                        </summary>
                        <div class="mail-text mt-2 border-l-2 border-trust-500/30 pl-4 whitespace-pre-line">{{ $translation['text'] }}</div>
                    </details>
                </div>
                @if ($retranslate)
                    <form method="POST" action="{{ $retranslate['action'] }}" class="mt-2" x-data x-on:submit="$dispatch('loading-start', { title: 'Nachricht wird neu übersetzt …' })">
                        @csrf
                        <input type="hidden" name="nachricht" value="{{ $article->id }}">
                        @foreach ($retranslate['fields'] as $name => $value)
                            @foreach ((array) $value as $item)<input type="hidden" name="{{ $name }}{{ is_array($value) ? '[]' : '' }}" value="{{ $item }}">@endforeach
                        @endforeach
                        <button type="submit" class="text-xs font-semibold text-slate-600 underline hover:text-slate-900">Neu übersetzen</button>
                    </form>
                @endif
            </div>
        @else
            <x-ticket.article-body :article="$article" />
        @endif

        @if ($article->quote)
            <details class="group mt-3">
                <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-down" size="16" class="transition group-open:rotate-180" />
                    <span class="group-open:hidden">Zitat anzeigen</span>
                    <span class="hidden group-open:inline">Zitat ausblenden</span>
                </summary>
                <div class="mail-text mt-2 border-l-2 border-slate-200 pl-4 text-slate-600">{{ $article->quote }}</div>
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
