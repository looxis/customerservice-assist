@props(['result', 'number', 'stand' => null])

{{-- Report a gap in the knowledge base (PROJ-12). Opened empty or prefilled
     from a gap the AI named (event "report-gap"). --}}
<details id="wissensluecke" class="group/gap mt-4 rounded-md ring-1 ring-slate-200 ring-inset scroll-mt-4"
         x-data="{ missing: @js(old('missing', '')), topic: @js(old('topic', '')) }"
         x-on:report-gap.window="missing = $event.detail.missing; topic = $event.detail.topic; $el.open = true; $nextTick(() => { $el.scrollIntoView({ block: 'start' }); $refs.missing.focus() })"
         @if ($errors->hasAny(['missing', 'solution', 'comment'])) open @endif>
    <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-sm font-semibold text-slate-900 hover:text-brand focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
        <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open/gap:rotate-90" />
        Wissenslücke melden
    </summary>
    <form method="POST" action="{{ route('tickets.analysis.gaps.store', ['number' => $number, 'analysis' => $result['id']]) }}" class="space-y-3 border-t border-slate-100 p-3">
        @csrf
        @if ($stand) <input type="hidden" name="stand" value="{{ $stand }}"> @endif
        <input type="hidden" name="topic" x-model="topic">
        <p class="text-xs font-medium text-warning-700">Bitte keine Kundendaten eintragen – die Meldung beschreibt eine Regel, nicht den Fall.</p>
        <x-field id="gap-missing" label="Was fehlt?" :error="$errors->first('missing')" required>
            <textarea name="missing" id="gap-missing" x-ref="missing" x-model="missing" rows="2" maxlength="1000" required
                      class="block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"></textarea>
        </x-field>
        <x-field id="gap-solution" label="So lösen wir das / so habe ich entschieden (optional)" :error="$errors->first('solution')">
            <textarea name="solution" id="gap-solution" rows="3" maxlength="4000"
                      class="block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">{{ old('solution') }}</textarea>
        </x-field>
        <x-field id="gap-comment" label="Kommentar (optional)" :error="$errors->first('comment')">
            <textarea name="comment" id="gap-comment" rows="2" maxlength="2000"
                      class="block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">{{ old('comment') }}</textarea>
        </x-field>
        <div class="flex justify-end">
            <x-button type="submit">Wissenslücke melden</x-button>
        </div>
    </form>
</details>
