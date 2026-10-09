<x-layouts.app title="Über die App">
    @if ($general === null && $developer === null)
        <x-alert type="warning">Beschreibung fehlt: Die Datei <span class="font-mono">docs/ABOUT.md</span> ist nicht vorhanden oder leer.</x-alert>
    @endif

    @if ($general !== null)
        <x-card>
            <x-knowledge.text :html="$general" />
        </x-card>
    @endif

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Aktueller Stand</h2>
        <dl class="mt-4 grid gap-6 sm:grid-cols-2 md:grid-cols-4">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-600">App-Version</dt>
                <dd class="mt-1 font-mono text-sm text-slate-900">{{ $version }}</dd>
            </div>
            <div class="sm:col-span-2 md:col-span-1">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Wissensstand</dt>
                <dd class="mt-1 font-mono text-sm text-slate-900">{{ $state->label() }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Verwendbare Dokumente</dt>
                <dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $counts['usable'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Davon Entwürfe</dt>
                <dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $counts['draft'] }}</dd>
            </div>
        </dl>
        @if ($knowledgeMessage !== null)
            <x-alert type="warning" class="mt-4">{{ $knowledgeMessage }}</x-alert>
        @endif
        <p class="mt-6 border-t border-slate-100 pt-4 text-sm">
            <a href="{{ route('knowledge.index') }}" class="font-semibold text-brand hover:text-brand-hover">Zur Knowledge-Übersicht</a>
        </p>
    </x-card>

    @if ($developer !== null)
        <section aria-labelledby="developer-heading" class="space-y-3 border-t-2 border-dashed border-slate-200 pt-6">
            <div class="flex items-center gap-2">
                <x-icon name="settings" class="text-slate-400" />
                <h2 id="developer-heading" class="text-base font-semibold text-slate-900">Für Entwickler</h2>
            </div>
            <x-card>
                <x-knowledge.text :html="$developer" />
            </x-card>
        </section>
    @endif
</x-layouts.app>
