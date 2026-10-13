@props(['stats'])

{{-- A few figures for everybody at the bottom of the sidebar (PROJ-41): counts only. --}}
<section class="shrink-0 border-t border-slate-200 px-4 py-3" aria-label="Aktueller Stand">
    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-600">Aktueller Stand</h2>
    <dl class="mt-2 space-y-1.5 text-xs text-slate-600">
        <div class="flex justify-between gap-2"><dt>Wissensstand</dt><dd class="font-mono text-slate-900">{{ $stats['state'] }}</dd></div>
        <div class="flex justify-between gap-2"><dt>Wissen</dt><dd class="text-right text-slate-900">{{ $stats['documents'] }} Dateien · {{ \App\Analysis\AppStats::size($stats['bytes']) }}</dd></div>
        <div class="flex justify-between gap-2"><dt>Bearbeitete Tickets</dt><dd class="font-semibold text-slate-900">{{ $stats['tickets'] }}</dd></div>
        <div class="flex justify-between gap-2"><dt>Gemeldete Wissenslücken</dt><dd class="font-semibold text-slate-900">{{ $stats['gaps'] }}</dd></div>
        <div class="flex justify-between gap-2"><dt>Davon noch offen</dt><dd class="font-semibold text-slate-900">{{ $stats['openGaps'] }}</dd></div>
    </dl>
    @if ($stats['gaps'] > 0)
        <p class="mt-2 text-xs text-slate-600">Gemeldete Lücken werden bearbeitet. Danke fürs Melden!</p>
    @endif
    <p class="mt-3 font-mono text-xs text-slate-600">Version {{ $stats['version'] }}</p>
</section>
