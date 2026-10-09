<?php

namespace App\Analysis;

use App\Analysis\Agents\TranslationAgent;
use App\Models\Analysis;
use App\Models\MessageTranslation;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use Carbon\CarbonImmutable;

/**
 * Translates messages of a ticket into German once and keeps the result for
 * everyone (PROJ-28). Works on the cleaned text of the ticket view; contact
 * data is replaced before sending and put back in the app. Also translates a
 * reply draft back into German for checking.
 */
class Translator
{
    public function __construct(
        private readonly LanguageModel $model,
        private readonly LanguageDetector $detector,
    ) {}

    /**
     * The valid stored translations of a ticket's messages, keyed by the
     * Zammad ID of the message. A translation of a changed message is left out.
     *
     * @return array<int, array{status: string, language: string|null, text: string|null, staff: string, at: CarbonImmutable}>
     */
    public function translations(Ticket $ticket): array
    {
        $fingerprints = $this->fingerprints($ticket);

        return MessageTranslation::query()
            ->whereIn('article_id', array_keys($fingerprints))
            ->get()
            ->filter(fn (MessageTranslation $translation): bool => $translation->fingerprint === $fingerprints[$translation->article_id])
            ->mapWithKeys(fn (MessageTranslation $translation): array => [$translation->article_id => [
                'status' => $translation->status,
                'language' => $translation->language,
                'text' => $translation->content['text'] ?? null,
                'staff' => $translation->staff_name,
                'at' => CarbonImmutable::parse($translation->created_at),
            ]])
            ->all();
    }

    /**
     * Messages that are probably not German and have no valid translation yet.
     *
     * @return list<TicketArticle>
     */
    public function pending(Ticket $ticket): array
    {
        $known = $this->translations($ticket);

        return array_values(array_filter(
            $ticket->articles,
            fn (TicketArticle $article): bool => $article->id !== null
                && ! isset($known[$article->id])
                && $article->hasText()
                && $this->detector->isForeign(TicketContext::plainText($article)) === true,
        ));
    }

    /**
     * Translate everything of the ticket that is not known to be German and
     * has no valid translation; with an article ID only that message, anew.
     *
     * @return array{translated: int, german: int, failed: int}
     *
     * @throws AnalysisException when no part could be translated.
     */
    public function translate(Ticket $ticket, string $staff, ?int $onlyArticleId = null): array
    {
        $known = $this->translations($ticket);
        $articles = array_values(array_filter($ticket->articles, fn (TicketArticle $article): bool => $article->id !== null && $article->hasText() && match (true) {
            $onlyArticleId !== null => $article->id === $onlyArticleId,
            isset($known[$article->id]) => false,
            default => $this->detector->isForeign(TicketContext::plainText($article)) !== false,
        }));

        $counts = ['translated' => 0, 'german' => 0, 'failed' => 0];
        $firstProblem = null;

        foreach ($this->batches($articles) as $batch) {
            try {
                foreach ($this->translateBatch($ticket, $batch, $staff) as $status) {
                    $counts[$status]++;
                }
            } catch (AnalysisException $exception) {
                $firstProblem ??= $exception;
                $counts['failed'] += count($batch);
            }
        }

        if ($firstProblem !== null && $counts['translated'] + $counts['german'] === 0) {
            throw $firstProblem;
        }

        return $counts;
    }

