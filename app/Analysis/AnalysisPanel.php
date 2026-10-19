<?php

namespace App\Analysis;

use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsItem;
use App\Eocs\EocsOrder;
use App\Eocs\EocsOrderItem;
use App\Eocs\OrderLookup;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeMarkdown;
use App\Knowledge\KnowledgeReviewLog;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\KnowledgeSuggester;
use App\Knowledge\ProcedureFinder;
use App\Knowledge\ProcedureRenderer;
use App\Orders\OrderNumberDetector;
use App\ProductForge\ForgeProduct;
use App\ProductForge\KnowledgeAreaSuggester;
use App\ProductForge\ProductForgeClient;
use App\ProductForge\ProductForgeException;
use App\ProductForge\ProductForgeProblem;
use App\ProductForge\SkuDetector;
use App\Zammad\RelatedTicketFinder;
use App\Zammad\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;

/**
 * Everything the ticket page needs for the analysis area: suggestions,
 * variants with preview, the stored summary and a stored result.
 */
class AnalysisPanel
{
    public function __construct(
        private readonly KnowledgeSelector $selector,
        private readonly KnowledgeSuggester $suggester,
        private readonly AnalysisStore $store,
        private readonly KnowledgeLibrary $library,
        private readonly KnowledgeMarkdown $markdown,
        private readonly ProductForgeClient $forge,
        private readonly KnowledgeAreaSuggester $areas,
        private readonly EocsClient $eocs,
        private readonly RelatedTicketFinder $relatedFinder,
        private readonly OrderNumberDetector $orderNumbers,
    ) {}

    /**
     * @param  list<OrderLookup>  $lookups
     * @return array<string, mixed>
     */
    public function build(Ticket $ticket, array $lookups, ?string $resultId): array
    {
        $orders = collect($lookups)->flatMap(fn (OrderLookup $lookup): array => $lookup->orders)->values()->all();
        $context = new TicketContext($ticket);
        $summary = $this->store->summary($ticket->summaryKey());
        $pseudonymizer = app(Pseudonymizer::class);

        foreach ($orders as $order) {
            if ($order->deliveryAddress !== [] && ! $pseudonymizer->hasDeliveryAddress()) {
                $pseudonymizer->withDeliveryAddress($order->deliveryAddress);
            }
        }

        $previews = [];

        foreach ($context->variants() as $variant) {
            $previews[$variant->value] = $variant === ContextVariant::LastWithSummary && $summary === null
                ? null
                : $pseudonymizer->apply($context->text($variant, $summary?->text));
        }

        $latestId = $this->store->latest($ticket->summaryKey());
        $result = $this->decorate($this->result($ticket, $resultId ?? $latestId), $ticket, $latestId);
        $inputs = $result['inputs'] ?? [];

        $choice = $this->store->caseChoice($ticket->number);
        [$group, $groupSource] = $this->suggestedGroup($ticket, $orders, $choice);

        if (is_string($inputs['kundengruppe'] ?? null)) {
            [$group, $groupSource] = [$inputs['kundengruppe'], 'wie im angezeigten Vorschlag'];
        }

        $chosenGroup = $inputs['kundengruppe'] ?? $choice['group'] ?? null;

        return [
            'groups' => $this->selector->selectableGroups(),
            'suggestedGroup' => $group,
            'groupSource' => $groupSource,
            'chosenGroupLabel' => $chosenGroup === null ? null : collect($this->selector->customerGroups())->firstWhere('key', $chosenGroup)?->label,
            ...$this->productSuggestions($context, $orders, $inputs['produkte'] ?? $choice['products'] ?? null),
            'forge' => $this->forgeChoice($orders, $inputs, $result, $context->customerTexts()),
            'related' => $this->relatedChoice($ticket, $inputs, $choice['related'] ?? null),
            'hasOrders' => $orders !== [],
            'variants' => $context->variants(),
            'defaultVariant' => ContextVariant::tryFrom((string) ($inputs['variante'] ?? '')) ?? $context->defaultVariant(),
            'inputs' => $inputs,
            'suggestsSummary' => $context->suggestsSummary(),
            'previews' => $previews,
            'stand' => $ticket->rewoundTo,
            'summary' => $summary,
            'summaryStale' => $summary?->isStale($context->earlierFingerprint()) ?? false,
            'summaryOffered' => $context->suggestsSummary() || ($summary !== null && $context->offersVariants()),
            'ignoredReplies' => $context->ignoredAfterLastCustomerMessage(),
            'messageCount' => $context->messageCount(),
            'result' => $result,
            'history' => $this->history($ticket),
            'deletion' => $result === null ? $this->store->deletion($ticket->number) : null,
            'resultMissing' => $resultId !== null && $result === null,
        ];
    }

