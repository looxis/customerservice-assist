{{-- The attachments of one message with "Ansehen" and "Herunterladen" (PROJ-60). --}}
@props(['article', 'ticketNumber' => null])

@if ($article->attachments !== [])
    <ul class="mt-4 space-y-1 border-t border-slate-100 pt-3 text-sm" aria-label="Anhänge">
        @foreach ($article->attachments as $attachment)
            <li class="flex flex-wrap items-center gap-x-2">
                <x-icon :name="$attachment->kindLabel() === 'Bild' ? 'image' : 'file'" size="16" class="text-slate-400" />
                <span class="font-mono text-slate-900">{{ $attachment->filename }}</span>
                <span class="text-slate-600">{{ $attachment->kindLabel() }}{{ $attachment->embedded ? ' im Text' : '' }}, {{ $attachment->sizeLabel() }}</span>
                @if ($attachment->id !== null && $article->id !== null && $ticketNumber !== null)
                    @php($attachmentUrl = route('tickets.attachments.show', ['number' => $ticketNumber, 'article' => $article->id, 'attachment' => $attachment->id]))
                    <span class="flex items-center gap-3">
                        @if ($attachment->isViewable())
                            <button type="button" class="inline-flex items-center gap-1 font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand"
                                    x-data x-on:click="$dispatch('view-attachment', { url: @js($attachmentUrl), downloadUrl: @js($attachmentUrl.'?download=1'), name: @js($attachment->filename), opener: $el })">
                                <x-icon name="eye" size="16" /> Ansehen
                            </button>
                        @endif
                        <a href="{{ $attachmentUrl }}?download=1" class="inline-flex items-center gap-1 font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand">
                            <x-icon name="download" size="16" /> Herunterladen
                        </a>
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
