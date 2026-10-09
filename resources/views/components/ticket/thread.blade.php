@props(['articles', 'later' => [], 'rewound' => false, 'rewindUrl' => null, 'translations' => [], 'untranslated' => 0, 'translate' => null, 'canRetranslate' => false])

@php
    $count = count($articles);
    $collapse = $count > 10;
    $hidden = $collapse ? range(1, $count - 6) : [];
    $lastCustomer = collect($articles)->keys()->filter(fn ($index) => $articles[$index]->kind === \App\Zammad\ArticleKind::Customer)->last();
    $translationOf = fn ($article) => $article->id !== null ? ($translations[$article->id] ?? null) : null;
    $hasTranslations = collect([...$articles, ...$later])->contains(fn ($article) => ($translationOf($article)['status'] ?? null) === 'translated');
    $retranslate = $canRetranslate && $translate ? $translate : null;
@endphp

{{-- Ticket thread, oldest first (PROJ-6). Above 10 messages the middle part is collapsed. --}}
<section id="verlauf" aria-label="Verlauf" class="scroll-mt-4 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-base font-semibold text-slate-900">Verlauf</h2>
        @if ($hasTranslations)
            {{-- Order of translation and original (PROJ-28), kept in this browser. --}}
            <div class="inline-flex rounded-md text-sm ring-1 ring-slate-300 ring-inset" role="group" aria-label="Reihenfolge von Übersetzung und Original"
                 x-data="{ originalFirst: (() => { try { return localStorage.getItem('translation.originalFirst') === '1' } catch (error) { return false } })(), set(value) { this.originalFirst = value; try { localStorage.setItem('translation.originalFirst', value ? '1' : '0') } catch (error) {} this.$dispatch('translation-order', value) } }">
                <button type="button" x-on:click="set(false)" x-bind:aria-pressed="! originalFirst" class="rounded-l-md px-3 py-1 font-medium" x-bind:class="! originalFirst ? 'bg-brand text-white' : 'text-slate-700 hover:bg-slate-50'">Deutsch zuerst</button>
                <button type="button" x-on:click="set(true)" x-bind:aria-pressed="originalFirst" class="rounded-r-md px-3 py-1 font-medium" x-bind:class="originalFirst ? 'bg-brand text-white' : 'text-slate-700 hover:bg-slate-50'">Original zuerst</button>
            </div>
        @endif
    </div>

    @if (session('translation_error'))
        <x-alert type="error" role="alert">{{ session('translation_error') }}</x-alert>
    @endif
    @if (session()->has('translation_done'))
        <x-alert type="success">{{ session('translation_done') === 1 ? 'Eine Nachricht wurde übersetzt.' : session('translation_done').' Nachrichten wurden übersetzt.' }}</x-alert>
    @endif

    @if ($untranslated > 0 && $translate)
        <form method="POST" action="{{ $translate['action'] }}" class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-trust-500/10 p-4 text-sm text-slate-900 ring-1 ring-trust-500/20 ring-inset"
              x-data="{ busy: false }" x-on:submit="if (busy) { $event.preventDefault(); return } busy = true; $dispatch('loading-start', { title: 'Nachrichten werden übersetzt …', text: 'Das dauert in der Regel 5 bis 15 Sekunden.' })" x-on:pageshow.window="busy = false">
            @csrf
            @foreach ($translate['fields'] as $name => $value)
                @foreach ((array) $value as $item)<input type="hidden" name="{{ $name }}{{ is_array($value) ? '[]' : '' }}" value="{{ $item }}">@endforeach
            @endforeach
            <p>{{ $untranslated === 1 ? 'Eine Nachricht ist' : $untranslated.' Nachrichten sind' }} nicht auf Deutsch.</p>
            <x-button type="submit" x-bind:disabled="busy">Übersetzen</x-button>
        </form>
    @endif

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
                            <x-ticket.article :article="$articles[$hiddenIndex]" :translation="$translationOf($articles[$hiddenIndex])" :retranslate="$retranslate" />
                        @endforeach
                    </div>
                </details>
            @endif

            @continue(in_array($index, $hidden, true))

            @if ($index === $lastCustomer && isset($summary))
                {{ $summary }}
            @endif

            <x-ticket.article :article="$article" :translation="$translationOf($article)" :retranslate="$retranslate" :latest="$index === $count - 1" :latest-label="$rewound ? 'Stand des Testlaufs' : 'Neueste Nachricht'" :rewind-url="$rewindUrl && $article->kind === \App\Zammad\ArticleKind::Customer && $article->id !== null ? $rewindUrl($article->id) : null" />
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
                    <x-ticket.article :article="$article" :translation="$translationOf($article)" />
                @endforeach
            </div>
        </details>
    @endif
</section>
