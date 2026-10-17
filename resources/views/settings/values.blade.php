<x-layouts.app title="Einstellungen: Kundengruppen und Werte" width="5xl">
    <nav aria-label="Brotkrumen" class="text-sm"><a href="{{ route('settings.index') }}" class="font-medium text-brand hover:text-brand-hover">Einstellungen</a> <span class="text-slate-500">/ Kundengruppen und Werte</span></nav>

    <p class="text-sm text-slate-600">
        Die Werte, mit denen Wissensdokumente und Vorschläge arbeiten. Eine Änderung gilt nach dem Speichern sofort für alle; bereits erstellte Vorschläge ändern sich nicht. Neue Kundenarten oder Kanäle bitte zuerst speichern, danach in einer Kundengruppe verwenden.
        @if ($canEdit) Du bist Admin und kannst ändern. @else Du kannst die Werte ansehen; ändern dürfen nur Admins. @endif
    </p>

    @if (session('saved') !== null)
        <x-alert type="success" role="status">{{ session('saved') === 0 ? 'Es gab nichts zu speichern; alle Werte sind unverändert.' : session('saved').' '.(session('saved') === 1 ? 'Gruppe wurde gespeichert' : 'Gruppen wurden gespeichert').' und gilt jetzt für alle.' }}</x-alert>
    @endif
    @if (session('released')) <x-alert type="success" role="status">Die Kundengruppe „{{ session('released') }}“ ist freigegeben und jetzt im Formular wählbar.</x-alert> @endif
    @if (session('restored')) <x-alert type="success" role="status">Die Version wurde wiederhergestellt (als neue Version).</x-alert> @endif
    @if (session('reset')) <x-alert type="success" role="status">Die Gruppe gilt wieder mit dem Standard der App.</x-alert> @endif
    @if ($errors->has('release')) <x-alert type="error" role="alert">{{ $errors->first('release') }}</x-alert> @elseif ($errors->any())
        <x-alert type="error" role="alert">Es wurde nichts gespeichert. Bitte die markierten Gruppen prüfen; deine Eingaben sind noch da.</x-alert>
    @endif

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Erlaubte Werte für Wissensautoren</h2>
        <p class="mt-1 text-sm text-slate-600">Diesen Text gibst du einem KI-Chat beim Schreiben von Dokumenten mit; er entspricht immer dem Stand dieser Seite.</p>
        <x-copy-field class="mt-3" :text="$copyText" label="Erlaubte Werte für den KI-Chat" />
    </x-card>

    <form method="POST" action="{{ route('settings.values.update') }}" class="space-y-4" id="werte">
        @csrf
        @method('PUT')

        @foreach ($groups as $key => $group)
            <x-settings.group :group="$group" :value="$values[$key]" :version="$versions[$key]" :is-default="$defaults[$key]" :last="$last[$key]" :history="$history[$key]" :can-edit="$canEdit" :options="$options" />

            @if ($key === 'kn_groups')
                @foreach ($checklists as $groupKey => $checklist)
                    @php($row = collect($values['kn_groups'])->firstWhere('key', $groupKey))
                    <x-card id="gruppe-{{ $groupKey }}" class="scroll-mt-4 border-l-4 border-l-warning-500">
                        <h3 class="text-base font-semibold text-slate-900">Prüfliste: „{{ $row['label'] }}“ ist in Vorbereitung</h3>
                        <p class="mt-1 text-sm text-slate-600">Diese Dokumente gelten für die Gruppe <strong>nicht</strong>, weil sie auf andere Kundenarten oder Kanäle eingeschränkt sind. Soll ein Dokument auch für „{{ $row['label'] }}“ gelten, ergänze die Kundenart ({{ $row['customer_type'] }}) oder den Kanal ({{ $row['sales_channel'] }}) im Kopf des Dokuments. Die Liste wird dann kürzer.</p>

                        <h4 class="mt-4 text-sm font-semibold text-slate-900">Verbindliche Regeln ({{ count($checklist['binding']) }})</h4>
                        @forelse ($checklist['binding'] as $doc)
                            <label class="mt-2 flex items-start gap-2 text-sm text-slate-900">
                                @if ($canEdit)<input type="checkbox" name="confirmed[]" value="{{ $doc['id'] }}" form="freigabe-{{ $groupKey }}" class="mt-0.5 size-4 rounded border-slate-300 text-brand focus:ring-brand">@endif
                                <span><a href="{{ route('knowledge.show', ['path' => $doc['path']]) }}" class="font-mono font-semibold text-brand hover:text-brand-hover">{{ $doc['id'] }}</a> {{ $doc['title'] }} <span class="text-xs text-slate-600">({{ $doc['reason'] }})</span>@if ($canEdit)<span class="block text-xs text-slate-600">bewusst nicht gültig für diese Gruppe</span>@endif</span>
                            </label>
                        @empty
                            <p class="mt-1 text-sm text-slate-600">Keine verbindliche Regel ist für die Gruppe ausgeschlossen.</p>
                        @endforelse

                        <details class="mt-4">
                            <summary class="cursor-pointer text-sm font-semibold text-slate-900">Weitere Dokumente ({{ count($checklist['others']) }})</summary>
                            <ul class="mt-2 space-y-1 text-sm text-slate-900">
                                @foreach ($checklist['others'] as $doc)
                                    <li><a href="{{ route('knowledge.show', ['path' => $doc['path']]) }}" class="font-mono font-semibold text-brand hover:text-brand-hover">{{ $doc['id'] }}</a> {{ $doc['title'] }} <span class="text-xs text-slate-600">({{ $doc['type'] }}; {{ $doc['reason'] }})</span></li>
                                @endforeach
                            </ul>
                        </details>

                        @if ($canEdit)
                            <p class="mt-4"><x-button type="submit" form="freigabe-{{ $groupKey }}">Prüfung abgeschlossen, Gruppe freigeben</x-button></p>
                        @endif
                    </x-card>
                @endforeach
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
        @foreach ($checklists as $groupKey => $checklist)
            <form id="freigabe-{{ $groupKey }}" method="POST" action="{{ route('settings.groups.release', ['group' => $groupKey]) }}">@csrf<input type="hidden" name="version" value="{{ $versions['kn_groups'] }}"></form>
        @endforeach
        @foreach ($groups as $key => $group)
            <form id="standard-{{ $key }}" method="POST" action="{{ route('settings.reset', ['group' => $key]) }}">@csrf</form>
            @foreach ($history[$key] as $entry)
                <form id="wiederherstellen-{{ $key }}-{{ $entry->version }}" method="POST" action="{{ route('settings.restore', ['group' => $key, 'version' => $entry->version]) }}">@csrf</form>
            @endforeach
        @endforeach
    @endif
</x-layouts.app>
