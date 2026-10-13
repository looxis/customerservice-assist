<?php

namespace App\Knowledge\Retrieval\Router;

/**
 * Recognises a customer message that has no content of its own (PROJ-37), such as "Vielen Dank!".
 * The test is deliberately narrow: only a short message made of nothing but courtesy words, without a question
 * mark, an attachment or a note from the employee. Anything else may carry a concern ("Das Problem besteht
 * weiterhin.", "Anbei die Fotos.") and runs the router. In doubt the router runs.
 */
class ContentlessMessage
{
    private const int MAX_WORDS = 12;

    public function isContentless(string $message, bool $hasAttachment, string $employeeContext = ''): bool
    {
        if ($hasAttachment || trim($employeeContext) !== '' || str_contains($message, '?')) {
            return false;
        }

        preg_match_all('/\p{L}+/u', mb_strtolower($message), $matches);
        $words = $matches[0];

        if ($words === [] || count($words) > self::MAX_WORDS) {
            return false;
        }

        $courtesy = array_map('mb_strtolower', (array) config('knowledge.router.courtesy_words'));

        return array_diff($words, $courtesy) === [];
    }
}
