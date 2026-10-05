<?php

namespace App\Eocs;

use App\Orders\OrderNumber;
use App\Orders\OrderNumberFormat;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads orders from EOCS over its orders API (docs/orders-api.md), read-only.
 * Every call asks EOCS directly; nothing is cached or stored, and neither
 * customer data nor order content is logged.
 */
class EocsClient
{
    private const int CLAIM_BATCH = 5;

    private const int MAX_CLAIMS = 20;

    /**
     * @param  array{url: ?string, token: ?string, timeout: int|string}  $config
     * @param  array<string, string>  $channels  EOCS channel name (lower case) => sales channel of the knowledge base.
     */
    public function __construct(
        private readonly array $config,
        private readonly array $channels = [],
    ) {}

    /**
     * Look up several order numbers at once; requests run in parallel.
     *
     * @param  list<OrderNumber>  $numbers
     * @return list<OrderLookup>
     *
     * @throws EocsException when EOCS cannot be asked at all.
     */
    public function lookup(array $numbers): array
    {
        if ($numbers === []) {
            return [];
        }

        $this->ensureConfigured();

        $responses = Http::pool(fn (Pool $pool): array => array_map(
            fn (OrderNumber $number, int $index) => $this->prepare($pool->as("order{$index}"))->get('orders', [
                ...($number->isEocsId() ? ['filter[id]' => $number->value] : ['filter[external_order_id]' => $number->value.',exact']),
                'include' => 'shipments,order_items',
            ]),
            $numbers,
            array_keys($numbers),
        ));

        $found = [];

        foreach ($numbers as $index => $number) {
            $found[$index] = array_map(fn (array $order): EocsOrder => $this->order($order), $this->data($responses["order{$index}"], $number));
        }

        $claims = $this->claims(array_merge(...array_values($found)));
        $lookups = [];

        foreach ($numbers as $index => $number) {
            $lookups[] = new OrderLookup($number, array_map(fn (EocsOrder $order): EocsOrder => $order->withClaims($claims[$order->id] ?? []), $found[$index]));
        }

        return $lookups;
    }

