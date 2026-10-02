@props(['variant' => 'primary', 'type' => 'button', 'href' => null])

@php
    $classes = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold shadow-1 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:cursor-not-allowed disabled:opacity-50 '.match ($variant) {
        'secondary' => 'bg-white text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50',
        'danger' => 'bg-danger-500 text-white hover:bg-danger-500/90',
        default => 'bg-brand text-white hover:bg-brand-hover',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
