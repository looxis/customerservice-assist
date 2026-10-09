@props(['article'])

{{-- Original text of a message with its collapsed signature (PROJ-6).
     body and signature are sanitized by App\Zammad (symfony/html-sanitizer). --}}
@if ($article->hasText())
    <div class="mail-text">{{ $article->body }}</div>

    @if ($article->signature)
        <details class="group mt-2">
            <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                <x-icon name="chevron-down" size="16" class="transition group-open:rotate-180" />
                <span class="group-open:hidden">Signatur anzeigen</span>
                <span class="hidden group-open:inline">Signatur ausblenden</span>
            </summary>
            <div class="mail-text mt-2 text-slate-600">{{ $article->signature }}</div>
        </details>
    @endif
@else
    <p class="text-sm text-slate-600">(kein Text, nur Anhang)</p>
@endif
