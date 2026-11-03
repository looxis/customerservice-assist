<?php

namespace App\Changelog;

use App\Knowledge\KnowledgeMarkdown;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * The changes of the app for the staff, kept by hand in docs/CHANGELOG.md: one heading
 * "## YYYY-MM-DD" per day with bullet points below. Text before the first heading and
 * headings without a valid date are not shown.
 */
final class Changelog
{
    /** Days shown in the notice on the start page: today and the six days before. */
    private const int RECENT_DAYS = 7;

    private const string DAY_HEADING = '/^##[ \t]+(\d{4}-\d{2}-\d{2})[ \t]*$/m';

    public function __construct(private readonly KnowledgeMarkdown $markdown) {}

    /**
     * All days, newest first.
     *
     * @return Collection<int, array{date: CarbonImmutable, html: HtmlString}>
     */
    public function all(): Collection
    {
        $path = config('app.changelog_path');
        $text = is_string($path) && is_file($path) ? (string) file_get_contents($path) : '';

        $parts = preg_split(self::DAY_HEADING, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $days = collect();

        for ($index = 1; $index < count($parts) - 1; $index += 2) {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $parts[$index]);
            $body = trim($parts[$index + 1]);

            if ($date === false || $date->format('Y-m-d') !== $parts[$index] || $body === '') {
                continue;
            }

            $days->push(['date' => $date, 'html' => $this->markdown->render($body)]);
        }

        return $days->sortByDesc(fn (array $day): int => $day['date']->getTimestamp())->values();
    }

    /**
     * The days of the last week, newest first.
     *
     * @return Collection<int, array{date: CarbonImmutable, html: HtmlString}>
     */
    public function recent(): Collection
    {
        $since = CarbonImmutable::today()->subDays(self::RECENT_DAYS - 1);

        return $this->all()->filter(fn (array $day): bool => $day['date']->greaterThanOrEqualTo($since))->values();
    }
}
