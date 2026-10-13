@props(['result', 'number'])

{{-- Follow-up question or addition to a question (PROJ-40): continues the case with the earlier text and the clarifications. --}}
@if ($result['is_latest'] ?? true)
    <section id="nachfrage" aria-labelledby="follow-up-heading" class="scroll-mt-4">
        <x-card>
            <h2 id="follow-up-heading" class="text-base font-semibold text-slate-900">Nachfrage oder Ergänzung</h2>
            <p class="mt-1 text-sm text-slate-600">Der bisherige Text und deine Klärungen bleiben erhalten; die App erstellt damit einen neuen Vorschlag.</p>

            @if ($errors->has('klaerung_text') || $errors->has('klaerung_analyse'))
                <x-alert type="error" class="mt-4" role="alert">{{ $errors->first('klaerung_text') ?: $errors->first('klaerung_analyse') }}</x-alert>
            @endif

            <form method="POST" action="{{ route('questions.follow-up', ['number' => $number]) }}" class="mt-4 space-y-4"
                  x-data="{ busy: false, note: @js((string) old('klaerung_text', '')) }"
                  x-on:submit="if (busy) { $event.preventDefault(); return } busy = true; $dispatch('loading-start', { title: 'Vorschlag wird erstellt …', text: 'Das dauert in der Regel 10 bis 30 Sekunden.' })"
                  x-on:pageshow.window="busy = false">
                @csrf
                <input type="hidden" name="klaerung_analyse" value="{{ $result['id'] }}">
                <x-field id="nachfrage-text" label="Was soll noch berücksichtigt werden?" hint="Bitte nach Möglichkeit keine Kundendaten eintragen." required>
                    <textarea name="klaerung_text" id="nachfrage-text" rows="3" maxlength="{{ (int) config('analysis.max_question_length', 6000) }}" x-model="note"
                              class="block w-full rounded-md border-0 bg-white px-3 py-2 text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand sm:text-sm"></textarea>
                </x-field>
                <input type="hidden" name="antwortentwurf" value="0">
                <label class="flex items-start gap-2 text-sm text-slate-900">
                    <input type="checkbox" name="antwortentwurf" value="1" @checked(old('antwortentwurf', $result['has_reply'] ?? false)) class="mt-0.5 size-4 rounded border-slate-300 text-brand focus:ring-brand">
                    <span>Antwortentwurf an den Kunden formulieren</span>
                </label>
                <div class="flex justify-end">
                    <x-button type="submit" variant="secondary" x-bind:disabled="busy || note.trim() === ''"><x-icon name="refresh" size="16" /> Neuen Vorschlag erstellen</x-button>
                </div>
            </form>
        </x-card>
    </section>
@endif
