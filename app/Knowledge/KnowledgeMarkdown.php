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
     * @param  CaseLinks|null  $case  The links of the case for `link:` targets (PROJ-58); without a case only the fixed tool addresses become buttons.
     */
    public function render(string $markdown, array $links = [], ?CaseLinks $case = null): HtmlString
    {
        $html = $this->converter()->convert($markdown)->getContent();

        return new HtmlString($this->toolLinks($this->linkIds($this->demoteHeadings($html), $links), $case ?? CaseLinks::none()));
    }

    /**
     * `[text](link:key)` becomes a button to the target of the key (one per order when there are several), or
     * stays plain text when the case has no target for it.
     */
    private function toolLinks(string $html, CaseLinks $case): string
    {
        return preg_replace_callback(
            '/<a href="link:([a-z0-9-]+)"[^>]*>(.*?)<\/a>/su',
            function (array $match) use ($case): string {
                $targets = $case->targets($match[1]);

                if ($targets === []) {
                    return $match[2];
                }

                return implode(' ', array_map(
                    fn (array $target): string => '<a href="'.e($target['url']).'" target="_blank" rel="noopener noreferrer" class="procedure-link">'.$match[2].($target['label'] === null ? '' : ' ('.e($target['label']).')').'</a>',
                    $targets,
                ));
            },
            $html,
        ) ?? $html;
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
