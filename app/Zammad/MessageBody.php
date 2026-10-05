<?php

namespace App\Zammad;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Illuminate\Support\HtmlString;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Turns the body of a Zammad message into safe HTML and splits off a quoted
 * earlier message at its end. Mail from outside is never trusted: only text
 * structure and links survive, nothing is loaded from elsewhere.
 */
class MessageBody
{
    /**
     * Start of a quoted earlier message in German or English mail clients.
     */
    private const string QUOTE_HEADER = '/^\s*(?:Am\s.{3,160}?\sschrieb\b.{0,160}?:|On\s.{3,160}?\swrote:|-{2,}\s*(?:Ursprüngliche Nachricht|Original Message|Weitergeleitete Nachricht|Forwarded message)\s*-{2,}|(?:Von|From):\s.{1,240}?(?:Gesendet|Sent|Datum|Date):)/isu';

    private const array QUOTE_MARKERS = ['gmail_quote', 'moz-cite-prefix', 'divRplyFwdMsg', 'appendonsend', 'OutlookMessageHeader', 'yahoo_quoted'];

    private ?HtmlSanitizer $sanitizer = null;

    /**
     * @return array{body: HtmlString, quote: HtmlString|null}
     */
    public function parse(?string $body, ?string $contentType): array
    {
        $body = (string) $body;

        [$main, $quote] = str_contains(strtolower((string) $contentType), 'html')
            ? $this->splitHtml($body)
            : array_map(fn (?string $part): ?string => $part === null ? null : $this->plainToHtml($part), $this->splitPlain($body));

        $quote = $quote === null ? '' : $this->sanitize($quote);

        return [
            'body' => new HtmlString($this->sanitize($main)),
            'quote' => trim(strip_tags($quote)) === '' ? null : new HtmlString($quote),
        ];
    }

    public function sanitize(string $html): string
    {
        return trim($this->sanitizer()->sanitize($html));
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function splitHtml(string $html): array
    {
        if (trim($html) === '') {
            return ['', null];
        }

        $document = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR, 'UTF-8');
        $container = $document->body;

        // Many mails wrap everything in a single <div>; look inside it.
        while ($container !== null && $this->onlyChild($container) !== null) {
            $container = $this->onlyChild($container);
        }

        $nodes = iterator_to_array($container?->childNodes ?? []);

        foreach ($nodes as $index => $node) {
            if ($index > 0 && $this->startsQuote($node) && $this->hasText(array_slice($nodes, 0, $index))) {
                return [
                    $this->serialize($document, array_slice($nodes, 0, $index)),
                    $this->serialize($document, array_slice($nodes, $index)),
                ];
            }
        }

        return [$html, null];
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function splitPlain(string $text): array
    {
        $lines = preg_split('/\R/u', str_replace("\r\n", "\n", $text));

        foreach ($lines as $index => $line) {
            if ($index > 0 && (str_starts_with(ltrim($line), '>') || preg_match(self::QUOTE_HEADER, $line))
                && trim(implode("\n", array_slice($lines, 0, $index))) !== '') {
                return [implode("\n", array_slice($lines, 0, $index)), implode("\n", array_slice($lines, $index))];
            }
        }

        return [$text, null];
    }

    private function plainToHtml(string $text): string
    {
        $paragraphs = preg_split('/\n\s*\n/u', trim(str_replace("\r\n", "\n", $text)));

        return implode('', array_map(
            fn (string $paragraph): string => '<p>'.nl2br(e($paragraph), false).'</p>',
            array_filter($paragraphs, fn (string $paragraph): bool => trim($paragraph) !== ''),
        ));
    }

    private function onlyChild(Node $node): ?Node
    {
        $elements = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE && trim($child->textContent) !== '') {
                return null;
            }

            if ($child->nodeType === XML_ELEMENT_NODE) {
                $elements[] = $child;
            }
        }

        return count($elements) === 1 && in_array(strtolower($elements[0]->nodeName), ['div', 'span', 'font', 'center', 'section', 'article'], true)
            ? $elements[0]
            : null;
    }

    private function startsQuote(Node $node): bool
    {
        if ($node instanceof Element) {
            if (strtolower($node->nodeName) === 'blockquote') {
                return true;
            }

            $marker = $node->getAttribute('class').' '.$node->getAttribute('id');

            foreach (self::QUOTE_MARKERS as $known) {
                if (str_contains($marker, $known)) {
                    return true;
                }
            }
        }

        return (bool) preg_match(self::QUOTE_HEADER, mb_substr(trim((string) $node->textContent), 0, 400));
    }

    /**
     * @param  list<Node>  $nodes
     */
    private function hasText(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (trim((string) $node->textContent) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<Node>  $nodes
     */
    private function serialize(HTMLDocument $document, array $nodes): string
    {
        return implode('', array_map(fn (Node $node): string => $document->saveHtml($node), $nodes));
    }

    private function sanitizer(): HtmlSanitizer
    {
        if ($this->sanitizer !== null) {
            return $this->sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->withMaxInputLength(-1)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks(false)
            ->forceAttribute('a', 'target', '_blank')
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow');

        foreach (['p', 'br', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'u', 'blockquote', 'pre', 'code', 'hr', 'table', 'thead', 'tbody', 'tr', 'td', 'th'] as $element) {
            $config = $config->allowElement($element);
        }

        $config = $config->allowElement('a', ['href']);

        foreach (['script', 'style', 'head', 'title', 'img', 'picture', 'svg', 'video', 'audio', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'noscript', 'template', 'meta', 'link', 'base'] as $element) {
            $config = $config->dropElement($element);
        }

        return $this->sanitizer = new HtmlSanitizer($config);
    }
}
