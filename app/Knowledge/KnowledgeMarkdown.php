<?php

namespace App\Knowledge;

use Illuminate\Support\HtmlString;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Turns the body of a knowledge document into safe HTML: raw HTML is escaped,
 * unsafe links are dropped, images are never loaded, links open in a new tab.
 */
class KnowledgeMarkdown
{
    /**
     * @param  array{types: array<string, array{prefix: string}>}  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * @param  array<string, string>  $links  Knowledge ID => URL of its document view.
     */
    public function render(string $markdown, array $links = []): HtmlString
    {
        $html = $this->converter()->convert($markdown)->getContent();

        return new HtmlString($this->linkIds($this->demoteHeadings($html), $links));
    }

    private function converter(): MarkdownConverter
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => [],
                'open_in_new_window' => true,
                'nofollow' => '',
                'noopener' => 'all',
                'noreferrer' => 'all',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new ExternalLinkExtension);

        $environment->addRenderer(Image::class, new class implements NodeRendererInterface
        {
            public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
            {
                $alt = trim(strip_tags($childRenderer->renderNodes($node->children())));

                return $alt === '' ? '[Bild]' : "[Bild: {$alt}]";
            }
        }, 10);

        return new MarkdownConverter($environment);
    }

    /**
     * The page title is the only h1; headings of the document start at h2.
     */
    private function demoteHeadings(string $html): string
    {
        return preg_replace_callback(
            '/<(\/?)h([1-5])(?=[\s>])/',
            fn (array $match): string => '<'.$match[1].'h'.((int) $match[2] + 1),
            $html,
        );
    }

    /**
     * Link knowledge IDs in the text to their documents. Tags, existing links
     * and code are left untouched.
     *
     * @param  array<string, string>  $links
     */
    private function linkIds(string $html, array $links): string
    {
        if ($links === []) {
            return $html;
        }

        $prefixes = array_column($this->config['types'], 'prefix');
        usort($prefixes, fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $pattern = '/(?<![\w-])(?:'.implode('|', array_map(fn (string $prefix): string => preg_quote($prefix, '/'), $prefixes)).')-\d{3}(?!\d)/';

        $skipDepth = 0;
        $result = '';

        foreach (preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            if (str_starts_with($part, '<')) {
                if (preg_match('/^<(a|code|pre)[\s>]/', $part)) {
                    $skipDepth++;
                } elseif (preg_match('/^<\/(a|code|pre)>/', $part)) {
                    $skipDepth = max(0, $skipDepth - 1);
                }

                $result .= $part;

                continue;
            }

            $result .= $skipDepth > 0 ? $part : preg_replace_callback(
                $pattern,
                fn (array $match): string => isset($links[$match[0]])
                    ? '<a href="'.e($links[$match[0]]).'">'.$match[0].'</a>'
                    : $match[0],
                $part,
            );
        }

        return $result;
    }
}
