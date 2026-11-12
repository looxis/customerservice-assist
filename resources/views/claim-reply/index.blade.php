<x-layouts.app title="Vorab-Antworten Reklamation" width="6xl">
    <p class="text-sm text-slate-600">Die automatischen ersten Antworten auf Reklamationen (PROJ-85), ein Eintrag je Ticket. Schalter, Prompt und Modell stehen in den <a href="{{ route('settings.claim-reply') }}" class="font-medium text-brand hover:text-brand-hover">Einstellungen</a>.</p>

    <nav class="flex flex-wrap gap-2" aria-label="Ergebnis">
        <a href="{{ route('claim-reply-runs.index') }}" @if ($result === null) aria-current="page" @endif
           @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => $result === null, 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => $result !== null])>
            Alle ({{ $total }})
        </a>
        @foreach (\App\Models\ClaimReplyRun::results() as $key => $label)
            @continue (($counts[$key] ?? 0) === 0 && $result !== $key)
            <a href="{{ route('claim-reply-runs.index', ['ergebnis' => $key]) }}" @if ($result === $key) aria-current="page" @endif
               @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => $result === $key, 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => $result !== $key])>
                {{ $label }} ({{ $counts[$key] ?? 0 }})
            </a>
        @endforeach
    </nav>

    <x-card class="p-0">
        @if ($runs->isEmpty())
            <p class="p-6 text-sm text-slate-600">{{ $total === 0 ? 'Noch keine Vorab-Antwort bearbeitet. Sie entstehen, sobald der Schalter auf „Trockenlauf“ oder „an“ steht und ein neues Ticket als Reklamation eingeordnet wird.' : 'Kein Eintrag mit diesem Ergebnis.' }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Ticket</th>
                            <th scope="col" class="px-4 py-3">Ergebnis</th>
                            <th scope="col" class="px-4 py-3">Bestellung und Fall</th>
                            <th scope="col" class="px-4 py-3">Zeit</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Details</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($runs as $run)
                            <tr>
                                <td class="px-4 py-3 align-top"><a href="{{ route('tickets.show', ['number' => $run->ticket_number]) }}" class="font-mono text-brand hover:text-brand-hover">Ticket#{{ $run->ticket_number }}</a></td>
                                <td class="px-4 py-3 align-top">
                                    <x-badge compact :tone="match ($run->result) { 'sent' => 'success', 'dry_run' => 'info', 'error', 'sending' => 'danger', 'note', 'retry', 'working' => 'warning', default => 'neutral' }">{{ $run->label() }}</x-badge>
                                    @if ($run->reason)<p class="mt-1 text-xs text-slate-600">{{ $run->reason }}</p>@endif
                                </td>
                                <td class="px-4 py-3 align-top text-slate-900">
                                    <span class="font-mono">{{ $run->order_number ?? '–' }}</span>
                                    <span class="block text-xs text-slate-600">{{ $run->scenarioLabel() }} · {{ $run->photo_count ?? '–' }} {{ $run->photo_count === 1 ? 'Foto' : 'Fotos' }}@if ($run->days_since_shipped !== null) · Versand vor {{ $run->days_since_shipped }} Tagen @endif</span>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-900">{{ ($run->processed_at ?? $run->created_at)->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr<span class="block text-xs text-slate-600">{{ $run->attempts }} {{ $run->attempts === 1 ? 'Versuch' : 'Versuche' }}</span></td>
                                <td class="px-4 py-3 text-right align-top"><a href="{{ route('claim-reply-runs.show', $run) }}" class="font-semibold text-brand hover:text-brand-hover">Öffnen</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $runs->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
