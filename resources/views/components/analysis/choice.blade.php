@props(['name', 'legend', 'options', 'model', 'value' => null, 'required' => false, 'hint' => null])

@php
    $selected = old($name, $value);
    $error = isset($errors) ? $errors->first($name) : null;
@endphp

{{-- A short single choice as radio buttons, bound to the Alpine property of the form (PROJ-86). --}}
<fieldset @if ($error) aria-describedby="{{ $name }}-error" @endif>
    <legend class="block text-sm font-medium text-slate-900">{{ $legend }}@if ($required)<span class="text-danger-500" aria-hidden="true"> *</span>@endif</legend>
    @if ($hint)
        <p class="mt-0.5 text-sm text-slate-600">{{ $hint }}</p>
    @endif
    <div class="mt-1 flex flex-wrap gap-x-6 gap-y-1">
        @foreach ($options as $optionValue => $optionLabel)
            <label class="flex min-h-9 items-center gap-2 text-sm text-slate-900">
                <input type="radio" name="{{ $name }}" value="{{ $optionValue }}" x-model="{{ $model }}" @checked($selected === $optionValue) @required($required) class="size-4 border-slate-300 text-brand focus:ring-brand">
                {{ $optionLabel }}
            </label>
        @endforeach
    </div>
    @if ($error)
        <p id="{{ $name }}-error" class="mt-1 text-sm text-danger-700" role="alert">{{ $error }}</p>
    @endif
</fieldset>
