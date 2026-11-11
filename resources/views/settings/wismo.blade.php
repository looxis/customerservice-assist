<x-layouts.app title="Einstellungen: WISMO-Automatik" width="5xl">
    <nav aria-label="Brotkrumen" class="text-sm"><a href="{{ route('settings.index') }}" class="font-medium text-brand hover:text-brand-hover">Einstellungen</a> <span class="text-slate-500">/ WISMO-Automatik</span></nav>

    <p class="text-sm text-slate-600">
        Neue Tickets, die die Ticket-Klassifikation als „Wo ist mein Paket?“ (WISMO) einordnet, kann die App selbst beantworten: mit dem Sendungsstand von DHL oder UPS. Eine Änderung gilt nach dem Speichern sofort für alle.
        Prompt und Modell der Antwort stehen unter <a href="{{ route('settings.prompts') }}" class="font-medium text-brand hover:text-brand-hover">Prompts und Modell</a>.
        @if ($canEdit)
            Du bist Admin und kannst ändern.
        @else
            Du kannst die Einstellungen ansehen; ändern dürfen nur Admins.
        @endif
    </p>

    @if (session('saved') !== null)
        <x-alert type="success" role="status">
            {{ session('saved') === 0 ? 'Es gab nichts zu speichern; alle Werte sind unverändert.' : session('saved').' '.(session('saved') === 1 ? 'Gruppe wurde gespeichert' : 'Gruppen wurden gespeichert').' und gilt jetzt für alle.' }}
        </x-alert>
    @endif
    @if (session('restored')) <x-alert type="success" role="status">Die Version wurde wiederhergestellt (als neue Version).</x-alert> @endif
    @if (session('reset')) <x-alert type="success" role="status">Die Gruppe gilt wieder mit dem Standard der App.</x-alert> @endif
    @if ($errors->any())
        <x-alert type="error" role="alert">Es wurde nichts gespeichert. Bitte die markierten Gruppen prüfen; deine Eingaben sind noch da.</x-alert>
    @endif

    <form method="POST" action="{{ route('settings.wismo.update') }}" class="space-y-4" id="wismo-einstellungen">
        @csrf
        @method('PUT')

        @foreach ($groups as $key => $group)
            <x-settings.group :group="$group" :value="$values[$key]" :version="$versions[$key]" :is-default="$defaults[$key]" :last="$last[$key]" :history="$history[$key]" :can-edit="$canEdit" />
        @endforeach

        @if ($canEdit)
            <div class="sticky bottom-0 -mx-1 flex items-center gap-3 bg-white/90 px-1 py-3 backdrop-blur">
                <x-button type="submit">Speichern</x-button>
                <span class="text-sm text-slate-600">Gespeichert werden nur Gruppen, die du geändert hast.</span>
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
