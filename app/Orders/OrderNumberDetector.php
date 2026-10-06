<?php

namespace App\Orders;

use App\Zammad\Ticket;
use App\Zammad\TicketArticle;

/**
 * Finds order numbers in a ticket thread and cleans up typed-in numbers,
 * using fixed patterns per channel.
 */
class OrderNumberDetector
{
    public const int MAX_SUGGESTIONS = 10;

    /**
     * Clean a typed or pasted order number; null when no known format fits.
     */
    public function normalize(string $input): ?OrderNumber
    {
        $value = preg_replace('/[\s\x{00A0}#]+/u', '', $input) ?? '';
        $value = preg_replace('/^BEST-PRO/i', '', $value) ?? $value;

        foreach (OrderNumberFormat::cases() as $format) {
            $candidate = $format === OrderNumberFormat::Vanilo ? strtoupper($value) : $value;

            if (preg_match($format->pattern(), $candidate, $match) && $match[1] === $candidate) {
                return new OrderNumber($candidate, $format);
            }
        }

        return null;
    }

    /**
     * Order numbers named in the ticket, in order of first appearance; orders
     * recognised from Amazon's notice come first, then the ticket title, then
     * the thread. Quotes count, links do not.
     *
     * @return list<OrderNumber>
     */
    public function detect(Ticket $ticket): array
    {
        $found = [];

        foreach ($ticket->orders as $order) {
            $found[$order->number] = new OrderNumber($order->number, OrderNumberFormat::Amazon);
        }

        $text = $ticket->title."\n".implode("\n", array_map(fn (TicketArticle $article): string => $this->text($article), $ticket->articles));
        $hits = [];

        foreach (OrderNumberFormat::cases() as $format) {
            preg_match_all($format->pattern(), $text, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[1] as [$number, $offset]) {
                $hits[] = [$offset, new OrderNumber($number, $format)];
            }
        }

        usort($hits, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        foreach ($hits as [, $number]) {
            $found[$number->value] ??= $number;
        }

        return array_values($found);
    }

    private function text(TicketArticle $article): string
    {
        $html = $article->body->toHtml().' '.($article->quote?->toHtml() ?? '');

        return html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/div|\/li|\/tr|\/td)\b[^>]*>/i', "\n", $html) ?? $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
