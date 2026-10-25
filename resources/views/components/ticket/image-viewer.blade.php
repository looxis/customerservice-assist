{{-- Image viewer for ticket attachments (PROJ-60): one overlay for the whole page, opened by the event "view-attachment". --}}
<div x-data="imageViewer" x-cloak x-show="open" x-on:view-attachment.window="show($event.detail)" x-on:keydown.window="key($event)"
     class="fixed inset-0 z-50 flex flex-col bg-slate-900/90" role="dialog" aria-modal="true" aria-label="Bildanhang ansehen">
    <div class="flex items-center justify-between gap-3 px-4 py-3 text-white">
        <p class="min-w-0 truncate font-mono text-sm" x-text="name"></p>
        <button type="button" x-ref="close" x-on:click="close()" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm font-semibold hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-white">
            <x-icon name="x" size="18" /> Schließen
        </button>
    </div>

    <div x-ref="stage" class="relative min-h-0 flex-1 touch-none select-none overflow-hidden" x-on:click.self="backdrop()"
         x-on:wheel.prevent="wheel($event)" x-on:pointerdown="dragStart($event)" x-on:pointermove="dragMove($event)" x-on:pointerup="dragEnd($event)" x-on:pointercancel="dragEnd($event)">
        <img x-ref="image" x-show="src" :src="src" alt="" draggable="false" x-on:load="loaded()"
             class="absolute left-0 top-0 max-w-none origin-top-left" :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
             :style="`transform: translate(${x}px, ${y}px) scale(${scale})`">
        <p x-show="loading" class="absolute inset-0 flex items-center justify-center text-sm text-white" role="status">Bild wird geladen …</p>
        <div x-show="error" class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center text-white" role="alert">
            <p x-text="error"></p>
            <button type="button" x-on:click="load()" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-white">Noch einmal laden</button>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3 text-sm text-white">
        <p class="text-white/80">
            <kbd class="rounded bg-white/15 px-1.5">Mausrad</kbd> / <kbd class="rounded bg-white/15 px-1.5">+</kbd> <kbd class="rounded bg-white/15 px-1.5">−</kbd> zoomen ·
            Ziehen / <kbd class="rounded bg-white/15 px-1.5">←</kbd><kbd class="rounded bg-white/15 px-1.5">↑</kbd><kbd class="rounded bg-white/15 px-1.5">↓</kbd><kbd class="rounded bg-white/15 px-1.5">→</kbd> verschieben ·
            <kbd class="rounded bg-white/15 px-1.5">0</kbd> einpassen · <kbd class="rounded bg-white/15 px-1.5">Esc</kbd> schließen
        </p>
        <div class="flex items-center gap-2">
            <button type="button" x-on:click="zoom(1 / 1.25)" class="inline-flex items-center rounded-md bg-white/10 px-2 py-1.5 hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white" aria-label="Verkleinern"><x-icon name="minus" size="16" /></button>
            <span class="w-14 text-center tabular-nums" x-text="Math.round(scale * 100) + ' %'" aria-live="polite"></span>
            <button type="button" x-on:click="zoom(1.25)" class="inline-flex items-center rounded-md bg-white/10 px-2 py-1.5 hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white" aria-label="Vergrößern"><x-icon name="plus" size="16" /></button>
            <button type="button" x-on:click="fit()" class="inline-flex items-center gap-1 rounded-md bg-white/10 px-2 py-1.5 hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white"><x-icon name="maximize" size="16" /> Einpassen</button>
            <a :href="downloadUrl" class="inline-flex items-center gap-1 rounded-md bg-brand px-3 py-1.5 font-semibold hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-white"><x-icon name="download" size="16" /> Herunterladen</a>
        </div>
    </div>
</div>
