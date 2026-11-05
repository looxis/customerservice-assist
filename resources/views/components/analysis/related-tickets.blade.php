@props(['related', 'inputs' => []])

@php
    $titles = ['linked' => 'In Zammad verknüpft', 'order' => 'Tickets zur Bestellung', 'customer' => 'Weitere Tickets dieses Kunden (letzte 90 Tage)'];
    $selected = array_map('strval', (array) old('related', $related['selected']));
    $found = collect($related['groups'])->flatten(1)->count();
    $any = $found > 0;
    $openIt = $errors->has('related') || $errors->has('related_manual') || filled(old('related_manual'));
@endphp

{{-- Tickets that may belong to the same case (PROJ-52): their course goes to the analysis as the earlier course. --}}
<details class="group min-w-0" @if ($openIt) open @endif>
<summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-2 text-sm font-medium text-slate-900 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
    <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open:rotate-90" />
    Zusammengehörige Tickets
    <span class="font-normal text-slate-600">({{ $found }} gefunden, {{ count($selected) }} vorgewählt)</span>
</summary>
<fieldset class="mt-2 min-w-0">
    <legend class="sr-only">Zusammengehörige Tickets</legend>
    <x-intro>
    <p id="zusammenhang-hinweis" class="mt-1 text-xs text-slate-600">Der Verlauf dieser Tickets geht als früherer Verlauf mit in den Vorschlag, damit Zusagen und frühere Wünsche berücksichtigt werden. Verknüpfte Tickets sind vorgewählt; bei den anderen entscheidest du.</p>
    </x-intro>
    <input type="hidden" name="related_set" value="1">

    @foreach ($related['problems'] as $problem)
        <p class="mt-1 text-xs font-medium text-warning-700">{{ $problem }}</p>
    @endforeach

    @unless ($any)
        <p class="mt-2 text-sm text-slate-600">Es wurden keine zusammengehörigen Tickets gefunden.</p>
    @endunless

    @foreach ($titles as $group => $title)
        @if ($related['groups'][$group] ?? [])
            <p class="mt-3 text-xs font-semibold text-slate-700">{{ $title }}</p>
            <ul class="mt-1 space-y-1">
                @foreach ($related['groups'][$group] as $item)
                    <li>
                        <label class="flex items-start gap-2 text-sm text-slate-900">
                            <input type="checkbox" name="related[]" value="{{ $item['number'] }}" @checked(in_array($item['number'], $selected, true)) class="mt-0.5 size-4 rounded border-slate-300 text-brand focus:ring-brand">
                            <input type="hidden" name="related_source[{{ $item['number'] }}]" value="{{ $item['source'] }}">
                            <span class="min-w-0">
                                <a href="{{ route('tickets.show', ['number' => $item['number']]) }}" target="_blank" rel="noopener" class="font-mono font-semibold text-brand hover:text-brand-hover">{{ $item['number'] }}</a>
                                {{ \Illuminate\Support\Str::limit($item['title'], 90) }}
                                <span class="text-xs text-slate-600">· {{ $item['state'] }} · {{ $item['articles'] }} {{ $item['articles'] === 1 ? 'Nachricht' : 'Nachrichten' }}@if ($item['date']) · {{ \Carbon\CarbonImmutable::parse($item['date'])->setTimezone('Europe/Berlin')->format('d.m.Y') }}@endif</span>
                            </span>
                        </label>
                    </li>
                @endforeach
            </ul>
        @endif
    @endforeach

    <div class="mt-3 max-w-xs">
        <label for="related_manual" class="block text-xs font-medium text-slate-700">Ticketnummer ergänzen</label>
        <input type="text" id="related_manual" name="related_manual" value="{{ old('related_manual') }}" inputmode="numeric" maxlength="20" autocomplete="off" placeholder="z. B. 2138745"
               class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 font-mono text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
        @error('related_manual')<p class="mt-1 text-sm text-danger-700">{{ $message }}</p>@enderror
        @error('related')<p class="mt-1 text-sm text-danger-700">{{ $message }}</p>@enderror
    </div>
</fieldset>
</details>
