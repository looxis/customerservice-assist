@props(['result', 'number', 'retrieval', 'meta', 'isLatest' => true, 'admin' => false, 'stand' => null])

{{-- The knowledge retrieval of an analysis (PROJ-36): what came along and why, what was checked and not retrieved, with the switch-on form. --}}
    <x-analysis.section title="Wissensabruf" :count="count($retrieval['documents'])">
        @php
            $classLabels = config('knowledge.retrieval.classes');
            $leftOut = collect($retrieval['not_retrieved'])->where('code', 'out_of_space');
            $switchable = collect($retrieval['not_retrieved']);
        @endphp

        <p class="font-semibold">Abgerufen ({{ count($retrieval['documents']) }})</p>
        <ul class="mt-2 space-y-2">
            @foreach ($retrieval['documents'] as $item)
                <li class="rounded-md bg-slate-50 p-2 ring-1 ring-slate-200 ring-inset">
                    <span class="font-mono text-xs text-slate-600">{{ $item['id'] }}</span>
                    <span class="font-medium">{{ $item['title'] ?? '–' }}</span>
                    <x-badge compact>{{ $classLabels[$item['class']] ?? $item['class'] }}</x-badge>
                    @if ($item['unverified'])<x-badge compact tone="warning">Voraussetzung ungeklärt</x-badge>@endif
                    <span class="block text-xs text-slate-600">{{ implode('; ', $item['reasons']) }}</span>
                </li>
            @endforeach
        </ul>

        @if ($leftOut->isNotEmpty())
            <x-alert type="warning" class="mt-4">
                <p class="font-semibold">Wegen Umfang nicht mitgesendet</p>
                <p class="mt-1">{{ $leftOut->map(fn ($item) => $item['id'].' – '.($item['title'] ?? ''))->implode('; ') }}</p>
            </x-alert>
        @endif

        @if (($retrieval['suppressed'] ?? []) !== [])
            <p class="mt-4 font-semibold">Unterdrückte Treffer</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($retrieval['suppressed'] as $item)
                    <li><span class="font-mono text-xs">{{ $item['id'] }}</span>: Auslöser „{{ $item['trigger'] }}“ durch „{{ $item['exclusion'] }}“ im Satz unterdrückt: <span class="text-slate-600">{{ $item['sentence'] }}</span></li>
                @endforeach
            </ul>
        @endif

        <p class="mt-4 font-semibold">Geprüft, nicht abgerufen ({{ $switchable->count() }})</p>
        @if ($switchable->isEmpty())
            <p class="mt-1 text-slate-600">Alles im Geltungsbereich wurde abgerufen.</p>
        @else
            <form method="POST" action="{{ route('tickets.analysis.run', ['number' => $number]) }}" class="mt-2" x-data="{ busy: false }"
                  x-on:submit="if (busy) { $event.preventDefault(); return } busy = true; $dispatch('loading-start', { title: 'Ticket wird analysiert …', text: 'Das dauert in der Regel 10 bis 30 Sekunden.' })">
                @csrf
                @if ($stand)<input type="hidden" name="stand" value="{{ $stand }}">@endif
                @foreach (['kundengruppe', 'kontext', 'variante', ...array_keys(\App\Http\Requests\AnalyzeTicketRequest::MANUAL_ORDER_FIELDS)] as $field)
                    @if (($result['inputs'][$field] ?? null) !== null)<input type="hidden" name="{{ $field }}" value="{{ $result['inputs'][$field] }}">@endif
                @endforeach
                @foreach ($result['inputs']['produkte'] ?? [] as $product)<input type="hidden" name="produkte[]" value="{{ $product }}">@endforeach
                @foreach ($meta['orders'] ?? [] as $order)<input type="hidden" name="bestellungen[]" value="{{ $order }}">@endforeach
                @foreach ($retrieval['manual'] ?? [] as $manualId)<input type="hidden" name="zusatzwissen[]" value="{{ $manualId }}">@endforeach

                <ul class="space-y-2">
                    @foreach ($switchable as $item)
                        <li class="rounded-md p-2 ring-1 ring-slate-200 ring-inset">
                            <label class="flex items-start gap-2">
                                @if ($isLatest)<input type="checkbox" name="zusatzwissen[]" value="{{ $item['id'] }}" class="mt-1 size-4 rounded border-slate-300 text-brand focus:ring-brand">@endif
                                <span>
                                    <span class="font-mono text-xs text-slate-600">{{ $item['id'] }}</span> <span class="font-medium">{{ $item['title'] ?? '–' }}</span>
                                    <span class="block text-xs text-slate-600">{{ $item['detail'] }}</span>
                                </span>
                            </label>
                        </li>
                    @endforeach
                </ul>
                @if ($isLatest)
                    <p class="mt-3"><x-button type="submit" variant="secondary"><x-icon name="refresh" size="16" /> Zuschalten und neu analysieren</x-button></p>
                @endif
            </form>
        @endif

        @if ($admin)
            @if (($retrieval['conditions'] ?? []) !== [])
                <p class="mt-4 font-semibold">Bewertete Bedingungen (nur Admins)</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($retrieval['conditions'] as $id => $condition)
                        <li class="text-xs"><span class="font-mono">{{ $id }}</span>: {{ ['yes' => 'erfüllt', 'no' => 'nicht erfüllt', 'unknown' => 'unbekannt'][$condition['result']] }}{{ $condition['order'] ? ', Bestellung '.$condition['order'] : '' }}{{ $condition['order_unclear'] ? ' (Bestellbezug unklar)' : '' }}
                            – {{ collect($condition['checks'])->map(fn ($check) => $check['key'].': '.$check['fact'])->implode('; ') }}</li>
                    @endforeach
                </ul>
            @endif
            <p class="mt-4 font-mono text-xs text-slate-600">Abrufregeln {{ $meta['retrieval_version'] ?? '–' }} · Wissen {{ number_format($retrieval['sizes']['knowledge'], 0, ',', '.') }} Zeichen (Pflicht {{ number_format($retrieval['sizes']['mandatory'], 0, ',', '.') }}, Ziel {{ number_format($retrieval['sizes']['target'], 0, ',', '.') }})</p>
        @endif
    </x-analysis.section>
