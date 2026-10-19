@props(['documentId', 'analysis' => null, 'ticket' => null, 'reported' => false])

{{-- "Bitte prüfen" (PROJ-56): any staff member can report a document as possibly outdated. A report never switches a document off. --}}
<details class="mt-3 text-sm" @if (session('review_reported') === $documentId) open @endif>
    <summary class="cursor-pointer font-medium text-slate-700 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand">Möglicherweise veraltet? Bitte prüfen lassen</summary>
    <form method="POST" action="{{ route('knowledge.review.store') }}" class="mt-3 max-w-xl space-y-3 rounded-md bg-slate-50 p-4 ring-1 ring-slate-200 ring-inset">
        @csrf
        <input type="hidden" name="document_id" value="{{ $documentId }}">
        @if ($analysis)<input type="hidden" name="analysis" value="{{ $analysis }}">@endif
        @if ($ticket)<input type="hidden" name="ticket" value="{{ $ticket }}">@endif
        <p class="text-slate-700">Melde dieses Wissen, wenn es nicht mehr passt, nicht hilft oder Kunden schlecht darauf reagieren. Ein Admin prüft es; die Meldung schaltet nichts ab.</p>
        <div>
            <label class="block font-medium text-slate-900" for="review-reason-{{ $documentId }}-{{ $analysis }}">Grund</label>
            <select id="review-reason-{{ $documentId }}-{{ $analysis }}" name="reason" required class="mt-1 block w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-8 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
                @foreach (\App\Knowledge\KnowledgeReviewLog::REASONS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block font-medium text-slate-900" for="review-comment-{{ $documentId }}-{{ $analysis }}">Kommentar <span class="font-normal text-slate-600">(optional, bitte keine Kundendaten)</span></label>
            <textarea id="review-comment-{{ $documentId }}-{{ $analysis }}" name="comment" rows="3" maxlength="1000" class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"></textarea>
        </div>
        <x-button type="submit" variant="secondary">Bitte prüfen melden</x-button>
    </form>
</details>
