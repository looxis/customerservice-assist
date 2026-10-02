@props(['href', 'icon', 'active' => false])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium focus-visible:outline-2 focus-visible:outline-brand',
        'bg-brand-tint text-brand-700' => $active,
        'text-slate-700 hover:bg-slate-50' => ! $active,
    ]) }}
>
    <x-icon :name="$icon" />
    <span class="truncate">{{ $slot }}</span>
</a>
