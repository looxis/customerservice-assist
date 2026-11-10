@props(['text', 'align' => 'left'])

{{-- A label followed by a question mark that explains it on hover, focus or tap; the box opens below the label. --}}
<span x-data="{ open: false }" x-id="['help-tip']" x-on:mouseleave="open = false" x-on:keydown.escape="open = false" x-on:click.outside="open = false" {{ $attributes->merge(['class' => 'relative inline-flex items-center gap-1']) }}>
    {{ $slot }}
    <button type="button" x-on:mouseenter="open = true" x-on:focus="open = true" x-on:blur="open = false" x-on:click="open = true" x-bind:aria-describedby="$id('help-tip')" aria-label="Erklärung" class="rounded-full text-slate-500 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-brand">
        <x-icon name="help" :size="14" />
    </button>
    <span x-bind:id="$id('help-tip')" role="tooltip" x-show="open" x-cloak x-transition.opacity class="absolute top-full z-20 {{ $align === 'right' ? 'right-0' : 'left-0' }} mt-1 w-72 max-w-[calc(100vw-3rem)] rounded-md bg-slate-900 px-3 py-2 text-xs leading-relaxed font-normal text-white shadow-2">{{ $text }}</span>
</span>
