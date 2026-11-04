{{-- Open ToDos on the start page (PROJ-70): mine, all open, overdue. Finished ones are only on the ToDo page. --}}
@props(['view', 'counts', 'list'])

@php
    $link = fn (string $key): string => route('tickets.analyze', array_filter([...request()->except('todos', 'page'), 'todos' => $key === \App\Todo\TodoBoard::DEFAULT_VIEW ? null : $key]));
@endphp

<section aria-labelledby="todo-heading" class="space-y-4">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h2 id="todo-heading" class="text-base font-semibold text-slate-900">ToDos</h2>
        <a href="{{ route('todos.index') }}" class="text-sm font-medium text-brand hover:text-brand-hover">Alle ToDos ansehen</a>
    </div>

    <div role="group" class="flex flex-wrap items-center gap-2" aria-label="ToDo-Ansichten">
        @foreach (\App\Todo\TodoBoard::VIEWS as $key => $label)
            <a href="{{ $link($key) }}" @if ($view === $key) aria-current="page" @endif class="rounded-pill px-3 py-1.5 text-sm font-medium ring-1 ring-inset {{ $view === $key ? 'bg-brand text-white ring-brand' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' }}">{{ $label }} ({{ $counts[$key] }})</a>
        @endforeach
    </div>

    <x-card class="p-0">
        @if ($list['rows']->isEmpty())
            <p class="p-6 text-sm text-slate-600">
                @if ($view === 'meine' && $counts['alle'] > 0) Keine offenen ToDos für dich. Unter „Alle offen“ stehen die ToDos der anderen.
                @elseif ($view === 'ueberfaellig') Nichts überfällig.
                @else Keine offenen ToDos. Du legst eines an der Ticketseite an oder beim Senden einer Antwort.
                @endif
            </p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($list['rows'] as $todo)<x-todo.row :todo="$todo" />@endforeach
            </ul>
            @if ($list['more'])<p class="border-t border-slate-100 px-4 py-3 text-sm text-slate-600">Weitere ToDos unter <a href="{{ route('todos.index') }}" class="font-medium text-brand hover:text-brand-hover">Alle ToDos ansehen</a>.</p>@endif
        @endif
    </x-card>

    <x-todo.dialog />
</section>