    /**
     * Follow-up orders "R1-<number>", "R2-<number>", … that point back to an
     * order. Asked exactly and in parallel for all orders at once: first only
     * R1 (most orders have none), then five more per round for orders whose
     * last asked number exists. A partial search takes several seconds in EOCS,
     * and EOCS answers only a few requests at a time.
     *
     * @param  list<EocsOrder>  $orders
     * @return array<int, list<EocsClaim>> Keyed by the order's EOCS id.
     *
     * @throws EocsException
     */
    private function claims(array $orders): array
    {
        $claims = [];
        $open = $orders;

        for ($first = 1; $open !== [] && $first <= self::MAX_CLAIMS; $first += count($range)) {
            $range = $first === 1 ? [1] : range($first, min(self::MAX_CLAIMS, $first + self::CLAIM_BATCH - 1));
            $responses = Http::pool(function (Pool $pool) use ($open, $range): array {
                $requests = [];

                foreach ($open as $order) {
                    foreach ($range as $n) {
                        $requests[] = $this->prepare($pool->as("{$order->id}-R{$n}"))->get('orders', ['filter[external_order_id]' => "R{$n}-{$order->externalNumber},exact"]);
                    }
                }

                return $requests;
            });

            $next = [];

            foreach ($open as $order) {
                $foundLast = false;

                foreach ($range as $n) {
                    foreach ($this->data($responses["{$order->id}-R{$n}"], new OrderNumber("R{$n}-{$order->externalNumber}", OrderNumberFormat::Amazon)) as $candidate) {
                        if ((int) ($candidate['origin_order_id'] ?? 0) !== $order->id) {
                            continue;
                        }

                        $claims[$order->id][] = new EocsClaim(
                            id: (int) $candidate['id'],
                            externalNumber: (string) $candidate['external_order_id'],
                            orderedAt: $this->time($candidate['order_date'] ?? null),
                            statusName: $candidate['status_name'] ?? null,
                            statusColor: $candidate['state']['color'] ?? null,
                            url: $this->orderUrl((int) $candidate['id']),
                        );
                        $foundLast = $foundLast || $n === end($range);
                    }
                }

                if ($foundLast) {
                    $next[] = $order;
                }
            }

            $open = $next;
        }

        return $claims;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws EocsException
     */
    private function data(mixed $response, OrderNumber $number): array
    {
        $problem = match (true) {
            ! $response instanceof Response => EocsProblem::Unavailable,
            $response->status() === 401, $response->status() === 403 => EocsProblem::Misconfigured,
            ! $response->successful() || ! is_array($response->json('data')) => EocsProblem::Unavailable,
            default => null,
        };

        if ($problem !== null) {
            $status = $response instanceof Response ? $response->status() : null;
            Log::warning('EOCS request failed', ['order' => $number->value, 'problem' => $problem->value, 'status' => $status]);

            throw new EocsException($problem, $status);
        }

        return array_values(array_filter($response->json('data'), 'is_array'));
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function order(array $order): EocsOrder
    {
        $channelName = $order['client']['name'] ?? $order['ordered_at'] ?? null;
        $platform = $order['client']['sales_platform']['name'] ?? null;

        return new EocsOrder(
            id: (int) $order['id'],
            externalNumber: (string) ($order['external_order_id'] ?? $order['id']),
            channelName: is_string($channelName) ? $channelName : null,
            channel: $this->channels[mb_strtolower((string) $channelName)] ?? $this->channels[mb_strtolower((string) $platform)] ?? null,
            orderedAt: $this->time($order['order_date'] ?? null),
            statusName: $order['status_name'] ?? null,
            statusColor: $order['state']['color'] ?? null,
            invoiceNumber: $order['invoice']['document_number'] ?? null,
            shipments: array_map(fn (array $shipment): EocsShipment => new EocsShipment(
                carrier: $shipment['carrier']['name'] ?? null,
                method: $shipment['shipping_method']['name'] ?? null,
                trackingNumber: isset($shipment['tracking_no']) ? (string) $shipment['tracking_no'] : null,
                shippedAt: $this->time($shipment['created_at'] ?? null, 'd.m.Y H:i'),
                delivered: ! empty($shipment['delivered']),
            ), array_values(array_filter($order['shipments']['data'] ?? [], 'is_array'))),
            items: array_map(fn (array $item): EocsOrderItem => new EocsOrderItem(
                itemNumber: isset($item['item']['item_id']) ? (string) $item['item']['item_id'] : null,
                name: trim((string) ($item['item']['name'] ?? $item['data']['title'] ?? 'Position')),
                productType: is_string($item['item']['product_type'] ?? null) ? $item['item']['product_type'] : null,
                quantity: (int) ($item['quantity'] ?? 1),
                status: $item['status_name'] ?? null,
                configurationCode: $item['custom_code'] ?? null,
                configurationUrl: $item['custom_code_url'] ?? null,
                asin: $item['data']['asin'] ?? null,
                customization: array_intersect_key((array) ($item['data'] ?? []), array_flip(['customizationData', 'customizationInfo'])),
            ), array_values(array_filter($order['order_items']['data'] ?? [], 'is_array'))),
            claims: [],
            url: $this->orderUrl((int) $order['id']),
        );
    }

    private function orderUrl(int $id): string
    {
        return $this->baseUrl()."/orders/{$id}";
    }

    private function time(mixed $value, ?string $format = null): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $time = $format === null ? CarbonImmutable::parse($value) : CarbonImmutable::createFromFormat($format, $value, 'Europe/Berlin');

            return $time?->setTimezone('Europe/Berlin');
        } catch (Throwable) {
            return null;
        }
    }

    private function prepare(mixed $request): mixed
    {
        $timeout = max(1, (int) $this->config['timeout']);

        return $request->baseUrl($this->baseUrl().'/api/v1/')
            ->withToken((string) $this->config['token'])
            ->acceptJson()
            ->connectTimeout($timeout)
            ->timeout($timeout);
    }

    /**
     * @throws EocsException
     */
    private function ensureConfigured(): void
    {
        if (blank($this->config['url']) || blank($this->config['token'])) {
            throw new EocsException(EocsProblem::Misconfigured);
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) $this->config['url'], '/');
    }
}
