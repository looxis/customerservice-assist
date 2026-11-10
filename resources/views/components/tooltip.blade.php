@props(['align' => 'left'])

{{-- Wraps a control, usually a button, and shows the slot "content" below it on hover (after a short pause, so that
     moving across a row of buttons does not flash every box) or at once on keyboard focus; closes with Escape or a click. --}}
<span x-data="{ open: false, timer: null, show(delay) { clearTimeout(this.timer); this.timer = setTimeout(() => this.open = true, delay) }, hide() { clearTimeout(this.timer); this.open = false } }"
      x-id="['tooltip']"
      x-init="$el.querySelector('button, a')?.setAttribute('aria-describedby', $id('tooltip'))"
      x-on:mouseenter="show(300)" x-on:mouseleave="hide()" x-on:focusin="show(0)" x-on:focusout="hide()" x-on:keydown.escape="hide()" x-on:click="hide()"
      {{ $attributes->merge(['class' => 'relative inline-flex']) }}>
    {{ $slot }}
    <span x-bind:id="$id('tooltip')" role="tooltip" x-show="open" x-cloak x-transition.opacity class="pointer-events-none absolute top-full z-20 {{ $align === 'right' ? 'right-0' : 'left-0' }} mt-1 w-72 max-w-[calc(100vw-3rem)] rounded-md bg-slate-900 px-3 py-2 text-left text-xs leading-relaxed font-normal whitespace-normal text-white shadow-2">{{ $content }}</span>
</span>
