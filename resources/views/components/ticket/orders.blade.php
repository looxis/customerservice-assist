@props(['ticket', 'number', 'selected' => [], 'suggestions' => [], 'moreSuggestions' => false, 'lookups' => [], 'problem' => null])

@php
    $url = fn (array $selection): string => route('tickets.show', ['number' => $number, 'bestellungen' => array_values($selection)]);
    $loadedNumbers = collect($lookups)->flatMap(fn ($lookup) => collect($lookup->orders)->pluck('externalNumber'))->all();
    $mentions = collect($ticket->orders)->keyBy('number');
    $inputError = isset($errors) ? $errors->first('bestellnummer') : null;
@endphp

{{-- Orders of the ticket (PROJ-7): each order number found gets one box with one button to load it
     from EOCS; the manual input is folded away when a number was found. Loaded orders follow below. --}}
<section class="mt-6 border-t border-slate-100 pt-4" aria-labelledby="orders-heading">
    <h3 id="orders-heading" class="text-sm font-semibold text-slate-900">Bestellungen</h3>

    @php
        // One entry per order number that is known but not loaded: found in the
        // text and/or named in Amazon's notice (which also carries the product).
        $known = collect($suggestions)->mapWithKeys(fn ($suggestion) => [$suggestion->value => ['number' => $suggestion->value, 'label' => $suggestion->format->label(), 'items' => []]]);
        foreach ($mentions as $mention) {
            $known[$mention->number] = ['number' => $mention->number, 'label' => $known[$mention->number]['label'] ?? null, 'source' => $mention->source, 'items' => $mention->items];
        }
        $open = $known->reject(fn ($entry) => in_array($entry['number'], $loadedNumbers, true) || in_array($entry['number'], $selected, true));
        $anyKnown = $known->isNotEmpty() || $selected !== [];
    @endphp

    @if ($open->isNotEmpty())
        <div class="mt-3 space-y-3" aria-label="Im Ticket gefundene Bestellungen">
            @foreach ($open as $entry)
                <x-ticket.mention-block :number="$entry['number']" :label="$entry['label']" :source="$entry['source'] ?? null" :items="$entry['items']" :load-url="$url([...$selected, $entry['number']])" />
            @endforeach
            @if ($moreSuggestions)
                <p class="text-xs text-slate-600">Weitere Bestellnummern bitte von Hand eintragen.</p>
            @endif
        </div>
    @elseif (! $anyKnown)
        <p class="mt-3 text-sm text-slate-600">Im Ticket wurde keine Bestellnummer gefunden.</p>
    @endif

    @if ($anyKnown)
        <details class="group/manual mt-3" @if ($inputError) open @endif>
            <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                <x-icon name="chevron-right" size="16" class="transition group-open/manual:rotate-90" /> Andere Bestellnummer eingeben
            </summary>
    @endif
    <form method="GET" action="{{ route('tickets.orders.add', ['number' => $number]) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start"
          x-data x-on:submit="$dispatch('loading-start', { title: 'Bestellung wird geladen …' })">
        @foreach ($selected as $value)
            <input type="hidden" name="bestellungen[]" value="{{ $value }}">
        @endforeach
        <div class="min-w-0 flex-1">
            <label for="bestellnummer" @class(['block text-sm font-medium text-slate-900', 'sr-only' => $anyKnown])>Bestellnummer von Hand eingeben</label>
            <input type="text" name="bestellnummer" id="bestellnummer" value="{{ old('bestellnummer') }}" placeholder="z. B. 402-4907715-1581912" autocomplete="off"
                   @if ($inputError) aria-invalid="true" aria-describedby="bestellnummer-error" @endif
                   @class([
                       'mt-1 block w-full rounded-md border-0 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-1 ring-1 ring-inset placeholder:font-body placeholder:text-slate-400 focus:ring-2 focus:ring-inset',
                       'ring-slate-300 focus:ring-brand' => ! $inputError,
                       'ring-danger-500 focus:ring-danger-500' => $inputError,
                   ])>
            @if ($inputError)
                <p id="bestellnummer-error" class="mt-1 text-sm text-danger-700">{{ $inputError }}</p>
            @endif
        </div>
        <x-button type="submit" variant="secondary" @class(['min-h-10', 'sm:mt-7' => ! $anyKnown, 'sm:mt-1' => $anyKnown])>Abrufen</x-button>
    </form>
    @if ($anyKnown)
        </details>
    @endif

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

    </div>
</section>
