@props(['text', 'label' => 'Text zum Kopieren', 'rows' => 12])

{{-- Read-only text with a copy button. Without clipboard access (e.g. no HTTPS)
     the text is selected so it can be copied with the keyboard. --}}
<div
    x-data="{
        copied: false,
        fallback: false,
        async copy() {
            try {
                await navigator.clipboard.writeText(this.$refs.text.value);
                this.fallback = false;
                this.copied = true;
                setTimeout(() => (this.copied = false), 2500);
            } catch (error) {
                this.$refs.text.focus();
                this.$refs.text.select();
                this.copied = false;
                this.fallback = true;
            }
        },
    }"
    {{ $attributes }}
>
    <textarea
        x-ref="text"
        readonly
        rows="{{ $rows }}"
        aria-label="{{ $label }}"
        class="block w-full rounded-md border-0 bg-slate-50 px-3 py-2 font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"
    >{{ $text }}</textarea>

    <div class="mt-3 flex flex-wrap items-center gap-3">
        <x-button variant="secondary" x-on:click="copy()"><x-icon name="doc" /> Kopieren</x-button>
        <span x-show="copied" x-cloak role="status" class="text-sm font-medium text-success-700">Kopiert</span>
        <span x-show="fallback" x-cloak role="status" class="text-sm text-slate-600">
            Der Browser erlaubt das Kopieren hier nicht. Der Text ist markiert – bitte mit Strg+C kopieren.
        </span>
    </div>
</div>
