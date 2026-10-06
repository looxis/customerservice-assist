<?php

namespace App\Analysis;

use RuntimeException;

/**
 * A versioned prompt file from resources/prompts. The version line at the top
 * ("<!-- version: … -->") is recorded with every call.
 */
final readonly class Prompt
{
    public function __construct(
        public string $name,
        public string $version,
        public string $text,
    ) {}

    public static function load(string $name): self
    {
        $path = config("analysis.prompts.{$name}");
        $content = is_string($path) && is_file($path) ? (string) file_get_contents($path) : '';

        if (trim($content) === '') {
            throw new RuntimeException("Prompt file for \"{$name}\" is missing.");
        }

        $version = preg_match('/<!--\s*version:\s*([^\s>]+)\s*-->/', $content, $match)
            ? $match[1]
            : $name.'-'.substr(hash('sha256', $content), 0, 12);

        return new self($name, $version, trim(preg_replace('/<!--.*?-->/s', '', $content) ?? $content));
    }
}