    /**
     * What the page of a question without a ticket needs (PROJ-40): the stored analysis as for a ticket,
     * the earlier versions and the question itself. Null when the question or the version is unknown.
     *
     * @return array<string, mixed>|null
     */
    public function forQuestion(string $number, ?string $analysisId): ?array
    {
        $ticket = QuestionTicket::make($number, '');
        $latestId = $this->store->latest($number);
        $result = $this->decorate($this->result($ticket, $analysisId ?? $latestId), $ticket, $latestId);

        if ($result === null) {
            return null;
        }

        $groups = collect($this->selector->customerGroups())->mapWithKeys(fn (CustomerGroup $group): array => [$group->key => $group->label]);
        $titles = collect($this->selector->products())->mapWithKeys(fn (array $product): array => [$product['slug'] => $product['title']]);
        $products = array_values((array) ($result['meta']['products'] ?? []));

        return [
            'result' => $result,
            'history' => $this->history($ticket),
            'question' => [
                'text' => (string) ($result['question']['text'] ?? ''),
                'group_label' => $groups[$result['meta']['customer_group'] ?? ''] ?? null,
                'products_label' => $products === [] ? null : implode(', ', array_map(fn (string $slug): string => $titles[$slug] ?? $slug, $products)),
                'placeholders' => ($result['placeholders'] ?? []) !== [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $result
     * @return array<string, mixed>|null
     */
    private function decorate(?array $result, Ticket $ticket, ?string $latestId): ?array
    {
        if ($result === null) {
            return null;
        }

        // Compared within the analysis' own scope, so a test run opened by its
        // link outside the test mode is not taken for an older analysis.
        $result['is_latest'] = $result['id'] === (($result['scope'] ?? $ticket->summaryKey()) === $ticket->summaryKey() ? $latestId : $this->store->latest((string) $result['scope']));
        $result['gaps_reported'] = app(KnowledgeGapLog::class)->reportedFor($result['id']);
        $result['procedures'] = $this->procedures($result);

        return $result;
    }

    /**
     * The group to preselect and why: chosen earlier for this ticket, from the
     * channel of a loaded order, remembered for this customer, or unclear.
     *
     * @param  list<EocsOrder>  $orders
     * @param  array{group: string, staff: string, at: string}|null  $choice
     * @return array{0: string, 1: string|null}
     */
    private function suggestedGroup(Ticket $ticket, array $orders, ?array $choice): array
    {
        $known = array_map(fn (CustomerGroup $group): string => $group->key, $this->selector->selectableGroups());

        if ($choice !== null && in_array($choice['group'], $known, true)) {
            return [$choice['group'], 'zuletzt gewählt von '.$choice['staff'].' am '.CarbonImmutable::parse($choice['at'])->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr'];
        }

        foreach ($orders as $order) {
            foreach ($this->selector->selectableGroups() as $group) {
                if ($order->channel !== null && $group->salesChannel === $order->channel) {
                    return [$group->key, 'aus dem Kanal der Bestellung'];
                }
            }
        }

        $remembered = $this->store->customerGroup($ticket->customerKey());

        if ($remembered !== null && in_array($remembered, $known, true)) {
            return [$remembered, 'bei früheren Tickets dieses Kunden gewählt'];
        }

        return ['', null];
    }

    /**
     * The reply as the AI wrote it, with the app's own placeholders filled in.
     *
     * @param  array<string, mixed>  $stored
     */
    public function originalReply(array $stored): string
    {
        return Pseudonymizer::fromValues($stored['placeholders'] ?? [])->restorePlain((string) ($stored['result']['reply']['text'] ?? ''));
    }

    /**
     * A stored analysis prepared for display: the reply with values put back
     * (or as edited), the inserted values, the sources with their state and
     * whether new messages came in since.
     *
     * @return array<string, mixed>|null
     */
    private function result(Ticket $ticket, ?string $id): ?array
    {
        $stored = $id === null ? null : $this->store->result($id);

        if ($stored === null || ($stored['ticket'] ?? null) !== $ticket->number) {
            return null;
        }

        $reply = (string) ($stored['result']['reply']['text'] ?? '');
        $original = $this->originalReply($stored);
        $edit = $stored['reply_edit'] ?? null;
        $last = $ticket->articles === [] ? null : $ticket->articles[array_key_last($ticket->articles)];
        $lastSeen = $stored['thread']['last_article_id'] ?? null;

        return [
            ...$stored,
            'id' => $id,
            'reply_original' => $original,
            'reply_text' => is_array($edit) ? (string) $edit['text'] : $original,
            'reply_edited' => is_array($edit) ? 'bearbeitet von '.$edit['staff'].' am '.CarbonImmutable::parse($edit['at'])->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr' : null,
            'inserted' => $this->inserted($reply, $stored['placeholders'] ?? []),
            'reply_foreign' => ! str_starts_with(mb_strtolower(trim((string) ($stored['result']['reply']['language'] ?? 'Deutsch'))), 'deutsch'),
            'reply_backtranslation' => isset($stored['reply_backtranslation']['text']) ? [
                'text' => (string) $stored['reply_backtranslation']['text'],
                'stale' => ($stored['reply_backtranslation']['fingerprint'] ?? null) !== hash('sha256', trim(is_array($edit) ? (string) $edit['text'] : $original)),
            ] : null,
            'has_reply' => is_array($stored['result']['reply'] ?? null),
            'steps' => array_values(array_filter((array) ($stored['result']['internal_steps'] ?? $stored['result']['internal_todos'] ?? []), 'is_string')),
            'sources' => $this->sources($stored),
            'new_messages' => $lastSeen !== null && $last !== null && $last->id !== $lastSeen && ((new TicketContext($ticket))->lastConsidered()?->id !== $lastSeen),
        ];
    }

    /**
     * Products suggested from the order lines and from the words in the
     * ticket (PROJ-33), each with its reasons. A choice made for the ticket
     * wins; products only mentioned are then pointed out.
     *
     * @param  list<EocsOrder>  $orders
     * @param  list<string>|null  $chosen
     * @return array{products: list<array{slug: string, title: string}>, suggestedProducts: list<string>, productReasons: array<string, list<string>>, mentionedProducts: list<string>}
     */
    private function productSuggestions(TicketContext $context, array $orders, ?array $chosen): array
    {
        $fromOrders = $this->suggester->products(array_map(fn (EocsOrderItem $item): array => ['article_number' => $item->itemNumber, 'description' => $item->name], collect($orders)->flatMap(fn (EocsOrder $order): array => $order->items)->all()));
        $fromText = $this->suggester->productsFromText($context->customerTexts());

        $reasons = [];

        foreach ($fromOrders as $slug) {
            $reasons[$slug][] = 'aus der Bestellung';
        }

        foreach ($fromText as $slug => $words) {
            $reasons[$slug][] = 'erkannt im Ticket: '.implode(', ', array_map(fn (string $word): string => '‚'.$word.'‘', array_slice($words, 0, 2)));
        }

        $suggested = array_keys($reasons);
        $products = collect($this->selector->products())
            ->sortBy(fn (array $product): int => isset($reasons[$product['slug']]) ? 0 : 1)
            ->values()
            ->all();

        return [
            'products' => $products,
            'suggestedProducts' => $chosen ?? $suggested,
            'productReasons' => $reasons,
            'mentionedProducts' => $chosen === null ? [] : array_values(array_diff(array_keys($fromText), $chosen)),
        ];
    }

    /**
     * The tickets that may belong to this case, for the form (PROJ-52). Not offered for a rewound test ticket.
     * What was ticked before is kept (in the form, and for the ticket when it is opened again); at first the linked ones
     * (and, with a claim number, those of the order) are ticked.
     *
     * @param  array<string, mixed>  $inputs
     * @param  list<string>|null  $remembered  The numbers chosen the last time an analysis was started for this ticket.
     * @return array{enabled: bool, groups: array<string, list<array<string, mixed>>>, selected: list<string>, problems: list<string>}
     */
    private function relatedChoice(Ticket $ticket, array $inputs, ?array $remembered = null): array
    {
        if ($ticket->rewoundTo !== null || $ticket->id === 0) {
            return ['enabled' => false, 'groups' => [], 'selected' => [], 'problems' => []];
        }

        $found = $this->relatedFinder->find($ticket, $this->orderNumbers->detect($ticket));
        $groups = ['linked' => [], 'order' => [], 'customer' => []];

        foreach ($found['tickets'] as $related) {
            $groups[$related->source][] = $related->toArray();
        }

        $offered = array_map(fn (array $related): string => (string) $related['number'], array_merge(...array_values($groups)) ?: []);
        $selected = match (true) {
            array_key_exists('related_set', $inputs) => array_values(array_map('strval', (array) ($inputs['related'] ?? []))),
            $remembered !== null => array_values(array_intersect($remembered, $offered)),
            default => $found['preselected'],
        };

        return ['enabled' => true, 'groups' => $groups, 'selected' => $selected, 'problems' => $found['problems']];
    }

    /**
     * The catalog products for the form (PROJ-38): the order positions found by their SKU (only when the
     * automatic assignment is switched on) and the products chosen before. Nothing is guessed: a SKU
     * that ProductForge does not know is named as unknown.
     *
     * @param  list<EocsOrder>  $orders
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>|null  $result
     * @param  list<string>  $customerTexts  Title and customer messages, searched for named article numbers.
     * @return array{enabled: bool, candidates: list<array<string, mixed>>, selected: list<int>, notices: list<string>}
     */
    private function forgeChoice(array $orders, array $inputs, ?array $result, array $customerTexts = []): array
    {
        if (! $this->forge->isConfigured()) {
            return ['enabled' => false, 'candidates' => [], 'selected' => [], 'notices' => []];
        }

        $candidates = [];
        $notices = [];
        $tried = [];

        if ((bool) config('services.productforge.order_lookup')) {
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $sku = $item->itemNumber;

                    if ($sku !== null && $sku !== '' && ! isset($tried[$sku])) {
                        $tried[$sku] = true;
                        $this->candidateForSku($sku, "Position: {$item->quantity} × {$item->name}", "Position „{$item->name}“ (Art.-Nr. {$sku})", $candidates, $notices);
                    }
                }
            }
        }

        // An article number the customer names in the text ("SKU: 12006") is offered like an order position.
        foreach (app(SkuDetector::class)->in($customerTexts) as $sku) {
            if (! isset($tried[$sku])) {
                $tried[$sku] = true;
                $this->candidateForSku($sku, "im Ticket genannt: Art.-Nr. {$sku}", "Im Ticket genannte Art.-Nr. {$sku}", $candidates, $notices);
            }
        }

        $saved = [];

        foreach ((array) ($result['product_data']['products'] ?? []) as $snapshot) {
            $saved[(int) ($snapshot['id'] ?? 0)] = (array) $snapshot;
        }

        $savedEocs = [];

        foreach ((array) ($result['product_data']['eocs_items'] ?? []) as $snapshot) {
            $savedEocs[(int) ($snapshot['item_id'] ?? 0)] = (array) $snapshot;
        }

        $selected = [];

        foreach (array_map('intval', (array) ($inputs['forge_produkte'] ?? [])) as $id) {
            $selected[] = "forge:{$id}";

            if (! isset($candidates["forge:{$id}"])) {
                $snapshot = $saved[$id] ?? ['id' => $id, 'name' => 'Produkt '.$id, 'sku' => null, 'type' => 'standalone', 'status' => 'active', 'master' => null];
                $product = ForgeProduct::fromSnapshot([...$snapshot, 'fetched_at' => $snapshot['fetched_at'] ?? now()->toIso8601String()]);
                $candidates["forge:{$id}"] = $this->candidate($product->identity(), 'forge', $product->hasServiceKnowledge(), 'zuvor gewählt', null);
            }
        }

        foreach (array_map('intval', (array) ($inputs['eocs_produkte'] ?? [])) as $number) {
            $selected[] = "eocs:{$number}";

            if (! isset($candidates["eocs:{$number}"])) {
                $item = EocsItem::fromSnapshot([...($savedEocs[$number] ?? ['item_id' => $number, 'name' => 'Artikel '.$number]), 'fetched_at' => $savedEocs[$number]['fetched_at'] ?? now()->toIso8601String()]);
                $candidates["eocs:{$number}"] = $this->candidate($item?->identity() ?? ['id' => $number, 'name' => 'Artikel '.$number, 'sku' => (string) $number, 'type' => 'standalone', 'status' => 'active', 'master' => null], 'eocs', false, 'zuvor gewählt', null);
            }
        }

        if ($selected === [] && count($candidates) === 1) {
            $selected = [array_key_first($candidates)];
        }

        return ['enabled' => true, 'candidates' => array_values($candidates), 'selected' => $selected, 'notices' => $notices];
    }

    /**
     * Looks a SKU up: ProductForge first; only when it does not know the product, EOCS is asked, and its
     * item is offered as a fallback. A SKU that neither knows is named, nothing is guessed.
     *
     * @param  array<string, array<string, mixed>>  $candidates
     * @param  list<string>  $notices
     */
    private function candidateForSku(string $sku, string $source, string $label, array &$candidates, array &$notices): void
    {
        try {
            $product = $this->forge->bySku($sku);
            $candidates["forge:{$product->id}"] ??= $this->candidate($product->identity(), 'forge', $product->hasServiceKnowledge(), $source, $sku);

            return;
        } catch (ProductForgeException $exception) {
            if ($exception->problem !== ProductForgeProblem::NotFound) {
                $notices[] = "{$label}: ".$exception->problem->message();

                return;
            }
        }

        try {
            $item = $this->eocs->item($sku);
        } catch (EocsException $exception) {
            $notices[] = "{$label}: nicht in ProductForge, und EOCS ist nicht erreichbar.";

            return;
        }

        if ($item === null) {
            $notices[] = "Die SKU {$sku} wurde in EOCS nicht gefunden, bitte auf Richtigkeit prüfen ({$label}).";

            return;
        }

        $candidates["eocs:{$item->itemId}"] ??= $this->candidate($item->identity(), 'eocs', false, $source, $sku);
    }

    /**
     * @param  array{id: int, name: string, sku: ?string, type: string, status: string, master: ?array<string, mixed>}  $identity
     * @param  'forge'|'eocs'  $origin
     * @return array<string, mixed>
     */
    private function candidate(array $identity, string $origin, bool $hasServiceKnowledge, string $source, ?string $skuUsed): array
    {
        return [
            ...$identity,
            'origin' => $origin,
            'key' => "{$origin}:{$identity['id']}",
            'source' => $source,
            'sku_used' => $skuUsed,
            'has_service_knowledge' => $origin === 'eocs' ? false : $hasServiceKnowledge,
            'knowledge_slugs' => $this->areas->slugsFor($identity),
        ];
    }

    /**
     * Internal procedures for the shown analysis (PROJ-30): the suggested
     * ones ready to show, and the list of all usable ones to pick by hand
     * (their text is loaded when picked).
     *
     * @param  array<string, mixed>  $result
     * @return array{suggested: list<array<string, mixed>>, catalogue: array<string, list<array{id: string, title: string|null, draft: bool, note: string|null}>>}
     */
    private function procedures(array $result): array
    {
        $finder = app(ProcedureFinder::class);
        $group = $this->selector->customerGroup((string) ($result['inputs']['kundengruppe'] ?? $result['meta']['customer_group'] ?? 'unclear'))
            ?? $this->selector->customerGroup('unclear');
        $products = array_values((array) ($result['meta']['products'] ?? []));

        return [
            'suggested' => array_map(
                fn (KnowledgeDocument $document): array => self::procedureCard($document),
                $finder->suggested($group, $products, $result['result']['category'] ?? null, array_values((array) ($result['result']['actions'] ?? []))),
            ),
            'catalogue' => array_map(fn (array $entries): array => array_map(fn (array $entry): array => [
                'id' => $entry['document']->id,
                'title' => $entry['document']->title,
                'draft' => $entry['document']->isDraft(),
                'note' => $entry['note'],
            ], $entries), $finder->catalogue($group, $products)),
        ];
    }

    /**
     * What the card of a procedure needs.
     *
     * @return array{id: string, title: string|null, draft: bool, actions: list<string>, html: HtmlString}
     */
    public static function procedureCard(KnowledgeDocument $document): array
    {
        return [
            'id' => (string) $document->id,
            'title' => $document->title,
            'draft' => $document->isDraft(),
            'actions' => array_map(fn (string $action): string => config("knowledge.actions.{$action}", $action), $document->actions()),
            'html' => app(ProcedureRenderer::class)->render($document),
        ];
    }

    /**
     * Earlier analyses of the ticket (or test-run cut point) with readable labels.
     *
     * @return list<array<string, mixed>>
     */
    private function history(Ticket $ticket): array
    {
        $groups = collect($this->selector->customerGroups())->mapWithKeys(fn (CustomerGroup $group): array => [$group->key => $group->label]);

        return array_map(fn (array $entry): array => [
            ...$entry,
            'group_label' => $groups[$entry['group']] ?? $entry['group'],
            'variant_label' => ContextVariant::tryFrom((string) $entry['variant'])?->label(),
        ], $this->store->history($ticket->summaryKey()));
    }

    /**
     * Names of the values the app put into the reply, e.g. "Lieferadresse".
     *
     * @param  array<string, string>  $values
     * @return list<string>
     */
    private function inserted(string $reply, array $values): array
    {
        $names = [];

        foreach (array_keys($values) as $placeholder) {
            if (str_contains($reply, $placeholder)) {
                $kind = preg_replace('/_\d+$/', '', trim($placeholder, '[]'));
                $names[] = match ($kind) {
                    'LIEFERADRESSE' => 'Lieferadresse',
                    'E-MAIL' => 'E-Mail-Adresse',
                    'TELEFON' => 'Telefonnummer',
                    'ADRESSE' => 'Anschrift',
                    default => ucfirst(strtolower((string) $kind)),
                };
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The cited knowledge as it was at the time of the analysis, marked when
     * it changed since or no longer exists. Older results only know the IDs.
     *
     * @param  array<string, mixed>  $stored
     * @return list<array<string, mixed>>
     */
    private function sources(array $stored): array
    {
        $snapshots = collect($stored['sources'] ?? [])->keyBy('id');
        $drafts = $stored['draft_ids'] ?? [];
        $reportedMany = array_map(fn (array $count): bool => $count['many'], app(KnowledgeReviewLog::class)->openCounts());

        return array_map(function (string $id) use ($snapshots, $drafts, $reportedMany): array {
            $snapshot = $snapshots->get($id);
            $current = $this->library->find($id);

            return [
                'id' => $id,
                'title' => $snapshot['title'] ?? $current?->title,
                'type' => $snapshot['type'] ?? $current?->type,
                'draft' => $snapshot['draft'] ?? in_array($id, $drafts, true),
                'trial_until' => $snapshot['trial_until'] ?? null,
                'trial_goal' => $snapshot['trial_goal'] ?? null,
                'reported_many' => $reportedMany[$id] ?? false,
                'html' => $snapshot !== null ? $this->markdown->render((string) $snapshot['body']) : null,
                'state' => match (true) {
                    $current === null => 'removed',
                    $snapshot !== null && $current->fingerprint !== $snapshot['fingerprint'] => 'changed',
                    default => 'same',
                },
                'url' => $current === null ? null : route('knowledge.show', ['path' => $current->path]),
            ];
        }, array_values(array_filter((array) ($stored['result']['knowledge_ids'] ?? []), 'is_string')));
    }

    public static function text(?string $value): HtmlString
    {
        return new HtmlString(nl2br(e((string) $value), false));
    }
}
