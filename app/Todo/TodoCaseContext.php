<?php

namespace App\Todo;

use App\Analysis\ComplaintDecision;
use App\Eocs\OrderLookup;
use App\Orders\OrderFormats;
use App\Orders\OrderNumber;
use App\Zammad\Ticket;

/**
 * What the case tells for the prepared replies and the preselected ToDo (PROJ-70): the customer's name and the
 * number the shipment belongs to. For a complaint that is the claim number ("R1-<order>"), not the original order.
 */
class TodoCaseContext
{
    public function __construct(private readonly OrderFormats $formats) {}

    /**
     * The claim number is taken from the reply text when it names one, else from the newest claim of the order in
     * EOCS, else from the ticket; without any claim it is the first selected order.
     *
     * @param  list<OrderNumber>  $selected
     * @param  list<OrderLookup>  $lookups
     * @param  list<string>  $ticketClaims  Claim numbers the ticket names.
     * @param  array<string, mixed>  $formInput  The stored inputs of the analysis; the complaint form (PROJ-86) decides the preselection and the claim number.
     * @return array{name: string, kind: string, number: string, preselect: bool}
     */
    public function build(Ticket $ticket, array $selected, array $lookups, array $ticketClaims, string $replyText, array $formInput = []): array
    {
        $name = trim((string) $ticket->customerName);
        $eocsClaims = [];

        foreach ($lookups as $lookup) {
            foreach ($lookup->orders as $order) {
                foreach ($order->claims as $claim) {
                    $eocsClaims[] = $claim->externalNumber;
                }
            }
        }

        $named = $this->namedInText($replyText, [...$ticketClaims, ...$eocsClaims], $selected);
        $number = $named ?? $this->preferred($eocsClaims, $ticketClaims) ?? ($ticketClaims[0] ?? null);
        $preselect = $named !== null;
        $name = $name === '' || str_contains($name, '@') || mb_strlen($name) > 80 ? '' : $name;
        $complaint = ComplaintDecision::fromInput($formInput);

        if ($complaint !== null) {
            // The complaint form decides: a justified complaint with the ToDo ticked gets it, whatever the text names. Without
            // a claim number the ToDo is prepared but not automatic (no reference to look for).
            $preselect = $complaint->isJustified() && $complaint->trackingTodo;

            return ['name' => $name, 'kind' => 'claim', 'number' => $complaint->claimNumber, 'preselect' => $preselect];
        }

        return [
            'name' => $name,
            'kind' => $number === null ? 'order' : 'claim',
            'number' => $number ?? ($selected[0]->value ?? ''),
            'preselect' => $preselect,
        ];
    }

    /**
     * @param  list<string>  $known
     * @param  list<OrderNumber>  $selected
     */
    private function namedInText(string $text, array $known, array $selected): ?string
    {
        foreach ($known as $number) {
            if (stripos($text, $number) !== false) {
                return $number;
            }
        }

        $prefix = preg_quote($this->formats->claimPrefix(), '/');

        foreach ($selected as $order) {
            if (preg_match('/\b'.$prefix.'\d+-'.preg_quote($order->value, '/').'\b/i', $text, $match) === 1) {
                return strtoupper($match[0]);
            }
        }

        return null;
    }

    /**
     * The newest claim in EOCS (the highest number), preferring one the ticket names.
     *
     * @param  list<string>  $eocsClaims
     * @param  list<string>  $ticketClaims
     */
    private function preferred(array $eocsClaims, array $ticketClaims): ?string
    {
        foreach ($ticketClaims as $number) {
            if (in_array(strtoupper($number), array_map('strtoupper', $eocsClaims), true)) {
                return $number;
            }
        }

        if ($eocsClaims === []) {
            return null;
        }

        $count = fn (string $number): int => preg_match('/^[A-Za-z]+(\d+)-/', $number, $match) === 1 ? (int) $match[1] : 0;
        usort($eocsClaims, fn (string $a, string $b): int => $count($b) <=> $count($a));

        return $eocsClaims[0];
    }
}
