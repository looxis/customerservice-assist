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

    public function __construct(private readonly OrderFormats $formats) {}

    /**
     * The same detection with other formats (to try out unsaved settings).
     *
     * @param  list<OrderNumberFormat>  $formats
     */
    public function using(array $formats): self
    {
        $other = clone $this;
        $other->override = $formats;

        return $other;
    }

    /**
     * @var list<OrderNumberFormat>|null
     */
    private ?array $override = null;

    /**
     * @return list<OrderNumberFormat>
     */
    private function formats(): array
    {
        return $this->override ?? $this->formats->all();
    }

    /**
     * Clean a typed or pasted order number; null when no known format fits.
     */
    public function normalize(string $input): ?OrderNumber
    {
        foreach ($this->formats() as $format) {
            $parsed = $format->parse($input);

            if ($parsed !== null) {
                return new OrderNumber($parsed['number'], $format, $parsed['claim'] === null ? [] : [$parsed['claim']]);
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
            $found[$order->number] = new OrderNumber($order->number, $this->formats->amazon());
        }

        $text = $ticket->title."\n".implode("\n", array_map(fn (TicketArticle $article): string => $this->text($article), $ticket->articles));

        foreach ($this->inText($text) as $number) {
            $found[$number->value] ??= $number;
        }

        return array_values($found);
    }

    /**
     * Order numbers named in a text, in order of first appearance (PROJ-36).
     *
     * @return list<OrderNumber>
     */
    public function inText(string $text): array
    {
        $hits = [];

        foreach ($this->formats() as $format) {
            preg_match_all($format->pattern(), $text, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches['number'] as $index => [$number, $offset]) {
                if ($format->accepts($number)) {
                    $claim = ($matches['claim'][$index][0] ?? '') === '' ? null : (int) substr($matches['claim'][$index][0], strlen($format->claimPrefix));
                    $hits[] = [$offset, new OrderNumber($number, $format, $claim === null ? [] : [$claim])];
                }
            }
        }

        usort($hits, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $found = [];

        foreach ($hits as [, $number]) {
            $known = $found[$number->value] ?? null;
            $claims = array_values(array_unique([...($known?->claims ?? []), ...$number->claims]));
            sort($claims);
            $found[$number->value] = new OrderNumber($number->value, $known?->format ?? $number->format, $claims);
        }

        return array_values($found);
    }

    private function text(TicketArticle $article): string
    {
        $html = $article->body->toHtml().' '.($article->quote?->toHtml() ?? '');

        return html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/div|\/li|\/tr|\/td)\b[^>]*>/i', "\n", $html) ?? $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
