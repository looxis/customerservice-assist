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
 * structure survives, clickable links are removed (except configured trusted
 * hosts) and nothing is loaded from elsewhere. Configured footers are cut off.
 */
class MessageBody
{
    /**
     * Start of a quoted earlier message in German or English mail clients.
     */
    private const string QUOTE_HEADER = '/^\s*(?:'
        .'Am\s.{3,250}?\sschrieb\b.{0,250}?:'
        .'|On\s.{3,250}?\swrote:'
        .'|Op\s.{3,250}?\s(?:heeft|schreef)\b.{0,250}?(?:geschreven|schreef)\s*:'
        .'|Le\s.{3,250}?\sa\s+écrit\s*:'
        .'|-{2,}\s*(?:Ursprüngliche Nachricht|Original Message|Weitergeleitete Nachricht|Forwarded message|Oorspronkelijk bericht|Doorgestuurd bericht|Message d\'origine)\s*-{2,}'
        .'|(?:Anfang der weitergeleiteten Nachricht|Begin forwarded message|Begin doorgestuurd bericht|Début du message réexpédié)\s*:'
        .'|(?:Von|From|Van|De)\s?:\s.{1,240}?(?:Gesendet|Sent|Datum|Date|Verzonden|Envoyé)\s?:'
        .')/isu';

    private const string LINK_REMOVED = '[Link entfernt]';

    private const string BARE_LINK = '/(?:\bhttps?:\/\/|\bwww\.)[^\s<>"\'\x{00A0}]+/iu';

    private const array QUOTE_MARKERS = ['js-signatureMarker', 'gmail_quote', 'moz-cite-prefix', 'divRplyFwdMsg', 'appendonsend', 'OutlookMessageHeader', 'yahoo_quoted'];

    private ?HtmlSanitizer $sanitizer = null;

    /**
     * @param  list<string>  $signatures  Our own text signatures (e.g. on Amazon), hidden at the end of our messages.
     * @param  list<string>  $allowedLinkHosts  Hosts (with their subdomains) whose links stay visible as plain text.
     * @param  list<string>  $clickableLinkHosts  Hosts whose links stay clickable; "*" stands for a country ending (e.g. "sellercentral.amazon.*").
     * @param  list<string>  $footers  Texts from which on the rest of a mail is boilerplate and hidden (e.g. Amazon's notice).
     */
    public function __construct(
        private readonly array $signatures = [],
        private readonly array $allowedLinkHosts = [],
        private readonly array $clickableLinkHosts = [],
        private readonly array $footers = [],
    ) {}

    /**
     * @param  bool  $ours  Message written by us: our signature is hidden.
     * @return array{body: HtmlString, quote: HtmlString|null, order: OrderMention|null}
     */
    public function parse(?string $body, ?string $contentType, bool $ours = false): array
    {
        $body = (string) $body;
        $order = null;

        if (str_contains(strtolower((string) $contentType), 'html')) {
            [$main, $quote, $order] = $this->splitHtml($body, $ours);
        } else {
            [$main, $quote] = array_map(fn (?string $part): ?string => $part === null ? null : $this->plainToHtml($part), $this->splitPlain($this->withoutPlainFooter($body)));
        }

        if ($ours) {
            $main = $this->withoutTextSignature($main);
        }

        $quote = $quote === null ? '' : $this->sanitize($quote);

        return [
            'body' => new HtmlString($this->sanitize($main)),
            'quote' => trim(strip_tags($quote)) === '' ? null : new HtmlString($quote),
            'order' => $order,
        ];
    }

    public function sanitize(string $html): string
    {
        return $this->tidyBlankLines($this->sanitizer()->sanitize($this->withoutLinks($html)));
    }

