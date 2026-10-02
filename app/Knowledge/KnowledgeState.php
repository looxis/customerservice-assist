<?php

namespace App\Knowledge;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Process;
use Throwable;

final readonly class KnowledgeState
{
    public function __construct(
        public ?string $commit,
        public ?CarbonImmutable $committedAt,
        public bool $dirty,
    ) {}

    /**
     * Ask git for the current state; fall back to the commit written at
     * deployment, then to "unknown". Never throws.
     */
    public static function detect(string $path, ?string $deployedCommit): self
    {
        try {
            $log = Process::path($path)->run(['git', 'log', '-1', '--format=%h|%cI']);

            if ($log->successful() && str_contains($log->output(), '|')) {
                [$commit, $date] = explode('|', trim($log->output()), 2);
                $status = Process::path($path)->run(['git', 'status', '--porcelain', '--', '.']);

                return new self($commit, CarbonImmutable::parse($date), $status->successful() && trim($status->output()) !== '');
            }
        } catch (Throwable) {
            // git is missing or the folder is not a repository
        }

        $deployedCommit = trim((string) $deployedCommit);

        return new self($deployedCommit === '' ? null : $deployedCommit, null, false);
    }

    public function isKnown(): bool
    {
        return $this->commit !== null;
    }

    public function label(): string
    {
        if (! $this->isKnown()) {
            return 'unbekannt';
        }

        $label = $this->commit;

        if ($this->committedAt !== null) {
            $label .= ' vom '.$this->committedAt->format('d.m.Y');
        }

        return $this->dirty ? $label.', mit uncommitteten Änderungen' : $label;
    }
}
