<?php

namespace App\Console\Commands;

use App\Knowledge\CaseContext;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelectionEntry;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\Retrieval\CaseFactsBuilder;
use App\Knowledge\Retrieval\KnowledgeRetriever;
use App\Knowledge\Retrieval\KnowledgeTooLarge;
use App\Knowledge\Retrieval\RetrievedDocument;
use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\HtmlString;

#[Signature('knowledge:select {group : Kundengruppe, z. B. private-looxis-de} {--product=* : Produkt-Slug, mehrfach möglich} {--text= : Kundentext; zeigt den gezielten Abruf (PROJ-36) statt der reinen Auswahl} {--title= : Betreff des Tickets für den Abruf}')]
#[Description('Zeigt, welche Knowledge-Dokumente für Kundengruppe und Produkte ausgewählt würden und welche aus welchem Grund nicht')]
class KnowledgeSelect extends Command
{
    public function handle(KnowledgeSelector $selector, KnowledgeRetriever $retriever, CaseFactsBuilder $facts): int
    {
        $group = $selector->customerGroup($this->argument('group'));

        if ($group === null) {
            $this->error("Unbekannte Kundengruppe `{$this->argument('group')}`.");
            $this->line('Erlaubt: '.implode(', ', array_map(fn (CustomerGroup $group): string => "{$group->key} ({$group->label})", $selector->customerGroups())));

            return self::FAILURE;
        }

        $products = array_values(array_filter(array_map('trim', $this->option('product')), fn (string $product): bool => $product !== ''));
        $allowedProducts = array_column($selector->products(), 'slug');
        $unknownProducts = array_values(array_diff($products, $allowedProducts));

        if ($unknownProducts !== []) {
            $this->error('Unbekanntes Produkt: '.implode(', ', $unknownProducts).'.');
            $this->line('Erlaubt: '.($allowedProducts === [] ? 'keine (es gibt noch keine verwendbare Produktdatei)' : implode(', ', $allowedProducts)));

            return self::FAILURE;
        }

        if ($this->option('text') !== null) {
            return $this->retrieval($retriever, $facts, $selector, new CaseContext($group, $products));
        }

        $selection = $selector->select(new CaseContext($group, $products));

        $this->line("Fallkontext: {$selection->context->label()}");
        $this->line("Wissensstand: {$selection->state->label()}");

        $this->newLine();
        $this->line('Ausgewählt: '.$this->documents(count($selection->selected)).', davon '.$selection->draftCount().' Entwurfs-Wissen');

        if (! $selection->isEmpty()) {
            $this->table(['ID', 'Titel', 'Status', 'Grund'], $this->rows($selection->selected));
        }

        $this->newLine();
        $this->line('Nicht ausgewählt: '.$this->documents(count($selection->excluded)));

        if ($selection->excluded !== []) {
            $this->table(['ID', 'Titel', 'Status', 'Grund'], $this->rows($selection->excluded));
        }

        $this->newLine();
        $this->line('Umfang: '.number_format($selection->characterCount(), 0, ',', '.').' von '.number_format($selection->characterLimit, 0, ',', '.').' Zeichen');

        foreach ($selection->warnings as $warning) {
            $this->warn("Warnung: {$warning}");
        }

        return self::SUCCESS;
    }

    /**
     * The targeted retrieval (PROJ-36) for a typed-in customer text, without orders.
     */
    private function retrieval(KnowledgeRetriever $retriever, CaseFactsBuilder $builder, KnowledgeSelector $selector, CaseContext $context): int
    {
        $article = new TicketArticle(ArticleKind::Customer, 'Kunde', CarbonImmutable::now(), new HtmlString(nl2br(e((string) $this->option('text')))));
        $ticket = new Ticket('0', 0, (string) $this->option('title'), 'open', false, null, null, null, null, CarbonImmutable::now(), [$article], '');
        $facts = $builder->build($ticket, $context->customerGroup, $context->products, []);

        try {
            $result = $retriever->retrieve($context, $facts);
        } catch (KnowledgeTooLarge $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line("Fallkontext: {$context->label()}");
        $this->line('Hinweis: Ohne Bestelldaten; Bedingungen mit Bestellschlüsseln sind unbekannt.');
        $this->newLine();
        $this->line('Abgerufen: '.$this->documents(count($result->retrieved)));

        $this->table(['ID', 'Titel', 'Klasse', 'Stärke', 'Grund'], array_map(fn (RetrievedDocument $item): array => [
            $item->document->id,
            $item->document->title,
            $item->class,
            $item->strength,
            $item->reason(),
        ], $result->retrieved));

        $this->newLine();
        $this->line('Geprüft, nicht abgerufen: '.$this->documents(count($result->notRetrieved)));

        if ($result->notRetrieved !== []) {
            $this->table(['ID', 'Titel', 'Grund'], array_map(fn (array $item): array => [$item['document']->id, $item['document']->title, $item['detail']], $result->notRetrieved));
        }

        $this->newLine();
        $this->line('Umfang: '.number_format($result->characters(), 0, ',', '.').' Zeichen, davon Pflichtwissen '.number_format($result->mandatoryCharacters, 0, ',', '.').' (Ziel '.number_format($result->target, 0, ',', '.').')');

        foreach ($result->warnings as $warning) {
            $this->warn("Warnung: {$warning}");
        }

        return self::SUCCESS;
    }

    private function documents(int $count): string
    {
        return $count === 1 ? '1 Dokument' : "{$count} Dokumente";
    }

    /**
     * @param  list<KnowledgeSelectionEntry>  $entries
     * @return list<list<string>>
     */
    private function rows(array $entries): array
    {
        return array_map(fn (KnowledgeSelectionEntry $entry): array => [
            $entry->document->id,
            $entry->document->title,
            $entry->document->status,
            $entry->reason,
        ], $entries);
    }
}
