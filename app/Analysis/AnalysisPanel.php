<?php

namespace App\Analysis;

use App\Eocs\EocsOrder;
use App\Eocs\EocsOrderItem;
use App\Eocs\OrderLookup;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeMarkdown;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\KnowledgeSuggester;
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
        $result = $this->result($ticket, $resultId ?? $latestId);

        if ($result !== null) {
            $result['is_latest'] = $result['id'] === $latestId;
        }
        $inputs = $result['inputs'] ?? [];

        $choice = $this->store->caseChoice($ticket->number);
        [$group, $groupSource] = $this->suggestedGroup($ticket, $orders, $choice);

        if (is_string($inputs['kundengruppe'] ?? null)) {
            [$group, $groupSource] = [$inputs['kundengruppe'], 'wie in der angezeigten Analyse'];
        }

        $chosenGroup = $inputs['kundengruppe'] ?? $choice['group'] ?? null;

        return [
            'groups' => $this->selector->customerGroups(),
            'products' => $this->selector->products(),
            'suggestedGroup' => $group,
            'groupSource' => $groupSource,
            'chosenGroupLabel' => $chosenGroup === null ? null : collect($this->selector->customerGroups())->firstWhere('key', $chosenGroup)?->label,
            'suggestedProducts' => $inputs['produkte'] ?? $choice['products'] ?? $this->suggester->products(array_map(fn (EocsOrderItem $item): array => ['article_number' => $item->itemNumber, 'description' => $item->name], collect($orders)->flatMap(fn (EocsOrder $order): array => $order->items)->all())),
            'hasOrders' => $orders !== [],
            'variants' => $context->variants(),
            'defaultVariant' => ContextVariant::tryFrom((string) ($inputs['variante'] ?? '')) ?? $context->defaultVariant(),
            'inputs' => $inputs,
            'suggestsSummary' => $context->suggestsSummary(),
            'previews' => $previews,
            'stand' => $ticket->rewoundTo,
            'summary' => $summary,
            'summaryStale' => $summary?->isStale($context->earlierFingerprint()) ?? false,
            'summaryOffered' => $context->offersVariants(),
            'result' => $result,
            'history' => $this->history($ticket),
            'deletion' => $result === null ? $this->store->deletion($ticket->number) : null,
            'resultMissing' => $resultId !== null && $result === null,
        ];
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
        $known = array_map(fn (CustomerGroup $group): string => $group->key, $this->selector->customerGroups());

        if ($choice !== null && in_array($choice['group'], $known, true)) {
            return [$choice['group'], 'zuletzt gewählt von '.$choice['staff'].' am '.CarbonImmutable::parse($choice['at'])->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr'];
        }

        foreach ($orders as $order) {
            foreach ($this->selector->customerGroups() as $group) {
                if ($order->channel !== null && $group->salesChannel === $order->channel) {
                    return [$group->key, 'aus dem Kanal der Bestellung'];
                }
            }
        }

        $remembered = $this->store->customerGroup($ticket->customerKey());

        if ($remembered !== null && in_array($remembered, $known, true)) {
            return [$remembered, 'bei früheren Tickets dieses Kunden gewählt'];
        }

        return [$this->suggester->customerGroup(null)->key, null];
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

        $placeholders = Pseudonymizer::fromValues($stored['placeholders'] ?? []);
        $reply = (string) ($stored['result']['reply']['text'] ?? '');
        $original = $placeholders->restorePlain($reply);
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
            'sources' => $this->sources($stored),
            'new_messages' => $lastSeen !== null && $last !== null && $last->id !== $lastSeen,
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

        return array_map(function (string $id) use ($snapshots, $drafts): array {
            $snapshot = $snapshots->get($id);
            $current = $this->library->find($id);

            return [
                'id' => $id,
                'title' => $snapshot['title'] ?? $current?->title,
                'type' => $snapshot['type'] ?? $current?->type,
                'draft' => $snapshot['draft'] ?? in_array($id, $drafts, true),
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
