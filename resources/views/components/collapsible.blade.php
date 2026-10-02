@props(['title', 'open' => false])

<details @if ($open) open @endif {{ $attributes->merge(['class' => 'group rounded-lg bg-white shadow-1 ring-1 ring-slate-200']) }}>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-lg p-6 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
        <span class="flex min-w-0 flex-wrap items-center gap-3">
            <span class="text-base font-semibold text-slate-900">{{ $title }}</span>
            {{ $meta ?? '' }}
        </span>
        <x-icon name="chevron-down" class="text-slate-400 transition group-open:rotate-180" />
    </summary>

    <div class="px-6 pb-6 wrap-anywhere">
        {{ $slot }}
    </div>
</details>
