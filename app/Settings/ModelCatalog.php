<?php

namespace App\Settings;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The language models of the provider (PROJ-31): the list for the settings page and the tiny test call that
 * checks a model before it is saved. The key of the provider never leaves the server.
 */
class ModelCatalog
{
    private const string EXCLUDED = '/(embedding|audio|tts|whisper|transcribe|realtime|image|moderation|search|dall-e|davinci|babbage|instruct|codex)/i';

    /**
     * The usable text models, newest names first; null when the provider cannot be asked.
     *
     * @return list<string>|null
     */
    public function available(): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        return Cache::remember('settings.models', now()->addHour(), function (): ?array {
            try {
                $response = Http::withToken((string) config('ai.providers.openai.key'))->acceptJson()->timeout(10)->get($this->url('models'));
            } catch (ConnectionException) {
                return null;
            }

            if (! $response->successful() || ! is_array($response->json('data'))) {
                return null;
            }

            $ids = collect($response->json('data'))->pluck('id')->filter(fn (mixed $id): bool => is_string($id) && preg_match('/^(gpt-|o\d|chatgpt-)/', $id) === 1 && preg_match(self::EXCLUDED, $id) !== 1)->unique()->sortDesc(SORT_NATURAL)->sortBy(fn (string $id): int => str_starts_with($id, 'gpt-') ? 0 : 1)->values()->all();

            return $ids === [] ? null : $ids;
        });
    }

    /**
     * Asks the model one tiny question. Null when it answers, otherwise the reason in plain German.
     */
    public function check(string $model): ?string
    {
        if (! $this->configured()) {
            return 'Der Zugang zum KI-Anbieter ist nicht eingerichtet; das Modell kann nicht geprüft werden.';
        }

        try {
            $response = Http::withToken((string) config('ai.providers.openai.key'))->acceptJson()->timeout(20)->post($this->url('responses'), [
                'model' => $model,
                'input' => 'Antworte nur mit: ok',
                'max_output_tokens' => 16,
                'store' => false,
            ]);
        } catch (ConnectionException) {
            return 'Der KI-Anbieter hat beim Testaufruf nicht rechtzeitig geantwortet. Bitte später erneut versuchen.';
        }

        return match (true) {
            $response->successful() => null,
            $response->status() === 404, $response->json('error.code') === 'model_not_found' => 'Das Modell „'.$model.'“ gibt es beim Anbieter nicht oder dein Zugang erlaubt es nicht.',
            $response->status() === 401, $response->status() === 403 => 'Der Anbieter hat den Zugang abgelehnt (Schlüssel ungültig oder das Modell ist nicht freigegeben).',
            $response->status() === 429 => 'Das Kontingent beim Anbieter ist gerade ausgeschöpft. Bitte später erneut versuchen.',
            $response->status() === 400 => 'Das Modell „'.$model.'“ nimmt den Testaufruf nicht an (vermutlich kein Modell für Textaufgaben).',
            default => 'Der Anbieter hat den Testaufruf abgelehnt (HTTP '.$response->status().').',
        };
    }

    private function configured(): bool
    {
        return filled(config('ai.providers.openai.key'));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('ai.providers.openai.url'), '/').'/'.$path;
    }
}
