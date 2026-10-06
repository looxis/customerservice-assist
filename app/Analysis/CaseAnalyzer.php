<?php

namespace App\Analysis;

use App\Analysis\Agents\CaseAgent;
use App\Eocs\EocsOrder;
use App\Knowledge\CaseContext;
use App\Knowledge\KnowledgeSelection;
use App\Knowledge\KnowledgeSelectionEntry;
use App\Knowledge\KnowledgeSelector;
use Carbon\CarbonImmutable;

/**
 * Stage 2: puts together ticket context, order data, employee context and
 * the selected knowledge, asks the language model for the fixed result
 * structure, checks the answer and keeps it for display.
 */
class CaseAnalyzer
{
    public function __construct(
        private readonly LanguageModel $model,
        private readonly KnowledgeSelector $selector,
        private readonly ResultValidator $validator,
        private readonly AnalysisStore $store,
    ) {}

    /**
     * @return string Id of the stored result.
     *
     * @throws AnalysisException
     */
    public function analyze(AnalysisRequest $request): string
    {
        $prompt = Prompt::load('analysis');
        $selection = $this->selector->select(new CaseContext($request->customerGroup, $request->products));
        $pseudonymizer = app(Pseudonymizer::class);

        foreach ($request->orders as $order) {
            if ($order->deliveryAddress !== [] && ! $pseudonymizer->hasDeliveryAddress()) {
                $pseudonymizer->withDeliveryAddress($order->deliveryAddress);
            }
        }

        $input = $pseudonymizer->apply($this->input($request, $selection, $pseudonymizer->hasDeliveryAddress()));
        $knowledgeIds = array_map(fn (KnowledgeSelectionEntry $entry): string => $entry->document->id, $selection->selected);
        $agent = new CaseAgent($prompt->text, config('knowledge.categories'), array_keys(config('knowledge.actions')), $knowledgeIds);
        $model = (string) config('analysis.models.analysis');

        $attempts = 0;

        do {
            $attempts++;

            try {
                $answer = $this->model->ask($agent, $input, $model, 'analysis', $request->ticket->number);
                $checked = $this->validator->check($answer['data'], $knowledgeIds);
            } catch (AnalysisException $exception) {
                if ($exception->problem !== AnalysisProblem::InvalidResult || $attempts >= 2) {
                    throw $exception;
                }

                $checked = null;
            }
        } while ($checked === null);

        $cited = array_flip($checked['result']['knowledge_ids']);
        $last = $request->ticket->articles === [] ? null : $request->ticket->articles[array_key_last($request->ticket->articles)];

        $id = $this->store->putResult([
            'ticket' => $request->ticket->number,
            'sources' => array_values(array_map(fn (KnowledgeSelectionEntry $entry): array => [
                'id' => $entry->document->id,
                'title' => $entry->document->title,
                'type' => $entry->document->type,
                'draft' => $entry->isDraft(),
                'path' => $entry->document->path,
                'body' => $entry->document->body,
                'fingerprint' => $entry->document->fingerprint,
            ], array_filter($selection->selected, fn (KnowledgeSelectionEntry $entry): bool => isset($cited[$entry->document->id])))),
            'thread' => ['last_article_id' => $last?->id, 'last_article_at' => $last?->createdAt->toIso8601String(), 'count' => count($request->ticket->articles)],
            'inputs' => $request->formInput,
            'reply_edit' => null,
            'result' => $checked['result'],
            'notes' => $checked['notes'],
            'placeholders' => $pseudonymizer->values(),
            'draft_ids' => array_values(array_map(fn (KnowledgeSelectionEntry $entry): string => $entry->document->id, array_filter($selection->selected, fn (KnowledgeSelectionEntry $entry): bool => $entry->isDraft()))),
            'meta' => [
                'staff' => $request->staffName,
                'test_until' => $request->ticket->rewoundAt()?->toIso8601String(),
                'created_at' => CarbonImmutable::now()->toIso8601String(),
                'provider' => config('analysis.provider'),
                'model' => $model,
                'prompt_version' => $prompt->version,
                'summary_prompt_version' => $request->variant === ContextVariant::LastWithSummary ? $request->summary?->promptVersion : null,
                'knowledge_state' => $selection->state->label(),
                'knowledge_fingerprints' => $selection->fingerprints(),
                'variant' => $request->variant->value,
                'customer_group' => $request->customerGroup->key,
                'products' => $request->products,
                'orders' => array_map(fn (EocsOrder $order): string => $order->externalNumber, $request->orders),
                'duration_ms' => $answer['duration_ms'],
                'input_tokens' => $answer['input_tokens'],
                'output_tokens' => $answer['output_tokens'],
                'attempts' => $attempts,
                'knowledge_warnings' => $selection->warnings,
            ],
        ]);

        $this->store->putLatest($request->ticket->summaryKey(), $id);

        return $id;
    }

