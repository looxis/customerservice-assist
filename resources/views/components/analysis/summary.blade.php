@props(['number', 'analysis', 'selected' => []])

@php
    $summary = $analysis['summary'];
    $stale = $analysis['summaryStale'];
@endphp

{{-- Summary of the earlier thread (PROJ-9, stage 1), shown above the last customer message. --}}
@if ($analysis['summaryOffered'])
    <section id="zusammenfassung" aria-label="Zusammenfassung des bisherigen Verlaufs" class="scroll-mt-4 rounded-lg border-l-4 border-l-trust-500 bg-white p-5 shadow-1 ring-1 ring-slate-200 md:w-4/5"
             x-data="{ editing: @js($errors->has('zusammenfassung')) }">
        <header class="flex flex-wrap items-center gap-2">
            <x-badge tone="info">Zusammenfassung des bisherigen Verlaufs</x-badge>
            @if ($summary)
                @if ($summary->until)<span class="text-xs text-slate-600">zusammengefasst bis Nachricht vom {{ $summary->until }}</span>@endif
                @if ($summary->edited)<x-badge compact>von Hand geändert</x-badge>@endif
                @if ($stale)<x-badge compact tone="warning">veraltet – neue Nachrichten</x-badge>@endif
            @endif
        </header>

        @if (session('summary_error'))
            <x-alert type="error" class="mt-3" role="alert">{{ session('summary_error') }}</x-alert>
        @endif

        @if (! $summary)
            <p class="mt-3 text-sm text-slate-600">Noch keine Zusammenfassung. Sie fasst alle Nachrichten vor der letzten Kundennachricht zusammen; der Originalverlauf bleibt erhalten.</p>
        @else
            <div class="knowledge-text mt-3" x-show="! editing">{{ app(\App\Knowledge\KnowledgeMarkdown::class)->render($summary->text) }}</div>

            <form method="POST" action="{{ route('tickets.summary.update', ['number' => $number]) }}" x-show="editing" x-cloak class="mt-3 space-y-2">
                @csrf
                @if ($analysis['stand']) <input type="hidden" name="stand" value="{{ $analysis['stand'] }}"> @endif
                @method('PUT')
                @foreach ($selected as $value)<input type="hidden" name="bestellungen[]" value="{{ $value }}">@endforeach
                <label for="zusammenfassung-text" class="sr-only">Zusammenfassung bearbeiten</label>
                <textarea id="zusammenfassung-text" name="zusammenfassung" rows="14" maxlength="20000" class="block w-full rounded-md border-0 bg-white px-3 py-2 font-mono text-xs text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">{{ old('zusammenfassung', $summary->text) }}</textarea>
                @error('zusammenfassung') <p class="text-sm text-danger-700">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <x-button type="submit">Übernehmen</x-button>
                    <x-button type="button" variant="secondary" x-on:click="editing = false">Abbrechen</x-button>
                </div>
            </form>
        @endif

        <div class="mt-3 flex flex-wrap gap-2" x-show="! editing">
            <form method="POST" action="{{ route('tickets.summary.create', ['number' => $number]) }}" x-data x-on:submit="$dispatch('loading-start', { title: 'Zusammenfassung wird erstellt …' })">
                @csrf
                @if ($analysis['stand']) <input type="hidden" name="stand" value="{{ $analysis['stand'] }}"> @endif
                @foreach ($selected as $value)<input type="hidden" name="bestellungen[]" value="{{ $value }}">@endforeach
                <x-button type="submit" :variant="$summary ? 'secondary' : 'primary'">{{ $summary ? 'Neu erstellen' : 'Zusammenfassung erstellen' }}</x-button>
            </form>
            @if ($summary)
                <x-button type="button" variant="secondary" x-on:click="editing = true">Bearbeiten</x-button>
                @if ($stale && $summary->edited)
                    <form method="POST" action="{{ route('tickets.summary.update', ['number' => $number]) }}">
                        @csrf
                        @if ($analysis['stand']) <input type="hidden" name="stand" value="{{ $analysis['stand'] }}"> @endif
                        @method('PUT')
                        @foreach ($selected as $value)<input type="hidden" name="bestellungen[]" value="{{ $value }}">@endforeach
                        <input type="hidden" name="zusammenfassung" value="{{ $summary->text }}">
                        <x-button type="submit" variant="secondary">Meine Fassung weiter verwenden</x-button>
                    </form>
                @endif
            @endif
        </div>
    </section>
@endif
