@php
    $levels = config('analysis.feedback_levels');
    $categories = config('knowledge.category_labels');
    $share = fn (?int $value): string => $value === null ? '–' : $value.' %';
    $productName = fn (string $slug): string => $productTitles[$slug] ?? $slug;
@endphp

<x-layouts.app title="Vorschläge" width="6xl">
    <x-card>
        <form method="GET" action="{{ route('analysis-overview.index') }}" class="space-y-4" aria-label="Filter">
            @if ($filter->problem)
                <x-alert type="warning">{{ $filter->problem }}</x-alert>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="contents" x-data="{ period: @js($filter->days === null ? 'eigen' : (string) $filter->days) }">
                    <x-select name="tage" label="Zeitraum" x-model="period" x-on:change="if (period !== 'eigen') { $refs.von.value = ''; $refs.bis.value = '' }">
                        @foreach (config('analysis.overview_periods') as $days => $label)
                            <option value="{{ $days }}" @selected($filter->days === $days)>Letzte {{ $label }}</option>
                        @endforeach
                        <option value="eigen" @selected($filter->days === null)>Von–Bis (eigener Zeitraum)</option>
                    </x-select>
                    <x-input name="von" type="date" label="Von" :value="$filter->from?->format('Y-m-d')" x-ref="von" x-on:input="period = 'eigen'" />
                    <x-input name="bis" type="date" label="Bis" :value="$filter->to?->format('Y-m-d')" x-ref="bis" x-on:input="period = 'eigen'" />
                </div>
                <x-select name="kundengruppe" label="Kundengruppe">
                    <option value="">Alle</option>
                    @foreach ($options['groups'] as $group)
                        <option value="{{ $group }}" @selected($filter->group === $group)>{{ $groups[$group] ?? ($group === 'unclear' ? 'Noch unklar' : $group) }}</option>
                    @endforeach
                </x-select>
                <x-select name="produkt" label="Produkt">
                    <option value="">Alle</option>
                    @foreach ($options['products'] as $product)
                        <option value="{{ $product }}" @selected($filter->product === $product)>{{ $productName($product) }}</option>
                    @endforeach
                </x-select>
                <x-select name="kategorie" label="Kategorie">
                    <option value="">Alle</option>
                    @foreach ($options['categories'] as $category)
                        <option value="{{ $category }}" @selected($filter->category === $category)>{{ $categories[$category] ?? $category }}</option>
                    @endforeach
                </x-select>
                <x-select name="feedback" label="Feedback">
                    <option value="">Alle</option>
                    @foreach ($levels as $key => $label)
                        <option value="{{ $key }}" @selected($filter->feedback === $key)>{{ ucfirst($label) }}</option>
                    @endforeach
                    <option value="none" @selected($filter->feedback === 'none')>Ohne Feedback</option>
                </x-select>
                <x-select name="kopiert" label="Entwurf">
                    <option value="">Alle</option>
                    @foreach ($overview::COPY_STATES as $key => $label)
                        <option value="{{ $key }}" @selected($filter->copy === $key)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select name="mitarbeiter" label="Mitarbeiter">
                    <option value="">Alle</option>
                    @foreach ($options['staff'] as $name)
                        <option value="{{ $name }}" @selected($filter->staff === $name)>{{ $name }}</option>
                    @endforeach
                </x-select>
                <x-select name="modell" label="Modell">
                    <option value="">Alle</option>
                    @foreach ($options['models'] as $model)
                        <option value="{{ $model }}" @selected($filter->model === $model)>{{ $model }}</option>
                    @endforeach
                </x-select>
                <x-select name="quelle" label="Quelle">
                    <option value="">Alle</option>
                    @foreach (\App\Analysis\AnalysisOverview::SOURCES as $key => $label)
                        <option value="{{ $key }}" @selected($filter->source === $key)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select name="router" label="Router-Entscheidung">
                    <option value="">Alle</option>
                    @foreach (\App\Analysis\AnalysisOverview::ROUTER_STATES as $key => $label)
                        <option value="{{ $key }}" @selected($filter->router === $key)>{{ ucfirst($label) }}</option>
                    @endforeach
                </x-select>
                <x-select name="prompt" label="Prompt-Version">
                    <option value="">Alle</option>
                    @foreach ($options['prompts'] as $prompt)
                        <option value="{{ $prompt }}" @selected($filter->prompt === $prompt)>{{ $prompt }}</option>
                    @endforeach
                </x-select>
            </div>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-900"><input type="checkbox" name="testlaeufe" value="1" @checked($filter->tests) class="size-4 rounded border-slate-300 text-brand focus:ring-brand"> Testläufe anzeigen</label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-900"><input type="checkbox" name="fehlgeschlagen" value="1" @checked($filter->failed) class="size-4 rounded border-slate-300 text-brand focus:ring-brand"> Fehlgeschlagene anzeigen</label>
                <div class="ml-auto flex flex-wrap items-center gap-3">
                    @if ($filter->isCustom())<x-button :href="route('analysis-overview.index')" variant="secondary"><x-icon name="refresh" size="16" /> Zurücksetzen</x-button>@endif
                    <x-button type="submit"><x-icon name="check" size="16" /> Filtern</x-button>
                </div>
            </div>
        </form>
    </x-card>

    <section aria-label="Kennzahlen" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-card>
            <p class="text-xs font-semibold text-slate-600">Vorschläge</p>
            <p class="mt-1 font-display text-2xl font-semibold text-slate-900">{{ $figures['count'] }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ $figures['rated'] }} mit Feedback · Ziel „unverändert/leicht“: mind. 70 %</p>
        </x-card>
        <x-card>
            <p class="text-xs font-semibold text-slate-600">Unverändert nutzbar oder leicht angepasst</p>
            <p class="mt-1 font-display text-2xl font-semibold text-slate-900">{{ $share($figures['usable']) }}</p>
            <p class="mt-1 text-xs text-slate-600">Verworfen: {{ $share($figures['discarded']) }} · Ohne Feedback: {{ $share($figures['without_feedback']) }}</p>
        </x-card>
        <x-card>
            <p class="text-xs font-semibold text-slate-600">Entwurf 1:1 kopiert</p>
            <p class="mt-1 font-display text-2xl font-semibold text-slate-900">{{ $share($figures['copied_unchanged']) }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ $figures['copied'] }} kopiert · {{ $figures['gaps'] }} {{ $figures['gaps'] === 1 ? 'Wissenslücke' : 'Wissenslücken' }} gemeldet</p>
        </x-card>
        @if ($figures['router'] !== null)
            @php($router = $figures['router'])
            <x-card class="sm:col-span-2 lg:col-span-3" id="router-kennzahlen">
                <p class="text-xs font-semibold text-slate-600">Erweiterte Wissenssuche (Router)</p>
                <p class="mt-1 text-sm text-slate-900">
                    @foreach (\App\Analysis\AnalysisOverview::ROUTER_STATES as $key => $label)
                        {{ ucfirst($label) }}: <strong>{{ $router['states'][$key] ?? 0 }}</strong>{{ $loop->last ? '' : ' · ' }}
                    @endforeach
                </p>
                <p class="mt-1 text-xs text-slate-600">
                    Ausfallquote: {{ $router['failure_share'] === null ? '–' : $router['failure_share'].' %' }}
                    · Ergänzungen je ausgeführtem Vorschlag: {{ $router['additions'] === null ? '–' : number_format($router['additions'], 1, ',', '.') }}
                    · zusätzlicher Umfang: {{ $router['characters'] === null ? '–' : number_format($router['characters'], 0, ',', '.').' Zeichen' }}
                    · Laufzeit: {{ $router['duration_ms'] === null ? '–' : number_format($router['duration_ms'] / 1000, 1, ',', '.').' s' }}
                    · Tokens (Ein/Aus): {{ $router['input_tokens'] === null ? '–' : number_format($router['input_tokens'], 0, ',', '.').' / '.number_format($router['output_tokens'], 0, ',', '.') }}
                    · Kosten: {{ $router['cost'] === null ? 'nicht ermittelbar (kein Preis hinterlegt)' : 'geschätzt '.number_format($router['cost'], 4, ',', '.').' $' }}
                </p>
            </x-card>
        @endif
    </section>

    <x-card class="p-0">
        @if ($rows->isEmpty())
            <p class="p-6 text-sm text-slate-600">Keine Vorschläge für diese Auswahl. Lockere die Filter oder wähle einen längeren Zeitraum.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[64rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Zeitpunkt</th>
                            <th scope="col" class="px-4 py-3">Ticket</th>
                            <th scope="col" class="px-4 py-3">Fall</th>
                            <th scope="col" class="px-4 py-3">Modell</th>
                            <th scope="col" class="px-4 py-3">Feedback</th>
                            <th scope="col" class="px-4 py-3">Entwurf</th>
                            <th scope="col" class="px-4 py-3 text-right">Lücken</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            @php($copy = $overview->copyState($row))
                            <tr>
                                <td class="px-4 py-3 align-top text-slate-900">{{ $row->created_at->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr<span class="block text-xs text-slate-600">{{ $row->staff_name }}</span></td>
                                <td class="px-4 py-3 align-top">@if (\App\Analysis\QuestionNumber::isQuestion($row->ticket_number))
                                        <a href="{{ route('questions.show', $overview->ticketParameters($row)) }}" class="font-mono text-brand hover:text-brand-hover">{{ $row->ticket_number }}</a><x-badge compact class="ml-1">Frage</x-badge>
                                    @else
                                        <a href="{{ route('tickets.show', $overview->ticketParameters($row)) }}" class="font-mono text-brand hover:text-brand-hover">Ticket#{{ $row->ticket_number }}</a>
                                    @endif</td>
                                <td class="px-4 py-3 align-top text-slate-900">
                                    {{ $groups[$row->customer_group] ?? ($row->customer_group === 'unclear' ? 'Noch unklar' : ($row->customer_group ?? '–')) }}
                                    <span class="block text-xs text-slate-600">{{ ($row->products ?? []) === [] ? 'kein Produktbezug' : implode(', ', array_map($productName, $row->products)) }}</span>
                                    <span class="block text-xs text-slate-600">{{ $row->category ? ($categories[$row->category] ?? $row->category) : '–' }}</span>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-900"><span class="block font-mono text-xs">{{ $row->model ?? '–' }}</span><span class="block text-xs text-slate-600">{{ $row->prompt_version ?? '–' }}</span></td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap items-center gap-1">
                                        @if ($row->status !== 'completed')
                                            <x-badge compact tone="danger">Fehlgeschlagen</x-badge>
                                        @elseif ($row->feedback_level)
                                            <x-badge compact :tone="match ($row->feedback_level) { 'unchanged', 'slight' => 'success', 'major' => 'warning', default => 'danger' }">{{ ucfirst($levels[$row->feedback_level] ?? $row->feedback_level) }}</x-badge>
                                        @else
                                            <span class="text-slate-600">–</span>
                                        @endif
                                        @if ($row->router_status === 'failed')<x-badge compact tone="warning" title="Die erweiterte Wissenssuche ist ausgefallen; es galt die reguläre Auswahl">Router ausgefallen</x-badge>@endif
                                        @if ($row->rule_check === 'incomplete')<x-badge compact tone="danger" title="{{ $row->rule_check_at ? 'Inzwischen von Hand geprüft' : 'Nicht bestätigt' }}">Regelprüfung unvollständig</x-badge>@endif
                                        @if ($row->test_until !== null || str_contains($row->scope_key, '.stand-'))<x-badge compact tone="warning">Testlauf</x-badge>@endif
                                    </div>
                                    @if ($row->status !== 'completed' && $row->error)<p class="mt-1 text-xs text-slate-600">{{ \App\Analysis\AnalysisProblem::tryFrom($row->error)?->message() ?? $row->error }}</p>@endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if ($copy === null)
                                        <span class="text-slate-600" title="Nicht erfasst">–</span>
                                    @else
                                        <x-badge compact :tone="match ($copy) { 'copied-unchanged', 'sent-unchanged' => 'success', 'copied-changed', 'sent-changed' => 'info', 'edited' => 'warning', default => 'neutral' }">{{ $overview::COPY_STATES[$copy] }}</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    @if ($row->gaps_count > 0)<a href="{{ route('knowledge-gaps.index', ['status' => 'open']) }}" class="font-semibold text-brand hover:text-brand-hover">{{ $row->gaps_count }}</a>@else<span class="text-slate-600">–</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{ $rows->links() }}
</x-layouts.app>
