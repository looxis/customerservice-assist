<?php

namespace App\Dhl;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orders DHL return labels over the Parcel DE Returns API v1 (PROJ-64). The access token of the system user is
 * fetched with the password grant and cached for its lifetime. Neither credentials, token, address nor label
 * content are logged. The order of a label is never repeated automatically: after an unclear outcome DHL may
 * already have created it.
 */
class DhlClient
{
    private const int MAX_PDF_BYTES = 10 * 1024 * 1024;

    /**
     * @param  array{url: ?string, token_url: ?string, client_id: ?string, client_secret: ?string, username: ?string, password: ?string, receiver_id: ?string, timeout: int|string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function isConfigured(): bool
    {
        foreach (['url', 'token_url', 'client_id', 'client_secret', 'username', 'password', 'receiver_id'] as $key) {
            if (blank($this->config[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Orders one return label (PDF, A4) for a sender in Germany.
     *
     * @throws DhlException
     */
    public function createReturn(ReturnRequest $return): ReturnLabelResult
    {
        $this->ensureConfigured();

        $payload = [
            'receiverId' => (string) $this->config['receiver_id'],
            'shipper' => [
                'name1' => $return->name,
                'addressStreet' => $return->street,
                'addressHouse' => $return->houseNumber,
                'postalCode' => $return->postalCode,
                'city' => $return->city,
            ],
            'customerReference' => $return->customerReference,
            ...($return->shipmentReference === null ? [] : ['shipmentReference' => $return->shipmentReference]),
        ];

        $response = $this->order($payload, $this->token());

        if ($response->status() === 401) {
            $response = $this->order($payload, $this->token(refresh: true));
        }

        if (! $response->successful()) {
            $problem = $this->problem($response);
            $this->log($problem, $response->status());

            throw new DhlException($problem, $response->status(), $problem === DhlProblem::Rejected ? $this->detail($response) : null);
        }

        return $this->result($response);
    }

    /**
     * The return receivers set up for the contract; proves that login and permissions work.
     *
     * @return list<array{receiverId: string, billingNumber: ?string, name: ?string, address: ?string}>
     *
     * @throws DhlException
     */
    public function locations(): array
    {
        $this->ensureConfigured();

        $response = $this->send(fn (PendingRequest $request): Response => $request->get($this->url('locations'), ['countryCode' => 'deu']), $this->token(), certain: true);

        if (! $response->successful()) {
            $problem = $this->problem($response);
            $this->log($problem, $response->status());

            throw new DhlException($problem, $response->status());
        }

        $rows = $response->json();

        if (! is_array($rows)) {
            throw new DhlException(DhlProblem::UnexpectedResponse, $response->status());
        }

        return array_values(array_map(fn (array $row): array => [
            'receiverId' => (string) ($row['receiverId'] ?? ''),
            'billingNumber' => isset($row['billingNumber']) ? (string) $row['billingNumber'] : null,
            'name' => isset($row['receiverAddress']['name1']) ? (string) $row['receiverAddress']['name1'] : null,
            'address' => $this->addressLine((array) ($row['receiverAddress'] ?? [])),
        ], array_filter($rows, 'is_array')));
    }

