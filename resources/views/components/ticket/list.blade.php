{{-- The list of tracked tickets on the start page (PROJ-67). --}}
@props(['filter', 'rows', 'options', 'sync', 'lacksStaffUser' => false])

@php
    $views = \App\Analysis\TicketList::VIEWS;
    $none = \App\Analysis\TicketList::NONE;
    $states = \App\Zammad\ZammadClient::STATE_LABELS;
    $categories = config('knowledge.category_labels');
    $link = fn (array $changes): string => route('tickets.analyze', array_filter([...$filter->parameters(), ...$changes], fn (?string $value): bool => $value !== null));
    $sortLink = fn (string $sort): string => $link([
        'sort' => $sort === \App\Analysis\TicketList::DEFAULT_SORT ? null : $sort,
        'richtung' => ($filter->sort === $sort ? ($filter->direction === 'desc' ? 'asc' : 'desc') : 'desc') === \App\Analysis\TicketList::DEFAULT_DIRECTION ? null : 'asc',
    ]);
    $columns = ['nummer' => 'Ticket', 'status' => 'Zammad-Status', 'aktivitaet' => 'Letzte Aktivität', 'erstellt' => 'Erstellt'];
    $sortHeader = fn (string $sort): array => ['href' => $sortLink($sort), 'label' => $columns[$sort], 'active' => $filter->sort === $sort, 'arrow' => $filter->direction === 'asc' ? '↑' : '↓', 'sort' => $filter->direction === 'asc' ? 'ascending' : 'descending'];
@endphp

