<?php

namespace App\Knowledge;

use App\Analysis\QuestionNumber;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\OrderLookup;
use App\Orders\OrderNumberDetector;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;

/**
 * Rebuilds the links of a case when a procedure is loaded by hand (PROJ-58): the page gives the ticket number and
 * the order numbers, the app reads ticket and orders again. Whatever cannot be read gives no link.
 */
class CaseLinksBuilder
{
    public function __construct(
        private readonly ZammadClient $zammad,
        private readonly EocsClient $eocs,
        private readonly OrderNumberDetector $detector,
    ) {}

    /**
     * @param  list<string>  $orderNumbers
     */
    public function build(?string $ticketNumber, array $orderNumbers): CaseLinks
    {
        if ($ticketNumber === null || preg_match('/^\d{1,12}$/', $ticketNumber) !== 1 || QuestionNumber::isQuestion($ticketNumber)) {
            return CaseLinks::none();
        }

        try {
            $ticket = $this->zammad->ticket($ticketNumber);
        } catch (ZammadException) {
            return CaseLinks::none();
        }

        $numbers = [];

        foreach (array_slice($orderNumbers, 0, OrderNumberDetector::MAX_SUGGESTIONS) as $value) {
            $order = $this->detector->normalize($value);

            if ($order !== null) {
                $numbers[$order->value] = $order;
            }
        }

        $orders = [];

        try {
            $orders = collect($this->eocs->lookup(array_values($numbers)))->flatMap(fn (OrderLookup $lookup): array => $lookup->orders)->values()->all();
        } catch (EocsException) {
        }

        return CaseLinks::forTicket($ticket, $orders);
    }
}
