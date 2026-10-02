@props(['status'])

@php
    [$tone, $label] = match ($status) {
        'draft' => ['warning', 'Entwurf'],
        'active' => ['success', 'Aktiv'],
        'deprecated' => ['neutral', 'Veraltet'],
        default => ['neutral', 'Ohne Status'],
    };
@endphp

<x-badge :tone="$tone" {{ $attributes }}>{{ $label }}</x-badge>
