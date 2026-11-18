@props(['record' => null])

{{-- Notification mails of the n8n workflows found at Lettermint for the orders (PROJ-97). --}}
@if (is_array($record) && $record !== [])
    @php
        $proven = collect($record)->contains(fn ($entry) => collect($entry['notices'] ?? [])->contains(fn ($notice) => ($notice['outcome'] ?? null) === 'accepted'));
        $tones = ['accepted' => 'success', 'handed_over' => 'warning', 'pending' => 'warning', 'failed' => 'danger', 'unknown' => 'neutral'];
    @endphp
    <div id="versandbenachrichtigungen" class="space-y-2">
        <p class="font-semibold text-slate-900">Versandbenachrichtigungen</p>
        @foreach ($record as $entry)
            <div>
                @if (count($record) > 1)<p class="text-xs text-slate-600">Bestellung {{ $entry['order'] }}</p>@endif
                @if (($entry['status'] ?? 'ok') !== 'ok')
                    <p class="text-slate-700">Benachrichtigungen nicht abrufbar (Lettermint).</p>
                @elseif (($entry['notices'] ?? []) === [])
                    <p class="text-slate-700">Keine Benachrichtigung zu dieser Bestellung gefunden.</p>
                @else
                    <ul class="space-y-1">
                        @foreach ($entry['notices'] as $notice)
                            <li class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="font-medium text-slate-900">{{ \App\Lettermint\DeliveryNotice::KIND_LABELS[$notice['kind']] ?? $notice['kind'] }}</span>
                                <x-badge compact :tone="$tones[$notice['outcome']] ?? 'neutral'">{{ \App\Lettermint\DeliveryNotice::OUTCOME_LABELS[$notice['outcome']] ?? $notice['outcome'] }}</x-badge>
                                <span class="text-slate-700">{{ $notice['at'] ?? 'Zeitpunkt unbekannt' }}</span>
                                @if ($notice['failure'] ?? null)<span class="text-danger-700">{{ $notice['failure'] }}</span>@endif
                                @if (($notice['to_ticket_sender'] ?? null) === false)<span class="text-xs text-slate-600">(an eine andere Adresse als die des Tickets)</span>@endif
                                <span class="w-full text-xs text-slate-600">{{ $notice['subject'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
        @unless ($proven)
            <x-alert type="warning">Keine Benachrichtigung per E-Mail belegt. Dem Kunden keine Mail nennen und den n8n-Workflow prüfen.</x-alert>
        @endunless
    </div>
@endif
