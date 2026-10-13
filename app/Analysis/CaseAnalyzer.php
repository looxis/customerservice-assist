<?php

namespace App\Analysis;

use App\Analysis\Agents\CaseAgent;
use App\Eocs\EocsOrder;
use App\Knowledge\CaseContext;
use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeSelection;
use App\Knowledge\KnowledgeSelectionEntry;
use App\Knowledge\KnowledgeSelector;
use App\Knowledge\Retrieval\CaseFactsBuilder;
use App\Knowledge\Retrieval\Evaluation;
use App\Knowledge\Retrieval\KnowledgeRetriever;
use App\Knowledge\Retrieval\KnowledgeTooLarge;
use App\Knowledge\Retrieval\RetrievalResult;
use App\Knowledge\Retrieval\RetrievedDocument;
use App\Knowledge\Retrieval\Router\KnowledgeRouter;
use App\Knowledge\Retrieval\Router\RouterOutcome;
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
        private readonly CaseFactsBuilder $facts,
        private readonly KnowledgeRetriever $retriever,
        private readonly Translator $translator,
        private readonly KnowledgeRouter $router,
    ) {}

    /**
     * @return string Id of the stored result.
     *
     * @throws AnalysisException
     */
    public function analyze(AnalysisRequest $request): string
    {
        $prompt = Prompt::load('analysis');
        $context = new CaseContext($request->customerGroup, $request->products);
        $selection = $this->selector->select($context);
        $pseudonymizer = app(Pseudonymizer::class);

        foreach ($request->orders as $order) {
            if ($order->deliveryAddress !== [] && ! $pseudonymizer->hasDeliveryAddress()) {
                $pseudonymizer->withDeliveryAddress($order->deliveryAddress);
            }
        }

        $model = (string) config('analysis.models.analysis');
        $active = (bool) config('knowledge.retrieval.active');

        if ($active) {
            $rules = Prompt::load('analysis-retrieval');
            $prompt = new Prompt($prompt->name, $prompt->version.'+'.$rules->version, $prompt->text."\n\n".$rules->text);
        }

        if ($request->isQuestion()) {
            $questionRules = Prompt::load('analysis-question');
            $prompt = new Prompt($prompt->name, $prompt->version.'+'.$questionRules->version, $prompt->text."\n\n".$questionRules->text);
        }

        $parts = $this->parts($request, $pseudonymizer->hasDeliveryAddress(), $active);
        $started = hrtime(true);
        $retrieval = null;
        $hints = [];
        $router = null;

        if ($active) {
            try {
                [$selection, $retrieval, $hints, $router] = $this->retrieve($request, $context, $selection, $prompt, $parts, $pseudonymizer);
            } catch (KnowledgeTooLarge) {
                $this->store->putFailure($request->ticket->number, $request->ticket->summaryKey(), $request->staffName, $model, AnalysisProblem::KnowledgeTooLarge->name, intdiv(hrtime(true) - $started, 1_000_000));

                throw new AnalysisException(AnalysisProblem::KnowledgeTooLarge);
            }
        }

        $totals = ['attempts' => 0, 'duration' => 0, 'input' => 0, 'output' => 0];
        $pass = $this->pass($request, $prompt, $parts, $selection, $hints, $pseudonymizer, $model, $totals, $started, true);
        $safeguard = null;
        $notes = [...$request->notes];

        if ($retrieval !== null) {
            [$pass, $selection, $hints, $safeguard] = $this->safeguard($request, $prompt, $parts, $selection, $hints, $retrieval, $pass, $pseudonymizer, $model, $totals, $started);
            $notes = [...$notes, ...$this->retrievalNotes($retrieval, $selection, $hints, $safeguard)];
        }

        $ruleCheck = $safeguard === null ? null : ($safeguard['failed'] || $safeguard['not_considered'] !== [] ? 'incomplete' : 'complete');

        $answer = $pass['answer'];
        $checked = $pass['checked'];
        $input = $pass['input'];
        $cited = array_flip([...$checked['result']['knowledge_ids'], ...($checked['result']['decision']['knowledge_ids'] ?? [])]);
        $last = $request->ticket->articles === [] ? null : $request->ticket->articles[array_key_last($request->ticket->articles)];

        $id = $this->store->putResult([
            'ticket' => $request->ticket->number,
            'scope' => $request->ticket->summaryKey(),
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
            ...($request->isQuestion() ? ['question' => ['text' => $request->questionText, 'reply_wanted' => $request->replyWanted]] : []),
            'decisions' => $request->decisions,
            'reply_edit' => null,
            'sent_input' => $input,
            'raw_response' => $answer['data'],
            'result' => $checked['result'],
            'notes' => [...$notes, ...$checked['notes']],
            'placeholders' => $pseudonymizer->values(),
            'draft_ids' => array_values(array_map(fn (KnowledgeSelectionEntry $entry): string => $entry->document->id, array_filter($selection->selected, fn (KnowledgeSelectionEntry $entry): bool => $entry->isDraft()))),
            ...($retrieval === null ? [] : ['retrieval' => $this->record($retrieval, $selection, $hints, $safeguard, $request->extraKnowledge, $ruleCheck, $router)]),
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
                'knowledge_characters' => $selection->characterCount(),
                'documents_retrieved' => count($selection->selected),
                'documents_omitted' => $retrieval === null ? 0 : count($retrieval->omitted()),
                'passes' => $safeguard !== null && $safeguard['loaded'] !== [] ? 2 : 1,
                'retrieval_version' => $retrieval === null ? null : KnowledgeRetriever::configurationVersion(),
                'rule_check' => $ruleCheck,
                ...$this->routerFigures($retrieval, $router),
                'variant' => $request->variant->value,
                'customer_group' => $request->customerGroup->key,
                'products' => $request->products,
                'orders' => array_map(fn (EocsOrder $order): string => $order->externalNumber, $request->orders),
                'duration_ms' => $totals['duration'],
                'input_tokens' => $totals['input'],
                'output_tokens' => $totals['output'],
                'attempts' => $totals['attempts'],
                'knowledge_warnings' => $selection->warnings,
            ],
        ]);

        return $id;
    }

    /**
     * One round with the language model, including the one repeat for an unusable answer.
     *
     * @param  array{case: string, orders: string, employee: string, ticket: string, rules: string}  $parts
     * @param  array<string, RetrievedDocument>  $hints
     * @param  array{attempts: int, duration: int, input: int, output: int}  $totals
     * @return array{answer: array<string, mixed>, checked: array<string, mixed>, input: string}
     *
     * @throws AnalysisException
     */
    private function pass(AnalysisRequest $request, Prompt $prompt, array $parts, KnowledgeSelection $selection, array $hints, Pseudonymizer $pseudonymizer, string $model, array &$totals, int|float $started, bool $logFailure): array
    {
        $input = $pseudonymizer->apply($this->compose($parts, $this->knowledgeSection($selection, $hints)));
        $knowledgeIds = array_map(fn (KnowledgeSelectionEntry $entry): string => $entry->document->id, $selection->selected);
        $agent = new CaseAgent($prompt->text, config('knowledge.categories'), array_keys(config('knowledge.actions')), $knowledgeIds);
        $attempts = 0;

        do {
            $attempts++;
            $totals['attempts']++;

            try {
                $answer = $this->model->ask($agent, $input, $model, 'analysis', $request->ticket->number);
                $checked = $this->validator->check($answer['data'], $knowledgeIds, $request->replyWanted);
            } catch (AnalysisException $exception) {
                if ($exception->problem !== AnalysisProblem::InvalidResult || $attempts >= 2) {
                    if ($logFailure) {
                        $this->store->putFailure($request->ticket->number, $request->ticket->summaryKey(), $request->staffName, $model, $exception->problem->name, intdiv(hrtime(true) - $started, 1_000_000));
                    }

                    throw $exception;
                }

                $checked = null;
            }
        } while ($checked === null);

        $totals['duration'] += (int) ($answer['duration_ms'] ?? 0);
        $totals['input'] += (int) ($answer['input_tokens'] ?? 0);
        $totals['output'] += (int) ($answer['output_tokens'] ?? 0);

        return ['answer' => $answer, 'checked' => $checked, 'input' => $input];
    }

    /**
     * The targeted retrieval (PROJ-36): measure the prompt without knowledge, give the knowledge
     * the rest, pick the documents. Returns the selection in the shape the rest of the analysis
     * knows, the result of the retrieval and the hints for documents whose condition is unclear.
     *
     * @param  array{case: string, orders: string, employee: string, ticket: string, rules: string}  $parts
     * @return array{0: KnowledgeSelection, 1: RetrievalResult, 2: array<string, RetrievedDocument>, 3: RouterOutcome}
     *
     * @throws KnowledgeTooLarge
     */
    private function retrieve(AnalysisRequest $request, CaseContext $context, KnowledgeSelection $base, Prompt $prompt, array $parts, Pseudonymizer $pseudonymizer): array
    {
        $facts = $this->facts->build(
            $request->ticket,
            $request->customerGroup,
            $request->products,
            $request->orders,
            $request->mentionedOrders,
            $this->translator->translations($request->ticket),
            $request->variant === ContextVariant::LastWithSummary ? $request->summary?->text : null,
            employeeContext: $request->factsText(),
        );

        $without = mb_strlen($prompt->text) + mb_strlen($this->compose($parts, "# Wissen\n"));
        $available = (int) config('analysis.limits.context_characters') - (int) config('analysis.limits.answer_reserve_characters') - $without;
        $available = max(0, $available);
        $result = $this->retriever->retrieve($context, $facts, $request->extraKnowledge, $available);
        $router = $this->router->run($context, $facts, $result, $request->extraKnowledge, $request->ticket, $pseudonymizer, $request->factsText());

        if ($router->additions !== []) {
            $result = $this->retriever->retrieve($context, $facts, $request->extraKnowledge, $available, $router->additions);
        }

        $warnings = [...array_values(array_filter($base->warnings, fn (string $warning): bool => ! str_starts_with($warning, 'Der Umfang'))), ...$result->warnings];

        $selection = new KnowledgeSelection(
            context: $base->context,
            state: $base->state,
            selected: $result->entries(),
            excluded: [],
            unknownProducts: $base->unknownProducts,
            productsWithoutKnowledge: $base->productsWithoutKnowledge,
            characterLimit: $result->target,
            warnings: $warnings,
        );

        return [$selection, $result, $this->hints($result), $router];
    }

    /**
     * @return array<string, RetrievedDocument>
     */
    private function hints(RetrievalResult $result): array
    {
        $hints = [];

        foreach ($result->retrieved as $item) {
            if ($item->unverified || in_array('von Hand zugeschaltet', $item->reasons, true)) {
                $hints[(string) $item->document->id] = $item;
            }
        }

        return $hints;
    }

    /**
     * The safeguard for binding rules (PROJ-36): the analysis may recommend a measure the customer
     * did not ask for. The binding documents of that measure that are not in the prompt are added
     * and the analysis runs once more. If that is not possible, the warning says so; the first result stays.
     *
     * @param  array{case: string, orders: string, employee: string, ticket: string, rules: string}  $parts
     * @param  array<string, RetrievedDocument>  $hints
     * @param  array{answer: array<string, mixed>, checked: array<string, mixed>, input: string}  $pass
     * @param  array{attempts: int, duration: int, input: int, output: int}  $totals
     * @return array{0: array{answer: array<string, mixed>, checked: array<string, mixed>, input: string}, 1: KnowledgeSelection, 2: array<string, RetrievedDocument>, 3: array{first_actions: list<string>, loaded: list<string>, load_actions: list<string>, failed: bool, not_considered: list<string>}}
     */
    private function safeguard(AnalysisRequest $request, Prompt $prompt, array $parts, KnowledgeSelection $selection, array $hints, RetrievalResult $retrieval, array $pass, Pseudonymizer $pseudonymizer, string $model, array &$totals, int|float $started): array
    {
        $actions = array_values($pass['checked']['result']['actions'] ?? []);
        $record = ['first_actions' => $actions, 'loaded' => [], 'load_actions' => [], 'failed' => false, 'not_considered' => []];
        $missing = $retrieval->safeguardDocuments($actions);

        if ($missing === []) {
            return [$pass, $selection, $hints, $record];
        }

        $room = $this->room($retrieval, $selection);
        $added = [];

        foreach ($missing as $document) {
            if ($document->length() > $room) {
                continue;
            }

            $room -= $document->length();
            $added[(string) $document->id] = $document;
        }

        foreach ($missing as $document) {
            if (! isset($added[(string) $document->id])) {
                $record['not_considered'][] = (string) $document->id;
            }
        }

        if ($added === []) {
            $record['failed'] = true;

            return [$pass, $selection, $hints, $record];
        }

        $reasons = [];

        foreach ($added as $id => $document) {
            $for = array_values(array_intersect($document->actions(), $actions));
            $record['load_actions'] = array_values(array_unique([...$record['load_actions'], ...$for]));
            $reasons[$id] = 'nachgeladen wegen Maßnahme '.implode(', ', $for);
        }

        $entries = [...$selection->selected, ...array_map(fn (KnowledgeDocument $document): KnowledgeSelectionEntry => new KnowledgeSelectionEntry($document, $reasons[(string) $document->id]), array_values($added))];
        $order = array_flip(array_keys(config('knowledge.types')));
        usort($entries, fn (KnowledgeSelectionEntry $a, KnowledgeSelectionEntry $b): int => [$order[$a->document->type] ?? PHP_INT_MAX, $a->document->id] <=> [$order[$b->document->type] ?? PHP_INT_MAX, $b->document->id]);

        $second = new KnowledgeSelection($selection->context, $selection->state, $entries, $selection->excluded, $selection->unknownProducts, $selection->productsWithoutKnowledge, $selection->characterLimit, $selection->warnings);

        try {
            $next = $this->pass($request, $prompt, $parts, $second, $hints, $pseudonymizer, $model, $totals, $started, false);
        } catch (AnalysisException) {
            $record['failed'] = true;
            $record['not_considered'] = array_values(array_unique([...$record['not_considered'], ...array_keys($added)]));

            return [$pass, $selection, $hints, $record];
        }

        $record['loaded'] = array_keys($added);
        $further = array_values(array_filter(
            array_map(fn (KnowledgeDocument $document): string => (string) $document->id, $retrieval->safeguardDocuments($next['checked']['result']['actions'] ?? [])),
            fn (string $id): bool => ! isset($added[$id]),
        ));
        $record['not_considered'] = array_values(array_unique([...$record['not_considered'], ...$further]));

        return [$next, $second, $hints, $record];
    }

    /**
     * What is left for knowledge in the prompt after the documents already chosen.
     */
    private function room(RetrievalResult $retrieval, KnowledgeSelection $selection): int
    {
        return max(0, $retrieval->available - $selection->characterCount());
    }

    /**
     * Hints for the employee, shown with the result.
     *
     * @param  array<string, RetrievedDocument>  $hints
     * @param  array{first_actions: list<string>, loaded: list<string>, load_actions: list<string>, failed: bool, not_considered: list<string>}|null  $safeguard
     * @return list<string>
     */
    private function retrievalNotes(RetrievalResult $retrieval, KnowledgeSelection $selection, array $hints, ?array $safeguard): array
    {
        $notes = [];

        if ($safeguard !== null && ($safeguard['failed'] || $safeguard['not_considered'] !== [])) {
            $notes[] = 'Regelprüfung unvollständig: Der Entwurf ist ungeprüft und darf erst nach bewusster eigener Prüfung der verbindlichen Regeln übernommen werden.';
        }

        $unverified = array_keys(array_filter($hints, fn (RetrievedDocument $item): bool => $item->unverified));

        if ($unverified !== []) {
            $notes[] = 'Voraussetzung ungeklärt bei verbindlichem Wissen ('.implode(', ', $unverified).'): Die Regel wurde vorsorglich mitgegeben, die KI behandelt sie nicht als erfüllt.';
        }

        if ($retrieval->omitted() !== []) {
            $notes[] = 'Wegen Umfang nicht mitgesendet: '.implode(', ', array_map(fn (KnowledgeDocument $document): string => (string) $document->id, $retrieval->omitted())).'.';
        }

        if ($safeguard !== null && $safeguard['loaded'] !== []) {
            $notes[] = 'Nachgeladen wegen Maßnahme '.implode(', ', $safeguard['load_actions']).': '.implode(', ', $safeguard['loaded']).'. Empfehlung und Entwurf stammen aus dem zweiten Durchgang.';
        }

        if ($safeguard !== null && $safeguard['not_considered'] !== []) {
            $notes[] = 'Verbindliche Regeln zur empfohlenen Maßnahme wurden nicht berücksichtigt: '.implode(', ', $safeguard['not_considered']).'. Der Vorschlag ist nicht abgesichert.';
        }

        return $notes;
    }

    /**
     * The log of the retrieval, stored encrypted with the analysis.
     *
     * @param  array<string, RetrievedDocument>  $hints
     * @param  array{first_actions: list<string>, loaded: list<string>, load_actions: list<string>, failed: bool, not_considered: list<string>}|null  $safeguard
     * @param  list<string>  $manual
     * @return array<string, mixed>
     */
    private function record(RetrievalResult $retrieval, KnowledgeSelection $selection, array $hints, ?array $safeguard, array $manual, ?string $ruleCheck, ?RouterOutcome $router = null): array
    {
        $reasons = [];

        foreach ($retrieval->retrieved as $item) {
            $reasons[(string) $item->document->id] = $item;
        }

        return [
            'documents' => array_values(array_map(function (KnowledgeSelectionEntry $entry) use ($reasons): array {
                $item = $reasons[(string) $entry->document->id] ?? null;

                return [
                    'id' => $entry->document->id,
                    'title' => $entry->document->title,
                    'class' => $item?->class ?? $entry->document->retrieval(),
                    'strength' => $item?->strength ?? RetrievedDocument::MANDATORY,
                    'reasons' => $item === null ? [$entry->reason] : $item->reasons,
                    'unverified' => $item?->unverified ?? false,
                    'unknown_keys' => $item?->unknownKeys ?? [],
                    'origin' => $item?->origin ?? RetrievedDocument::ORIGIN_SAFEGUARD,
                ];
            }, $selection->selected)),
            'not_retrieved' => array_values(array_map(fn (array $item): array => ['id' => $item['document']->id, 'title' => $item['document']->title, 'code' => $item['code'], 'detail' => $item['detail']], $retrieval->notRetrieved)),
            'suppressed' => $retrieval->suppressed,
            'conditions' => array_map(fn (Evaluation $evaluation): array => ['result' => $evaluation->result->value, 'order' => $evaluation->order, 'order_unclear' => $evaluation->orderUnclear, 'checks' => $evaluation->checks], $retrieval->evaluations),
            'unclassified' => $retrieval->unclassified,
            'manual' => $manual,
            'safeguard' => $safeguard,
            'rule_check' => $ruleCheck,
            'router' => $router?->toArray(),
            'configuration' => ['version' => KnowledgeRetriever::configurationVersion(), 'budget' => config('knowledge.retrieval.budget'), 'limits' => config('analysis.limits')],
            'sizes' => ['knowledge' => $selection->characterCount(), 'mandatory' => $retrieval->mandatoryCharacters, 'target' => $retrieval->target, 'available' => $retrieval->available],
            'warnings' => $retrieval->warnings,
        ];
    }

    /**
     * The figures of the router for the analysis overview (PROJ-14); empty without the targeted retrieval.
     *
     * @return array<string, mixed>
     */
    private function routerFigures(?RetrievalResult $retrieval, ?RouterOutcome $router): array
    {
        if ($retrieval === null || $router === null) {
            return [];
        }

        $added = array_filter($retrieval->retrieved, fn (RetrievedDocument $item): bool => $item->origin === RetrievedDocument::ORIGIN_ROUTER);

        return [
            'router_status' => $router->status,
            'router_additions' => count($added),
            'router_characters' => array_sum(array_map(fn (RetrievedDocument $item): int => $item->document->length(), $added)),
            'router_duration_ms' => $router->durationMs,
            'router_input_tokens' => $router->inputTokens,
            'router_output_tokens' => $router->outputTokens,
        ];
    }

    /**
     * The sections of the prompt except the knowledge (PROJ-36 measures them before it chooses).
     *
     * @return array{case: string, orders: string, employee: string, ticket: string, rules: string}
     */
    private function parts(AnalysisRequest $request, bool $hasDeliveryAddress, bool $neutralShipping): array
    {
        $group = $request->customerGroup;

        return [
            'case' => "# Fall\n"
                ."Kundengruppe: {$group->label}".($group->isUnclear() ? ' (Kundenart und Kanal noch nicht geklärt)' : " (Kundenart {$group->customerType}, Kanal {$group->salesChannel})")."\n"
                .'Produkte: '.($request->products === [] ? 'kein Produktbezug / unklar' : implode(', ', $request->products)),
            'orders' => "# Bestellungen\n".$this->orders($request, $hasDeliveryAddress, $neutralShipping),
            'employee' => "# Zusätzliche Informationen vom Mitarbeiter (geprüfte Fakten)\n".(trim($request->employeeContext) === '' ? '–' : trim($request->employeeContext))
                .($request->decisions === [] ? '' : "\n\n# Interne Klärungen (geprüfte Fakten, bereits geklärt)\n".$request->decisionsText()),
            'ticket' => $request->isQuestion()
                ? "# Anfrage des Mitarbeiters (kein Ticket)\n".trim((string) $request->questionText)."\n\nAntwortentwurf an den Kunden: ".($request->replyWanted ? 'gewünscht' : 'nicht gewünscht')
                : "# Ticket\n".(new TicketContext($request->ticket))->text($request->variant, $request->summary?->text),
            'rules' => "# Vorgaben\n"
                .'Kategorien: '.implode(', ', array_map(fn (string $key): string => $key.' ('.config("knowledge.category_labels.{$key}", $key).')', config('knowledge.categories')))."\n"
                .'Vorgänge: '.implode(', ', array_map(fn (string $key, string $label): string => "{$key} ({$label})", array_keys(config('knowledge.actions')), config('knowledge.actions')))
                .$this->actionHints(),
        ];
    }

    /**
     * Which of the similar actions to name when; empty when no hints are configured.
     */
    private function actionHints(): string
    {
        $hints = array_filter(config('knowledge.action_hints', []), fn (string $key): bool => array_key_exists($key, config('knowledge.actions')), ARRAY_FILTER_USE_KEY);

        return $hints === [] ? '' : "\nZur Unterscheidung ähnlicher Vorgänge: ".implode('; ', array_map(fn (string $key, string $hint): string => "{$key} = {$hint}", array_keys($hints), $hints)).'.';
    }

    /**
     * @param  array{case: string, orders: string, employee: string, ticket: string, rules: string}  $parts
     */
    private function compose(array $parts, string $knowledge): string
    {
        return implode("\n\n", [$parts['case'], $parts['orders'], $parts['employee'], $parts['ticket'], $knowledge, $parts['rules']]);
    }

    /**
     * @param  array<string, RetrievedDocument>  $hints
     */
    private function knowledgeSection(KnowledgeSelection $selection, array $hints): string
    {
        return "# Wissen\n".($selection->isEmpty()
            ? 'Für diesen Fall ist kein Wissen hinterlegt.'
            : implode("\n\n", array_map(function (KnowledgeSelectionEntry $entry) use ($hints): string {
                $hint = isset($hints[(string) $entry->document->id]) ? $this->hint($hints[(string) $entry->document->id]) : '';

                return '## '.$entry->document->id.' – '.$entry->document->title
                    .' ('.config("knowledge.types.{$entry->document->type}.label").($entry->isDraft() ? ', Entwurf' : '').")\n".$hint.$entry->document->body;
            }, $selection->selected)));
    }

    /**
     * The note in front of a document that came along without its precondition being checked,
     * or that the employee switched on by hand.
     */
    private function hint(RetrievedDocument $item): string
    {
        if ($item->unverified) {
            return 'Hinweis der App: Die Voraussetzung dieser Regel konnte nicht geprüft werden (fehlende Angaben: '.implode(', ', $item->unknownKeys).'). Behandle sie nicht als erfüllt. Wende die Regel nur an, wenn der Fall die Voraussetzung bestätigt; sonst nenne die fehlende Angabe als Rückfrage oder internen Schritt.'."\n";
        }

        return "Hinweis der App: Dieses Wissen wurde vom Mitarbeiter von Hand zugeschaltet.\n";
    }

    private function orders(AnalysisRequest $request, bool $hasDeliveryAddress, bool $neutralShipping = false): string
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
                $lines[] = $neutralShipping ? 'Versand: keine Sendung in EOCS gefunden' : 'Versand: noch nicht versandt';
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

        foreach ($request->mentionedOrders as $mention) {
            $lines[] = "## Bestellung {$mention['number']} (im Ticket genannt, nicht aus EOCS geladen)";
            $lines[] = 'Kanal laut Nummernformat: '.($mention['channel'] ?? 'unbekannt').' · Status, Versand und Positionen sind nicht bekannt.';

            foreach ($mention['items'] as $item) {
                $lines[] = "- laut Benachrichtigung im Ticket: {$item}";
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
