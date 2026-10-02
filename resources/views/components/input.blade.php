@props(['name', 'id' => null, 'label' => null, 'hint' => null, 'error' => null, 'required' => false, 'type' => 'text', 'value' => null])

@php
    $id ??= $name;
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error ??= isset($errors) ? $errors->first($key) : null;
    $describedBy = $error ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp

<x-field :$id :$label :$hint :$error :$required>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($key, $value) }}"
        @required($required)
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'block w-full rounded-md border-0 bg-white px-3 py-1.5 text-slate-900 shadow-1 ring-1 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-inset disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm',
            'ring-slate-300 focus:ring-brand' => ! $error,
            'ring-danger-500 focus:ring-danger-500' => $error,
        ]) }}
    >
</x-field>
