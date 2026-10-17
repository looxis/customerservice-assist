<x-layouts.app title="Einstellungen" width="5xl">
    <p class="text-sm text-slate-600">Hier steht, nach welchen Regeln die App arbeitet. Alle können die Regeln einsehen, ändern dürfen sie nur Admins; jede Änderung wird mit Name und Zeitpunkt festgehalten und lässt sich zurücknehmen.</p>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($pages as $page)
            <a href="{{ route($page['route']) }}" class="block rounded-lg bg-white p-6 shadow-1 ring-1 ring-slate-200 hover:ring-brand focus-visible:outline-2 focus-visible:outline-brand">
                <h2 class="text-base font-semibold text-slate-900">{{ $page['title'] }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $page['description'] }}</p>
            </a>
        @endforeach
    </div>
</x-layouts.app>
