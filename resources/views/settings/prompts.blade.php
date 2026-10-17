<x-layouts.app title="Einstellungen: Prompts und Modell" width="5xl">
    <nav aria-label="Brotkrumen" class="text-sm"><a href="{{ route('settings.index') }}" class="font-medium text-brand hover:text-brand-hover">Einstellungen</a> <span class="text-slate-500">/ Prompts und Modell</span></nav>

    <p class="text-sm text-slate-600">
        Das sind die Anweisungen an die KI und das Modell je KI-Aufruf. Eine Änderung gilt nach dem Speichern sofort für alle neuen Vorschläge; bereits erstellte Vorschläge ändern sich nicht. Jeder Vorschlag zeigt unter „Details zum Vorschlag“, mit welcher Prompt-Version und welchem Modell er entstand.
        @if ($canEdit) Du bist Admin und kannst ändern. @else Du kannst die Einstellungen ansehen; ändern dürfen nur Admins. @endif
    </p>
    <x-alert type="info">Fachliche Regeln, Befugnisse und Kulanz gehören in die Wissensbasis (Knowledge), nicht in den Prompt. Der Prompt beschreibt, <em>wie</em> die KI arbeitet, nicht, <em>was</em> bei LOOXIS gilt.</x-alert>

    @if (session('saved') !== null)
        <x-alert type="success" role="status">{{ session('saved') === 0 ? 'Es gab nichts zu speichern; alle Werte sind unverändert.' : session('saved').' '.(session('saved') === 1 ? 'Gruppe wurde gespeichert' : 'Gruppen wurden gespeichert').' und gilt ab dem nächsten Vorschlag für alle.' }}</x-alert>
    @endif
    @if (session('restored')) <x-alert type="success" role="status">Die Version wurde wiederhergestellt (als neue Version).</x-alert> @endif
    @if (session('reset')) <x-alert type="success" role="status">Die Einstellung gilt wieder mit dem Standard der App.</x-alert> @endif
    @if ($errors->any())
        <x-alert type="error" role="alert">Es wurde nichts gespeichert. Bitte die markierten Einstellungen prüfen; deine Eingaben sind noch da.</x-alert>
    @endif
    @if ($canEdit && $models === null)
        <p class="text-sm text-slate-600">Die Liste der verfügbaren Modelle ist gerade nicht erreichbar; du kannst den Modellnamen trotzdem von Hand eintragen. Vor dem Speichern wird er geprüft.</p>
    @endif

    @if ($models !== null)
        <datalist id="modelle">@foreach ($models as $model)<option value="{{ $model }}"></option>@endforeach</datalist>
    @endif

    <form method="POST" action="{{ route('settings.prompts.update') }}" class="space-y-4" id="prompt-einstellungen"
          @if ($canEdit) x-data x-on:submit="if (! window.confirm('Die Änderung gilt sofort für alle neuen Vorschläge. Speichern?')) { $event.preventDefault() }" @endif>
        @csrf
        @method('PUT')

        <h2 class="pt-2 text-lg font-semibold text-slate-900">KI-Modelle</h2>
        @foreach ($groups as $key => $group)
            @if ($group->kind === \App\Settings\SettingGroup::MODEL)
                <x-settings.group :group="$group" :value="$values[$key]" :version="$versions[$key]" :is-default="$defaults[$key]" :last="$last[$key]" :history="$history[$key]" :can-edit="$canEdit" />
            @endif
        @endforeach

        <h2 class="pt-4 text-lg font-semibold text-slate-900">Prompts</h2>
        @foreach ($groups as $key => $group)
            @if ($group->kind === \App\Settings\SettingGroup::PROMPT)
                <x-settings.group :group="$group" :value="$values[$key]" :version="$versions[$key]" :is-default="$defaults[$key]" :last="$last[$key]" :history="$history[$key]" :can-edit="$canEdit" :outdated="in_array($key, $outdated, true)" />
            @endif
        @endforeach

        @if ($canEdit)
            <div class="sticky bottom-0 -mx-1 flex items-center gap-3 bg-white/90 px-1 py-3 backdrop-blur">
                <x-button type="submit">Speichern</x-button>
                <span class="text-sm text-slate-600">Gespeichert werden nur Einstellungen, die du geändert hast. Ein geändertes Modell wird vorher mit einem Testaufruf geprüft.</span>
            </div>
        @endif
    </form>

    @if ($canEdit)
        @foreach ($groups as $key => $group)
            <form id="standard-{{ $key }}" method="POST" action="{{ route('settings.reset', ['group' => $key]) }}">@csrf</form>
            @foreach ($history[$key] as $entry)
                <form id="wiederherstellen-{{ $key }}-{{ $entry->version }}" method="POST" action="{{ route('settings.restore', ['group' => $key, 'version' => $entry->version]) }}">@csrf</form>
            @endforeach
        @endforeach
    @endif
</x-layouts.app>