    /**
     * Clickable links are never needed to handle a complaint and may lead to
     * phishing. None stays clickable: a link to an allowed host remains as
     * plain text, any other becomes "[Link entfernt]" behind its describing text.
     * Addresses written as plain text are left as they are.
     */
    private function withoutLinks(string $html): string
    {
        return preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a\s*>/isu', function (array $match): string {
            $text = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\u{00A0}");
            $href = preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/is', $match[1], $attribute) ? html_entity_decode($attribute[2], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';

            if ($this->isClickableLink($href)) {
                return $text === '' ? '' : '<a href="'.e($href).'">'.e($text).'</a>';
            }

            if ($this->isAllowedLink($href)) {
                return e($text !== '' ? $text : $href);
            }

            return $text === '' || preg_match(self::BARE_LINK, $text) ? self::LINK_REMOVED : e($text).' '.self::LINK_REMOVED;
        }, $html) ?? $html;
    }

    private function isClickableLink(string $href): bool
    {
        $host = strtolower((string) parse_url($href, PHP_URL_HOST));

        if ($host === '' || strtolower((string) parse_url($href, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }

        foreach ($this->clickableLinkHosts as $pattern) {
            $regex = '/^'.str_replace('\\*', '(?:[a-z]{2,3}|co\.[a-z]{2}|com\.[a-z]{2})', preg_quote(strtolower(trim($pattern)), '/')).'$/';

            if (trim($pattern) !== '' && preg_match($regex, $host)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedLink(string $href): bool
    {
        $host = strtolower((string) parse_url($href, PHP_URL_HOST));

        if ($host === '' || ! in_array(strtolower((string) parse_url($href, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return false;
        }

        foreach ($this->allowedLinkHosts as $allowed) {
            $allowed = strtolower(trim($allowed));

            if ($allowed !== '' && ($host === $allowed || str_ends_with($host, '.'.$allowed))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mails often carry many empty lines. Keep at most one blank line, drop
     * empty lines at the start and end and line breaks at the end of a block.
     */
    private function tidyBlankLines(string $html): string
    {
        $space = '(?:\s|&nbsp;|\x{00A0})*';
        $break = '<br\s*\/?>';
        $block = '(?:div|p|h[1-6]|ul|ol|li|blockquote|pre|table)';

        $rules = [
            // Empty blocks become a single line break.
            '/<(div|p)\b[^>]*>'.$space.'(?:'.$break.$space.')*<\/\1>/iu' => '<br />',
            // Line breaks at the end of a block add nothing visible but space.
            '/(?:'.$space.$break.')+'.$space.'(<\/'.$block.'>)/iu' => '$1',
            // After a block, one line break is one blank line; more are collapsed.
            '/(<\/'.$block.'>)'.$space.'(?:'.$break.$space.'){2,}/iu' => '$1<br />',
            // In running text, at most one blank line.
            '/(?:'.$break.$space.'){3,}/iu' => '<br /><br />',
            // Nothing blank at the very start or end.
            '/^(?:'.$space.$break.')+/iu' => '',
            '/(?:'.$break.$space.')+$/iu' => '',
        ];

        do {
            $before = $html;

            foreach ($rules as $pattern => $replacement) {
                $html = preg_replace($pattern, $replacement, $html) ?? $html;
            }
        } while ($html !== $before);

        return trim($html);
    }

    /**
     * Cut the document at the first quote start in reading order, at any depth.
     * Everything from there on (including what follows in enclosing elements)
     * is the quote. Without text before it, nothing is cut.
     *
     * @return array{0: string, 1: string|null, 2: OrderMention|null}
     */
    private function splitHtml(string $html, bool $ours): array
    {
        if (trim($html) === '') {
            return ['', null, null];
        }

        $document = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR, 'UTF-8');
        $body = $document->body;

        if ($ours) {
            foreach (iterator_to_array($document->querySelectorAll('[data-signature]')) as $signature) {
                $signature->remove();
            }
        }

        $order = (new AmazonNotice)->extract($document);
        $footer = $this->findFooterStart($body);

        if ($footer !== null) {
            $this->cutFrom($document, $body, $footer);
        }

        $start = $this->findQuoteStart($body);

        if ($start === null) {
            return [...($this->splitLeadingQuote($document, $body) ?? [$this->innerHtml($document, $body), null]), $order];
        }

        $before = $this->innerHtml($document, $body);
        $quote = $this->cutFrom($document, $body, $start);
        $main = $this->innerHtml($document, $body);

        return trim(strip_tags($main)) === '' ? [$before, null, $order] : [$main, $quote, $order];
    }

    /**
     * A reply written below the quote: the quote opens the message and new text
     * follows after it. The top-level block holding the quote is collapsed.
     *
     * @return array{0: string, 1: string}|null
     */
    private function splitLeadingQuote(HTMLDocument $document, Node $body): ?array
    {
        $quoted = [];

        foreach (iterator_to_array($body->childNodes) as $node) {
            $quoted[] = $node;

            if ($this->hasText($node)) {
                break;
            }
        }

        $block = end($quoted);

        if ($block === false || ! $this->opensWithQuote($block)) {
            return null;
        }

        $rest = array_slice(iterator_to_array($body->childNodes), count($quoted));

        if (! array_filter($rest, fn (Node $node): bool => $this->hasText($node))) {
            return null;
        }

        return [
            implode('', array_map(fn (Node $node): string => $document->saveHtml($node), $rest)),
            implode('', array_map(fn (Node $node): string => $document->saveHtml($node), $quoted)),
        ];
    }

    private function opensWithQuote(Node $node): bool
    {
        if ($this->startsQuote($node)) {
            return true;
        }

        foreach ($node->childNodes as $child) {
            if ($this->opensWithQuote($child)) {
                return true;
            }

            if ($this->hasText($child)) {
                return false;
            }
        }

        return false;
    }

    private function hasText(Node $node): bool
    {
        return trim(str_replace("\u{00A0}", ' ', (string) $node->textContent)) !== '';
    }

    /**
     * The first node in reading order that starts a quote and has text before it.
     */
    private function findQuoteStart(Node $root): ?Node
    {
        $seenText = false;
        $stack = array_reverse(iterator_to_array($root->childNodes));

        while ($stack !== []) {
            $node = array_pop($stack);

            if ($this->startsQuote($node)) {
                if ($seenText) {
                    return $node;
                }

                continue;
            }

            if ($node->nodeType === XML_TEXT_NODE) {
                $seenText = $seenText || $this->hasText($node);

                continue;
            }

            foreach (array_reverse(iterator_to_array($node->childNodes)) as $child) {
                $stack[] = $child;
            }
        }

        return null;
    }

    /**
     * Remove a node and everything that follows it in reading order, also
     * outside its enclosing elements. Returns the removed part as HTML.
     */
    private function cutFrom(HTMLDocument $document, Node $root, Node $start): string
    {
        $removed = [];

        for ($node = $start; $node !== null && $node !== $root; $node = $node->parentNode) {
            for ($sibling = $node === $start ? $node : $node->nextSibling; $sibling !== null; $sibling = $sibling->nextSibling) {
                $removed[] = $sibling;
            }
        }

        $html = implode('', array_map(fn (Node $node): string => $document->saveHtml($node), $removed));

        foreach ($removed as $node) {
            $node->parentNode?->removeChild($node);
        }

        return $html;
    }

    /**
     * The first text node that contains one of the configured footer texts.
     */
    private function findFooterStart(Node $root): ?Node
    {
        if ($this->footers === []) {
            return null;
        }

        $stack = [$root];

        while ($stack !== []) {
            $node = array_pop($stack);

            if ($node->nodeType === XML_TEXT_NODE && $this->containsFooter((string) $node->textContent)) {
                return $node;
            }

            foreach (array_reverse(iterator_to_array($node->childNodes)) as $child) {
                $stack[] = $child;
            }
        }

        return null;
    }

    private function containsFooter(string $text): bool
    {
        $text = mb_strtolower(preg_replace('/[\s\x{00A0}]+/u', ' ', $text) ?? $text);

        foreach ($this->footers as $footer) {
            $footer = mb_strtolower(preg_replace('/\s+/u', ' ', trim($footer)) ?? $footer);

            if ($footer !== '' && str_contains($text, $footer)) {
                return true;
            }
        }

        return false;
    }

    private function withoutPlainFooter(string $text): string
    {
        $lines = preg_split('/\R/u', $text) ?: [$text];

        foreach ($lines as $index => $line) {
            if ($this->containsFooter($line)) {
                return implode("\n", array_slice($lines, 0, $index));
            }
        }

        return $text;
    }

    private function innerHtml(HTMLDocument $document, Node $node): string
    {
        return implode('', array_map(fn (Node $child): string => $document->saveHtml($child), iterator_to_array($node->childNodes)));
    }

    /**
     * Remove one of our configured text signatures at the very end of a message,
     * whatever tags or line breaks sit between its words.
     */
    private function withoutTextSignature(string $html): string
    {
        foreach ($this->signatures as $signature) {
            $words = preg_split('/\s+/u', trim($signature), -1, PREG_SPLIT_NO_EMPTY);

            if ($words === []) {
                continue;
            }

            $gap = '(?:\s|&nbsp;|<[^>]+>)+';
            $pattern = '/'.$gap.'?'.implode($gap, array_map(fn (string $word): string => preg_quote(e($word), '/'), $words)).'(?:\s|&nbsp;|<[^>]+>)*$/iu';
            $stripped = preg_replace($pattern, '', $html, 1);

            if (is_string($stripped) && $stripped !== $html && trim(strip_tags($stripped)) !== '') {
                return $stripped;
            }
        }

        return $html;
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

        return (bool) preg_match(self::QUOTE_HEADER, mb_substr(preg_replace('/^[\s\x{00A0}]+/u', '', (string) $node->textContent), 0, 400));
    }

    private function sanitizer(): HtmlSanitizer
    {
        if ($this->sanitizer !== null) {
            return $this->sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->withMaxInputLength(-1)
            ->allowLinkSchemes(['https'])
            ->allowRelativeLinks(false)
            ->allowElement('a', ['href'])
            ->forceAttribute('a', 'target', '_blank')
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow');

        foreach (['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'u', 'blockquote', 'pre', 'code', 'hr', 'table', 'thead', 'tbody', 'tr', 'td', 'th'] as $element) {
            $config = $config->allowElement($element);
        }

        foreach (['script', 'style', 'head', 'title', 'img', 'picture', 'svg', 'video', 'audio', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'noscript', 'template', 'meta', 'link', 'base'] as $element) {
            $config = $config->dropElement($element);
        }

        return $this->sanitizer = new HtmlSanitizer($config);
    }
}
