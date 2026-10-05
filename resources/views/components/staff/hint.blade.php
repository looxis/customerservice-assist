@props(['names', 'current' => null])

{{-- Shown on "Ticket analysieren" until a name is chosen (PROJ-5). --}}
@if ($current === null && $names !== [])
    <div x-data x-show="! $store.staff.name" role="region" aria-label="Name wählen" class="rounded-md bg-warning-500/10 p-4 ring-1 ring-warning-500/20 ring-inset">
        <p class="text-sm font-semibold text-warning-700">Bitte wähle zuerst deinen Namen.</p>
        <p class="mt-1 text-sm text-slate-700">Analysen und Rückmeldungen werden diesem Namen zugeordnet. Du wählst ihn einmal; der Browser merkt ihn sich.</p>
        <form
            method="POST"
            action="{{ route('staff.select') }}"
            x-on:submit.prevent="$store.staff.select($el, $event.submitter.value)"
            class="mt-3 flex flex-wrap gap-2"
        >
            @csrf
            @foreach ($names as $name)
                <x-button type="submit" variant="secondary" name="name" value="{{ $name }}" x-bind:disabled="$store.staff.saving">{{ $name }}</x-button>
            @endforeach
        </form>
        <p x-show="$store.staff.failed" x-cloak class="mt-2 text-xs text-danger-700" role="alert">Speichern fehlgeschlagen. Bitte noch einmal versuchen.</p>
    </div>
@endif
