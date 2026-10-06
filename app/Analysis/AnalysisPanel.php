<?php

namespace App\Analysis;

use App\Eocs\EocsOrder;
use App\Eocs\EocsOrderItem;
use App\Eocs\OrderLookup;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\KnowledgeSuggester;
use App\Zammad\Ticket;
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
    ) {}

    /**
     * @param  list<OrderLookup>  $lookups
     * @return array<string, mixed>
     */
    public function build(Ticket $ticket, array $lookups, ?string $resultId): array
    {
        $orders = collect($lookups)->flatMap(fn (OrderLookup $lookup): array => $lookup->orders)->values()->all();
        $context = new TicketContext($ticket);
        $summary = $this->store->summary($ticket->number);
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

        return [
            'groups' => $this->selector->customerGroups(),
            'products' => $this->selector->products(),
            'suggestedGroup' => $this->suggestedGroup($orders)->key,
            'suggestedProducts' => $this->suggester->products(array_map(fn (EocsOrderItem $item): array => ['article_number' => $item->itemNumber, 'description' => $item->name], collect($orders)->flatMap(fn (EocsOrder $order): array => $order->items)->all())),
            'hasOrders' => $orders !== [],
            'variants' => $context->variants(),
            'defaultVariant' => $context->defaultVariant(),
            'suggestsSummary' => $context->suggestsSummary(),
            'previews' => $previews,
            'summary' => $summary,
            'summaryStale' => $summary?->isStale($context->earlierFingerprint()) ?? false,
            'summaryOffered' => $context->offersVariants(),
            'result' => $resultId === null ? null : $this->result($ticket, $resultId),
        ];
    }

    /**
     * @param  list<EocsOrder>  $orders
     */
    private function suggestedGroup(array $orders): CustomerGroup
    {
        foreach ($orders as $order) {
            foreach ($this->selector->customerGroups() as $group) {
                if ($order->channel !== null && $group->salesChannel === $order->channel) {
                    return $group;
                }
            }
        }

        return $this->suggester->customerGroup(null);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function result(Ticket $ticket, string $id): ?array
    {
        $stored = $this->store->result($id);

        if ($stored === null || ($stored['ticket'] ?? null) !== $ticket->number) {
            return null;
        }

        $placeholders = Pseudonymizer::fromValues($stored['placeholders'] ?? []);

        return [
            ...$stored,
            'reply_html' => $placeholders->restore((string) ($stored['result']['reply']['text'] ?? '')),
            'reply_plain' => $placeholders->restorePlain((string) ($stored['result']['reply']['text'] ?? '')),
        ];
    }

    public static function text(?string $value): HtmlString
    {
        return new HtmlString(nl2br(e((string) $value), false));
    }
}
