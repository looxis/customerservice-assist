@props(['analysis'])

@php
    $max = (int) config('analysis.max_question_length', 6000);
@endphp

{{-- Question without a ticket (PROJ-40): a text, the scope of the knowledge, and the wish for a reply draft. --}}
<x-card>
    <h2 id="question-heading" class="text-base font-semibold text-slate-900">Frage stellen</h2>
    <p class="mt-1 text-sm text-slate-600">Frage, Notiz zu einem Anruf oder eine eingefügte Kundennachricht – die App antwortet aus dem Unternehmenswissen.</p>

    @if (session('question_error'))
        <x-alert type="error" class="mt-4" role="alert">
            <p>{{ session('question_error') }}</p>
            @if (session('question_retry'))
                <p class="mt-3"><x-button type="submit" form="frage-formular" variant="secondary"><x-icon name="refresh" size="16" /> Erneut versuchen</x-button></p>
            @endif
        </x-alert>
    @endif

    <form id="frage-formular" method="POST" action="{{ route('questions.store') }}" class="mt-4 space-y-5"
          x-data="{ busy: false, length: @js(mb_strlen((string) old('text', ''))) }"
          x-on:submit="if (busy) { $event.preventDefault(); return } busy = true; $dispatch('loading-start', { title: 'Vorschlag wird erstellt …', text: 'Das dauert in der Regel 10 bis 30 Sekunden.' })"
          x-on:pageshow.window="busy = false">
        @csrf

        <x-field id="text" label="Deine Frage oder Notiz" hint="Bitte nach Möglichkeit keine Kundendaten eintragen (Name, Anschrift, E-Mail, Telefon, Bestellnummer). Erkennbare Kontaktdaten werden vor dem Senden an die KI durch Platzhalter ersetzt." :error="$errors->first('text')" required>
            <textarea name="text" id="text" rows="7" maxlength="{{ $max }}" required x-on:input="length = $event.target.value.length"
                      placeholder="z. B.:&#10;· Darf ich bei einem Fachhändler einen Neuversand ohne Rücksprache auslösen?&#10;· Anruf: Kundin sagt, das Motiv der Zaubertasse erscheint nicht – was frage ich zuerst?"
                      @if ($errors->has('text')) aria-invalid="true" @endif
                      class="block w-full rounded-md border-0 bg-white px-3 py-2 text-slate-900 shadow-1 ring-1 ring-inset placeholder:text-slate-500 focus:ring-2 focus:ring-inset sm:text-sm {{ $errors->has('text') ? 'ring-danger-500 focus:ring-danger-500' : 'ring-slate-300 focus:ring-brand' }}">{{ old('text') }}</textarea>
            <p class="mt-1 text-right text-xs text-slate-600"><span x-text="length">0</span> / {{ number_format($max, 0, ',', '.') }}</p>
        </x-field>

        <x-analysis.scope-fields :analysis="$analysis" :chosen-products="old('produkte', $analysis['suggestedProducts'] ?? [])" />

        @error('kundengruppe') <p class="text-sm text-danger-700">{{ $message }}</p> @enderror
        @error('produkte.*') <p class="text-sm text-danger-700">{{ $message }}</p> @enderror

        <label class="flex items-start gap-2 text-sm text-slate-900">
            <input type="checkbox" name="antwortentwurf" value="1" @checked(old('antwortentwurf')) class="mt-0.5 size-4 rounded border-slate-300 text-brand focus:ring-brand">
            <span>Auch einen Antwortentwurf an den Kunden formulieren
                <span class="block text-xs text-slate-600">Ohne Haken bekommst du nur die Auskunft für dich. Einen Entwurf kannst du auch später mit einer Nachfrage anfordern.</span>
            </span>
        </label>

        <div class="flex justify-end">
            <x-button type="submit" class="min-h-11" x-bind:disabled="busy"><x-icon name="sparkle" size="16" /> Vorschlag erstellen</x-button>
        </div>
    </form>
</x-card>
