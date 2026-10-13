<?php

namespace App\Knowledge\Retrieval\Router;

use App\Analysis\Agents\RouterAgent;
use App\Analysis\AnalysisException;
use App\Analysis\AnalysisProblem;
use App\Analysis\LanguageModel;
use App\Analysis\Prompt;
use App\Analysis\Pseudonymizer;
use App\Analysis\TicketContext;
use App\Knowledge\CaseContext;
use App\Knowledge\Retrieval\CaseFacts;
use App\Knowledge\Retrieval\RetrievalResult;
use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use Throwable;

/**
 * The router of the knowledge retrieval (PROJ-37). After the deterministic retrieval it asks a cheap model which
 * further documents of a short catalogue the customer's request needs, in case the triggers missed a different
 * wording. It only ever adds, only from the pool, and every proposal is checked here. Whatever goes wrong, the
 * deterministic result stays as it is: the outcome then says why the router did not help.
 */
class KnowledgeRouter
{
    public function __construct(
        private readonly LanguageModel $model,
        private readonly RouterPool $pool,
        private readonly CandidatePreselector $preselector,
        private readonly ContentlessMessage $contentless,
        private readonly EvidenceCheck $evidence,
    ) {}

    /**
     * @param  list<string>  $manualIds
     */
    public function run(CaseContext $context, CaseFacts $facts, RetrievalResult $first, array $manualIds, Ticket $ticket, Pseudonymizer $pseudonymizer, string $employeeContext = ''): RouterOutcome
    {
        $settings = (array) config('knowledge.router');

        if (! ($settings['active'] ?? false) || ! config('knowledge.retrieval.active')) {
            return RouterOutcome::disabled('Router ausgeschaltet');
        }

        $base = ['model' => (string) $settings['model'], 'version' => (string) $settings['version']];
        $last = $this->lastCustomerMessage($ticket);

        if ($last !== null && $this->contentless->isContentless(TicketContext::plainText($last), $last->attachments !== [], $employeeContext)) {
            return new RouterOutcome(RouterOutcome::SKIPPED, 'ohne Sachinhalt (reine Höflichkeits- oder Bestätigungsnachricht)', details: $base);
        }

        $pool = $this->pool->build($first);

        if ($pool === []) {
            return new RouterOutcome(RouterOutcome::SKIPPED, 'kein zusätzliches Dokument im Geltungsbereich', details: [...$base, 'pool' => [], 'pool_total' => 0]);
        }

        $selection = $this->preselector->select($pool, $facts->tiers, $context);
        $presented = $selection['selected'];
        $text = RouterText::build($facts, $pseudonymizer, (int) $settings['text_characters']);
        $details = [
            ...$base,
            'pool_total' => count($pool),
            'pool' => array_map(fn (PoolEntry $entry): string => $entry->id(), $pool),
            'presented' => array_map(fn (PoolEntry $entry): string => $entry->id(), $presented),
            'preselected' => $selection['preselected'],
            'left_out' => $selection['left_out'],
            'cut' => $text->cut,
            'text_characters' => mb_strlen($text->text),
        ];

        if ($presented === [] || trim($text->text) === '') {
            return new RouterOutcome(RouterOutcome::SKIPPED, $presented === [] ? 'kein Dokument in der Vorauswahl' : 'kein Kundentext', details: $details);
        }

        $prompt = Prompt::load('router');
        $details['prompt_version'] = $prompt->version;
        $agent = new RouterAgent($prompt->text, config('knowledge.categories'));

        try {
            $answer = $this->model->ask($agent, $this->message($context, $text, $first, $manualIds, $presented), (string) $settings['model'], 'router', $ticket->number, (int) $settings['timeout']);
        } catch (AnalysisException $exception) {
            return new RouterOutcome(RouterOutcome::FAILED, $this->failure($exception), details: $details);
        } catch (Throwable) {
            return new RouterOutcome(RouterOutcome::FAILED, 'unerwarteter Fehler', details: $details);
        }

        $checked = $this->check($answer['data'], $presented, $first, $manualIds, $text, $pseudonymizer, (int) $settings['max_additions']);

        return new RouterOutcome(
            RouterOutcome::EXECUTED,
            count($checked['additions']) === 0 ? 'keine Ergänzung nötig' : count($checked['additions']).' Ergänzung(en) übernommen',
            $checked['additions'],
            [...$details, 'proposals' => $checked['proposals'], 'categories' => $checked['categories']],
            (int) ($answer['input_tokens'] ?? 0),
            (int) ($answer['output_tokens'] ?? 0),
            (int) ($answer['duration_ms'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<PoolEntry>  $presented
     * @param  list<string>  $manualIds
     * @return array{additions: list<RouterAddition>, proposals: list<array<string, mixed>>, categories: list<array{key: string, reason: string}>}
     */
    private function check(array $data, array $presented, RetrievalResult $first, array $manualIds, RouterText $text, Pseudonymizer $pseudonymizer, int $max): array
    {
        $entries = [];

        foreach ($presented as $entry) {
            $entries[$entry->id()] = $entry;
        }

        $taken = array_flip([...$first->ids(), ...$manualIds]);
        $proposals = [];
        $seen = [];

        foreach ((array) ($data['additions'] ?? []) as $position => $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = strtoupper(trim((string) ($item['id'] ?? '')));
            $basis = ($item['basis'] ?? '') === 'earlier' ? 'earlier' : 'current';
            $proposal = [
                'id' => $id,
                'rank' => (int) ($item['rank'] ?? $position + 1),
                'basis' => $basis,
                'fit' => ($item['fit'] ?? '') === 'clear' ? 'clear' : 'possible',
                'reason' => mb_substr(trim((string) ($item['reason'] ?? '')), 0, 400),
                'evidence' => mb_substr(trim((string) ($item['evidence'] ?? '')), 0, 600),
                'accepted' => false,
                'rejection' => null,
            ];
            $proposal['rejection'] = match (true) {
                isset($seen[$id]) => 'Dublette',
                isset($taken[$id]) => 'bereits ausgewählt',
                $proposal['fit'] !== 'clear' && (bool) config('knowledge.router.clear_only', true) && isset($entries[$id]) => 'nur möglicher Bezug (nicht eindeutig)',
                ! isset($entries[$id]) => 'nicht in der Liste des Routers',
                default => $this->evidenceRejection($entries[$id], $proposal['evidence'], $text, $pseudonymizer),
            };
            $seen[$id] = true;
            $proposals[] = $proposal;
        }

        usort($proposals, fn (array $a, array $b): int => $a['rank'] <=> $b['rank']);
        $additions = [];

        foreach ($proposals as &$proposal) {
            if ($proposal['rejection'] !== null) {
                continue;
            }

            if (count($additions) >= $max) {
                $proposal['rejection'] = 'wegen Obergrenze nicht übernommen';

                continue;
            }

            $proposal['accepted'] = true;
            $additions[] = new RouterAddition($entries[$proposal['id']]->document, $proposal['basis'] === 'current' ? RouterAddition::STRONG : RouterAddition::WEAK, $proposal['rank'], $proposal['reason'] !== '' ? $proposal['reason'] : 'sinngemäß passend zur Kundenbeschreibung');
        }

        unset($proposal);

        $known = (array) config('knowledge.categories');
        $categories = [];

        foreach ((array) ($data['categories'] ?? []) as $item) {
            $key = is_array($item) ? (string) ($item['key'] ?? '') : '';

            if (in_array($key, $known, true) && ! isset($categories[$key])) {
                $categories[$key] = ['key' => $key, 'reason' => mb_substr(trim((string) ($item['reason'] ?? '')), 0, 300)];
            }
        }

        return ['additions' => $additions, 'proposals' => $proposals, 'categories' => array_values($categories)];
    }

    private function evidenceRejection(PoolEntry $entry, string $evidence, RouterText $text, Pseudonymizer $pseudonymizer): ?string
    {
        if ($entry->suppressed === []) {
            return null;
        }

        $sentences = array_map(fn (array $item): string => $pseudonymizer->apply($item['sentence']), $entry->suppressed);

        return $this->evidence->check($evidence, $sentences, $text->segments);
    }

    /**
     * @param  list<string>  $manualIds
     * @param  list<PoolEntry>  $presented
     */
    private function message(CaseContext $context, RouterText $text, RetrievalResult $first, array $manualIds, array $presented): string
    {
        $group = $context->customerGroup;
        $selected = array_map(fn ($item): string => '['.$item->document->id.'] '.$item->document->title, $first->retrieved);

        return implode("\n\n", [
            "# Fall\nKundengruppe: {$group->label}\nProdukte: ".($context->products === [] ? 'kein Produktbezug / unklar' : implode(', ', $context->products)),
            "# Kundenanfrage\n".$text->text,
            "# Bereits ausgewählt\n".($selected === [] ? '–' : implode("\n", $selected)),
            "# Liste der Dokumente\n".implode("\n", array_map(fn (PoolEntry $entry): string => $entry->line(), $presented)),
            '# Fallarten (Liste)'."\n".implode(', ', (array) config('knowledge.categories')),
        ]);
    }

    private function failure(AnalysisException $exception): string
    {
        return match ($exception->problem) {
            AnalysisProblem::Misconfigured => 'Zugang oder Konfiguration',
            AnalysisProblem::InvalidResult => 'ungültige Antwort',
            default => str_contains($exception->getMessage(), 'Connection') ? 'Zeitüberschreitung oder Verbindungsfehler' : 'API-Fehler',
        };
    }

    private function lastCustomerMessage(Ticket $ticket): ?TicketArticle
    {
        $customer = array_values(array_filter($ticket->articles, fn (TicketArticle $article): bool => $article->kind === ArticleKind::Customer));

        return $customer === [] ? null : $customer[array_key_last($customer)];
    }
}
