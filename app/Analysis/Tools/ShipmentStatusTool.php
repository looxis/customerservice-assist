<?php

namespace App\Analysis\Tools;

use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;
use App\Lettermint\DeliveryNotice;
use App\Lettermint\DeliveryNotices;
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
 *
 * With Lettermint set up (PROJ-97), the same call also reports per order whether the n8n notification mails
 * ("Paket kommt heute", pickup notice) were accepted by the customer's mail server, and keeps that record for the
 * result view.
 */
class ShipmentStatusTool implements Tool
{
    private const int MAX_SHIPMENTS_PER_ORDER = 10;

    private ?string $answer = null;

    /**
     * @var list<array{order: string, status: string, notices: list<array<string, mixed>>}>|null
     */
    private ?array $noticeRecord = null;

    /**
     * @param  list<EocsOrder>  $orders
     */
    public function __construct(
        private readonly array $orders,
        private readonly ShipmentTracker $tracker,
        private readonly string $ticketNumber = '',
        private readonly ?DeliveryNotices $notices = null,
        private readonly ?string $ticketEmail = null,
    ) {}

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
     * The notification mails found per order, for the result view; null when the tool was not called or Lettermint
     * is not set up.
     *
     * @return list<array{order: string, status: string, notices: list<array<string, mixed>>}>|null
     */
    public function noticeRecord(): ?array
    {
        return $this->noticeRecord;
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

        if ($lines === []) {
            return 'Keine Sendung mit Sendungsnummer vorhanden.';
        }

        return implode("\n", [...$lines, ...$this->noticeLines()]);
    }

    /**
     * One line per order with the notification mails found at Lettermint (PROJ-97).
     *
     * @return list<string>
     */
    private function noticeLines(): array
    {
        if ($this->notices === null || ! $this->notices->enabled()) {
            return [];
        }

        $this->noticeRecord = [];
        $lines = [];

        foreach ($this->orders as $order) {
            if (self::trackable($order) === []) {
                continue;
            }

            $found = $this->notices->forOrder($order->externalNumber, $this->ticketEmail, $this->ticketNumber);
            $this->noticeRecord[] = ['order' => $order->externalNumber, 'status' => $found['status'], 'notices' => array_map(fn (DeliveryNotice $notice): array => $notice->toStored(), $found['notices'])];

            $lines[] = match (true) {
                $found['status'] !== 'ok' => "Bestellung {$order->externalNumber}, Benachrichtigungen von LOOXIS per E-Mail: nicht abrufbar.",
                $found['notices'] === [] => "Bestellung {$order->externalNumber}, Benachrichtigungen von LOOXIS per E-Mail: keine gefunden.",
                default => "Bestellung {$order->externalNumber}, Benachrichtigungen von LOOXIS per E-Mail: ".json_encode(array_map(fn (DeliveryNotice $notice): array => $notice->facts(), $found['notices']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            };
        }

        return $lines;
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
