@props(['id', 'label' => null, 'hint' => null, 'error' => null, 'required' => false])

<div {{ $attributes }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-900">
            {{ $label }}@if ($required)<span class="text-danger-500" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    <div @class(['mt-1' => $label])>
        {{ $slot }}
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-danger-500">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-sm text-slate-600">{{ $hint }}</p>
    @endif
</div>
