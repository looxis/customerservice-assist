@props(['number', 'loadUrl', 'label' => null, 'source' => null, 'items' => []])

{{-- An order number found in the ticket (in the text or in Amazon's notice, PROJ-6/7)
     that is not loaded from EOCS yet: one box, one button. --}}
<article class="rounded-lg p-4 ring-1 ring-slate-200 ring-inset" aria-label="Bestellung {{ $number }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="font-mono text-sm font-semibold text-slate-900">{{ $number }}</p>
            <p class="mt-0.5 text-xs text-slate-600">{{ implode(' · ', array_filter([$label, $source ? 'aus '.$source : 'im Ticket gefunden'])) }}</p>
        </div>
        <x-button :href="$loadUrl" variant="secondary" x-data x-on:click="$dispatch('loading-start', { title: 'Bestellung wird geladen …' })">Bestelldetails aus EOCS abrufen</x-button>
    </div>
    @if ($items !== [])
        <ul class="mt-3 space-y-1 text-sm">
            @foreach ($items as $item)
                <li class="text-slate-900">{{ $item->name }} @if ($item->asin)<span class="font-mono text-xs text-slate-600">· ASIN {{ $item->asin }}</span>@endif</li>
            @endforeach
        </ul>
    @endif
</article>
