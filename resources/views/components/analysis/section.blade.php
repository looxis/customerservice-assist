@props(['title', 'count' => null])

{{-- Collapsible part of the analysis result (PROJ-10), closed at first. --}}
<details {{ $attributes->merge(['class' => 'group border-t border-slate-100']) }}>
    <summary class="flex cursor-pointer list-none items-center gap-2 py-3 text-sm font-semibold text-slate-900 hover:text-brand focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
        <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open:rotate-90" />
        {{ $title }}@if ($count !== null) <span class="font-normal text-slate-600">({{ $count }})</span>@endif
    </summary>
    <div class="pb-4 pl-6 text-sm text-slate-900">
        {{ $slot }}
    </div>
</details>
