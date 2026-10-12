<?php

namespace App\Knowledge\Retrieval;

/**
 * A trigger word found in the customer texts: in which tier (A the current
 * concern, B recent messages and summary, C the rest) and in which sentence.
 */
final readonly class TriggerHit
{
    public function __construct(
        public string $trigger,
        public string $tier,
        public string $sentence,
    ) {}
}
