<x-layouts.app title="DHL Retoure">
    <div class="space-y-6">
        <x-card>
            <div class="flex items-start gap-3">
                <x-icon name="doc" class="mt-0.5 text-brand" />
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-semibold text-slate-900">DHL-Retourenlabel erstellen</h2>
                    <p class="mt-1 text-sm text-slate-600">Ticketnummer oder Bestellnummer eingeben. Die Bestellung wird aus EOCS geladen, die Lieferadresse kannst du prüfen und ändern. Das Label gilt nur für Rücksendungen aus Deutschland; DHL schickt dem Kunden selbst keine E-Mail.</p>
                    <form method="GET" action="{{ route('dhl-return.index') }}" role="search" aria-label="Bestellung suchen" class="mt-4 flex flex-col gap-3 sm:flex-row">
                        <label for="suche" class="sr-only">Ticketnummer oder Bestellnummer</label>
                        <input type="text" id="suche" name="suche" value="{{ $query }}" autocomplete="off" autofocus placeholder="Ticket#2137942 oder Bestellnummer"
                            class="block w-full min-w-0 flex-1 rounded-md border-0 bg-white px-3 py-2 font-mono text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand sm:text-sm">
                        <x-button type="submit" class="min-h-11">Bestellung laden</x-button>
                    </form>
                    @error('suche')<p class="mt-2 text-sm text-danger-700" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        @unless ($configured)
            <x-alert type="warning">Die DHL-Anbindung ist noch nicht eingerichtet (Zugangsdaten oder Empfänger-ID fehlen in der Konfiguration). Labels können erst erzeugt werden, wenn das erledigt ist.</x-alert>
        @endunless

        @if ($problem)
            <x-alert type="error">{{ $problem }}</x-alert>
        @endif

        @if ($ticket)
            <x-card>
                <p class="text-sm text-slate-700"><span class="font-medium">Ticket {{ $ticket->number }}</span> · {{ $ticket->title }} · <a href="{{ route('tickets.show', ['number' => $ticket->number]) }}" class="font-medium text-brand underline">Ticket in der App öffnen</a></p>
            </x-card>
        @endif

        @if ($query !== '' && ! $problem && collect($lookups)->flatMap(fn ($lookup) => $lookup->orders)->isEmpty())
            <x-alert type="warning">
                @if ($lookups === [])
                    Zu diesem Ticket wurde keine Bestellnummer erkannt. Gib die Bestellnummer direkt ein.
                @else
                    In EOCS wurde keine passende Bestellung gefunden.
                @endif
            </x-alert>
        @endif

        @foreach ($lookups as $lookup)
            @foreach ($lookup->orders as $order)
                <x-dhl.return-label
                    :order="$order"
                    :ticket="$ticket"
                    :seen-article="$seenArticle"
                    :label="$labels->firstWhere('order_number', $order->externalNumber)"
                    :signature="$signature"
                    :has-token="$hasToken"
                    :configured="$configured"
                    :return-group="$returnGroup"
                    :receiver="$receiver"
                />
            @endforeach
        @endforeach

        @if ($labels->isNotEmpty())
            <x-card>
                <h2 class="text-base font-semibold text-slate-900">Bisherige Labels zu dieser Suche</h2>
                <ul class="mt-3 divide-y divide-slate-200 text-sm" aria-label="Bisherige Labels">
                    @foreach ($labels as $past)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1 py-2 text-slate-900">
                            <span class="font-mono">{{ $past->order_number }}</span>
                            <span>{{ $past->created_at->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr · {{ $past->created_by }}</span>
                            <span class="text-slate-600">Sendungsnr. {{ $past->shipment_no ?? '–' }}</span>
                            <x-badge :tone="match ($past->status) { 'created' => 'success', 'failed' => 'danger', 'creating' => 'neutral', default => 'warning' }">{{ match ($past->status) { 'created' => 'erzeugt', 'failed' => 'abgelehnt', 'creating' => 'wird erzeugt', 'outcome_unknown' => 'Ausgang unklar', default => 'Datei unbrauchbar' } }}</x-badge>
                            @if ($past->sent_at)<span class="text-slate-600">gesendet an Ticket {{ $past->sent_ticket_number }} von {{ $past->sent_by }}</span>@endif
                            @if ($past->hasFile())<a href="{{ route('dhl-return.download', ['label' => $past->id, 'ticket' => $past->ticket_number]) }}" class="font-medium text-brand underline">Herunterladen</a>@endif
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>
</x-layouts.app>
