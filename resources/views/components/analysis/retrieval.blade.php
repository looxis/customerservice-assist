@props(['result', 'number', 'retrieval', 'meta', 'isLatest' => true, 'admin' => false, 'stand' => null])

{{-- The knowledge retrieval of an analysis (PROJ-36): what came along and why, what was checked and not retrieved, with the switch-on form. --}}
    <x-analysis.section title="Wissensabruf" :count="count($retrieval['documents'])">
        @php
            $classLabels = config('knowledge.retrieval.classes');
            $leftOut = collect($retrieval['not_retrieved'])->where('code', 'out_of_space');
            $switchable = collect($retrieval['not_retrieved']);
            $router = $retrieval['router'] ?? null;
            $originLabels = ['core' => 'Grundwissen', 'trigger' => 'Durch Auslöser oder Bedingung ausgewählt', 'router' => 'Vom KI-Router ergänzt', 'manual' => 'Manuell zugeschaltet', 'safeguard' => 'Nachgeladen wegen Maßnahme'];
            $routerCut = ($router['cut']['a_shortened'] ?? 0) > 0 || ($router['cut']['b_removed'] ?? 0) > 0;
        @endphp

        <p class="font-semibold">Abgerufen ({{ count($retrieval['documents']) }})</p>
        <ul class="mt-2 space-y-2">
            @foreach ($retrieval['documents'] as $item)
                <li class="rounded-md bg-slate-50 p-2 ring-1 ring-slate-200 ring-inset">
                    <span class="font-mono text-xs text-slate-600">{{ $item['id'] }}</span>
                    <span class="font-medium">{{ $item['title'] ?? '–' }}</span>
                    <x-badge compact>{{ $classLabels[$item['class']] ?? $item['class'] }}</x-badge>
                    @php
                        $origin = $item['origin'] ?? (str_contains(implode(' ', $item['reasons']), 'von Hand zugeschaltet') ? 'manual' : (($item['class'] ?? '') === 'core' ? 'core' : 'trigger'));
                    @endphp
                    <x-badge compact :tone="$origin === 'router' ? 'info' : 'neutral'">{{ $originLabels[$origin] ?? $originLabels['trigger'] }}</x-badge>
                    @if ($item['unverified'])<x-badge compact tone="warning">Voraussetzung ungeklärt</x-badge>@endif
                    @if ($origin === 'router')
                        <span class="block text-xs text-slate-600">KI-Einschätzung: {{ \Illuminate\Support\Str::after(implode('; ', $item['reasons']), 'KI-Router: ') }}</span>
                    @else
                        <span class="block text-xs text-slate-600">{{ implode('; ', $item['reasons']) }}</span>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($routerCut)
            <p class="mt-3 text-xs text-slate-600">Hinweis: Der Text, den die erweiterte Wissenssuche geprüft hat, war sehr lang und wurde gekürzt ({{ ($router['cut']['a_shortened'] ?? 0) > 0 ? 'die aktuelle Nachricht in der Mitte, sichtbar markiert' : 'der frühere Verlauf' }}).</p>
        @endif

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
                  x-on:submit="if (busy) { $event.preventDefault(); return } busy = true; $dispatch('loading-start', { title: 'Vorschlag wird erstellt …', text: 'Das dauert in der Regel 10 bis 30 Sekunden.' })">
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
                    <p class="mt-3"><x-button type="submit" variant="secondary"><x-icon name="refresh" size="16" /> Zuschalten und neuen Vorschlag erstellen</x-button></p>
                @endif
            </form>
        @endif

        @php
            $outOfScope = collect($retrieval['out_of_scope'] ?? []);
            $productNames = collect(app(\App\Knowledge\KnowledgeSelector::class)->products())->mapWithKeys(fn (array $product): array => [$product['slug'] => $product['name'] ?? $product['title']]);
            $scopeGroups = [
                'product-missing' => ['Gelten nur für bestimmte Produkte – es war keins gewählt', 'Diese Dokumente wurden gar nicht geprüft. Gehört der Fall zu einem Produkt, wähle es und erstelle den Vorschlag neu.'],
                'product' => ['Gelten für andere Produkte als die gewählten', null],
                'group' => ['Gelten nur für andere Kundengruppen', null],
            ];
        @endphp
        @if ($outOfScope->isNotEmpty())
            <details class="mt-4 rounded-md ring-1 ring-slate-200 ring-inset" id="ausserhalb-geltungsbereich" @if ($outOfScope->contains('cause', 'product-missing')) open @endif>
                <summary class="cursor-pointer p-3 font-semibold">Nicht geprüft, außerhalb des Geltungsbereichs ({{ $outOfScope->count() }})</summary>
                <div class="space-y-4 p-3 pt-0">
                    @foreach ($scopeGroups as $cause => [$heading, $note])
                        @php
                            $items = $outOfScope->where('cause', $cause)->values();
                        @endphp
                        @if ($items->isNotEmpty())
                            <div>
                                <p class="font-medium text-slate-900">{{ $heading }} ({{ $items->count() }})</p>
                                @if ($note)<p class="mt-1 text-xs text-slate-600">{{ $note }}</p>@endif
                                <ul class="mt-2 space-y-1 text-xs">
                                    @foreach ($items->take(30) as $item)
                                        <li><span class="font-mono text-slate-600">{{ $item['id'] }}</span> {{ $item['title'] ?? '–' }}
                                            @if ($cause !== 'group' && ($item['products'] ?? []) !== [])<span class="text-slate-600">– {{ collect($item['products'])->map(fn ($slug) => $productNames[$slug] ?? $slug)->implode(', ') }}</span>@endif
                                            @if ($cause === 'group')<span class="text-slate-600">– {{ $item['reason'] }}</span>@endif
                                        </li>
                                    @endforeach
                                </ul>
                                @if ($items->count() > 30)<p class="mt-1 text-xs text-slate-600">… und {{ $items->count() - 30 }} weitere</p>@endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </details>
        @endif

        @if ($admin && $router !== null)
            @php
                $statusLabels = ['executed' => 'ausgeführt', 'skipped' => 'regulär übersprungen', 'disabled' => 'deaktiviert', 'failed' => 'fehlgeschlagen'];
            @endphp
            <details class="mt-4 rounded-md ring-1 ring-slate-200 ring-inset" id="router-details">
                <summary class="cursor-pointer p-3 font-semibold">Erweiterte Wissenssuche (Router, nur Admins): {{ $statusLabels[$router['status']] ?? $router['status'] }}</summary>
                <div class="space-y-2 p-3 pt-0 text-xs text-slate-700">
                    <p>{{ $router['reason'] }}</p>
                    @if (isset($router['pool_total']))
                        <p>Kandidaten: {{ $router['pool_total'] }} im Pool, {{ count($router['presented'] ?? []) }} dem Router gezeigt{{ ($router['preselected'] ?? false) ? ' (Vorauswahl über der Obergrenze, ausgelassen: '.count($router['left_out'] ?? []).')' : '' }}. Text an den Router: {{ number_format($router['text_characters'] ?? 0, 0, ',', '.') }} Zeichen.</p>
                    @endif
                    @if (($router['proposals'] ?? []) !== [])
                        <p class="font-semibold">Vorschläge des Routers</p>
                        <ul class="space-y-1">
                            @foreach ($router['proposals'] as $proposal)
                                <li><span class="font-mono">{{ $proposal['id'] }}</span> (Rang {{ $proposal['rank'] }}, {{ $proposal['basis'] === 'current' ? 'aktuelles Anliegen' : 'früherer Verlauf' }}): {{ $proposal['accepted'] ? 'übernommen' : 'verworfen – '.$proposal['rejection'] }}<br><span class="text-slate-600">KI-Einschätzung: {{ $proposal['reason'] }}</span>@if ($proposal['evidence'] !== '')<br><span class="text-slate-600">Beleg: {{ $proposal['evidence'] }}</span>@endif</li>
                            @endforeach
                        </ul>
                    @endif
                    @if (($router['categories'] ?? []) !== [])
                        <p class="font-semibold">Erkannte Fallarten (KI-Einschätzung, kein Filter)</p>
                        <ul class="list-disc pl-5">
                            @foreach ($router['categories'] as $category)
                                <li>{{ config('knowledge.category_labels.'.$category['key'], $category['key']) }}: {{ $category['reason'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="font-mono text-slate-600">Modell {{ $router['model'] ?? '–' }} · Router-Version {{ $router['version'] ?? '–' }} · Anweisung {{ $router['prompt_version'] ?? '–' }} · Tokens {{ $router['input_tokens'] ?? 0 }}/{{ $router['output_tokens'] ?? 0 }} · {{ $router['duration_ms'] ?? 0 }} ms{{ ($router['cost'] ?? null) !== null ? ' · geschätzt '.number_format($router['cost'], 4, ',', '.').' $' : '' }}</p>
                </div>
            </details>
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
