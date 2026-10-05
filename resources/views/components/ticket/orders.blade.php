@props(['ticket', 'number', 'selected' => [], 'suggestions' => [], 'moreSuggestions' => false, 'lookups' => [], 'problem' => null])

@php
    $url = fn (array $selection): string => route('tickets.show', ['number' => $number, 'bestellungen' => array_values($selection)]);
    $loadedNumbers = collect($lookups)->flatMap(fn ($lookup) => collect($lookup->orders)->pluck('externalNumber'))->all();
    $mentions = collect($ticket->orders)->keyBy('number');
    $inputError = isset($errors) ? $errors->first('bestellnummer') : null;
@endphp

{{-- Orders of the ticket (PROJ-7): suggestions from the thread, manual input, loaded EOCS orders. --}}
<section class="mt-6 border-t border-slate-100 pt-4" aria-labelledby="orders-heading">
    <h3 id="orders-heading" class="text-sm font-semibold text-slate-900">Bestellungen</h3>

    @if ($suggestions !== [])
        <div class="mt-3 flex flex-wrap items-center gap-2" aria-label="Gefundene Bestellnummern">
            <span class="text-sm text-slate-600">Im Ticket gefunden:</span>
            @foreach ($suggestions as $suggestion)
                @if (in_array($suggestion->value, $selected, true))
                    <span class="inline-flex min-h-9 items-center gap-1 rounded-md bg-brand-tint px-3 font-mono text-sm text-brand-700" aria-current="true">
                        <x-icon name="check" size="14" /> {{ $suggestion->value }}
                    </span>
                @else
                    <a href="{{ $url([...$selected, $suggestion->value]) }}" x-data x-on:click="$dispatch('loading-start', { title: 'Bestellung wird geladen …' })"
                       class="inline-flex min-h-9 items-center gap-2 rounded-md bg-white px-3 text-sm text-slate-900 shadow-1 ring-1 ring-slate-300 ring-inset hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-brand">
                        <span class="font-mono">{{ $suggestion->value }}</span>
                        <span class="text-xs text-slate-600">{{ $suggestion->format->label() }}</span>
                    </a>
                @endif
            @endforeach
            @if ($moreSuggestions)
                <span class="text-xs text-slate-600">weitere bitte von Hand eintragen</span>
            @endif
        </div>
    @else
        <p class="mt-3 text-sm text-slate-600">Keine Bestellnummer gefunden – bitte von Hand eintragen.</p>
    @endif

    <form method="GET" action="{{ route('tickets.orders.add', ['number' => $number]) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start"
          x-data x-on:submit="$dispatch('loading-start', { title: 'Bestellung wird geladen …' })">
        @foreach ($selected as $value)
            <input type="hidden" name="bestellungen[]" value="{{ $value }}">
        @endforeach
        <div class="min-w-0 flex-1">
            <label for="bestellnummer" class="sr-only">Bestellnummer von Hand</label>
            <input type="text" name="bestellnummer" id="bestellnummer" value="{{ old('bestellnummer') }}" placeholder="Bestellnummer, z. B. 402-4907715-1581912" autocomplete="off"
                   @if ($inputError) aria-invalid="true" aria-describedby="bestellnummer-error" @endif
                   @class([
                       'block w-full rounded-md border-0 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-1 ring-1 ring-inset placeholder:font-body placeholder:text-slate-400 focus:ring-2 focus:ring-inset',
                       'ring-slate-300 focus:ring-brand' => ! $inputError,
                       'ring-danger-500 focus:ring-danger-500' => $inputError,
                   ])>
            @if ($inputError)
                <p id="bestellnummer-error" class="mt-1 text-sm text-danger-700">{{ $inputError }}</p>
            @endif
        </div>
        <x-button type="submit" variant="secondary" class="min-h-10">Laden</x-button>
    </form>

    @if ($problem)
        <x-alert type="error" class="mt-4">
            <p>{{ $problem['message'] }}</p>
            @if ($problem['retry'])
                <p class="mt-3"><x-button :href="$url($selected)" variant="secondary"><x-icon name="refresh" size="16" /> Erneut versuchen</x-button></p>
            @endif
        </x-alert>
    @endif

    <div class="mt-4 space-y-4">
        @foreach ($lookups as $lookup)
            @if (! $lookup->found())
                <x-alert type="warning">
                    Bestellung <span class="font-mono">{{ $lookup->number->value }}</span> wurde in EOCS nicht gefunden.
                    <a href="{{ $url(array_diff($selected, [$lookup->number->value])) }}" class="ml-2 font-semibold underline">Entfernen</a>
                </x-alert>
            @else
                @if (count($lookup->orders) > 1)
                    <p class="text-sm text-slate-600">Mehrere Bestellungen zu <span class="font-mono">{{ $lookup->number->value }}</span>:</p>
                @endif
                @foreach ($lookup->orders as $order)
                    <x-ticket.order-block :order="$order" :mention="$mentions->get($order->externalNumber)" :remove-url="$url(array_diff($selected, [$lookup->number->value]))" />
                @endforeach
            @endif
        @endforeach

        @foreach ($mentions as $mention)
            @continue(in_array($mention->number, $loadedNumbers, true) || in_array($mention->number, $selected, true))
            <x-ticket.mention-block :mention="$mention" :load-url="$url([...$selected, $mention->number])" />
        @endforeach
    </div>
</section>
