<x-layouts.app title="ToDos" width="6xl">
    <x-staff.hint :names="$staffNames" :current="$currentStaff" />

    <section aria-labelledby="todos-heading" class="space-y-4">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <h1 id="todos-heading" class="text-lg font-semibold text-slate-900">ToDos</h1>
            <x-intro><p class="text-xs text-slate-600">Offene ToDos und erledigte der letzten 12 Monate · {{ $rows->total() }} {{ $rows->total() === 1 ? 'Eintrag' : 'Einträge' }}</p></x-intro>
        </div>

        <x-card>
            <form method="GET" action="{{ route('todos.index') }}" aria-label="Filter der ToDos" class="flex flex-wrap items-end gap-4">
                <x-select name="zeige" label="Anzeigen">
                    <option value="alle" @selected($show === 'alle')>Offene und erledigte</option>
                    <option value="offen" @selected($show === 'offen')>Nur offene</option>
                    <option value="erledigt" @selected($show === 'erledigt')>Nur erledigte</option>
                </x-select>
                <x-select name="zustaendig" label="Zuständig">
                    <option value="">Alle</option>
                    @foreach ($staffNames as $name)<option value="{{ $name }}" @selected($assignee === $name)>{{ $name }}</option>@endforeach
                </x-select>
                <x-button type="submit"><x-icon name="check" size="16" /> Filtern</x-button>
            </form>
        </x-card>

        @php
            [$doneRows, $openRows] = $rows->getCollection()->partition(fn ($todo) => $todo->status === \App\Todo\TodoStatus::Done);
        @endphp

        @if ($rows->isEmpty())
            <x-card><p class="text-sm text-slate-600">Keine ToDos für diese Auswahl.</p></x-card>
        @endif

        @if ($openRows->isNotEmpty())
            <x-card class="p-0">
                <h2 class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">Offen ({{ $openRows->count() }})</h2>
                <ul class="divide-y divide-slate-100">@foreach ($openRows as $todo)<x-todo.row :todo="$todo" />@endforeach</ul>
            </x-card>
        @endif

        @if ($doneRows->isNotEmpty())
            <x-card class="p-0">
                <h2 class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">Erledigt ({{ $doneRows->count() }})</h2>
                <ul class="divide-y divide-slate-100">@foreach ($doneRows as $todo)<x-todo.row :todo="$todo" />@endforeach</ul>
            </x-card>
        @endif

        {{ $rows->links() }}
        <x-todo.dialog />
    </section>
</x-layouts.app>
