{{-- The ToDos of one ticket (PROJ-70), with the dialog to create one. --}}
@props(['number', 'todos'])

@php
    $open = $todos->reject(fn ($todo) => $todo->status === \App\Todo\TodoStatus::Done);
    $finished = $todos->filter(fn ($todo) => $todo->status === \App\Todo\TodoStatus::Done);
@endphp

<section id="todos" aria-labelledby="ticket-todo-heading" class="mt-6 space-y-3" x-data>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="ticket-todo-heading" class="text-base font-semibold text-slate-900">ToDos zu diesem Ticket @if ($open->isNotEmpty())<span class="font-normal text-slate-600">({{ $open->count() }} offen)</span>@endif</h2>
        <x-button variant="secondary" x-on:click="$dispatch('new-todo')"><x-icon name="plus" size="16" /> ToDo anlegen</x-button>
    </div>

    @if ($todos->isEmpty())
        <p class="text-sm text-slate-600">Noch kein ToDo zu diesem Ticket. Eine Folgeaufgabe, die nach dem Schließen offen bleibt, legst du hier oder beim Senden der Antwort an.</p>
    @else
        @if ($open->isNotEmpty())<x-card class="p-0"><ul class="divide-y divide-slate-100">@foreach ($open as $todo)<x-todo.row :todo="$todo" :show-ticket="false" />@endforeach</ul></x-card>@endif
        @if ($finished->isNotEmpty())
            <details class="text-sm">
                <summary class="cursor-pointer font-medium text-slate-700">{{ $finished->count() }} erledigt</summary>
                <x-card class="mt-2 p-0"><ul class="divide-y divide-slate-100">@foreach ($finished as $todo)<x-todo.row :todo="$todo" :show-ticket="false" />@endforeach</ul></x-card>
            </details>
        @endif
    @endif

    <x-todo.dialog :ticket-number="$number" />
</section>
