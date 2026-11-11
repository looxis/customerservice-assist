<?php

namespace App\Tracking;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * The DHL Shipment Tracking API (PROJ-82). The recipient's postcode goes along, because DHL then adds details of the
 * delivery and of the pickup point.
 */
class DhlTrackingClient implements TrackingClient
{
    /**
     * @param  array{url: ?string, key: ?string, timeout: int|string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function track(string $trackingNumber, ?string $postalCode): TrackingStatus
    {
        $link = Carriers::link('DHL', $trackingNumber);

        if (blank($this->config['key'] ?? null) || blank($this->config['url'] ?? null)) {
            throw new TrackingException('DHL-Tracking ist nicht eingerichtet (DHL_TRACKING_API_KEY).', false);
        }

        try {
            $response = Http::withHeaders(['DHL-API-Key' => $this->config['key'], 'Accept' => 'application/json'])
                ->timeout((int) $this->config['timeout'])
                ->get($this->config['url'], array_filter(['trackingNumber' => $trackingNumber, 'recipientPostalCode' => $postalCode, 'language' => 'de']));
        } catch (ConnectionException) {
            throw new TrackingException('DHL ist nicht erreichbar.');
        }

        if ($response->status() === 404) {
            return TrackingStatus::unknown('DHL', $trackingNumber, $link);
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new TrackingException('DHL-Tracking: Zugang abgelehnt (HTTP '.$response->status().').', false);
        }

        if (! $response->successful()) {
            throw new TrackingException('DHL-Tracking: HTTP '.$response->status().'.');
        }

        $shipment = $response->json('shipments.0');

        return is_array($shipment) ? $this->status($shipment, $trackingNumber, $link) : TrackingStatus::unknown('DHL', $trackingNumber, $link);
    }

    /**
     * @param  array<string, mixed>  $shipment
     */
    private function status(array $shipment, string $trackingNumber, ?string $link): TrackingStatus
    {
        $events = array_values(array_filter((array) ($shipment['events'] ?? []), 'is_array'));
        $latest = $events[0] ?? [];
        $problem = null;

        foreach ($events as $event) {
            $text = DeliveryText::strip($event['description'] ?? '');
            $remark = DeliveryText::strip($event['remark'] ?? '');

            if (($event['statusCode'] ?? null) === 'failure' || DeliveryText::isProblem($text.' '.$remark)) {
                $problem = $text !== '' ? $text : ($remark !== '' ? $remark : 'Zustellproblem laut DHL');

                break;
            }
        }

        $code = (string) ($shipment['status']['statusCode'] ?? $latest['statusCode'] ?? '');
        $description = DeliveryText::strip($latest['description'] ?? $shipment['status']['description'] ?? '');
        $remark = DeliveryText::strip($latest['remark'] ?? $shipment['status']['remark'] ?? '');
        $delivered = $code === 'delivered';
        $pickup = ! $delivered && DeliveryText::isPickupReady($description.' '.$remark);

        return new TrackingStatus(
            carrier: 'DHL',
            trackingNumber: $trackingNumber,
            known: true,
            status: $description !== '' ? $description : 'Status unbekannt',
            location: $latest['location']['address']['addressLocality'] ?? null,
            at: self::time($latest['timestamp'] ?? null),
            delivered: $delivered,
            deliveryDetails: $delivered ? trim($description.($remark !== '' && $remark !== $description ? ' – '.$remark : '')) : null,
            receivedBy: $delivered ? (trim((string) ($shipment['details']['receiver']['name'] ?? '')) ?: null) : null,
            pickupLocation: $pickup ? ($remark !== '' ? $remark : $description) : null,
            pickupReady: $pickup,
            estimate: self::time($shipment['estimatedTimeOfDelivery'] ?? null),
            problem: $problem,
            link: $link,
        );
    }

    private static function time(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr';
        } catch (\Throwable) {
            return $value;
        }
    }
}
