@props(['ticket', 'translations' => []])

@php
    $article = $ticket->focusArticle();
    $count = count($ticket->articles);
    $customers = collect($ticket->articles)->filter(fn ($item) => $item->kind === \App\Zammad\ArticleKind::Customer)->count();
@endphp

{{-- The message to read first on the ticket page (PROJ-43): the last customer message, a copy of the one in the thread. --}}
@if ($article)
    <section id="kundennachricht" aria-labelledby="focus-heading" class="scroll-mt-4 rounded-lg bg-white p-5 shadow-1 ring-1 ring-slate-200 md:p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <h2 id="focus-heading" class="text-base font-semibold text-slate-900">{{ $count > 1 ? ($customers > 0 ? 'Letzte Kundennachricht' : 'Letzte Nachricht') : 'Kundennachricht' }}</h2>
            @if ($count > 1)
                <a href="#verlauf" class="text-sm font-medium text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand">Der ganze Verlauf ({{ $count }} Nachrichten) steht unten</a>
            @endif
        </div>
        <div class="mt-3 max-h-[28rem] overflow-y-auto">
            <x-ticket.article :ticket-number="$ticket->number" :article="$article" :translation="$article->id !== null ? ($translations[$article->id] ?? null) : null" :full="true" />
        </div>
    </section>
@endif
