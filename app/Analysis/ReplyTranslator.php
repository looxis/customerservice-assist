<?php

namespace App\Analysis;

use App\Analysis\Agents\ReplyTranslationAgent;
use App\Settings\SettingsStore;
use App\Zammad\TicketArticle;

/**
 * Brings a prepared ToDo reply and its signature into the language of the customer (PROJ-95). The texts are kept in
 * German; the language comes from the model, which reads the customer's last message only to tell its language.
 * A customer known to write German, or whose last message is plainly German, costs no call.
 */
class ReplyTranslator
{
    public const string GERMAN = 'Deutsch';

    public function __construct(
        private readonly LanguageModel $model,
        private readonly LanguageDetector $detector,
    ) {}

    public static function isGerman(?string $language): bool
    {
        return str_starts_with(mb_strtolower(trim((string) $language)), 'deutsch');
    }

    /**
     * @return array{language: string, german: bool, reply: string, signature: string}
     *
     * @throws AnalysisException
     */
    public function translate(string $ticketNumber, string $reply, string $signature, ?TicketArticle $customerMessage, ?string $knownLanguage = null): array
    {
        $unchanged = ['language' => self::GERMAN, 'german' => true, 'reply' => $reply, 'signature' => $signature];

        if (self::isGerman($knownLanguage) || trim($reply) === '') {
            return $unchanged;
        }

        $customer = $customerMessage === null ? '' : trim(TicketContext::plainText($customerMessage));

        // No reference for the language, or a message that is plainly German: nothing to translate, no call.
        if ($customer === '' || $this->detector->isForeign($customer) === false) {
            return $unchanged;
        }

        $prompt = Prompt::load('reply-translation');
        $model = (string) app(SettingsStore::class)->value('model_translation');
        $pseudonymizer = app(Pseudonymizer::class);
        $input = "### Kundennachricht\n".$pseudonymizer->apply(mb_substr($customer, 0, 3000))
            ."\n\n### Antwort\n".$pseudonymizer->apply(trim($reply))
            ."\n\n### Signatur\n".$pseudonymizer->apply(trim($signature));

        $data = $this->model->ask(new ReplyTranslationAgent($prompt->text), $input, $model, 'reply-translation', $ticketNumber)['data'];
        $language = mb_substr(trim((string) ($data['language'] ?? '')), 0, 40);

        if (($data['is_german'] ?? false) === true || self::isGerman($language)) {
            return $unchanged;
        }

        $translated = trim((string) ($data['reply'] ?? ''));

        if ($translated === '' || $language === '') {
            throw new AnalysisException(AnalysisProblem::InvalidResult);
        }

        $translatedSignature = trim((string) ($data['signature'] ?? ''));

        return [
            'language' => $language,
            'german' => false,
            'reply' => $pseudonymizer->restorePlain($translated),
            'signature' => trim($signature) === '' || $translatedSignature === '' ? $signature : $pseudonymizer->restorePlain($translatedSignature),
        ];
    }
}
