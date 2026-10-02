@props(['type' => 'info'])

@php
    $classes = 'rounded-md p-4 text-sm font-medium ring-1 ring-inset wrap-anywhere '.match ($type) {
        'success' => 'bg-success-500/10 text-success-700 ring-success-500/20',
        'warning' => 'bg-warning-500/10 text-warning-700 ring-warning-500/20',
        'error' => 'bg-danger-500/10 text-danger-700 ring-danger-500/20',
        default => 'bg-trust-500/10 text-trust-500 ring-trust-500/20',
    };
    $role = in_array($type, ['warning', 'error']) ? 'alert' : 'status';
@endphp

<div role="{{ $role }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</div>
