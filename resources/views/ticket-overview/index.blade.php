<x-layouts.app title="Ticket-Übersicht" width="6xl">
    <nav class="flex flex-wrap gap-2" aria-label="Stand">
        <a href="{{ route('ticket-overview.index') }}" @if ($stage === null) aria-current="page" @endif
           @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => $stage === null, 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => $stage !== null])>
            Alle ({{ $total }})
        </a>
        @foreach (\App\Analysis\TicketOverview::STAGES as $key => $label)
            <a href="{{ route('ticket-overview.index', ['stand' => $key]) }}" @if ($stage === $key) aria-current="page" @endif
               @class(['rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand', 'bg-brand text-white ring-brand' => $stage === $key, 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => $stage !== $key])>
                {{ $label }} ({{ $counts[$key] }})
            </a>
        @endforeach
    </nav>

    <x-card class="p-0">
        @if ($rows->isEmpty())
            <p class="p-6 text-sm text-slate-600">{{ $total === 0 ? 'Noch keine Tickets abgerufen.' : 'Kein Ticket in diesem Stand.' }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Ticket</th>
                            <th scope="col" class="px-4 py-3">Kunde</th>
                            <th scope="col" class="px-4 py-3">Betreff</th>
                            <th scope="col" class="px-4 py-3">Stand</th>
                            <th scope="col" class="px-4 py-3">Letzter Abruf</th>
                            <th scope="col" class="px-4 py-3 text-right">Abrufe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            @php($visit = $row['visit'])
                            <tr>
                                <td class="px-4 py-3 align-top"><a href="{{ route('tickets.show', ['number' => $visit->ticket_number]) }}" class="font-mono text-brand hover:text-brand-hover">Ticket#{{ $visit->ticket_number }}</a></td>
                                <td class="px-4 py-3 align-top text-slate-900">{{ $visit->customer_name ?? '–' }}</td>
                                <td class="px-4 py-3 align-top text-slate-900">{{ $visit->title ?? '–' }}</td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap items-center gap-1">
                                        <x-badge compact :tone="match ($row['stage']) { 'copied-unchanged' => 'success', 'copied-changed' => 'info', 'edited' => 'warning', default => 'neutral' }">{{ \App\Analysis\TicketOverview::STAGES[$row['stage']] }}</x-badge>
                                        @if ($row['translated'] && $row['stage'] !== 'translated')<x-badge compact>Übersetzt</x-badge>@endif
                                    </div>
                                    @if ($row['analyses'] > 0)
                                        <p class="mt-1 text-xs text-slate-600">{{ $row['analyses'] }} {{ $row['analyses'] === 1 ? 'Analyse' : 'Analysen' }}@if ($row['feedback']) · Feedback: {{ config('analysis.feedback_levels.'.$row['feedback']) }}@endif</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-slate-900">{{ $visit->last_opened_at->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr<span class="block text-xs text-slate-600">{{ $visit->last_opened_by ?? '–' }}</span></td>
                                <td class="px-4 py-3 text-right align-top text-slate-900">{{ $visit->opened_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{ $rows->links() }}
</x-layouts.app>
