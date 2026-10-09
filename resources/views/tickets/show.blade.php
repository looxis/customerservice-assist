@php
    $ticket ??= null;
    $problem ??= null;
@endphp

<x-layouts.app :title="'Ticket#'.$number" width="5xl">
    <x-staff.hint :names="$staffNames" :current="$currentStaff" />

    <x-ticket.lookup :value="$ticket ? '' : 'Ticket#'.$number" />

    @if ($problem)
        <x-alert type="error" role="alert">
            <p>{{ $problem['message'] }}</p>
            @if ($problem['retry'] ?? false)
                <p class="mt-3">
                    <x-button :href="route('tickets.show', ['number' => $number])" variant="secondary" x-data x-on:click="$dispatch('loading-start', { title: 'Ticket wird geladen …' })">
                        <x-icon name="refresh" size="16" /> Erneut versuchen
                    </x-button>
                </p>
            @endif
        </x-alert>
    @endif

    @if ($ticket)
        @if ($rewindProblem ?? null)
            <x-alert type="warning">{{ $rewindProblem }}</x-alert>
        @endif

        <x-ticket.header :ticket="$ticket" :customer-group="$analysis['chosenGroupLabel'] ?? null" :can-delete="$testModeAvailable && (($analysis['history'] ?? []) !== [])">
            <x-slot:orders>
                <x-ticket.orders :ticket="$ticket" :number="$number" :selected="$selected ?? []" :suggestions="$suggestions ?? []"
                                 :more-suggestions="$moreSuggestions ?? false" :lookups="$lookups ?? []" :problem="$orderProblem ?? null" />
            </x-slot:orders>
        </x-ticket.header>
        @isset($analysis)
            @if ($analysis['resultMissing'])
                <x-alert type="warning">Diese Analyse ist nicht mehr verfügbar.</x-alert>
            @endif

            @if ($analysis['deletion'])
                <x-alert>Inhalte gelöscht von {{ $analysis['deletion']['staff'] }} am {{ $analysis['deletion']['at']->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr.</x-alert>
            @endif

            @if ($analysis['result'])
                <x-analysis.result :result="$analysis['result']" :number="$number" :history="$analysis['history']" :admin="$testModeAvailable" :stand="$analysis['stand']" />

                <x-analysis.procedures :result="$analysis['result']" :number="$number" />
            @endif

            <x-analysis.form :number="$number" :analysis="$analysis" :selected="$selected ?? []" :test-mode="$testMode ?? false"
                             :unloaded-orders="collect($suggestions ?? [])->reject(fn ($order) => $order->isEocsId() || in_array($order->value, $selected ?? [], true))->pluck('value')->values()->all()" />
        @endisset

        <x-ticket.thread :articles="$ticket->articles" :translations="$translations ?? []" :untranslated="$untranslated ?? 0" :can-retranslate="$testModeAvailable"
                         :translate="['action' => route('tickets.translation.store', ['number' => $number]), 'fields' => array_filter(['bestellungen' => $selected ?? [], 'stand' => $ticket->rewoundTo])]"
                         :later="$later ?? []" :rewound="$ticket->rewoundTo !== null" :rewind-url="($testMode ?? false) ? fn (int $id): string => route('tickets.show', ['number' => $number, 'bestellungen' => $selected ?? [], 'stand' => $id]).'#analyse' : null">
            @isset($analysis)
                <x-slot:summary>
                    <x-analysis.summary :number="$number" :analysis="$analysis" :selected="$selected ?? []" />
                </x-slot:summary>
            @endisset
        </x-ticket.thread>
    @endif
</x-layouts.app>
