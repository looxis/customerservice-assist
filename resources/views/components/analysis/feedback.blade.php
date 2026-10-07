@props(['result', 'number'])

@php
    $levels = config('analysis.feedback_levels');
    $feedback = $result['feedback'] ?? null;
@endphp

{{-- Feedback on an analysis (PROJ-12): asked after copying, the suggestion
     follows how much the draft was changed; can be given any time. --}}
<div
    x-data="{
        open: false,
        level: @js($feedback['level'] ?? null),
        by: @js($feedback ? 'Bewertet von '.$feedback['by'].' am '.$feedback['at']->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr' : null),
        comment: @js($feedback['comment'] ?? ''),
        withComment: false,
        suggested: null,
        status: '',
        original: @js($result['reply_original']),
        suggest() {
            const field = document.querySelector('textarea[aria-label=Antwortentwurf]');
            const norm = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();
            const a = norm(this.original);
            const b = norm(field ? field.value : this.original);
            if (a === b) { return 'unchanged' }
            let prefix = 0;
            while (prefix < a.length && prefix < b.length && a[prefix] === b[prefix]) { prefix++ }
            let suffix = 0;
            while (suffix < a.length - prefix && suffix < b.length - prefix && a[a.length - 1 - suffix] === b[b.length - 1 - suffix]) { suffix++ }
            const changed = Math.max(a.length, b.length) - prefix - suffix;
            return changed / Math.max(a.length, 1) <= 0.2 ? 'slight' : 'major';
        },
        ask() { this.suggested = this.suggest(); this.open = true },
        async save(level) {
            this.status = 'saving';
            try {
                const response = await fetch(@js(route('tickets.analysis.feedback', ['number' => $number, 'analysis' => $result['id']])), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ level, suggested: this.suggested, comment: this.comment }),
                });
                if (! response.ok) { throw new Error(response.status) }
                const data = await response.json();
                this.level = data.level;
                this.by = data.by;
                this.status = 'saved';
                this.open = false;
            } catch (error) {
                this.status = 'error';
            }
        },
    }"
    x-on:reply-copied.window="if (! level) { ask() }"
    class="mt-4 rounded-md bg-slate-50 p-3 text-sm ring-1 ring-slate-200 ring-inset"
>
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <template x-if="level && ! open">
            <p class="text-slate-900">
                <span x-show="status === 'saved'" class="font-medium text-success-700">Danke – </span>
                <span class="font-medium" x-text="@js($levels)[level]"></span>
                <span class="text-xs text-slate-600" x-text="'· ' + by"></span>
            </p>
        </template>
        <button type="button" x-show="! open" x-on:click="ask()" class="text-sm font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand"
                x-text="level ? 'ändern' : 'Vorschlag bewerten'">Vorschlag bewerten</button>
    </div>

    <div x-show="open" x-cloak>
        <p class="font-medium text-slate-900">Wie brauchbar war der Vorschlag?</p>
        <div class="mt-2 flex flex-wrap gap-2" role="group" aria-label="Bewertung">
            @foreach ($levels as $key => $label)
                <button type="button" x-on:click="save(@js($key))"
                        class="rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand"
                        x-bind:class="suggested === @js($key) ? 'bg-brand text-white ring-brand' : 'bg-white text-slate-900 ring-slate-300 hover:bg-slate-100'">
                    {{ $label }}<span x-show="suggested === @js($key)" class="sr-only"> (vorgeschlagen)</span>
                </button>
            @endforeach
        </div>
        <button type="button" x-show="! withComment" x-on:click="withComment = true" class="mt-2 text-xs font-semibold text-brand hover:text-brand-hover">Kommentar hinzufügen</button>
        <div x-show="withComment" x-cloak class="mt-2">
            <label class="block text-xs font-medium text-slate-900" for="feedback-comment-{{ $result['id'] }}">Was war falsch oder fehlte? (optional)</label>
            <textarea id="feedback-comment-{{ $result['id'] }}" x-model="comment" rows="2" maxlength="2000"
                      class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"></textarea>
            <p class="mt-1 text-xs text-slate-600">Danach eine Stufe anklicken – der Kommentar wird mit ihr gespeichert.</p>
        </div>
        <p x-show="status === 'error'" x-cloak class="mt-2 text-xs text-danger-700">Nicht gespeichert – bitte erneut klicken.</p>
    </div>
</div>
