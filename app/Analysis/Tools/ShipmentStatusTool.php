<?php

namespace App\Analysis\Tools;

use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;
use App\Tracking\ShipmentTracker;
use App\Tracking\TrackingException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Lets the model ask the carrier (DHL, UPS) for the current state of the shipments of the loaded orders (PROJ-88).
 * The model calls it only when the customer's concern is the whereabouts of the parcel; otherwise no carrier is
 * asked and nothing about the shipment reaches the prompt. The answer is kept, so later passes of one analysis do
 * not ask the carrier again.
 */
class ShipmentStatusTool implements Tool
{
    private const int MAX_SHIPMENTS_PER_ORDER = 3;

    private ?string $answer = null;

    /**
     * @param  list<EocsOrder>  $orders
     */
    public function __construct(private readonly array $orders, private readonly ShipmentTracker $tracker, private readonly string $ticketNumber = '') {}

    /**
     * Whether any loaded order has a shipment with a tracking number, so that the tool is worth offering.
     *
     * @param  list<EocsOrder>  $orders
     */
    public static function applies(array $orders): bool
    {
        return collect($orders)->contains(fn (EocsOrder $order): bool => self::trackable($order) !== []);
    }

    public function description(): Stringable|string
    {
        return 'Fragt den aktuellen Sendungsstatus der Bestellung beim Paketdienst ab (zugestellt, unterwegs, noch nicht abgeholt, Zustellproblem) samt Tracking-Link. Nur aufrufen, wenn die aktuelle Kundenanfrage den Verbleib, die Lieferzeit, einen Liefertermin oder die Zustellung des Pakets betrifft. Nicht aufrufen bei Reklamationen von Mängeln oder Beschädigungen, Produktfragen, Angeboten, Änderungen oder Stornos.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->answer ??= $this->query();
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    private function query(): string
    {
        Log::info('Sendungsstatus abgefragt', ['ticket' => $this->ticketNumber]);

        $lines = [];

        foreach ($this->orders as $order) {
            foreach (self::trackable($order) as $shipment) {
                try {
                    $status = $this->tracker->track($shipment, $order->shippingAddress?->postalCode);
                } catch (TrackingException $exception) {
                    Log::info('Sendungsstatus nicht abrufbar.', ['ticket' => $this->ticketNumber, 'reason' => $exception->getMessage()]);
                    $lines[] = "Bestellung {$order->externalNumber}, Sendungsnummer {$shipment->trackingNumber}: Status beim Paketdienst nicht abrufbar.";

                    continue;
                }

                $lines[] = $status === null
                    ? "Bestellung {$order->externalNumber}, Sendungsnummer {$shipment->trackingNumber}: Status für diesen Paketdienst nicht abrufbar."
                    : "Bestellung {$order->externalNumber}, soeben beim Paketdienst abgefragt: ".json_encode($status->facts(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $lines === [] ? 'Keine Sendung mit Sendungsnummer vorhanden.' : implode("\n", $lines);
    }

    /**
     * @return list<EocsShipment>
     */
    private static function trackable(EocsOrder $order): array
    {
        return collect($order->shipments)
            ->filter(fn (EocsShipment $shipment): bool => trim((string) $shipment->trackingNumber) !== '')
            ->take(self::MAX_SHIPMENTS_PER_ORDER)
            ->values()
            ->all();
    }
}
