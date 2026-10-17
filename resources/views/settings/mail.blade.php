<x-layouts.app title="Einstellungen: Mail-Anzeige" width="5xl">
    <nav aria-label="Brotkrumen" class="text-sm"><a href="{{ route('settings.index') }}" class="font-medium text-brand hover:text-brand-hover">Einstellungen</a> <span class="text-slate-500">/ Mail-Anzeige</span></nav>

    <p class="text-sm text-slate-600">
        So bereinigt die App Nachrichten im Ticketverlauf. Eine Änderung gilt nach dem Speichern sofort für alle; bereits erstellte Vorschläge ändern sich nicht.
        @if ($canEdit)
            Du bist Admin und kannst ändern. Mit „Ausprobieren“ siehst du vor dem Speichern, wie ein echtes Ticket mit deinen Änderungen aussähe.
        @else
            Du kannst die Regeln ansehen; ändern dürfen nur Admins.
        @endif
    </p>

    @if (session('saved') !== null)
        <x-alert type="success" role="status">
            {{ session('saved') === 0 ? 'Es gab nichts zu speichern; alle Werte sind unverändert.' : session('saved').' '.(session('saved') === 1 ? 'Gruppe wurde gespeichert' : 'Gruppen wurden gespeichert').' und gilt jetzt für alle.' }}
            @foreach ((array) session('notes', []) as $note)<span class="block text-xs">{{ $note }}</span>@endforeach
        </x-alert>
    @endif
    @if (session('restored')) <x-alert type="success" role="status">Die Version wurde wiederhergestellt (als neue Version).</x-alert> @endif
    @if (session('reset')) <x-alert type="success" role="status">Die Gruppe gilt wieder mit dem Standard der App.</x-alert> @endif
    @if ($errors->any() && ! $errors->has('ticket'))
        <x-alert type="error" role="alert">Es wurde nichts gespeichert. Bitte die markierten Gruppen prüfen; deine Eingaben sind noch da.</x-alert>
    @endif

    <form method="POST" action="{{ route('settings.mail.update') }}" class="space-y-4" id="mail-einstellungen">
        @csrf
        @method('PUT')

        @if ($canEdit)
            <x-card>
                <h2 class="text-base font-semibold text-slate-900">Mit Ticket ausprobieren</h2>
                <p class="mt-1 text-sm text-slate-600">Zeigt den bereinigten Verlauf (die letzten 10 Nachrichten) mit den gespeicherten Regeln und mit deinen noch nicht gespeicherten Änderungen. Dabei wird nichts gespeichert.</p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                    <div class="min-w-0 sm:w-64">
                        <label for="ticket" class="sr-only">Ticketnummer</label>
                        <input type="text" id="ticket" name="ticket" value="{{ old('ticket') }}" inputmode="numeric" maxlength="20" placeholder="Ticketnummer, z. B. 2139862" autocomplete="off"
                               class="block w-full rounded-md border-0 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
                        @error('ticket')<p class="mt-1 text-sm text-danger-700" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <x-button type="submit" variant="secondary" formaction="{{ route('settings.mail.try') }}">Ausprobieren</x-button>
                </div>

                @if ($trial)
                    @if (isset($trial['error']))
                        <x-alert type="error" class="mt-4" role="alert">{{ $trial['error'] }}</x-alert>
                    @else
                        <h3 class="mt-5 text-sm font-semibold text-slate-900">Ticket {{ $trial['ticket'] }}: {{ $trial['title'] }}</h3>
                        @foreach ($trial['notes'] as $note)<p class="text-xs text-slate-600">{{ $note }}</p>@endforeach
                        <div class="mt-3 space-y-4">
                            @foreach ($trial['messages'] as $message)
                                <article class="rounded-lg p-3 ring-1 ring-slate-200 ring-inset">
                                    <p class="text-xs text-slate-600">{{ $message['kind'] }} · {{ $message['sender'] }} · {{ $message['at'] }}
                                        @if ($message['same']) <x-badge compact>kein Unterschied</x-badge> @else <x-badge compact tone="warning">Unterschied</x-badge> @endif
                                    </p>
                                    <div class="mt-2 grid gap-3 md:grid-cols-2">
                                        @foreach (['saved' => 'Gespeicherte Regeln', 'draft' => 'Mit deinen Änderungen'] as $side => $label)
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-slate-700">{{ $label }}</p>
                                                <div class="knowledge-text mt-1 text-sm">{!! $message[$side]['body'] !!}</div>
                                                @if ($message[$side]['quote'])<p class="mt-1 text-xs text-slate-500">+ Zitat (eingeklappt)</p>@endif
                                                @if ($message[$side]['signature'])<p class="mt-1 text-xs text-slate-500">+ Signatur (eingeklappt)</p>@endif
                                            </div>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                @endif
            </x-card>
        @endif

        @foreach ($groups as $key => $group)
            @if ($key === 'mail_quote_markers')
                <details class="rounded-lg ring-1 ring-slate-200 ring-inset" @if ($errors->has('settings.'.$key)) open @endif>
                    <summary class="cursor-pointer list-none p-4 text-sm font-medium text-slate-900 [&::-webkit-details-marker]:hidden"><x-icon name="chevron-down" size="16" class="inline" /> Erweitert</summary>
                    <div class="space-y-4 p-4 pt-0">
                        <x-settings.group :group="$group" :value="$values[$key]" :version="$versions[$key]" :is-default="$defaults[$key]" :last="$last[$key]" :history="$history[$key]" :can-edit="$canEdit" />
                    </div>
                </details>
            @else
                <x-settings.group :group="$group" :value="$values[$key]" :version="$versions[$key]" :is-default="$defaults[$key]" :last="$last[$key]" :history="$history[$key]" :can-edit="$canEdit" />
            @endif
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
