@props(['procedure', 'open' => true])

{{-- One internal procedure (PROJ-30). Used in the page and as the fragment
     loaded when a procedure is picked, so it carries no Alpine directives;
     "Schließen" is handled by the surrounding section via data-close-procedure. --}}
<details data-procedure="{{ $procedure['id'] }}" @if ($open) open @endif class="group/procedure rounded-md bg-white shadow-1 ring-1 ring-slate-200">
    <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 p-4 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
        <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open/procedure:rotate-90" />
        <span class="font-mono text-xs text-slate-600">{{ $procedure['id'] }}</span>
        <span class="font-semibold text-slate-900">{{ $procedure['title'] }}</span>
        @foreach ($procedure['actions'] as $label)<x-badge compact>{{ $label }}</x-badge>@endforeach
        @if ($procedure['draft'])<x-badge compact tone="warning">Entwurf</x-badge>@endif
        <button type="button" data-close-procedure="{{ $procedure['id'] }}" class="ml-auto text-xs font-semibold text-slate-600 hover:text-slate-900">Schließen</button>
    </summary>
    <div class="border-t border-slate-100 p-4">
        @if ($procedure['draft'])
            <x-alert type="warning" class="mb-4">Dieser Ablauf ist noch ein Entwurf. Wenn dir etwas falsch vorkommt, melde es als Wissenslücke.</x-alert>
        @endif
        <x-knowledge.text :html="$procedure['html']" />
    </div>
</details>
