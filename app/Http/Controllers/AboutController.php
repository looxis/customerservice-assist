<?php

namespace App\Http\Controllers;

use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeMarkdown;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

class AboutController extends Controller
{
    private const string DEVELOPER_HEADING = '/^#{1,6}[ \t]+Für Entwickler[ \t]*$/mu';

    private const string WORK_IN_PROGRESS = '[in Arbeit]';

    /**
     * The "Über die App" page: the text of docs/ABOUT.md, split at the
     * developer heading, with the current app and knowledge state in between.
     */
    public function __invoke(KnowledgeLibrary $library, KnowledgeMarkdown $markdown): View
    {
        $path = config('app.about_path');
        $text = is_file($path) ? trim((string) file_get_contents($path)) : '';

        $parts = preg_split(self::DEVELOPER_HEADING, $text, 2);
        $usable = $library->usable();

        return view('about', [
            'general' => $this->render($markdown, $parts[0] ?? ''),
            'developer' => $this->render($markdown, $parts[1] ?? ''),
            'version' => config('app.version'),
            'state' => $library->state(),
            'counts' => [
                'usable' => $usable->count(),
                'draft' => $usable->where('status', 'draft')->count(),
            ],
            'knowledgeMessage' => $library->all()->isEmpty() ? ($library->issues()->first()?->message ?? 'Der Knowledge-Ordner enthält keine Dokumente.') : null,
        ]);
    }

    private function render(KnowledgeMarkdown $markdown, string $text): ?HtmlString
    {
        if (trim($text) === '') {
            return null;
        }

        return new HtmlString($this->markWorkInProgress($markdown->render(trim($text))->toHtml()));
    }

    /**
     * Turn the "[in Arbeit]" marker into a badge; code keeps the marker as written.
     */
    private function markWorkInProgress(string $html): string
    {
        $codeDepth = 0;
        $result = '';

        foreach (preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            if (str_starts_with($part, '<')) {
                if (preg_match('/^<(code|pre)[\s>]/', $part)) {
                    $codeDepth++;
                } elseif (preg_match('/^<\/(code|pre)>/', $part)) {
                    $codeDepth = max(0, $codeDepth - 1);
                }

                $result .= $part;

                continue;
            }

            $result .= $codeDepth > 0 ? $part : str_replace(self::WORK_IN_PROGRESS, '<span class="about-wip">in Arbeit</span>', $part);
        }

        return $result;
    }
}
