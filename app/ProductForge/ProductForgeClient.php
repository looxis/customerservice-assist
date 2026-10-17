<?php

namespace App\ProductForge;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * Reads product information from ProductForge (LOOXIS Content Studio) over its
 * read-only API v1 (docs/productforge-product-api.md). Every call asks
 * ProductForge directly; the bearer token only ever travels in the header and
 * neither token, query nor content is logged.
 */
class ProductForgeClient
{
    private const int MAX_WAIT_SECONDS = 5;

    /**
     * @param  array{url: ?string, token: ?string, timeout: int|string, retries?: int|string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function isConfigured(): bool
    {
        return filled($this->config['url'] ?? null) && filled($this->config['token'] ?? null);
    }

    /**
     * Exact lookup by current or historical SKU. The SKU is sent unchanged.
     *
     * @throws ProductForgeException
     */
    public function bySku(string $sku): ForgeProduct
    {
        $data = $this->get('products/by-sku', ['sku' => $sku]);

        return ForgeProduct::fromResponse($this->object($data, 'data'));
    }

    /**
     * @throws ProductForgeException
     */
    public function byId(int $id): ForgeProduct
    {
        $data = $this->get("products/{$id}");

        return ForgeProduct::fromResponse($this->object($data, 'data'));
    }

    /**
     * Candidates only (no content); ambiguous results are never resolved here.
     *
     * @throws ProductForgeException
     */
    public function search(string $term, int $page = 1, int $perPage = 20, string $status = 'active'): ForgeSearchPage
    {
        $data = $this->get('products', ['q' => $term, 'page' => max(1, $page), 'per_page' => min(50, max(1, $perPage)), 'status' => $status]);
        $rows = $data['data'] ?? null;
        $meta = $data['meta'] ?? null;

        if (! is_array($rows) || ! is_array($meta)) {
            throw new ProductForgeException(ProductForgeProblem::UnexpectedResponse);
        }

        $hits = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! is_int($row['id'] ?? null) || ! is_string($row['name'] ?? null)) {
                throw new ProductForgeException(ProductForgeProblem::UnexpectedResponse);
            }

            $master = $row['master'] ?? null;

            $hits[] = new ForgeSearchHit(
                $row['id'],
                $row['name'],
                is_string($row['sku'] ?? null) ? $row['sku'] : null,
                is_string($row['type'] ?? null) ? $row['type'] : 'standalone',
                is_string($row['status'] ?? null) ? $row['status'] : 'active',
                is_array($master) && is_int($master['id'] ?? null) ? [
                    'id' => $master['id'],
                    'name' => is_string($master['name'] ?? null) ? $master['name'] : '',
                    'sku' => is_string($master['sku'] ?? null) ? $master['sku'] : null,
                ] : null,
            );
        }

        return new ForgeSearchPage(
            $hits,
            (int) ($meta['page'] ?? $page),
            (int) ($meta['per_page'] ?? $perPage),
            (int) ($meta['total'] ?? count($hits)),
            max(1, (int) ($meta['last_page'] ?? 1)),
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws ProductForgeException
     */
    private function get(string $path, array $query = []): array
    {
        $this->ensureConfigured();

        $attempts = 1 + max(0, min(3, (int) ($this->config['retries'] ?? 2)));
        $rateLimitedOnce = false;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->prepare(Http::baseUrl($this->baseUrl()))->get($path, $query);
            } catch (ConnectionException $exception) {
                $problem = str_contains($exception->getMessage(), 'cURL error 28') ? ProductForgeProblem::Timeout : ProductForgeProblem::Unavailable;

                if ($attempt < $attempts) {
                    continue;
                }

                $this->log($problem);

                throw new ProductForgeException($problem);
            }

            if ($response->successful()) {
                return $this->json($response);
            }

            $status = $response->status();

            if ($status === 429 && ! $rateLimitedOnce && $attempt < $attempts) {
                $rateLimitedOnce = true;
                Sleep::for(min(self::MAX_WAIT_SECONDS, max(1, (int) $response->header('Retry-After'))))->seconds();

                continue;
            }

            if ($status >= 500 && $attempt < $attempts) {
                continue;
            }

            $problem = $this->problem($response);
            $this->log($problem, $status);

            throw new ProductForgeException($problem, $status);
        }

        throw new ProductForgeException(ProductForgeProblem::Unavailable);
    }

    private function problem(Response $response): ProductForgeProblem
    {
        $code = $response->json('error.code');

        return match (true) {
            $response->status() === 401, $response->status() === 403 => ProductForgeProblem::Misconfigured,
            $response->status() === 404 && $code === 'product_not_found' => ProductForgeProblem::NotFound,
            $response->status() === 404 => ProductForgeProblem::Misconfigured,
            $response->status() === 429 => ProductForgeProblem::RateLimited,
            $response->status() >= 500 => ProductForgeProblem::Unavailable,
            default => ProductForgeProblem::UnexpectedResponse,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        try {
            $json = $response->json();
        } catch (Throwable) {
            $json = null;
        }

        if (! is_array($json)) {
            $this->log(ProductForgeProblem::UnexpectedResponse, $response->status());

            throw new ProductForgeException(ProductForgeProblem::UnexpectedResponse, $response->status());
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function object(array $data, string $key): array
    {
        if (! is_array($data[$key] ?? null)) {
            throw new ProductForgeException(ProductForgeProblem::UnexpectedResponse);
        }

        return $data[$key];
    }

    private function prepare(PendingRequest $request): PendingRequest
    {
        $timeout = min(30, max(1, (int) ($this->config['timeout'] ?? 10)));

        return $request->withToken((string) $this->config['token'])
            ->acceptJson()
            ->connectTimeout($timeout)
            ->timeout($timeout);
    }

    private function baseUrl(): string
    {
        $url = rtrim((string) $this->config['url'], '/');

        return str_ends_with($url, '/api/v1') ? $url : $url.'/api/v1';
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new ProductForgeException(ProductForgeProblem::Misconfigured);
        }
    }

    private function log(ProductForgeProblem $problem, ?int $status = null): void
    {
        Log::warning('ProductForge request failed', ['problem' => $problem->value, 'status' => $status]);
    }
}
