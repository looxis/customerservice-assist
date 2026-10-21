<?php

namespace App\Knowledge;

use App\Eocs\EocsOrder;
use App\Zammad\ReplyTarget;
use App\Zammad\Ticket;

/**
 * What a procedure may link to for one case (PROJ-58): the orders in EOCS, the ticket in Zammad, the e-mail
 * address of the customer for the search in Vanilo, and the fixed addresses of the internal tools. A key without
 * a target gives no link, the text stays. Built per request, never stored.
 */
final readonly class CaseLinks
{
    /** Keys whose target depends on the case. */
    public const array CASE_KEYS = ['eocs-auftrag', 'zammad-ticket'];

    /**
     * @param  list<EocsOrder>  $orders
     */
    public function __construct(
        public array $orders = [],
        public ?string $ticketUrl = null,
        public ?string $customerEmail = null,
    ) {}

    public static function none(): self
    {
        return new self;
    }

    /**
     * The links of a ticket page: its loaded orders, the ticket itself, the customer of the ticket (or, if the
     * ticket has none, the address of the last customer message).
     *
     * @param  list<EocsOrder>  $orders
     */
    public static function forTicket(Ticket $ticket, array $orders): self
    {
        if ($ticket->id === 0) {
            return new self($orders);
        }

        $email = trim((string) $ticket->customerEmail);

        return new self($orders, $ticket->zammadUrl !== '' ? $ticket->zammadUrl : null, $email !== '' ? $email : ReplyTarget::for($ticket)?->to);
    }

    /**
     * Every key a procedure may use.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return [...self::CASE_KEYS, ...array_keys(config('knowledge.tool_links', []))];
    }

    public static function isKnown(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }

    /**
     * The targets of a key; an order gets a label (its number) when there are several.
     *
     * @return list<array{url: string, label: string|null}>
     */
    public function targets(string $key): array
    {
        return match ($key) {
            'eocs-auftrag' => $this->orderTargets(),
            'zammad-ticket' => $this->ticketUrl === null ? [] : [['url' => $this->ticketUrl, 'label' => null]],
            'vanilo-kunden' => $this->fixed($key, $this->customerEmail === null ? null : ['email' => $this->customerEmail, 'order_by' => 'newest_first']),
            default => $this->fixed($key),
        };
    }

    /**
     * @return list<array{url: string, label: string|null}>
     */
    private function orderTargets(): array
    {
        $entries = [];

        foreach ($this->orders as $order) {
            $entries[] = ['url' => $order->url, 'label' => $order->externalNumber];

            foreach ($order->claims as $claim) {
                $entries[] = ['url' => $claim->url, 'label' => $claim->externalNumber];
            }
        }

        return count($entries) === 1 ? [['url' => $entries[0]['url'], 'label' => null]] : $entries;
    }

    /**
     * @param  array<string, string>|null  $query
     * @return list<array{url: string, label: string|null}>
     */
    private function fixed(string $key, ?array $query = null): array
    {
        $url = trim((string) config("knowledge.tool_links.{$key}"));

        if (! str_starts_with($url, 'https://')) {
            return [];
        }

        return [['url' => $query === null ? $url : $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query, '', '&', PHP_QUERY_RFC3986), 'label' => null]];
    }
}