    /**
     * The configured return receiver as one line ("Name, Straße Nr, PLZ Ort"), for the employee to check; null when
     * unknown or DHL cannot be asked. Cached for an hour.
     */
    public function receiverLine(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return Cache::remember('dhl.receiver.'.sha1((string) $this->config['receiver_id']), now()->addHour(), function (): ?string {
            try {
                $row = collect($this->locations())->firstWhere('receiverId', (string) $this->config['receiver_id']);
            } catch (DhlException) {
                return null;
            }

            return is_array($row) && filled($row['address'] ?? null) ? $row['address'] : null;
        });
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function addressLine(array $address): ?string
    {
        $text = fn (string $key): string => trim((string) ($address[$key] ?? ''));
        $line = implode(', ', array_filter([
            $text('name1'),
            trim($text('addressStreet').' '.$text('addressHouse')),
            trim($text('postalCode').' '.$text('city')),
        ]));

        return $line === '' ? null : $line;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function order(array $payload, string $token): Response
    {
        return $this->send(fn (PendingRequest $request): Response => $request
            ->withQueryParameters(['labelType' => 'SHIPMENT_LABEL', 'docFormat' => 'PDF', 'printFormat' => 'A4'])
            ->post($this->url('orders'), $payload), $token, certain: false);
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     * @param  bool  $certain  Whether a failed call proves that nothing changed at DHL (reads).
     *
     * @throws DhlException
     */
    private function send(callable $call, string $token, bool $certain): Response
    {
        try {
            return $call($this->prepare(Http::withToken($token)));
        } catch (ConnectionException $exception) {
            // Could not even connect or resolve the host: nothing was sent.
            $neverSent = $certain || preg_match('/cURL error (6|7):/', $exception->getMessage()) === 1;
            $problem = $neverSent ? DhlProblem::Unavailable : DhlProblem::OutcomeUnknown;
            $this->log($problem);

            throw new DhlException($problem);
        }
    }

    /**
     * @throws DhlException
     */
    private function token(bool $refresh = false): string
    {
        $key = 'dhl.token.'.sha1(($this->config['token_url'] ?? '').'|'.($this->config['client_id'] ?? '').'|'.($this->config['username'] ?? ''));

        if ($refresh) {
            Cache::forget($key);
        }

        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return Cache::lock($key.'.lock', 10)->block(5, function () use ($key): string {
            $cached = Cache::get($key);

            if (is_string($cached) && $cached !== '') {
                return $cached;
            }

            return $this->fetchToken($key);
        });
    }

    /**
     * @throws DhlException
     */
    private function fetchToken(string $key): string
    {
        try {
            $response = $this->prepare(Http::asForm())->post((string) $this->config['token_url'], [
                'grant_type' => 'password',
                'client_id' => (string) $this->config['client_id'],
                'client_secret' => (string) $this->config['client_secret'],
                'username' => (string) $this->config['username'],
                'password' => (string) $this->config['password'],
            ]);
        } catch (ConnectionException) {
            $this->log(DhlProblem::Unavailable);

            throw new DhlException(DhlProblem::Unavailable);
        }

        if (! $response->successful()) {
            $problem = in_array($response->status(), [400, 401, 403], true) ? DhlProblem::Unauthorized : $this->problem($response);
            $this->log($problem, $response->status());

            throw new DhlException($problem, $response->status());
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            $this->log(DhlProblem::UnexpectedResponse, $response->status());

            throw new DhlException(DhlProblem::UnexpectedResponse, $response->status());
        }

        $seconds = max(60, (int) ($response->json('expires_in') ?? 1800) - 60);
        Cache::put($key, $token, now()->addSeconds($seconds));

        return $token;
    }

    /**
     * @throws DhlException
     */
    private function result(Response $response): ReturnLabelResult
    {
        $shipmentNo = $response->json('shipmentNo');
        $encoded = $response->json('label.b64');

        if (! is_string($shipmentNo) && ! is_int($shipmentNo) || (string) $shipmentNo === '') {
            $this->log(DhlProblem::UnexpectedResponse, $response->status());

            throw new DhlException(DhlProblem::UnexpectedResponse, $response->status());
        }

        $shipmentNo = (string) $shipmentNo;
        $pdf = is_string($encoded) ? base64_decode($encoded, true) : false;

        if (! is_string($pdf) || ! $this->isPdf($pdf)) {
            $this->log(DhlProblem::InvalidLabel, $response->status());

            throw new DhlException(DhlProblem::InvalidLabel, $response->status(), shipmentNo: $shipmentNo);
        }

        return new ReturnLabelResult($shipmentNo, $pdf);
    }

    private function isPdf(string $content): bool
    {
        return $content !== ''
            && strlen($content) <= self::MAX_PDF_BYTES
            && str_starts_with($content, '%PDF-')
            && str_contains(substr($content, -1024), '%%EOF');
    }

    private function problem(Response $response): DhlProblem
    {
        return match (true) {
            $response->status() === 401 => DhlProblem::Unauthorized,
            $response->status() === 403 => DhlProblem::Forbidden,
            in_array($response->status(), [400, 422], true) => DhlProblem::Rejected,
            $response->status() === 429 => DhlProblem::RateLimited,
            $response->status() >= 500 => DhlProblem::OutcomeUnknown,
            default => DhlProblem::UnexpectedResponse,
        };
    }

    /**
     * DHL's wording of a rejection, so the employee can see which field is wrong. Shortened, a single line.
     */
    private function detail(Response $response): ?string
    {
        try {
            $detail = $response->json('detail');
        } catch (Throwable) {
            return null;
        }

        return is_string($detail) && trim($detail) !== '' ? mb_substr(trim((string) preg_replace('/\s+/', ' ', $detail)), 0, 300) : null;
    }

    private function prepare(PendingRequest $request): PendingRequest
    {
        $timeout = min(60, max(1, (int) ($this->config['timeout'] ?? 10)));

        return $request->acceptJson()->connectTimeout(min(5, $timeout))->timeout($timeout);
    }

    private function url(string $path): string
    {
        return rtrim((string) $this->config['url'], '/').'/'.$path;
    }

    /**
     * @throws DhlException
     */
    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new DhlException(DhlProblem::Misconfigured);
        }
    }

    private function log(DhlProblem $problem, ?int $status = null): void
    {
        Log::warning('DHL request failed', ['problem' => $problem->value, 'status' => $status]);
    }
}
