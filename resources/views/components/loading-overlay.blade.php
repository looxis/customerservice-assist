{{-- Full-screen overlay for long-running actions. Trigger from anywhere:
     $dispatch('loading-start', { title: '…', text: '…' }) / $dispatch('loading-stop') --}}
<div
    x-data="{ open: false, title: '', text: '' }"
    x-on:loading-start.window="title = $event.detail?.title ?? 'Bitte warten …'; text = $event.detail?.text ?? ''; open = true"
    x-on:loading-stop.window="open = false"
    x-on:pageshow.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/50 p-4"
>
    <div role="alert" aria-live="assertive" class="flex w-full max-w-sm flex-col items-center gap-3 rounded-lg bg-white p-8 text-center shadow-3">
        <x-icon name="refresh" size="28" class="animate-spin text-brand" />
        <p class="text-base font-semibold text-slate-900" x-text="title"></p>
        <p class="text-sm text-slate-600" x-show="text" x-text="text"></p>
    </div>
</div>
