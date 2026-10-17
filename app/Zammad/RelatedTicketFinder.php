<?php

namespace App\Zammad;

use App\Orders\OrderNumber;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finds the tickets that may belong to the same case (PROJ-52): the ones linked in Zammad, the ones that name the
 * original order number, and the latest ones of the same customer. Read only; a problem with Zammad only means
 * fewer offers and a hint.
 */
class RelatedTicketFinder
{
    public const int CUSTOMER_DAYS = 90;

    public const int CUSTOMER_LIMIT = 10;

    public const int ORDER_LIMIT = 10;

    public function __construct(private readonly ZammadClient $zammad) {}

    /**
     * @param  list<OrderNumber>  $orders  The order numbers the ticket names (originals; claim numbers lead to them).
     * @return array{tickets: list<RelatedTicket>, preselected: list<string>, problems: list<string>}
     */
    public function find(Ticket $ticket, array $orders): array
    {
        $found = [];
        $problems = [];
        $preselected = [];
        $claimed = array_filter($orders, fn (OrderNumber $order): bool => $order->claims !== []) !== [];

        $add = function (RelatedTicket $related, bool $selected) use (&$found, &$preselected, $ticket): void {
            if ($related->number === $ticket->number || isset($found[$related->number])) {
                return;
            }

            $found[$related->number] = $related;

            if ($selected) {
                $preselected[] = $related->number;
            }
        };

        try {
            foreach ($this->zammad->linkedTickets($ticket->id) as $related) {
                $add($related, true);
            }
        } catch (Throwable) {
            $problems[] = 'Die in Zammad verknüpften Tickets konnten nicht gelesen werden.';
        }

        foreach (array_slice(array_values(array_unique(array_map(fn (OrderNumber $order): string => $order->value, array_filter($orders, fn (OrderNumber $order): bool => ! $order->isEocsId())))), 0, 3) as $number) {
            try {
                foreach (array_slice($this->zammad->searchTickets($number, RelatedTicket::ORDER, 20), 0, self::ORDER_LIMIT) as $related) {
                    $add($related, $claimed);
                }
            } catch (Throwable) {
                $problems[] = 'Die Suche nach Tickets zur Bestellung '.$number.' ist fehlgeschlagen.';
            }
        }

        if ($ticket->customerId !== null) {
            try {
                $since = now()->subDays(self::CUSTOMER_DAYS);
                $recent = array_filter($this->zammad->searchTickets('customer_id:'.$ticket->customerId, RelatedTicket::CUSTOMER, 30), fn (RelatedTicket $related): bool => $related->createdAt === null || $related->createdAt->greaterThanOrEqualTo($since));

                foreach (array_slice(array_values(array_filter($recent, fn (RelatedTicket $related): bool => $related->number !== $ticket->number && ! isset($found[$related->number]))), 0, self::CUSTOMER_LIMIT) as $related) {
                    $add($related, false);
                }
            } catch (Throwable) {
                $problems[] = 'Die Suche nach weiteren Tickets dieses Kunden ist fehlgeschlagen.';
            }
        }

        if ($problems !== []) {
            Log::info('Related tickets incomplete', ['ticket' => $ticket->number, 'problems' => count($problems)]);
        }

        return ['tickets' => array_values($found), 'preselected' => $preselected, 'problems' => $problems];
    }
}
