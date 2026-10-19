@props(['result', 'number', 'history' => [], 'admin' => false, 'stand' => null, 'question' => false])

@php
    $r = $result['result'];
    $meta = $result['meta'];
    $text = fn ($value) => \App\Analysis\AnalysisPanel::text($value);
    $assessment = ['berechtigt' => 'success', 'unberechtigt' => 'danger', 'unklar' => 'warning'];
    $confidence = ['HOCH' => 'success', 'MITTEL' => 'warning', 'NIEDRIG' => 'danger'];
    $time = fn (string $value): string => \Carbon\CarbonImmutable::parse($value)->setTimezone('Europe/Berlin')->format('d.m.Y, H:i');
    $usedDrafts = collect($result['sources'])->where('draft', true)->pluck('id')->all();
    $usedTrials = collect($result['sources'])->filter(fn ($source) => ($source['trial_until'] ?? null) !== null);
    $gaps = $r['knowledge_gaps'] ?? [];
    $legacyMissing = $r['missing_information'] ?? [];
    $hasReply = $result['has_reply'] ?? true;
    $decided = $result['decisions'] ?? [];
    $notes = $result['notes'] ?? [];
    $mayDecide = $r['authority']['agent_may_decide'] ?? false;
    $isLatest = $result['is_latest'] ?? true;
    $reported = $result['gaps_reported'] ?? ['topics' => [], 'all' => 0];
    $rule = $result['rule_check'] ?? ['status' => null, 'by' => null, 'at' => null];
    $ruleIncomplete = ($rule['status'] ?? null) === 'incomplete';
    $ruleAcknowledged = $ruleIncomplete && ($rule['at'] ?? null) !== null;
    $retrieval = $result['retrieval'] ?? null;
    $infoOnly = ($r['next_step'] ?? 'reply') === 'info_only';
    $link = fn (?string $id): string => route($question ? 'questions.show' : 'tickets.show', array_filter(['number' => $number, 'analyse' => $id, 'stand' => $stand])).'#ergebnis';
@endphp

{{-- Analysis result (PROJ-10): what to do and the reply first, the reasoning
     in collapsible sections below. --}}
