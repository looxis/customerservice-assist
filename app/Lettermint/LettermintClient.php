<?php

namespace App\Lettermint;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Read-only access to the Lettermint Team API (PROJ-97): outgoing messages by subject and the event history of one
 * message. Every page is read while `next_cursor` is set, up to a fixed limit.
 */
class LettermintClient
{
    private const int MAX_PAGES = 3;

    /**
     * @param  array{url: ?string, token: ?string, project?: ?string, timeout: int|string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function configured(): bool
    {
        return filled($this->config['token'] ?? null) && filled($this->config['url'] ?? null);
    }

    /**
     * Outgoing live messages whose subject contains the given text (Lettermint searches substrings).
     *
     * @return list<array<string, mixed>>
     *
     * @throws LettermintException
     */
    public function messages(string $subject): array
    {
        return $this->pages('/messages', array_filter([
            'filter[type]' => 'outbound',
            'filter[delivery_mode]' => 'live',
            'filter[subject]' => $subject,
            'filter[project]' => $this->config['project'] ?? null,
        ], fn ($value): bool => filled($value)));
    }

    /**
     * The events of one message (queued, processed, delivered, opened, bounces …).
     *
     * @return list<array<string, mixed>>
     *
     * @throws LettermintException
     */
    public function events(string $messageId): array
    {
        return $this->pages('/messages/'.rawurlencode($messageId).'/events', []);
    }

    /**
     * @param  array<string, string>  $query
     * @return list<array<string, mixed>>
     *
     * @throws LettermintException
     */
    private function pages(string $path, array $query): array
    {
        if (! $this->configured()) {
            throw new LettermintException('Lettermint ist nicht eingerichtet (LETTERMINT_TEAM_TOKEN).', false);
        }

        $rows = [];
        $cursor = null;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $response = $this->get($path, $cursor === null ? $query : [...$query, 'page[cursor]' => $cursor]);

            foreach ((array) $response->json('data', []) as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }

            $cursor = $response->json('next_cursor');

            if (! is_string($cursor) || $cursor === '') {
                break;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $query
     *
     * @throws LettermintException
     */
    private function get(string $path, array $query): Response
    {
        try {
            $response = Http::withToken((string) $this->config['token'])
                ->acceptJson()
                ->timeout((int) $this->config['timeout'])
                ->retry(2, 500, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response->status() === 429 || $exception->response->serverError())), throw: false)
                ->get(rtrim((string) $this->config['url'], '/').$path, $query);
        } catch (ConnectionException) {
            throw new LettermintException('Lettermint ist nicht erreichbar.');
        }

        return match (true) {
            $response->successful() => $response,
            in_array($response->status(), [401, 403], true) => throw new LettermintException('Lettermint: Zugang abgelehnt (HTTP '.$response->status().'), Token oder Berechtigung read:messages prüfen.', false),
            $response->status() === 404 => throw new LettermintException('Lettermint: Verlauf nicht verfügbar (HTTP 404).', false),
            $response->status() === 422 => throw new LettermintException('Lettermint: Filter abgelehnt (HTTP 422).', false),
            default => throw new LettermintException('Lettermint: HTTP '.$response->status().'.'),
        };
    }
}
