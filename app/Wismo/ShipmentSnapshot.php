<?php

namespace App\Wismo;

use App\Eocs\EocsOrder;
use App\Eocs\EocsShipment;
use App\Lettermint\DeliveryNotice;
use App\Lettermint\DeliveryNotices;
use App\Tracking\ShipmentHistory;
use App\Tracking\ShipmentTracker;
use App\Tracking\TrackingException;
use App\Tracking\TrackingStatus;
use Illuminate\Support\Facades\Log;

/**
 * The state of all shipments of the loaded orders for the form "Aktualisierte WISMO-Infos" (PROJ-101): asked in code
 * from the carrier and Lettermint, with the same cases and reply rules as the automatic WISMO reply (PROJ-99). The
 * automatic reply itself (WismoResponder) is not touched.
 */
final readonly class ShipmentSnapshot
{
    public const int MAX_SHIPMENTS_PER_ORDER = 3;

    /**
     * @param  list<string>  $lines  One line per shipment (and per order for the notifications) for the model.
     * @param  list<TrackingStatus>  $statuses  The shipments whose state the carrier gave.
     * @param  list<array{order: string, mail: string, sent_at: ?string}>  $notifications  Notification mails LOOXIS sent.
     * @param  list<array{order: string, status: string, notices: list<array<string, mixed>>}>|null  $noticeRecord  For the result view (PROJ-97); null without Lettermint.
     * @param  bool  $unreachable  Whether a carrier could not be asked, so the case is not known for every shipment.
     */
    public function __construct(
        public array $lines,
        public array $statuses,
        public array $notifications,
        public ?array $noticeRecord,
        public bool $unreachable,
    ) {}

    /**
     * Whether any order has a shipment with a tracking number; without one the form has nothing to report.
     *
     * @param  list<EocsOrder>  $orders
     */
    public static function trackable(array $orders): bool
    {
        return collect($orders)->contains(fn (EocsOrder $order): bool => self::shipments($order) !== []);
    }

    /**
     * @param  list<EocsOrder>  $orders
     */
    public static function take(array $orders, ShipmentTracker $tracker, DeliveryNotices $notices, ?string $ticketEmail, string $ticketNumber): self
    {
        $lines = [];
        $statuses = [];
        $unreachable = false;

        foreach ($orders as $order) {
            foreach (self::shipments($order) as $shipment) {
                try {
                    $status = $tracker->track($shipment, $order->shippingAddress?->postalCode);
                } catch (TrackingException $exception) {
                    Log::info('Sendungsstatus nicht abrufbar.', ['ticket' => $ticketNumber, 'reason' => $exception->getMessage()]);
                    $status = null;
                }

                if ($status === null) {
                    $unreachable = true;
                    $lines[] = "Bestellung {$order->externalNumber}, Sendungsnummer {$shipment->trackingNumber}: Status beim Paketdienst nicht abrufbar.";

                    continue;
                }

                $statuses[] = $status;
                $lines[] = "Bestellung {$order->externalNumber}, soeben beim Paketdienst abgefragt: ".json_encode($status->facts(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        [$notifications, $record, $noticeLines] = self::notices($orders, $notices, $ticketEmail, $ticketNumber);

        return new self([...$lines, ...$noticeLines], $statuses, $notifications, $record, $unreachable);
    }

    /**
     * The reply rules of the automatic WISMO reply for these shipments (PROJ-99).
     *
     * @return list<string>
     */
    public function rules(): array
    {
        return WismoResponder::replyRules($this->statuses, array_map(fn (array $notice): array => ['mail' => $notice['mail'], 'sent_at' => $notice['sent_at']], $this->notifications));
    }

    /**
     * Whether the ticket can be closed after the reply: every shipment is known and in a case the automatic reply closes.
     */
    public function closable(): bool
    {
        return ! $this->unreachable && $this->statuses !== [] && collect($this->statuses)->every(fn (TrackingStatus $status): bool => ShipmentHistory::closable($status->case()));
    }

    /**
     * @param  list<EocsOrder>  $orders
     * @return array{0: list<array{order: string, mail: string, sent_at: ?string}>, 1: list<array{order: string, status: string, notices: list<array<string, mixed>>}>|null, 2: list<string>}
     */
    private static function notices(array $orders, DeliveryNotices $notices, ?string $ticketEmail, string $ticketNumber): array
    {
        if (! $notices->enabled()) {
            return [[], null, []];
        }

        $sent = [];
        $record = [];
        $lines = [];

        foreach ($orders as $order) {
            if (self::shipments($order) === []) {
                continue;
            }

            $found = $notices->forOrder($order->externalNumber, $ticketEmail, $ticketNumber);
            $record[] = ['order' => $order->externalNumber, 'status' => $found['status'], 'notices' => array_map(fn (DeliveryNotice $notice): array => $notice->toStored(), $found['notices'])];
            $mails = collect($found['notices'])
                ->filter(fn (DeliveryNotice $notice): bool => $notice->kind !== 'other' && $notice->sent())
                ->map(fn (DeliveryNotice $notice): array => ['order' => $order->externalNumber, 'mail' => DeliveryNotice::KIND_LABELS[$notice->kind], 'sent_at' => $notice->sentAt])
                ->reverse()
                ->values()
                ->all();
            array_push($sent, ...$mails);

            $lines[] = match (true) {
                $found['status'] !== 'ok' => "Bestellung {$order->externalNumber}, Benachrichtigungen von LOOXIS per E-Mail: nicht abrufbar.",
                $mails === [] => "Bestellung {$order->externalNumber}, Benachrichtigungen von LOOXIS per E-Mail: keine gefunden.",
                default => "Bestellung {$order->externalNumber}, Benachrichtigungen von LOOXIS per E-Mail (`notifications`): ".json_encode(array_map(fn (array $mail): array => ['mail' => $mail['mail'], 'sent_at' => $mail['sent_at']], $mails), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            };
        }

        return [$sent, $record, $lines];
    }

    /**
     * @return list<EocsShipment>
     */
    private static function shipments(EocsOrder $order): array
    {
        return collect($order->shipments)
            ->filter(fn (EocsShipment $shipment): bool => trim((string) $shipment->trackingNumber) !== '')
            ->take(self::MAX_SHIPMENTS_PER_ORDER)
            ->values()
            ->all();
    }
}
