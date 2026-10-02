@props(['tone' => 'neutral', 'compact' => false])

@php
    $classes = 'inline-flex items-center whitespace-nowrap rounded-pill font-medium ring-1 ring-inset '
        .($compact ? 'px-2 py-0.5 text-[11px] ' : 'px-2.5 py-0.5 text-xs ')
        .match ($tone) {
            'success' => 'bg-success-500/10 text-success-500 ring-success-500/20',
            'info' => 'bg-trust-500/10 text-trust-500 ring-trust-500/20',
            'warning' => 'bg-warning-500/10 text-warning-500 ring-warning-500/20',
            'danger' => 'bg-danger-500/10 text-danger-500 ring-danger-500/20',
            default => 'bg-slate-100 text-slate-600 ring-slate-300/40',
        };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</span>