<section id="ergebnis" aria-labelledby="result-heading" class="scroll-mt-4">
    <x-card>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="result-heading" class="text-base font-semibold text-slate-900">Vorschlag</h2>
                <p class="mt-1 text-sm text-slate-600">Vorschlag von {{ $meta['staff'] }}, {{ $time($meta['created_at']) }} Uhr</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (($retrieval['router']['status'] ?? null) === 'failed')
            <div role="status" class="mt-4 rounded-md bg-slate-50 p-3 text-sm text-slate-700 ring-1 ring-slate-200 ring-inset" id="router-ausfall">
                <p class="font-medium">Erweiterte Wissenssuche nicht verfügbar.</p>
                <p class="mt-1">Der Antwortvorschlag wurde mit der regulären Wissensauswahl erstellt. Möglicherweise wurde weiteres relevantes Wissen nicht berücksichtigt. Bitte den Vorschlag besonders sorgfältig prüfen.</p>
            </div>
        @endif

        @if ($ruleIncomplete)<x-badge tone="danger">Regelprüfung unvollständig</x-badge>@endif
                @if ($gaps !== [])<x-badge tone="warning">Wissenslücke</x-badge>@endif
                @if ($r['assessment'] ?? null)<x-badge :tone="$assessment[$r['assessment']] ?? 'neutral'">{{ ucfirst($r['assessment']) }}</x-badge>@endif
                <x-badge :tone="$confidence[$r['confidence']['level']] ?? 'neutral'">Confidence {{ $r['confidence']['level'] }}</x-badge>
            </div>
        </div>

        @if (count($history) > 1)
            <details class="group/history mt-3 rounded-md ring-1 ring-slate-200 ring-inset">
                <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 hover:text-brand focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                    <x-icon name="chevron-right" size="16" class="text-slate-400 transition group-open/history:rotate-90" />
                    Frühere Vorschläge ({{ count($history) - 1 }})
                </summary>
                <ul class="divide-y divide-slate-100 border-t border-slate-100 text-sm">
                    @foreach ($history as $entry)
                        <li @class(['flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2', 'bg-brand/5' => $entry['id'] === $result['id']])>
                            @if ($entry['removed'] === null && $entry['id'] !== $result['id'])
                                <a href="{{ $link($entry['id']) }}" class="font-medium text-brand hover:text-brand-hover">{{ $entry['created_at']->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr</a>
                            @else
                                <span class="font-medium text-slate-900">{{ $entry['created_at']->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr</span>
                            @endif
                            <span class="text-slate-600">{{ $entry['staff'] }} · {{ $entry['group_label'] ?? '–' }} · {{ $entry['variant_label'] ?? '–' }}</span>
                            @if ($entry['assessment'])<x-badge compact :tone="$assessment[$entry['assessment']] ?? 'neutral'">{{ ucfirst($entry['assessment']) }}</x-badge>@endif
                            @if ($entry['confidence'])<x-badge compact :tone="$confidence[$entry['confidence']] ?? 'neutral'">{{ $entry['confidence'] }}</x-badge>@endif
                            @if ($entry['test'])<x-badge compact tone="warning">Testlauf</x-badge>@endif
                            @if ($entry['feedback'] ?? null)<x-badge compact tone="info">{{ config('analysis.feedback_levels.'.$entry['feedback']) }}</x-badge>@endif
                            @if ($entry['id'] === $result['id'])<span class="text-xs font-semibold text-brand-700">angezeigt</span>@endif
                            @if ($entry['removed'] === 'purged')<span class="text-xs text-slate-600">Inhalte nach 12 Monaten gelöscht</span>@endif
                            @if ($entry['removed'] === 'deleted')<span class="text-xs text-slate-600">Inhalte gelöscht</span>@endif
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if (! $isLatest)
            <x-alert type="warning" class="mt-4">
                Älterer Vorschlag vom {{ $time($meta['created_at']) }} Uhr – nur lesbar.
                <a href="{{ $link(null) }}" class="font-semibold underline">Zur neuesten</a>
            </x-alert>
        @endif

        @if ($meta['test_until'] ?? null)
            <p class="mt-2 inline-flex rounded-md bg-warning-500/10 px-2 py-1 text-xs font-semibold text-warning-700">Testlauf (Stand bis Nachricht vom {{ $time($meta['test_until']) }})</p>
        @endif

        @if ($result['new_messages'] ?? false)
            <x-alert type="warning" class="mt-4">
                <p>Seit diesem Vorschlag sind neue Nachrichten eingegangen.</p>
                <p class="mt-3">
                    <x-button variant="secondary" x-data x-on:click="$dispatch('open-analysis-form')"><x-icon name="refresh" size="16" /> Neuen Vorschlag erstellen</x-button>
                </p>
            </x-alert>
        @endif

        @if ($ruleIncomplete)
            <x-alert :type="$ruleAcknowledged ? 'warning' : 'error'" class="mt-4" id="regelpruefung">
                <p class="font-semibold">Regelprüfung unvollständig – der Entwurf ist ungeprüft</p>
                <p class="mt-1">Zur empfohlenen Maßnahme{{ ($retrieval['safeguard']['first_actions'] ?? []) !== [] ? ' ('.implode(', ', array_map(fn ($action) => config("knowledge.actions.{$action}", $action), $retrieval['safeguard']['first_actions'])).')' : '' }} konnten verbindliche Regeln nicht berücksichtigt werden:
                    <span class="font-mono text-xs">{{ implode(', ', $retrieval['safeguard']['not_considered'] ?? []) }}</span>.
                    Bitte diese Regeln in der Knowledge-Übersicht selbst nachlesen, bevor du den Vorschlag übernimmst.</p>
                @if ($ruleAcknowledged)
                    <p class="mt-2 font-medium">Von {{ $rule['by'] }} am {{ $rule['at']->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr als selbst geprüft bestätigt.</p>
                @elseif ($isLatest)
                    <form method="POST" action="{{ route('tickets.analysis.rule-check', ['number' => $number, 'analysis' => $result['id']]) }}" class="mt-3" x-data
                          x-on:submit="if (! confirm('Hast du die genannten verbindlichen Regeln selbst geprüft? Erst dann kann der Entwurf kopiert werden.')) { $event.preventDefault() }">
                        @csrf
                        @if ($stand)<input type="hidden" name="stand" value="{{ $stand }}">@endif
                        @foreach ($meta['orders'] ?? [] as $order)<input type="hidden" name="bestellungen[]" value="{{ $order }}">@endforeach
                        <x-button type="submit" variant="secondary"><x-icon name="check" size="16" /> Regeln selbst geprüft – Entwurf freigeben</x-button>
                    </form>
                @endif
            </x-alert>
        @endif

        {{-- What to do --}}
        <div class="mt-4 space-y-4 rounded-lg bg-slate-50 p-4 text-sm ring-1 ring-slate-200 ring-inset">
            <h3 class="font-semibold text-slate-900">Was ist zu tun?</h3>

            <div>
                <p class="text-slate-900">{{ $text($r['recommendation']) }}</p>
                @if (($r['actions'] ?? []) !== [])
                    <p class="mt-2 flex flex-wrap gap-2">@foreach ($r['actions'] as $action)<x-badge compact>{{ config("knowledge.actions.{$action}", $action) }}</x-badge>@endforeach</p>
                @endif
            </div>

            <p @class(['flex items-start gap-2 font-medium', 'text-success-700' => $mayDecide, 'text-warning-700' => ! $mayDecide])>
                <x-icon :name="$mayDecide ? 'check' : 'warning'" size="16" class="mt-0.5 shrink-0" />
                <span>
                    {{ $mayDecide ? 'Du darfst das selbst entscheiden.' : 'Freigabe nötig'.(($r['authority']['approval_by'] ?? null) ? ' durch '.$r['authority']['approval_by'] : '').'.' }}
                    @if ($r['authority']['permission_id'] ?? null)<span class="font-mono text-xs font-normal text-slate-600">({{ $r['authority']['permission_id'] }})</span>@endif
                </span>
            </p>

            @if ($gaps !== [])
                <x-alert type="warning">
                    <p class="font-semibold">Wissenslücken</p>
                    <p class="mt-1">{{ count($gaps) === 1 ? 'Für diesen Fall fehlt die folgende Regel' : 'Für diesen Fall fehlen die folgenden '.count($gaps).' Regeln' }} in der Wissensdatenbank (Regeln, Befugnisse und Abläufe unter „Knowledge“, nicht die Produktdaten aus dem Produktkatalog). Der Vorschlag sagt dazu bewusst nichts zu. Bitte nicht nach Gefühl entscheiden, sondern nachfragen und jede Lücke mit „Lücke melden“ melden.</p>
                    <ul class="mt-3 space-y-2 border-t border-warning-500/30 pt-3">
                        @foreach ($gaps as $gap)
                            @php($reportedBy = $reported['topics'][\App\Analysis\KnowledgeGapLog::topicHash($gap['topic'])] ?? null)
                            <li class="rounded-md bg-white/60 p-2 ring-1 ring-warning-500/30 ring-inset">
                                <span class="font-medium">{{ $loop->iteration }}. {{ $gap['topic'] }}</span>@if ($gap['question'] ?? null)<br><span>Offene Frage: {{ $gap['question'] }}</span>@endif
                                <br>
                                @if ($reportedBy)
                                    <span class="text-xs">bereits gemeldet von {{ $reportedBy }}</span>
                                @else
                                    <button type="button" class="mt-1 text-xs font-semibold underline"
                                            x-data x-on:click="$dispatch('report-gap', { topic: @js($gap['topic']), missing: @js(trim($gap['topic'].(($gap['question'] ?? null) ? ': '.$gap['question'] : ''))) })">Lücke melden</button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            @php($catalogSelected = collect($result['product_data']['products'] ?? [])->whereIn('id', $result['product_data']['selected'] ?? []))
            @php($eocsSelected = collect($result['product_data']['eocs_items'] ?? [])->whereIn('item_id', $result['product_data']['eocs_selected'] ?? []))
            @php($catalogHasService = $catalogSelected->contains(fn ($product) => filled($product['content']['customer_service_knowledge']['combined'] ?? null)))
            @if (($meta['unknown_product'] ?? null) && ! $catalogHasService)
                @php($unknownReportedBy = $reported['topics'][\App\Analysis\KnowledgeGapLog::topicHash(\App\Analysis\KnowledgeGapLog::unknownProductTopic($meta['unknown_product']))] ?? null)
                <x-alert type="warning">
                    <p class="font-semibold">{{ $eocsSelected->isNotEmpty() ? 'Noch nicht in ProductForge gepflegt:' : ($catalogSelected->isNotEmpty() ? 'Kein Kundenservice-Wissen im Produktkatalog zu' : 'Kein Produktwissen zu') }} „{{ $meta['unknown_product'] }}“</p>
                    @if ($eocsSelected->isNotEmpty())
                        <p class="mt-1 text-sm">Beschreibung und Bullet Points kommen aus EOCS; Kundenservice-Wissen gibt es dazu nicht. Das fehlende Produkt in ProductForge wurde automatisch gemeldet, du musst nichts tun.</p>
                    @elseif ($catalogSelected->isNotEmpty())
                        <p class="mt-1 text-sm">Die Produktdaten aus dem Katalog liegen vor (siehe „Produktinformationen“), aber dort ist zu diesem Produkt kein Kundenservice-Wissen gepflegt, und in der Wissensdatenbank (Knowledge) gibt es keine Regeln dazu.</p>
                    @else
                        <p class="mt-1 text-sm">Weder im Produktkatalog noch in der Wissensdatenbank (Knowledge) ist zu diesem Produkt etwas hinterlegt.</p>
                    @endif
                    @if ($eocsSelected->isEmpty())
                        @if ($unknownReportedBy)
                            <p class="mt-1">Zu diesem Produkt ist noch kein Wissen hinterlegt; der Vorschlag sagt dazu bewusst nichts zu. Die fehlende Information wurde automatisch gemeldet, zusammen mit deinen Angaben aus dem Formular. Du musst nichts weiter tun.</p>
                        @else
                            <p class="mt-1">Zu diesem Produkt ist noch kein Wissen hinterlegt; der Vorschlag sagt dazu bewusst nichts zu. Bitte melde das fehlende Wissen, damit es ergänzt wird.</p>
                            <button type="button" class="mt-1 text-xs font-semibold underline" x-data x-on:click="$dispatch('report-gap', { topic: @js(\App\Analysis\KnowledgeGapLog::unknownProductTopic($meta['unknown_product'])), missing: @js('Produktwissen zu „'.$meta['unknown_product'].'“') })">Wissenslücke melden</button>
                        @endif
                    @endif
                </x-alert>
            @endif

            @php($withoutProduct = collect($retrieval['out_of_scope'] ?? [])->where('cause', 'product-missing'))
            @if ($withoutProduct->isNotEmpty() && ! ($meta['unknown_product'] ?? null))
                @php($productNames = collect(app(\App\Knowledge\KnowledgeSelector::class)->products())->mapWithKeys(fn (array $product): array => [$product['slug'] => $product['name'] ?? $product['title']]))
                <x-alert type="warning">
                    <p class="font-semibold">Ohne Produktwahl nicht berücksichtigt</p>
                    <p class="mt-1">{{ $withoutProduct->count() }} {{ $withoutProduct->count() === 1 ? 'Dokument gilt' : 'Dokumente gelten' }} nur für bestimmte Produkte
                        ({{ $withoutProduct->pluck('products')->flatten()->unique()->take(4)->map(fn ($slug) => $productNames[$slug] ?? $slug)->implode(', ') }}{{ $withoutProduct->pluck('products')->flatten()->unique()->count() > 4 ? ' …' : '' }}) und wurde nicht geprüft, weil kein Produkt gewählt war.
                        Geht es um ein Produkt, wähle es und erstelle den Vorschlag neu.</p>
                    @unless ($question)
                        <p class="mt-2"><x-button variant="secondary" x-data x-on:click="$dispatch('open-analysis-form')"><x-icon name="refresh" size="16" /> Produkt wählen und neu erstellen</x-button></p>
                    @endunless
                </x-alert>
            @endif

            @if (($meta['knowledge_fingerprints'] ?? null) === [])
                <x-alert type="warning">
                    <p class="font-semibold">Kein Wissen für diesen Fall</p>
                    <p class="mt-1">Für die gewählte Kundengruppe und die Produkte gibt es kein passendes Wissen. Der Vorschlag beruht nur auf dem Ticket und ist entsprechend vorsichtig. Stimmen Kundengruppe und Produkte?</p>
                </x-alert>
            @endif

            @if ($usedTrials->isNotEmpty())
                <p class="flex items-start gap-2 text-warning-700">
                    <x-icon name="warning" size="16" class="mt-0.5 shrink-0" />
                    <span>Beruht teilweise auf vorläufigem Wissen (in Erprobung):
                        @foreach ($usedTrials as $trial)
                            <span class="font-mono text-xs">{{ $trial['id'] }}</span> bis {{ \Carbon\CarbonImmutable::parse($trial['trial_until'])->format('d.m.Y') }}@if (\Carbon\CarbonImmutable::parse($trial['trial_until'], 'Europe/Berlin')->endOfDay()->isPast()) <span class="font-semibold">(Testphase abgelaufen, Überprüfung überfällig)</span>@endif @if ($trial['trial_goal'])<span class="text-slate-600">– Ziel: {{ $trial['trial_goal'] }}</span>@endif @unless ($loop->last)·@endunless
                        @endforeach
                    </span>
                </p>
            @endif
            @if ($usedDrafts !== [])
                <p class="flex items-start gap-2 text-warning-700">
                    <x-icon name="warning" size="16" class="mt-0.5 shrink-0" />
                    <span>Beruht teilweise auf Entwurfs-Wissen – bitte kritisch prüfen: <span class="font-mono text-xs">{{ implode(', ', $usedDrafts) }}</span></span>
                </p>
            @endif

            @if ($notes !== [] || ($meta['knowledge_warnings'] ?? []) !== [])
                <p class="text-slate-600">Prüfhinweise vorhanden – siehe unten.</p>
            @endif
        </div>

        @if ($decided !== [])
            <div class="mt-4 rounded-lg bg-slate-50 p-4 text-sm ring-1 ring-slate-200 ring-inset">
                <p class="font-semibold text-slate-900">Bereits intern geklärt</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($decided as $item)<li>{{ $item['question'] }} → <span class="font-medium">{{ $item['answer'] }}</span> <span class="text-slate-600">({{ $item['by'] }})</span></li>@endforeach
                </ul>
            </div>
        @endif

        @if ($hasReply)
            <x-analysis.reply-editor :result="$result" :number="$number" :readonly="! $isLatest" :locked="$ruleIncomplete && ! $ruleAcknowledged" />

            <x-analysis.feedback :result="$result" :number="$number" />
        @elseif ($infoOnly)
            <x-alert type="info" class="mt-4" id="kein-entwurf-angefordert">
                <p class="font-semibold">Kein Antwortentwurf angefordert</p>
                <p class="mt-1">Das ist die Auskunft für dich. Möchtest du dem Kunden antworten, fordere unter <a href="#nachfrage" class="font-semibold underline">Nachfrage oder Ergänzung</a> einen Antwortentwurf an.</p>
            </x-alert>
        @else
            <x-alert type="info" class="mt-4" id="noch-keine-antwort">
                <p class="font-semibold">Noch kein Antwortentwurf – zuerst intern klären</p>
                <p class="mt-1">Die Antwort an den Kunden hängt von einer internen Klärung ab. Sie steht unter <a href="#ablaeufe" class="font-semibold underline">Interne Abläufe</a>; nach dem Ergebnis geht die Analyse weiter.</p>
            </x-alert>
        @endif

        @if (session('gap_reported'))
            <x-alert type="success" class="mt-4">Wissenslücke gemeldet – danke.</x-alert>
        @endif
        @if (session('gap_error'))
            <x-alert type="error" class="mt-4">{{ session('gap_error') }}</x-alert>
        @endif

        <x-analysis.gap-form :result="$result" :number="$number" :stand="$stand" />

        {{-- Reasoning and details --}}
        <div class="mt-6">
            <x-analysis.section title="Begründung">
                <p>{{ $text($r['reasoning']) }}</p>
            </x-analysis.section>

            <x-analysis.section title="Quellen" :count="count($result['sources'])">
                @forelse ($result['sources'] as $source)
                    <details class="group/source border-b border-slate-100 last:border-b-0">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center gap-2 py-2 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                            <x-icon name="chevron-right" size="14" class="text-slate-400 transition group-open/source:rotate-90" />
                            <span class="font-mono text-xs text-slate-600">{{ $source['id'] }}</span>
                            <span class="font-medium">{{ $source['title'] ?? '–' }}</span>
                            @if ($source['type'])<x-badge compact>{{ $source['type'] }}</x-badge>@endif
                            @if ($source['draft'])<x-badge compact tone="warning">Entwurf</x-badge>@endif
                            @if ($source['trial_until'] ?? null)<x-badge compact :tone="\Carbon\CarbonImmutable::parse($source['trial_until'], 'Europe/Berlin')->endOfDay()->isPast() ? 'danger' : 'info'">Vorläufig bis {{ \Carbon\CarbonImmutable::parse($source['trial_until'])->format('d.m.Y') }}</x-badge>@endif
                            @if ($source['reported_many'] ?? false)<x-badge compact tone="warning">Mehrfach zur Prüfung gemeldet</x-badge>@endif
                            @if ($source['state'] === 'changed')<x-badge compact tone="info">Seit dem Vorschlag geändert</x-badge>@endif
                            @if ($source['state'] === 'removed')<x-badge compact tone="danger">nicht mehr vorhanden</x-badge>@endif
                        </summary>
                        <div class="pb-4 pl-6">
                            @if ($source['html'])
                                <x-knowledge.text :html="$source['html']" class="text-sm" />
                            @else
                                <p class="text-slate-600">Der Text aus der Zeit des Vorschlags ist nicht gespeichert.</p>
                            @endif
                            @if ($source['url'])
                                <p class="mt-3"><a href="{{ $source['url'] }}" class="font-semibold text-brand hover:text-brand-hover">In der Knowledge-Übersicht öffnen</a></p>
                            @endif
                            @if ($source['state'] !== 'removed')
                                @if (session('review_error'))<p class="mt-2 text-sm font-medium text-danger-700" role="alert">{{ session('review_error') }}</p>@endif
                                @if (session('review_reported') === $source['id'])<p class="mt-2 text-sm font-medium text-success-700" role="status">Danke, die Meldung ist angekommen. Ein Admin schaut sich das Dokument an.</p>@endif
                                <x-knowledge.review-form :document-id="$source['id']" :analysis="$result['id'] ?? null" :ticket="$number" />
                            @endif
                        </div>
                    </details>
                @empty
                    <p class="text-slate-600">Der Vorschlag nennt keine Quellen.</p>
                @endforelse
            </x-analysis.section>

            @if (($result['related']['tickets'] ?? []) !== [])
                <x-analysis.section title="Zusammengehörige Tickets" :count="count($result['related']['tickets'])">
                    <p class="text-sm text-slate-600">Der Verlauf dieser Tickets ist als früherer Verlauf in den Vorschlag eingeflossen.</p>
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach ($result['related']['tickets'] as $relatedTicket)
                            <li>
                                <a href="{{ route('tickets.show', ['number' => $relatedTicket['number']]) }}" class="font-mono font-semibold text-brand hover:text-brand-hover">{{ $relatedTicket['number'] }}</a>
                                {{ $relatedTicket['title'] }}
                                <span class="block text-xs text-slate-600">{{ $relatedTicket['state'] }} · {{ ['linked' => 'in Zammad verknüpft', 'order' => 'gleiche Bestellnummer', 'customer' => 'gleicher Kunde', 'manual' => 'von Hand ergänzt'][$relatedTicket['source']] ?? $relatedTicket['source'] }} · {{ $relatedTicket['included'] }} von {{ $relatedTicket['articles'] }} Nachrichten @if ($relatedTicket['omitted'] > 0)<span class="font-medium text-warning-700">({{ $relatedTicket['omitted'] }} ältere ausgelassen)</span>@endif</span>
                            </li>
                        @endforeach
                    </ul>
                </x-analysis.section>
            @endif

            @if (($result['product_sent'] ?? null) !== null)
                @php($fieldLabels = ['service' => 'Kundenservice-Wissen', 'warnings' => 'Warnhinweise', 'care_instructions' => 'Pflegehinweise', 'technical_data' => 'Technische Daten', 'included_in_delivery' => 'Im Lieferumfang', 'description' => 'Beschreibung', 'bullet_points' => 'Bullet Points', 'shared' => 'Gemeinsamer Wissensursprung', 'eocs_bullet_points' => 'Bullet Points (EOCS)', 'eocs_description' => 'Beschreibung (EOCS)', 'all' => 'alle Felder'])
                @php($reasonLabels = ['budget' => 'zu umfangreich für den Platz im Prompt', 'channel_unclear' => 'Kanal unklar', 'not_affected' => 'nicht als betroffen markiert'])
                @php($forgeProducts = collect($result['product_data']['products'] ?? [])->keyBy('id'))
                @php($sent = $result['product_sent'])
                <x-analysis.section title="Produktinformationen" :count="count($sent['sent']['products'] ?? []) + count($sent['sent']['eocs'] ?? [])">
                    @foreach ($sent['sent']['products'] ?? [] as $productId => $fields)
                        @php($product = $forgeProducts->get($productId) ?? ['name' => 'Produkt '.$productId, 'sku' => null])
                        <details class="group/product border-b border-slate-100 last:border-b-0">
                            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-2 py-2 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                                <x-icon name="chevron-right" size="14" class="text-slate-400 transition group-open/product:rotate-90" />
                                <span class="font-medium">{{ $product['name'] }}</span>
                                @if ($product['sku'] ?? null)<span class="font-mono text-xs text-slate-600">{{ $product['sku'] }}</span>@endif
                                <span class="text-xs text-slate-600">abgerufen {{ \Carbon\CarbonImmutable::parse($product['fetched_at'] ?? now())->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr</span>
                                @if (in_array((int) $productId, $result['product_data']['reused'] ?? [], true))<x-badge compact tone="info">aus erstem Schritt übernommen</x-badge>@endif
                            </summary>
                            <div class="pb-4 pl-6 text-sm">
                                @foreach ($fields as $field => $fieldText)
                                    <p class="mt-2 font-semibold">{{ $fieldLabels[$field] ?? $field }}</p>
                                    <p class="whitespace-pre-line">{{ $fieldText }}</p>
                                @endforeach
                                @if ($admin)
                                    <p class="mt-3 text-xs text-slate-600">ID {{ $productId }} · {{ $product['resolved_via'] ?? '–' }} · {{ $product['data_version'] ?? '–' }}</p>
                                @endif
                            </div>
                        </details>
                    @endforeach
                    @foreach ($sent['sent']['eocs'] ?? [] as $itemNumber => $fields)
                        @php($eocsItem = collect($result['product_data']['eocs_items'] ?? [])->firstWhere('item_id', (int) $itemNumber) ?? ['name' => 'Artikel '.$itemNumber])
                        <details class="group/eocs border-b border-slate-100 last:border-b-0">
                            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-2 py-2 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                                <x-icon name="chevron-right" size="14" class="text-slate-400 transition group-open/eocs:rotate-90" />
                                <span class="font-medium">{{ $eocsItem['name'] }}</span>
                                <span class="font-mono text-xs text-slate-600">{{ $itemNumber }}</span>
                                <x-badge compact tone="warning">nur EOCS, noch nicht in ProductForge</x-badge>
                            </summary>
                            <div class="pb-4 pl-6 text-sm">
                                @foreach ($fields as $field => $fieldText)
                                    <p class="mt-2 font-semibold">{{ $fieldLabels[$field] ?? $field }}</p>
                                    <p class="whitespace-pre-line">{{ $fieldText }}</p>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                    @foreach ($sent['sent']['shared'] ?? [] as $shared)
                        <p class="mt-2 text-sm"><span class="font-semibold">Gemeinsamer Wissensursprung: {{ $shared['title'] }}</span><br><span class="whitespace-pre-line">{{ $shared['text'] }}</span></p>
                    @endforeach
                    @if (($sent['omitted'] ?? []) !== [])
                        <p class="mt-3 text-sm text-slate-700">Nicht mitgesendet:
                            {{ collect($sent['omitted'])->map(fn ($entry) => ($fieldLabels[$entry['field']] ?? $entry['field']).' ('.($reasonLabels[$entry['reason']] ?? $entry['reason']).')')->unique()->implode(', ') }}.</p>
                    @endif
                    @if (($sent['problems'] ?? []) !== [])
                        <p class="mt-3 text-sm font-medium text-warning-700">Zu einem Produkt konnten keine Produktinformationen abgerufen werden. Der Vorschlag nutzt dazu nur die Wissensbasis.</p>
                    @endif
                    @if (($sent['sent']['products'] ?? []) === [] && ($sent['sent']['eocs'] ?? []) === [] && ($sent['problems'] ?? []) === [])
                        <p class="text-slate-600">Es wurden keine Produktfelder mitgesendet.</p>
                    @endif
                </x-analysis.section>
            @endif

            @if ($retrieval)
                <x-analysis.retrieval :result="$result" :number="$number" :retrieval="$retrieval" :meta="$meta" :is-latest="$isLatest" :admin="$admin" :stand="$stand" />
            @endif

            <x-analysis.section title="Kurzfassung">
                <dl class="space-y-3">
                    <div><dt class="font-semibold">Was ist passiert?</dt><dd class="mt-1">{{ $text($r['summary']['incident'] ?? '') }}</dd></div>
                    <div><dt class="font-semibold">Was möchte der Kunde?</dt><dd class="mt-1">{{ $text($r['summary']['customer_wish'] ?? '') }}</dd></div>
                    <div><dt class="font-semibold">Kategorie</dt><dd class="mt-1">{{ ($r['category'] ?? null) ? config('knowledge.category_labels.'.$r['category'], $r['category']) : '–' }}</dd></div>
                    <div><dt class="font-semibold">Fallmuster</dt><dd class="mt-1">{{ $r['case_pattern'] ?? '–' }}</dd></div>
                </dl>
            </x-analysis.section>

            <x-analysis.section :title="'Confidence '.$r['confidence']['level'].' – Gründe'" :count="count($r['confidence']['reasons'] ?? [])">
                <ul class="list-disc pl-5">@foreach ($r['confidence']['reasons'] ?? [] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
            </x-analysis.section>

            @if ($legacyMissing !== [])
                <x-analysis.section title="Fehlende Angaben (älterer Vorschlag)" :count="count($legacyMissing)">
                    <ul class="space-y-2">
                        @foreach ($legacyMissing as $item)
                            <li>{{ $item['what'] ?? '' }} <span class="text-slate-600">– von {{ $item['from'] ?? '' }}</span><br><span class="font-medium">Rückfrage:</span> {{ $item['question'] ?? '' }}</li>
                        @endforeach
                    </ul>
                </x-analysis.section>
            @endif

            @if ($notes !== [] || ($meta['knowledge_warnings'] ?? []) !== [])
                <x-analysis.section title="Prüfhinweise" :count="count($notes) + count($meta['knowledge_warnings'] ?? [])">
                    <ul class="list-disc pl-5">
                        @foreach ($notes as $note)<li>{{ $note }}</li>@endforeach
                        @foreach ($meta['knowledge_warnings'] ?? [] as $warning)<li>{{ $warning }}</li>@endforeach
                    </ul>
                </x-analysis.section>
            @endif

            @if ($admin && (($result['sent_input'] ?? null) !== null))
                <x-analysis.section title="Protokoll (nur Admins)">
                    <p class="font-semibold">An die KI gesendet</p>
                    <pre class="mt-2 max-h-96 overflow-auto rounded-md bg-slate-50 p-3 font-mono text-xs whitespace-pre-wrap ring-1 ring-slate-200">{{ $result['sent_input'] }}</pre>
                    <p class="mt-4 font-semibold">Antwort der KI (unverändert)</p>
                    <pre class="mt-2 max-h-96 overflow-auto rounded-md bg-slate-50 p-3 font-mono text-xs whitespace-pre-wrap ring-1 ring-slate-200">{{ json_encode($result['raw_response'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    <p class="mt-4 font-semibold">Metadaten</p>
                    <pre class="mt-2 max-h-96 overflow-auto rounded-md bg-slate-50 p-3 font-mono text-xs whitespace-pre-wrap ring-1 ring-slate-200">{{ json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </x-analysis.section>
            @endif

            <x-analysis.section title="Details zum Vorschlag">
                <p class="font-mono text-xs text-slate-600">
                    {{ $meta['staff'] }} · {{ $time($meta['created_at']) }} Uhr · {{ $meta['model'] }} · Prompt {{ $meta['prompt_version'] }} · Wissensstand {{ $meta['knowledge_state'] }} · {{ number_format($meta['duration_ms'] / 1000, 1, ',', '.') }} s
                </p>
            </x-analysis.section>
        </div>
    </x-card>
</section>
