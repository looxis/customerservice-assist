<?php

namespace App\Analysis;

use Carbon\CarbonImmutable;

/**
 * The chosen filters of the analysis overview (PROJ-14), already cleaned: values
 * that do not fit are dropped, so a hand-changed address never causes an error.
 */
final readonly class AnalysisFilter
{
    public function __construct(
        public ?int $days = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?string $group = null,
        public ?string $product = null,
        public ?string $category = null,
        public ?string $feedback = null,
        public ?string $copy = null,
        public ?string $staff = null,
        public ?string $model = null,
        public ?string $prompt = null,
        public bool $tests = false,
        public bool $failed = false,
        public ?string $problem = null,
    ) {}

    /**
     * Whether anything other than the standard view is chosen.
     */
    public function isCustom(): bool
    {
        $customPeriod = $this->from !== null || $this->to !== null || ($this->days !== null && $this->days !== (int) config('analysis.overview_default_days'));

        return $customPeriod
            || $this->group !== null || $this->product !== null || $this->category !== null || $this->feedback !== null
            || $this->copy !== null || $this->staff !== null || $this->model !== null || $this->prompt !== null
            || $this->tests || $this->failed;
    }
}
