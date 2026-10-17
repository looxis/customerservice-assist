<x-layouts.app title="Einstellungen: Erkennungsregeln" width="5xl">
    <nav aria-label="Brotkrumen" class="text-sm"><a href="{{ route('settings.index') }}" class="font-medium text-brand hover:text-brand-hover">Einstellungen</a> <span class="text-slate-500">/ Erkennungsregeln</span></nav>

    <p class="text-sm text-slate-600">
        Woran die App Bestellnummern und Artikelnummern (SKU) in einem Ticket erkennt. Eine Änderung gilt nach dem Speichern sofort für alle; bereits erstellte Vorschläge ändern sich nicht.
        @if ($canEdit) Du bist Admin und kannst ändern. Mit „Ausprobieren“ siehst du vor dem Speichern, was die App in einem Text erkennt. @else Du kannst die Regeln ansehen; ändern dürfen nur Admins. @endif
    </p>

    @if (session('saved') !== null)
        <x-alert type="success" role="status">{{ session('saved') === 0 ? 'Es gab nichts zu speichern; alle Werte sind unverändert.' : session('saved').' '.(session('saved') === 1 ? 'Gruppe wurde gespeichert' : 'Gruppen wurden gespeichert').' und gilt jetzt für alle.' }}</x-alert>
    @endif
    @if (session('restored')) <x-alert type="success" role="status">Die Version wurde wiederhergestellt (als neue Version).</x-alert> @endif
    @if (session('reset')) <x-alert type="success" role="status">Die Gruppe gilt wieder mit dem Standard der App.</x-alert> @endif
    @if ($errors->any() && ! $errors->has('sample'))
        <x-alert type="error" role="alert">Es wurde nichts gespeichert. Bitte die markierten Gruppen prüfen; deine Eingaben sind noch da.</x-alert>
    @endif

    <form method="POST" action="{{ route('settings.detection.update') }}" class="space-y-4" id="erkennungsregeln">
        @csrf
        @method('PUT')

        @if ($canEdit)
            <x-card>
                <h2 class="text-base font-semibold text-slate-900">Erkennung ausprobieren</h2>
                <p class="mt-1 text-sm text-slate-600">Füge einen Text ein (z. B. eine Kundenmail). Du siehst, was die gespeicherten Regeln erkennen und was deine noch nicht gespeicherten erkennen würden. Es wird nichts gespeichert.</p>
                <label for="sample" class="sr-only">Text zum Ausprobieren</label>
                <textarea id="sample" name="sample" rows="5" maxlength="10000" placeholder="z. B. Bestellung 402-4907715-1581912, Artikelnummer 12006, Bestell-Nr. 30019578 …"
                          class="mt-3 block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">{{ old('sample') }}</textarea>
                @error('sample')<p class="mt-1 text-sm text-danger-700" role="alert">{{ $message }}</p>@enderror
                <div class="mt-2"><x-button type="submit" variant="secondary" formaction="{{ route('settings.detection.try') }}">Ausprobieren</x-button></div>

                @if ($trial)
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach (['saved' => 'Gespeicherte Regeln', 'draft' => 'Mit deinen Änderungen'] as $side => $label)
                            <div class="min-w-0 rounded-lg p-3 ring-1 ring-slate-200 ring-inset">
                                <p class="text-xs font-semibold text-slate-700">{{ $label }}</p>
                                <p class="mt-2 text-sm font-medium text-slate-900">Bestellnummern</p>
                                <ul class="text-sm text-slate-900">
                                    @forelse ($trial[$side]['orders'] as $order)
                                        <li><span class="font-mono">{{ $order['value'] }}</span> – {{ $order['format'] }} <span class="text-xs text-slate-600">({{ $order['automatic'] ? 'wird automatisch geladen' : 'wird erst auf Klick geladen' }})</span></li>
                                    @empty
                                        <li class="text-slate-600">keine erkannt</li>
                                    @endforelse
                                </ul>
                                <p class="mt-2 text-sm font-medium text-slate-900">Artikelnummern</p>
                                <ul class="text-sm text-slate-900">
                                    @forelse ($trial[$side]['skus'] as $sku)
                                        <li class="font-mono">{{ $sku }}</li>
                                    @empty
                                        <li class="text-slate-600">keine erkannt</li>
                                    @endforelse
                                </ul>
                            </div>
                        @endforeach
                    </div>
                    @if ($trial['saved'] === $trial['draft'])<p class="mt-2 text-xs text-slate-600">Kein Unterschied zwischen gespeicherten und ungespeicherten Regeln.</p>@endif
                @endif
            </x-card>
        @endif

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