    /**
     * The German back translation of a reply draft, stored with the analysis
     * and reused while the draft text stays the same.
     *
     * @return array{text: string, reused: bool}|null Null when the analysis is gone.
     *
     * @throws AnalysisException
     */
    public function backTranslate(string $ticketNumber, string $analysisId, string $reply, string $staff): ?array
    {
        $analysis = Analysis::query()->where('uuid', $analysisId)->where('ticket_number', $ticketNumber)->whereNotNull('content')->first();

        if ($analysis === null) {
            return null;
        }

        $content = $analysis->content;
        $fingerprint = hash('sha256', trim($reply));

        if (($content['reply_backtranslation']['fingerprint'] ?? null) === $fingerprint) {
            return ['text' => (string) $content['reply_backtranslation']['text'], 'reused' => true];
        }

        $prompt = Prompt::load('translation');
        $model = (string) config('analysis.models.translation');
        $pseudonymizer = app(Pseudonymizer::class);
        $answer = $this->model->ask(new TranslationAgent($prompt->text), "### Nachricht entwurf\n".$pseudonymizer->apply(trim($reply)), $model, 'back-translation', $ticketNumber);
        $entry = collect($answer['data']['messages'] ?? [])->first(fn (mixed $message): bool => is_array($message));
        $text = trim((string) ($entry['translation'] ?? ''));

        if ($text === '') {
            if (($entry['is_german'] ?? false) !== true) {
                throw new AnalysisException(AnalysisProblem::InvalidResult);
            }

            $text = trim($reply);
        }

        $text = $pseudonymizer->restorePlain($text);
        $content['reply_backtranslation'] = ['text' => $text, 'fingerprint' => $fingerprint, 'staff' => $staff, 'at' => now()->toIso8601String(), 'model' => $model, 'prompt_version' => $prompt->version];
        $analysis->update(['content' => $content]);

        return ['text' => $text, 'reused' => false];
    }

    public static function fingerprint(TicketArticle $article): string
    {
        return hash('sha256', TicketContext::plainText($article));
    }

    /**
     * @param  list<TicketArticle>  $batch
     * @return list<string> Status per stored message: "translated" or "german"; "failed" for messages the answer left out.
     *
     * @throws AnalysisException
     */
    private function translateBatch(Ticket $ticket, array $batch, string $staff): array
    {
        $prompt = Prompt::load('translation');
        $model = (string) config('analysis.models.translation');
        $pseudonymizer = app(Pseudonymizer::class);
        // Contact data is replaced in the message text only: the heading with
        // the ID is added afterwards, so it can never be taken for an address.
        $input = implode("\n\n", array_map(fn (TicketArticle $article): string => "### Nachricht {$article->id}\n".$pseudonymizer->apply(TicketContext::plainText($article)), $batch));

        $answer = $this->model->ask(new TranslationAgent($prompt->text), $input, $model, 'translation', $ticket->number);
        $byId = collect($answer['data']['messages'] ?? [])->filter(fn (mixed $message): bool => is_array($message))->keyBy(fn (array $message): string => trim((string) ($message['id'] ?? '')));
        $statuses = [];

        foreach ($batch as $article) {
            $entry = $byId->get((string) $article->id);
            $german = ($entry['is_german'] ?? false) === true;
            $text = trim((string) ($entry['translation'] ?? ''));

            if ($entry === null || (! $german && $text === '')) {
                $statuses[] = 'failed';

                continue;
            }

            MessageTranslation::query()->updateOrCreate(['article_id' => $article->id], [
                'ticket_number' => $ticket->number,
                'fingerprint' => self::fingerprint($article),
                'status' => $german ? 'german' : 'translated',
                'language' => $german ? 'Deutsch' : mb_substr(trim((string) ($entry['language'] ?? '')), 0, 40),
                'content' => $german ? null : ['text' => $pseudonymizer->restorePlain($text)],
                'staff_name' => $staff,
                'model' => $model,
                'prompt_version' => $prompt->version,
            ]);

            $statuses[] = $german ? 'german' : 'translated';
        }

        return $statuses;
    }

    /**
     * @param  list<TicketArticle>  $articles
     * @return list<list<TicketArticle>>
     */
    private function batches(array $articles): array
    {
        $limit = (int) config('analysis.translation.batch_characters');
        $batches = [];
        $current = [];
        $size = 0;

        foreach ($articles as $article) {
            $length = mb_strlen(TicketContext::plainText($article));

            if ($current !== [] && $size + $length > $limit) {
                $batches[] = $current;
                [$current, $size] = [[], 0];
            }

            $current[] = $article;
            $size += $length;
        }

        return $current === [] ? $batches : [...$batches, $current];
    }

    /**
     * @return array<int, string>
     */
    private function fingerprints(Ticket $ticket): array
    {
        $fingerprints = [];

        foreach ($ticket->articles as $article) {
            if ($article->id !== null) {
                $fingerprints[$article->id] = self::fingerprint($article);
            }
        }

        return $fingerprints;
    }
}
