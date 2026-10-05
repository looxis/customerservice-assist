@props(['value' => ''])

@php
    $serverError = isset($errors) ? $errors->first('ticket') : null;
@endphp

{{-- Ticket input (PROJ-6). Works as a plain GET form; Alpine adds paste, cleanup and the double-submit lock. --}}
<form
    method="GET"
    action="{{ route('tickets.lookup') }}"
    x-data="ticketLookup(@js(old('ticket', $value)), @js($serverError ?? ''))"
    x-ref="form"
    x-on:submit="submit($event)"
    x-on:pageshow.window="reset()"
    role="search"
    aria-label="Ticket laden"
    {{ $attributes->merge(['class' => 'rounded-lg bg-white p-6 shadow-1 ring-1 ring-slate-200']) }}
>
    <label for="ticket" class="block text-sm font-medium text-slate-900">Ticket aus Zammad</label>
    <div class="mt-1 flex flex-col gap-3 sm:flex-row">
        <input
            type="text"
            name="ticket"
            id="ticket"
            x-ref="input"
            x-model="value"
            x-on:input="error = ''"
            value="{{ old('ticket', $value) }}"
            placeholder="Ticket#2137942"
            autocomplete="off"
            autofocus
            inputmode="text"
            aria-describedby="ticket-help"
            x-bind:aria-invalid="error ? 'true' : null"
            @if ($serverError) aria-invalid="true" @endif
            class="block w-full min-w-0 flex-1 rounded-md border-0 bg-white px-3 py-2 font-mono text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand sm:text-sm"
            x-bind:class="error ? 'ring-danger-500 focus:ring-danger-500' : ''"
        >
        <div class="flex gap-3">
            <x-button type="button" variant="secondary" x-on:click="paste()" x-bind:disabled="busy" class="min-h-11 flex-1 sm:flex-none">
                <x-icon name="download" size="16" /> Einfügen
            </x-button>
            <x-button type="submit" x-bind:disabled="busy" class="min-h-11 flex-1 sm:flex-none">Ticket laden</x-button>
        </div>
    </div>
    <p id="ticket-help" class="mt-2 text-sm" role="status" aria-live="polite">
        <span x-show="error" x-text="error" class="text-danger-700" @unless ($serverError) x-cloak @endunless>{{ $serverError }}</span>
        <span x-show="! error" class="text-slate-600" @if ($serverError) x-cloak @endif>In Zammad am Ticket auf „Kopieren" klicken, dann hier „Einfügen".</span>
    </p>
</form>
