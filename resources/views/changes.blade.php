<x-layouts.app title="Änderungen">
    @if ($days->isEmpty())
        <x-alert type="warning">Noch keine Änderungen eingetragen: Die Datei <span class="font-mono">docs/CHANGELOG.md</span> ist nicht vorhanden oder enthält keinen Tag.</x-alert>
    @else
        <x-card>
            <x-changelog.days :days="$days" />
        </x-card>
    @endif
</x-layouts.app>
