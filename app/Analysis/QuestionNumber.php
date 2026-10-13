<?php

namespace App\Analysis;

use App\Models\Analysis;

/**
 * The case number of a question without a ticket (PROJ-40): the letter F and eight digits,
 * chosen at random. Ticket numbers of Zammad are digits only, so the two never clash.
 */
final class QuestionNumber
{
    public const string PATTERN = 'F[0-9]{8}';

    /** Ticket numbers or question numbers, for the routes that work with both. */
    public const string TICKET_OR_QUESTION = '[0-9]{1,20}|F[0-9]{8}';

    public static function isQuestion(string $number): bool
    {
        return preg_match('/^'.self::PATTERN.'$/', $number) === 1;
    }

    public static function generate(): string
    {
        do {
            $number = 'F'.str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT);
        } while (Analysis::query()->where('ticket_number', $number)->exists());

        return $number;
    }
}
