<?php

namespace App\Knowledge;

final readonly class KnowledgeIssue
{
    public function __construct(
        public string $path,
        public Severity $severity,
        public string $message,
    ) {}

    public static function error(string $path, string $message): self
    {
        return new self($path, Severity::Error, $message);
    }

    public static function warning(string $path, string $message): self
    {
        return new self($path, Severity::Warning, $message);
    }

    public function isError(): bool
    {
        return $this->severity === Severity::Error;
    }
}
