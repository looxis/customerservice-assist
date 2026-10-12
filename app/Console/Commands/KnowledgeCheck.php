<?php

namespace App\Console\Commands;

use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeIssue;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\Retrieval\MandatoryScope;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('knowledge:check {--strict : Auch Warnungen als Fehlschlag werten}')]
#[Description('Prüft die Knowledge-Dateien und listet Fehler und Warnungen je Datei')]
class KnowledgeCheck extends Command
{
    public function handle(KnowledgeLibrary $library, MandatoryScope $mandatory): int
    {
        $documents = $library->all();
        $errors = $library->errors();
        $warnings = $library->warnings();

        $this->line("Knowledge Base: {$documents->count()} Dokumente, {$library->usable()->count()} verwendbar");
        $this->line("Wissensstand: {$library->state()->label()}");

        $documents
            ->filter(fn (KnowledgeDocument $document): bool => $document->type !== null)
            ->groupBy(fn (KnowledgeDocument $document): string => $document->type)
            ->each(function ($group, string $type): void {
                $statuses = $group
                    ->countBy(fn (KnowledgeDocument $document): string => $document->status ?? 'ohne Status')
                    ->map(fn (int $count, string $status): string => "{$count} {$status}")
                    ->implode(', ');

                $this->line("  {$type}: {$group->count()} ({$statuses})");
            });

        $library->issues()
            ->sortBy(fn (KnowledgeIssue $issue): string => $issue->path)
            ->groupBy(fn (KnowledgeIssue $issue): string => $issue->path)
            ->each(function ($issues, string $path) use ($library): void {
                $this->newLine();
                $this->line($path === '.' ? 'Knowledge Base' : $path);

                foreach ($library->issuesFor($path) as $issue) {
                    $text = "  {$issue->severity->label()}: {$issue->message}";

                    $issue->isError() ? $this->error($text) : $this->warn($text);
                }
            });

        $unclassified = $mandatory->unclassified();
        $violations = $mandatory->violations();

        if ($unclassified->isNotEmpty()) {
            $this->newLine();
            $this->line('Noch nicht klassifiziert (retrieval fehlt): '.$unclassified->count().' '.($unclassified->count() === 1 ? 'Dokument' : 'Dokumente').', '.number_format($unclassified->sum(fn (KnowledgeDocument $document): int => $document->length()), 0, ',', '.').' Zeichen; sie zählen vorläufig als Grundwissen.');
            $this->line('  '.$unclassified->map(fn (KnowledgeDocument $document): string => (string) $document->id)->implode(', '));
        }

        if ($violations !== []) {
            $this->newLine();
            $this->line('Pflichtumfang');

            foreach ($violations as $violation) {
                $this->error("  Fehler: {$violation}");
            }
        }

        $this->newLine();

        if ($errors->isEmpty() && $warnings->isEmpty() && $violations === []) {
            $this->info('Keine Fehler, keine Warnungen.');

            return self::SUCCESS;
        }

        $errorCount = $errors->count() + count($violations);

        $this->line("{$errorCount} Fehler, {$warnings->count()} ".($warnings->count() === 1 ? 'Warnung' : 'Warnungen'));

        if ($errors->isNotEmpty()) {
            $this->line('Dokumente mit Fehlern werden von der App nicht verwendet.');
        }

        return $errorCount > 0 || $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }
}
