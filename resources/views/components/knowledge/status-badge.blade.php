@props(['status'])

@php
    [$tone, $label] = match ($status) {
        'active' => ['success', 'Aktiv'],
        'deprecated' => ['neutral', 'Veraltet'],
        default => ['neutral', 'Ohne Status'],
    };
@endphp

<x-badge :tone="$tone" {{ $attributes }}>{{ $label }}</x-badge>
