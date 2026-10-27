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

    /** Keys that lead to a page of this app (PROJ-34). */
    public const array APP_KEYS = ['angebotsrechner'];

    /**
     * @param  list<EocsOrder>  $orders
     */
    public function __construct(
        public array $orders = [],
        public ?string $ticketUrl = null,
        public ?string $customerEmail = null,
        public ?string $ticketNumber = null,
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

        $number = preg_match('/^[0-9]{1,20}$/', (string) $ticket->number) === 1 ? (string) $ticket->number : null;

        return new self($orders, $ticket->zammadUrl !== '' ? $ticket->zammadUrl : null, $email !== '' ? $email : ReplyTarget::for($ticket)?->to, $number);
    }

    /**
     * Every key a procedure may use.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return [...self::CASE_KEYS, ...self::APP_KEYS, ...array_keys(config('knowledge.order_links', [])), ...array_keys(config('knowledge.tool_links', []))];
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
            'angebotsrechner' => [['url' => route('quote.index', $this->ticketNumber === null ? [] : ['ticket' => $this->ticketNumber]), 'label' => null]],
            'zammad-ticket' => $this->ticketUrl === null ? [] : [['url' => $this->ticketUrl, 'label' => null]],
            'amazon-bestellung' => $this->amazonTargets($key),
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
     * One link per Amazon order of the case: the order number goes into the configured address, only if it
     * has the format of an Amazon order number.
     *
     * @return list<array{url: string, label: string|null}>
     */
    private function amazonTargets(string $key): array
    {
        $template = trim((string) config("knowledge.order_links.{$key}"));

        if (! str_starts_with($template, 'https://') || ! str_contains($template, '{number}')) {
            return [];
        }

        $entries = [];

        foreach ($this->orders as $order) {
            if ($order->channel === 'amazon' && preg_match('/^\d{3}-\d{7}-\d{7}$/', $order->externalNumber) === 1) {
                $entries[] = ['url' => str_replace('{number}', $order->externalNumber, $template), 'label' => $order->externalNumber];
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
