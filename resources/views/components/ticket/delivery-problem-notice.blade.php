@props(['problem' => null, 'showNumber' => false, 'number' => null])

{{-- A delivery problem found in the tracking history by the WISMO automation (PROJ-82); read from the stored run, no carrier call.
     Since PROJ-99 only a special problem (or a run from before) waits for a person; a return or an address problem was
     explained to the customer, who was asked to get in touch. --}}
@if ($problem !== null)
    @php
        $special = ($problem['case'] ?? null) === null || $problem['case'] === \App\Tracking\ShipmentHistory::SPECIAL;
        $stand = empty($problem['at']) ? '' : ' (Stand '.\Carbon\CarbonImmutable::parse($problem['at'])->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr)';
    @endphp
    <x-alert :type="$special ? 'error' : 'warning'" {{ $attributes }}>
        <p class="font-semibold">
            @if ($showNumber && $number)Bestellung <span class="font-mono">{{ $number }}</span>: @endif
            {{ $special ? 'Zustellproblem, bitte prüfen.' : ($problem['label'] ?? 'Zustellproblem').'.' }}
        </p>
        <p class="mt-1 font-normal">
            {{ $problem['reason'] ?? 'Die Sendungsverfolgung meldet ein Problem bei der Zustellung.' }}{{ $stand }}
            @if ($special)
                Der Kunde hat eine vorsichtige Zwischenantwort bekommen und erwartet eine Rückmeldung.
            @else
                Der Kunde hat den Verlauf automatisch erhalten und wurde gebeten, sich bei uns zu melden.@if (! empty($problem['closed'])) Das Ticket wurde dabei geschlossen.@endif
            @endif
        </p>
    </x-alert>
@endif
