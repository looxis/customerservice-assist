@props(['problem' => null, 'showNumber' => false, 'number' => null])

{{-- A delivery problem found in the tracking history by the WISMO automation (PROJ-82); read from the stored run, no carrier call. --}}
@if ($problem !== null)
    <x-alert type="error" {{ $attributes }}>
        <p class="font-semibold">
            @if ($showNumber && $number)Bestellung <span class="font-mono">{{ $number }}</span>: @endif
            Zustellproblem, bitte prüfen.
        </p>
        <p class="mt-1 font-normal">
            {{ $problem['reason'] ?? 'Die Sendungsverfolgung meldet ein Problem bei der Zustellung.' }}
            @if (! empty($problem['at'])) (Stand {{ \Carbon\CarbonImmutable::parse($problem['at'])->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr) @endif
            Der Kunde hat eine vorsichtige Zwischenantwort bekommen und erwartet eine Rückmeldung.
        </p>
    </x-alert>
@endif
