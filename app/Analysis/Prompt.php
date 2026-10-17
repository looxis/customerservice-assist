<?php

namespace App\Analysis;

use App\Settings\SettingsRegistry;
use App\Settings\SettingsStore;
use RuntimeException;
use Throwable;

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

    /**
     * The prompt in force: the version an admin saved on the settings page (PROJ-31), else the file of the app.
     */
    public static function load(string $name): self
    {
        $file = self::fromFile($name);
        $key = self::settingKey($name);

        try {
            $settings = app(SettingsStore::class);

            if (SettingsRegistry::group($key) !== null && ! $settings->isDefault($key)) {
                $value = $settings->value($key);

                if (is_array($value) && is_string($value['text'] ?? null) && trim($value['text']) !== '') {
                    return new self($name, $file->version.'~E'.$settings->version($key), trim($value['text']));
                }
            }
        } catch (Throwable) {
            // The file of the app applies.
        }

        return $file;
    }

    /**
     * The prompt as the file of the app has it (the default of the settings page).
     */
    public static function fromFile(string $name): self
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

    /**
     * The key of the settings group of a prompt ("analysis-products" => "prompt_analysis_products").
     */
    public static function settingKey(string $name): string
    {
        return 'prompt_'.str_replace('-', '_', $name);
    }
}