    private function input(AnalysisRequest $request, KnowledgeSelection $selection, bool $hasDeliveryAddress): string
    {
        $group = $request->customerGroup;
        $sections = [];

        $sections[] = "# Fall\n"
            ."Kundengruppe: {$group->label}".($group->isUnclear() ? ' (Kundenart und Kanal noch nicht geklärt)' : " (Kundenart {$group->customerType}, Kanal {$group->salesChannel})")."\n"
            .'Produkte: '.($request->products === [] ? 'kein Produktbezug / unklar' : implode(', ', $request->products));

        $sections[] = "# Bestellungen\n".$this->orders($request, $hasDeliveryAddress);

        $sections[] = "# Zusätzliche Informationen vom Mitarbeiter (geprüfte Fakten)\n".(trim($request->employeeContext) === '' ? '–' : trim($request->employeeContext));

        $sections[] = "# Ticket\n".(new TicketContext($request->ticket))->text($request->variant, $request->summary?->text);

        $sections[] = "# Wissen\n".($selection->isEmpty()
            ? 'Für diesen Fall ist kein Wissen hinterlegt.'
            : implode("\n\n", array_map(fn (KnowledgeSelectionEntry $entry): string => '## '.$entry->document->id.' – '.$entry->document->title
                .' ('.config("knowledge.types.{$entry->document->type}.label").($entry->isDraft() ? ', Entwurf' : '').")\n".$entry->document->body, $selection->selected)));

        $sections[] = "# Vorgaben\n"
            .'Kategorien: '.implode(', ', config('knowledge.categories'))."\n"
            .'Vorgänge: '.implode(', ', array_map(fn (string $key, string $label): string => "{$key} ({$label})", array_keys(config('knowledge.actions')), config('knowledge.actions')));

        return implode("\n\n", $sections);
    }

    private function orders(AnalysisRequest $request, bool $hasDeliveryAddress): string
    {
        $lines = [];

        foreach ($request->orders as $order) {
            $lines[] = "## Bestellung {$order->externalNumber} (aus EOCS)";
            $lines[] = 'Kanal: '.($order->channelName ?? 'unbekannt').' · bestellt: '.($order->orderedAt?->format('d.m.Y') ?? '–').' · Status: '.($order->statusName ?? '–');

            foreach ($order->shipments as $shipment) {
                $lines[] = 'Versand: '.($shipment->carrier ?? '–').($shipment->trackingNumber ? ", Sendungsnummer {$shipment->trackingNumber}" : '')
                    .($shipment->shippedAt ? ', versandt am '.$shipment->shippedAt->format('d.m.Y') : '').($shipment->delivered ? ', zugestellt' : '');
            }

            if ($order->shipments === []) {
                $lines[] = 'Versand: noch nicht versandt';
            }

            foreach ($order->items as $item) {
                $lines[] = "- {$item->quantity} × {$item->name}".($item->itemNumber ? " (Art.-Nr. {$item->itemNumber})" : '').($item->status ? ", {$item->status}" : '')
                    .($item->configurationCode ? ", Konfigurations-ID {$item->configurationCode}" : '')
                    .($item->customization !== [] ? ', Personalisierung: '.json_encode($item->customization, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '');
            }

            foreach ($order->claims as $claim) {
                $lines[] = "Reklamationsauftrag {$claim->externalNumber}".($claim->orderedAt ? ' vom '.$claim->orderedAt->format('d.m.Y') : '').', Status: '.($claim->statusName ?? '–');
            }
        }

        $manual = array_filter($request->manualOrder, fn (string $value): bool => trim($value) !== '');

        if ($manual !== []) {
            $lines[] = '## Bestelldaten vom Mitarbeiter eingetragen';

            foreach ($manual as $label => $value) {
                $lines[] = "{$label}: ".trim($value);
            }
        }

        if ($hasDeliveryAddress) {
            $lines[] = 'Die Lieferadresse ist der App bekannt und kann im Antwortentwurf als '.Pseudonymizer::DELIVERY_ADDRESS.' verwendet werden.';
        }

        return $lines === [] ? 'Keine Bestellung geladen.' : implode("\n", $lines);
    }
}
