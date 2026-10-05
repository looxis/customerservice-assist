<?php

namespace App\Zammad;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;

/**
 * Amazon's buyer-message notice repeats the same header in every mail:
 * "Du hast eine Nachricht erhalten.", the order number, a product table and
 * the label "Nachricht:". This reads the order once and removes the header,
 * so only the buyer's words remain in the thread.
 */
class AmazonNotice
{
    private const string INTRO = '/^(?:Du hast eine Nachricht erhalten|You have received a message)\.?$/iu';

    private const string ORDER = '/^(?:Bestellnummer|Order ID|Order number)\s*:?\s*(\d{3}-\d{7}-\d{7})\s*:?$/iu';

    private const string LABEL = '/^(?:Nachricht|Message)\s*:$/iu';

    /**
     * Remove the notice header from the document and return the order it names.
     */
    public function extract(HTMLDocument $document): ?OrderMention
    {
        $body = $document->body;

        if ($body === null || ! preg_match('/\d{3}-\d{7}-\d{7}/', (string) $body->textContent)) {
            return null;
        }

        $intro = $this->first($body, ['p', 'div', 'td', 'span'], self::INTRO);
        $orderNode = $this->first($body, ['p', 'div', 'td', 'span'], self::ORDER);

        if ($intro === null || $orderNode === null) {
            return null;
        }

        preg_match(self::ORDER, $this->text($orderNode), $match);
        [$table, $items] = $this->productTable($body);

        foreach ([$intro, $orderNode, $table, $this->first($body, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div'], self::LABEL)] as $node) {
            $node?->remove();
        }

        return new OrderMention($match[1], 'Amazon-Nachricht', $items);
    }

    /**
     * The table whose header row reads "#", "ASIN", "Produktname".
     *
     * @return array{0: Element|null, 1: list<OrderMentionItem>}
     */
    private function productTable(Node $body): array
    {
        foreach ($body->getElementsByTagName('table') as $table) {
            $rows = [];

            foreach ($table->getElementsByTagName('tr') as $row) {
                $cells = array_filter(iterator_to_array($row->children), fn (Element $cell): bool => in_array(strtolower($cell->nodeName), ['td', 'th'], true));
                $rows[] = array_values(array_map(fn (Element $cell): string => $this->text($cell), $cells));
            }

            $header = array_map('mb_strtolower', $rows[0] ?? []);
            $asin = array_search('asin', $header, true);
            $name = array_search('produktname', $header, true) ?: array_search('product name', $header, true);

            if ($asin === false || $name === false) {
                continue;
            }

            $items = [];

            foreach (array_slice($rows, 1) as $row) {
                if (($row[$name] ?? '') !== '') {
                    $items[] = new OrderMentionItem($row[$name], ($row[$asin] ?? '') !== '' ? $row[$asin] : null);
                }
            }

            return [$table, $items];
        }

        return [null, []];
    }

    /**
     * The innermost matching element (no matching element inside it).
     *
     * @param  list<string>  $tags
     */
    private function first(Node $root, array $tags, string $pattern): ?Element
    {
        foreach ($root->querySelectorAll(implode(',', $tags)) as $element) {
            if (preg_match($pattern, $this->text($element))) {
                foreach ($element->querySelectorAll(implode(',', $tags)) as $inner) {
                    if (preg_match($pattern, $this->text($inner))) {
                        continue 2;
                    }
                }

                return $element;
            }
        }

        return null;
    }

    private function text(Node $node): string
    {
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', (string) $node->textContent) ?? '');
    }
}
