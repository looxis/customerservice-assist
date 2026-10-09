@props(['names', 'current' => null])

{{-- Name picker in the header (PROJ-5). Saves in the background; without JavaScript the form reloads the page. --}}
@if ($names === [])
    <span class="text-sm text-slate-600">Keine Namen hinterlegt</span>
@else
    <div
        x-data="{ open: false }"
        x-init="$store.staff.name = @js($current)"
        x-on:keydown.escape.window="if (open) { open = false; $refs.toggle.focus() }"
        x-on:click.outside="open = false"
        class="relative"
    >
        <button
            type="button"
            x-ref="toggle"
            x-on:click="open = ! open; if (open) $nextTick(() => $refs.list.querySelector('[aria-pressed=true], button')?.focus())"
            x-bind:aria-expanded="open.toString()"
            x-bind:aria-label="$store.staff.name ? `Angemeldet als ${$store.staff.name} – Name wechseln` : 'Name wählen'"
            aria-expanded="false"
            aria-label="{{ $current ? "Angemeldet als {$current} – Name wechseln" : 'Name wählen' }}"
            aria-controls="staff-picker-list"
            x-bind:class="{ 'text-slate-900 hover:bg-slate-50 px-2': $store.staff.name, 'bg-brand text-white shadow-1 hover:bg-brand-hover px-3': ! $store.staff.name }"
            @class([
                'inline-flex min-h-11 items-center gap-2 rounded-md text-sm font-semibold focus-visible:outline-2 focus-visible:outline-brand',
                'text-slate-900 hover:bg-slate-50 px-2' => $current,
                'bg-brand text-white shadow-1 hover:bg-brand-hover px-3' => ! $current,
            ])
        >
            <span x-show="$store.staff.name" @unless ($current) x-cloak @endunless class="flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-full bg-brand-tint font-display text-sm font-semibold text-brand-700" aria-hidden="true" x-text="($store.staff.name ?? '').charAt(0)">{{ mb_substr((string) $current, 0, 1) }}</span>
                <span class="hidden md:inline" x-text="$store.staff.name">{{ $current }}</span>
            </span>
            <span x-show="! $store.staff.name" @if ($current) x-cloak @endif>Name wählen</span>
            <x-icon name="chevron-down" size="16" class="hidden md:block" />
        </button>

        <form
            method="POST"
            action="{{ route('staff.select') }}"
            id="staff-picker-list"
            x-ref="list"
            x-show="open"
            x-cloak
            x-on:submit.prevent="if (await $store.staff.select($el, $event.submitter.value)) { open = false; $refs.toggle.focus() }"
            class="absolute right-0 z-40 mt-2 w-56 rounded-lg bg-white p-1 shadow-3 ring-1 ring-slate-200"
        >
            @csrf
            <p class="px-3 pt-2 pb-1 text-xs font-medium uppercase tracking-wide text-slate-600">Ich bin</p>
            @foreach ($names as $name)
                <button
                    type="submit"
                    name="name"
                    value="{{ $name }}"
                    x-bind:aria-pressed="($store.staff.name === @js($name)).toString()"
                    aria-pressed="{{ $current === $name ? 'true' : 'false' }}"
                    x-bind:disabled="$store.staff.saving"
                    class="flex min-h-11 w-full items-center justify-between rounded-md px-3 text-left text-sm text-slate-900 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-brand aria-pressed:bg-brand-tint aria-pressed:font-semibold aria-pressed:text-brand-700"
                >
                    {{ $name }}
                    <x-icon name="check" size="16" x-show="$store.staff.name === {{ \Illuminate\Support\Js::from($name) }}" x-cloak />
                </button>
            @endforeach
            <p x-show="$store.staff.failed" x-cloak class="px-3 py-2 text-xs text-danger-700" role="alert">Speichern fehlgeschlagen. Bitte noch einmal versuchen.</p>
        </form>
    </div>
@endif
