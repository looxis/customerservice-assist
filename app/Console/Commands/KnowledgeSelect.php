<?php

namespace App\Console\Commands;

use App\Knowledge\CaseContext;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelectionEntry;
use App\Knowledge\KnowledgeSelector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('knowledge:select {group : Kundengruppe, z. B. private-looxis-de} {--product=* : Produkt-Slug, mehrfach möglich}')]
#[Description('Zeigt, welche Knowledge-Dokumente für Kundengruppe und Produkte ausgewählt würden und welche aus welchem Grund nicht')]
class KnowledgeSelect extends Command
{
    public function handle(KnowledgeSelector $selector): int
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