<section aria-labelledby="ticket-list-heading" class="space-y-4">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h2 id="ticket-list-heading" class="text-base font-semibold text-slate-900">Tickets</h2>
        <p class="text-xs text-slate-600">
            @if ($sync['marker'])
                Stand: {{ $sync['marker']->locale('de')->diffForHumans() }}
            @else
                Noch kein Abgleich mit Zammad gelaufen
            @endif
        </p>
    </div>

    @if ($sync['stale'] || $sync['reconcile_failed'])
        <x-alert type="warning">
            Der Abgleich mit Zammad hängt, diese Liste kann veraltet sein. Die Eingabe per Ticketnummer funktioniert weiterhin.
        </x-alert>
    @endif

    <div role="group" class="flex flex-wrap items-center gap-2" aria-label="Ansichten">
        @foreach ($views as $key => $view)
            @php($isCurrent = ! $filter->unreadable && $filter->view === $key)
            <a href="{{ $link(['ansicht' => $key === \App\Analysis\TicketList::DEFAULT_VIEW ? null : $key, 'mitarbeiter' => null, 'sync' => null, 'page' => null]) }}" @if ($isCurrent) aria-current="page" @endif class="rounded-pill px-3 py-1.5 text-sm font-medium ring-1 ring-inset {{ $isCurrent ? 'bg-brand text-white ring-brand' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' }}">{{ $view['label'] }}</a>
        @endforeach
        @if ($sync['unreadable'] > 0)
            <a href="{{ route('tickets.analyze', ['sync' => \App\Analysis\TicketList::UNREADABLE]) }}" @if ($filter->unreadable) aria-current="page" @endif class="ml-auto text-sm font-medium text-brand hover:text-brand-hover">{{ $sync['unreadable'] }} {{ $sync['unreadable'] === 1 ? 'Ticket' : 'Tickets' }} in Zammad nicht lesbar</a>
        @endif
    </div>

    <x-card>
        <form method="GET" action="{{ route('tickets.analyze') }}" aria-label="Filter der Ticketliste" class="space-y-4">
            @if ($filter->view !== \App\Analysis\TicketList::DEFAULT_VIEW)<input type="hidden" name="ansicht" value="{{ $filter->view }}">@endif
            @if ($filter->unreadable)<input type="hidden" name="sync" value="{{ \App\Analysis\TicketList::UNREADABLE }}">@endif
            @if ($filter->sort !== \App\Analysis\TicketList::DEFAULT_SORT)<input type="hidden" name="sort" value="{{ $filter->sort }}">@endif
            @if ($filter->direction !== \App\Analysis\TicketList::DEFAULT_DIRECTION)<input type="hidden" name="richtung" value="{{ $filter->direction }}">@endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-select name="status" label="Status">
                    <option value="">Alle</option>
                    @foreach ($states as $key => $label)
                        <option value="{{ $key }}" @selected($filter->state === $key)>{{ ucfirst($label) }}</option>
                    @endforeach
                </x-select>
                <x-select name="mitarbeiter" label="Mitarbeiter">
                    <option value="">Wie Ansicht</option>
                    <option value="{{ $none }}" @selected($filter->owner === $none)>Nicht zugewiesen</option>
                    @foreach ($options['owners'] as $id => $name)
                        <option value="{{ $id }}" @selected($filter->owner === (string) $id)>{{ $name }}</option>
                    @endforeach
                </x-select>
                <x-select name="kanal" label="Kanal">
                    <option value="">Alle</option>
                    <option value="{{ $none }}" @selected($filter->channel === $none)>Unbekannt</option>
                    @foreach ($options['channels'] as $channel)
                        <option value="{{ $channel }}" @selected($filter->channel === $channel)>{{ $channel }}</option>
                    @endforeach
                </x-select>
                <x-select name="klassifikation" label="Klassifikation">
                    <option value="">Alle</option>
                    <option value="{{ $none }}" @selected($filter->category === $none)>Nicht klassifiziert</option>
                    @foreach ($options['categories'] as $category)
                        <option value="{{ $category }}" @selected($filter->category === $category)>{{ $categories[$category] ?? $category }}</option>
                    @endforeach
                </x-select>
                <x-select name="csa" label="CSA-Status">
                    <option value="">Alle</option>
                    @foreach (\App\Analysis\ProcessingState::cases() as $state)
                        <option value="{{ $state->value }}" @selected($filter->processing === $state->value)>{{ $state->label() }}</option>
                    @endforeach
                </x-select>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                @if ($filter->hasFilters())<x-button :href="$link(['status' => null, 'mitarbeiter' => null, 'kanal' => null, 'klassifikation' => null, 'csa' => null])" variant="secondary"><x-icon name="refresh" size="16" /> Zurücksetzen</x-button>@endif
                <x-button type="submit"><x-icon name="check" size="16" /> Filtern</x-button>
            </div>
        </form>
    </x-card>

    @if ($lacksStaffUser)
        <x-alert>Für „Meine“ ist für deinen Namen noch kein Zammad-Benutzer hinterlegt, oder es ist noch kein Name gewählt.</x-alert>
    @else
        <x-card class="p-0">
            @if ($rows->isEmpty())
                <p class="p-6 text-sm text-slate-600">
                    @if ($sync['marker'] === null)
                        Noch keine Tickets erfasst. Sie erscheinen hier, sobald der Abgleich mit Zammad gelaufen ist.
                    @else
                        Keine Tickets für diese Auswahl.
                    @endif
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[64rem] text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs font-semibold text-slate-600">
                            <tr>
                                @php($header = $sortHeader('nummer'))
                                <th scope="col" class="px-4 py-3" @if ($header['active']) aria-sort="{{ $header['sort'] }}" @endif><a href="{{ $header['href'] }}" class="hover:text-slate-900">{{ $header['label'] }} @if ($header['active'])<span aria-hidden="true">{{ $header['arrow'] }}</span>@endif</a></th>
                                <th scope="col" class="px-4 py-3">Betreff</th>
                                @php($header = $sortHeader('status'))
                                <th scope="col" class="px-4 py-3" @if ($header['active']) aria-sort="{{ $header['sort'] }}" @endif><a href="{{ $header['href'] }}" class="hover:text-slate-900">{{ $header['label'] }} @if ($header['active'])<span aria-hidden="true">{{ $header['arrow'] }}</span>@endif</a></th>
                                <th scope="col" class="px-4 py-3">Mitarbeiter</th>
                                <th scope="col" class="px-4 py-3">Kanal</th>
                                <th scope="col" class="px-4 py-3">Klassifikation</th>
                                <th scope="col" class="px-4 py-3">CSA-Status</th>
                                @php($header = $sortHeader('aktivitaet'))
                                <th scope="col" class="px-4 py-3" @if ($header['active']) aria-sort="{{ $header['sort'] }}" @endif><a href="{{ $header['href'] }}" class="hover:text-slate-900">{{ $header['label'] }} @if ($header['active'])<span aria-hidden="true">{{ $header['arrow'] }}</span>@endif</a></th>
                                @php($header = $sortHeader('erstellt'))
                                <th scope="col" class="px-4 py-3" @if ($header['active']) aria-sort="{{ $header['sort'] }}" @endif><a href="{{ $header['href'] }}" class="hover:text-slate-900">{{ $header['label'] }} @if ($header['active'])<span aria-hidden="true">{{ $header['arrow'] }}</span>@endif</a></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 align-top whitespace-nowrap"><a href="{{ route('tickets.show', $row->number) }}" class="font-mono text-brand hover:text-brand-hover">{{ $row->number }}</a></td>
                                    <td class="px-4 py-3 align-top text-slate-900 wrap-anywhere">
                                        <a href="{{ route('tickets.show', $row->number) }}" class="hover:text-brand">{{ $row->title }}</a>
                                        @if ($row->sync_status === \App\Zammad\SyncStatus::SyncFailed)
                                            <x-badge compact tone="warning" class="ml-1">Stand evtl. veraltet</x-badge>
                                        @elseif ($row->sync_status !== \App\Zammad\SyncStatus::Synced)
                                            <x-badge compact tone="danger" class="ml-1">In Zammad nicht lesbar</x-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-900">{{ ucfirst($states[$row->state] ?? $row->state) }}</td>
                                    <td class="px-4 py-3 align-top text-slate-900">{{ $row->owner_zammad_id === null ? '–' : ($row->owner_name ?? "Zammad-Benutzer {$row->owner_zammad_id}") }}</td>
                                    <td class="px-4 py-3 align-top text-slate-900">{{ $row->channel ?? '–' }}</td>
                                    <td class="px-4 py-3 align-top text-slate-900">
                                        @if ($row->category)
                                            {{ $categories[$row->category] ?? $row->category }}
                                        @else
                                            <span title="Nicht klassifiziert"><x-icon name="x-circle" class="text-danger-500/60" /><span class="sr-only">Nicht klassifiziert</span></span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top"><x-badge compact :tone="$row->processing_state->tone()">{{ $row->processing_state->label() }}</x-badge></td>
                                    <td class="px-4 py-3 align-top whitespace-nowrap text-slate-900">{{ $row->last_activity_at->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }}</td>
                                    <td class="px-4 py-3 align-top whitespace-nowrap text-slate-600">{{ $row->zammad_created_at?->setTimezone('Europe/Berlin')->format('d.m.Y') ?? '–' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        {{ $rows->links() }}
    @endif
</section>
