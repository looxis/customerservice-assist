@props(['decision' => false])

{{-- Free result of an internal clarification (PROJ-39): inside the form of the clarification (it uses its Alpine state:
     choice, note, busy). With a decision it is the last choice, "Anderes Ergebnis"; without one it is the only input. --}}
<div @class(['rounded-md p-3 ring-1 ring-slate-300 ring-inset transition focus-within:ring-2 focus-within:ring-brand', 'mt-2' => $decision])>
    <label for="klaerung-text" class="block text-sm font-semibold text-slate-900">{{ $decision ? 'Anderes Ergebnis oder Ergänzung' : 'Ergebnis der internen Prüfung' }}</label>
    <textarea id="klaerung-text" name="klaerung_text" rows="3" x-model="note" maxlength="{{ config('analysis.max_context_length') }}"
              placeholder="{{ $decision ? 'Passt keine Option? Hier das Ergebnis eintragen – oder eine Ergänzung zur gewählten Option.' : 'Was hat die interne Prüfung ergeben?' }}"
              class="mt-2 block w-full rounded-md border-0 bg-slate-50 px-3 py-2 text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 placeholder:text-slate-500 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand sm:text-sm"></textarea>
    @error('klaerung_text')<p class="mt-1 text-sm text-danger-700">{{ $message }}</p>@enderror
    @error('klaerung_option')<p class="mt-1 text-sm text-danger-700">{{ $message }}</p>@enderror
    @error('klaerung_analyse')<p class="mt-1 text-sm text-danger-700">{{ $message }}</p>@enderror
    <p class="mt-1 text-xs text-slate-600">
        @if ($decision)Ohne gewählte Option gilt dein Text als Ergebnis, mit gewählter Option als Ergänzung dazu. @endif
        Gilt für die KI als geprüfter Fakt. Danach geht es mit diesem Ergebnis weiter.
    </p>
</div>
<div class="mt-3">
    <x-button type="submit" variant="secondary" x-bind:disabled="busy || (choice === '' && note.trim() === '')"><x-icon name="refresh" size="16" /> Mit diesem Ergebnis fortsetzen</x-button>
</div>
