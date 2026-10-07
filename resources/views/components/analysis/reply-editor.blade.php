@props(['result', 'number', 'readonly' => false])

@php
    $language = $result['result']['reply']['language'] ?? null;
@endphp

{{-- Editable reply draft (PROJ-10): saved in the background while typing,
     copied as plain text, open placeholders counted with the same rule as
     App\Analysis\Pseudonymizer::restore(). Without JavaScript the text stays readable. --}}
<div
    x-data="{
        text: @js($result['reply_text']),
        original: @js($result['reply_original']),
        edited: @js($result['reply_edited']),
        status: '',
        copied: false,
        fallback: false,
        timer: null,
        get open() { return (this.text.match(/\[[A-ZÄÖÜ][A-ZÄÖÜ\-]*(?:_\d+)?\]/gu) || []).length },
        changed() {
            this.status = 'saving';
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.save(false), 800);
        },
        async save(original) {
            try {
                const response = await fetch(@js(route('tickets.analysis.reply', ['number' => $number, 'analysis' => $result['id']])), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify(original ? { original: true } : { text: this.text }),
                });
                if (! response.ok) { throw new Error(response.status) }
                this.edited = (await response.json()).edited;
                this.status = 'saved';
            } catch (error) {
                this.status = 'error';
                this.timer = setTimeout(() => this.save(original), 5000);
            }
        },
        restore() {
            if (! confirm('Original der KI wiederherstellen? Deine Änderungen gehen dabei verloren.')) { return }
            clearTimeout(this.timer);
            this.text = this.original;
            this.status = 'saving';
            this.save(true);
        },
        async copy() {
            if (this.open > 0 && ! confirm(`Noch ${this.open} offene ${this.open === 1 ? 'Stelle' : 'Stellen'} – trotzdem kopieren?`)) { return }
            try {
                await navigator.clipboard.writeText(this.text);
                this.fallback = false;
                this.copied = true;
                this.$dispatch('reply-copied');
                setTimeout(() => (this.copied = false), 2500);
            } catch (error) {
                this.$refs.text.focus();
                this.$refs.text.select();
                this.fallback = true;
                this.$dispatch('reply-copied');
            }
        },
    }"
    class="mt-6"
>
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h3 class="text-sm font-semibold text-slate-900">Antwortentwurf @if ($language)<span class="font-normal text-slate-600">({{ $language }})</span>@endif</h3>
        <p class="text-xs text-slate-600" aria-live="polite">
            <span x-text="edited" @if (! $result['reply_edited']) x-cloak @endif>{{ $result['reply_edited'] }}</span>
            <span x-show="status === 'saving'" x-cloak> · wird gespeichert …</span>
            <span x-show="status === 'saved'" x-cloak class="text-success-700"> · Gespeichert</span>
            <span x-show="status === 'error'" x-cloak class="text-danger-700"> · Nicht gespeichert – wird erneut versucht</span>
        </p>
    </div>

    @if ($result['inserted'] !== [])
        <p class="mt-2 text-xs text-slate-600"><span class="font-semibold">Von der App eingesetzt:</span> {{ implode(', ', $result['inserted']) }} – bitte prüfen.</p>
    @endif
    <p x-show="open > 0" x-cloak class="mt-2 text-xs font-semibold text-warning-700">
        <span x-text="open"></span> <span x-text="open === 1 ? 'Stelle' : 'Stellen'"></span> noch ausfüllen (in eckigen Klammern)
    </p>

    <textarea
        x-ref="text"
        x-model="text"
        @if ($readonly) readonly @else x-on:input="changed()" @endif
        rows="12"
        maxlength="{{ config('analysis.max_reply_length') }}"
        aria-label="Antwortentwurf"
        class="mt-2 field-sizing-content block min-h-48 w-full rounded-md border-0 bg-white px-3 py-2 text-sm leading-relaxed text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"
    >{{ $result['reply_text'] }}</textarea>

    <div class="mt-3 flex flex-wrap items-center gap-3">
        <x-button x-on:click="copy()" x-bind:disabled="text.trim() === ''"><x-icon name="doc" size="16" /> Kopieren</x-button>
        @unless ($readonly)
        <x-button variant="secondary" x-show="text !== original" x-cloak x-on:click="restore()"><x-icon name="refresh" size="16" /> Original der KI wiederherstellen</x-button>
        @endunless
        <span x-show="copied" x-cloak role="status" class="text-sm font-medium text-success-700">Kopiert</span>
        <span x-show="fallback" x-cloak role="status" class="text-sm text-slate-600">Der Browser erlaubt das Kopieren hier nicht. Der Text ist markiert – bitte mit Strg+C kopieren.</span>
    </div>
</div>
