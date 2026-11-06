@props(['preview' => false])

<div class="space-y-4">
    <nav class="flex flex-wrap gap-2" aria-label="Beschriftung">
        @foreach (['labeling.index' => 'Übersicht', 'labeling.review' => 'Prüfen', 'labeling.tickets' => 'Tickets'] as $route => $label)
            <a href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" @endif
               @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => request()->routeIs($route), 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => ! request()->routeIs($route)])>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($preview)
        <x-alert type="warning">Vorschau mit erfundenen Beispieldaten. Abruf, Klassifikation und Tags nach Zammad sind noch nicht angeschlossen.</x-alert>
    @endif
</div>
