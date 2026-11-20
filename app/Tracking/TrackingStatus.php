<?php

namespace App\Tracking;

/**
 * The state of one shipment in the words of the carrier, reduced to what the WISMO reply needs (PROJ-82). Built from
 * the answer of DHL or UPS; the decisions (delivery problem, pickup address, the case from the whole history, PROJ-99)
 * are made in code, never by the model.
 */
final readonly class TrackingStatus
{
    public function __construct(
        public string $carrier,
        public string $trackingNumber,
        public bool $known,
        public string $status,
        public ?string $location = null,
        public ?string $at = null,
        public bool $delivered = false,
        public ?string $deliveryDetails = null,
        public ?string $receivedBy = null,
        public ?string $pickupLocation = null,
        public bool $pickupReady = false,
        public ?string $pickupDeadline = null,
        public ?string $estimate = null,
        public ?string $problem = null,
        public ?string $link = null,
        public ?string $shipmentCase = null,
        public ?ShipmentHistory $history = null,
    ) {}

    public static function unknown(string $carrier, string $trackingNumber, ?string $link): self
    {
        return new self($carrier, $trackingNumber, false, 'Die Sendung ist beim Paketdienst noch nicht bekannt oder wird gerade vorbereitet.', link: $link);
    }

    public function withLink(?string $link): self
    {
        return new self($this->carrier, $this->trackingNumber, $this->known, $this->status, $this->location, $this->at, $this->delivered, $this->deliveryDetails, $this->receivedBy, $this->pickupLocation, $this->pickupReady, $this->pickupDeadline, $this->estimate, $this->problem, $link, $this->shipmentCase, $this->history);
    }

    /**
     * The case of the shipment (PROJ-99); without a history (another carrier, unknown number) from the current state.
     */
    public function case(): string
    {
        return $this->shipmentCase ?? match (true) {
            $this->problem !== null => ShipmentHistory::SPECIAL,
            ! $this->known => ShipmentHistory::UNKNOWN,
            $this->delivered => ShipmentHistory::DELIVERED,
            $this->pickupReady => ShipmentHistory::PICKUP_READY,
            default => ShipmentHistory::IN_TRANSIT,
        };
    }

    public function returned(): bool
    {
        return $this->history?->returned() ?? false;
    }

    /**
     * What the language model is told about the shipment. A delivery problem hides everything the model must not name;
     * after a return to LOOXIS "delivered" means delivered to us, so neither delivery nor receiver reach the model.
     *
     * @return array<string, mixed>
     */
    public function facts(): array
    {
        $problem = $this->problem !== null;
        $returned = $this->returned();
        $status = match (true) {
            $returned && $this->history->returnedToUs => ShipmentHistory::BACK_AT_LOOXIS,
            $returned => 'Die Sendung ist auf dem Rückweg zum Absender (LOOXIS).',
            default => $this->status,
        };

        return array_filter([
            'carrier' => $this->carrier,
            'tracking_number' => $this->trackingNumber,
            'tracking_link' => $this->link,
            'case' => $this->case(),
            'status' => $status,
            'location' => $problem ? null : $this->location,
            'status_time' => $problem ? null : $this->at,
            'delivered' => $problem ? false : $this->delivered,
            'delivery_details' => $problem ? null : $this->deliveryDetails,
            'received_by' => $problem ? null : $this->receivedBy,
            'pickup_location' => $problem ? null : $this->pickupLocation,
            'pickup_ready' => $problem ? false : $this->pickupReady,
            'pickup_deadline' => $problem || $this->pickupLocation === null ? null : $this->pickupDeadline,
            'delivery_estimate' => $problem || $this->delivered ? null : $this->estimate,
            'delivery_problem' => $problem,
            'returned_to_us' => $returned ? $this->history->returnedToUs : null,
            'returned_on' => $returned ? $this->history->returnedOn : null,
            'storage_days' => $this->history?->storageDays,
            'history' => $this->history === null || $this->history->milestones === [] ? null : $this->history->milestones,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
