<?php

namespace App\Analysis;

use App\Models\Analysis;
use App\Models\ProductQuestion;
use App\Models\ProductQuestionState;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Detail questions of customers that the product information does not answer (PROJ-57): counted
 * silently per ticket (or question), product and topic, evaluated on the admin page „Häufige
 * Produktfragen“. The product is the master of a variant, so one question about any size counts once.
 */
class ProductQuestionLog
{
    public const array STATUSES = ['open' => 'Offen', 'ignored' => 'Ignoriert', 'done' => 'Erledigt'];

    /**
     * The products a detail question of this case can belong to, keyed by the name the model sees.
     *
     * @return array<string, array{key: string, name: string}>
     */
    public function choices(AnalysisRequest $request): array
    {
        $choices = [];

        foreach ($request->productPackage->affected() as $product) {
            $master = $product->master;
            $name = $master !== null ? $master['name'] : $product->name;
            $choices[$name] ??= ['key' => 'pf:'.($master !== null ? $master['id'] : $product->id), 'name' => $name];
        }

        foreach ($request->productPackage->affectedEocs() as $item) {
            $choices[$item->name] ??= ['key' => 'eocs:'.$item->itemId, 'name' => $item->name];
        }

        if ($choices === []) {
            foreach ($request->products as $slug) {
                $choices[$slug] = ['key' => 'kb:'.$slug, 'name' => $slug];
            }
        }

        if ($choices === [] && $request->unknownProduct !== null && trim($request->unknownProduct) !== '') {
            $name = trim($request->unknownProduct);
            $choices[$name] = ['key' => 'x:'.mb_substr(mb_strtolower($name), 0, 70), 'name' => $name];
        }

        return $choices;
    }

    /**
     * The keywords used so far for these products, most often first, for the model to reuse.
     *
     * @param  list<string>  $productKeys
     * @return array<string, list<string>>
     */
    public function knownTopics(array $productKeys): array
    {
        if ($productKeys === []) {
            return [];
        }

        $rows = ProductQuestion::query()->where('test_run', false)->whereIn('product_key', $productKeys)
            ->selectRaw('product_key, topic, count(*) as total')->groupBy('product_key', 'topic')->orderByDesc('total')->get();
        $limit = (int) config('analysis.product_questions.known_topics');

        return $rows->groupBy('product_key')->map(fn (Collection $topics): array => $topics->take($limit)->pluck('topic')->all())->all();
    }

    /**
     * Keeps the detail questions of a stored suggestion: one per ticket (or question), product and topic;
     * a repeated suggestion only refreshes the wording.
     *
     * @param  list<array{product: ?string, topic: string, question: string}>  $questions
     * @param  array<string, array{key: string, name: string}>  $choices
     */
    public function report(string $analysisId, array $questions, array $choices): int
    {
        $analysis = Analysis::query()->where('uuid', $analysisId)->whereNotNull('content')->first();

        if ($analysis === null) {
            return 0;
        }

        $kept = 0;

        foreach ($questions as $question) {
            $topic = self::normalize($question['topic']);
            $choice = count($choices) === 1 ? array_values($choices)[0] : ($choices[(string) ($question['product'] ?? '')] ?? null);

            if ($topic === '') {
                continue;
            }

            ProductQuestion::query()->updateOrCreate(
                ['ticket_number' => $analysis->ticket_number, 'product_key' => $choice['key'] ?? '', 'topic' => $topic, 'test_run' => $analysis->test_until !== null],
                ['analysis_id' => $analysis->id, 'product_name' => $choice['name'] ?? null, 'question' => $question['question'], 'customer_group' => $analysis->customer_group],
            );
            $kept++;
        }

        return $kept;
    }

    /**
     * The topics for the admin page, most urgent first. Open topics appear from the threshold on unless
     * "all" is set; a topic marked done starts counting again with the next question.
     *
     * @return list<array{product_key: string, product_name: string|null, topic: string, status: string, recent: int, total: int, last: CarbonImmutable, since: CarbonImmutable|null}>
     */
    public function topics(string $status = 'open', ?string $productKey = null, bool $all = false): array
    {
        $cutoff = CarbonImmutable::now()->subMonths((int) config('analysis.product_questions.window_months'));
        $threshold = (int) config('analysis.product_questions.threshold');

        $rows = DB::table('product_questions as q')
            ->leftJoin('product_question_states as s', fn ($join) => $join->on('s.product_key', '=', 'q.product_key')->on('s.topic', '=', 'q.topic'))
            ->where('q.test_run', false)
            ->get(['q.product_key', 'q.product_name', 'q.topic', 'q.created_at', 's.status as state', 's.changed_at']);

        $topics = [];

        foreach ($rows->groupBy(fn (object $row): string => self::topicKey($row->product_key, $row->topic)) as $group) {
            $first = $group->first();
            $changedAt = $first->changed_at === null ? null : CarbonImmutable::parse($first->changed_at);
            $dates = $group->map(fn (object $row): CarbonImmutable => CarbonImmutable::parse($row->created_at));
            $after = $changedAt === null ? $dates : $dates->filter(fn (CarbonImmutable $date): bool => $date->greaterThan($changedAt));
            $topicStatus = match ($first->state) {
                'ignored' => 'ignored',
                'done' => $after->isEmpty() ? 'done' : 'open',
                default => 'open',
            };
            $counted = $topicStatus === 'done' ? $dates : ($first->state === 'done' ? $after : $dates);

            $topics[] = [
                'product_key' => (string) $first->product_key,
                'product_name' => $group->pluck('product_name')->filter()->last(),
                'topic' => (string) $first->topic,
                'status' => $topicStatus,
                'recent' => $counted->filter(fn (CarbonImmutable $date): bool => $date->greaterThanOrEqualTo($cutoff))->count(),
                'total' => $counted->count(),
                'last' => $counted->max(),
                'since' => $first->state === 'done' && $topicStatus === 'open' ? $changedAt : null,
            ];
        }

        return collect($topics)
            ->filter(fn (array $topic): bool => $topic['status'] === $status
                && ($productKey === null || $topic['product_key'] === $productKey)
                && ($status !== 'open' || $all || $topic['recent'] >= $threshold))
            ->sortBy([['recent', 'desc'], ['total', 'desc'], ['last', 'desc']])
            ->values()
            ->all();
    }

    /**
     * How many topics there are per status (open ones from the threshold on) for the tabs.
     *
     * @return array<string, int>
     */
    public function counts(?string $productKey = null): array
    {
        return collect(array_keys(self::STATUSES))->mapWithKeys(fn (string $status): array => [$status => count($this->topics($status, $productKey))])->all();
    }

    /**
     * The products that have questions, for the filter.
     *
     * @return array<string, string>
     */
    public function products(): array
    {
        return ProductQuestion::query()->where('test_run', false)->orderBy('product_name')->get(['product_key', 'product_name'])
            ->unique('product_key')->mapWithKeys(fn (ProductQuestion $question): array => [$question->product_key => $question->product_name ?? 'ohne Produkt'])->all();
    }

    /**
     * The wordings of the listed topics, newest first, by `topicKey()`. Only questions that count
     * (after the topic was marked done).
     *
     * @param  list<array{product_key: string, topic: string, since: CarbonImmutable|null}>  $topics
     * @return array<string, Collection<int, ProductQuestion>>
     */
    public function entries(array $topics): array
    {
        if ($topics === []) {
            return [];
        }

        $since = collect($topics)->mapWithKeys(fn (array $topic): array => [self::topicKey($topic['product_key'], $topic['topic']) => $topic['since']]);
        $found = ProductQuestion::query()->where('test_run', false)
            ->whereIn('product_key', collect($topics)->pluck('product_key')->unique()->all())
            ->whereIn('topic', collect($topics)->pluck('topic')->unique()->all())
            ->latest()->latest('id')->get();

        $grouped = [];

        foreach ($found as $question) {
            $key = self::topicKey($question->product_key, $question->topic);

            if (! $since->has($key) || ($since[$key] !== null && ! $question->created_at->greaterThan($since[$key]))) {
                continue;
            }

            $grouped[$key] ??= collect();
            $grouped[$key]->push($question);
        }

        return $grouped;
    }

    /**
     * Marks a topic done or ignored, or opens it again (removes the mark).
     */
    public function mark(string $productKey, string $topic, string $status, string $staff): void
    {
        if ($status === 'open') {
            ProductQuestionState::query()->where('product_key', $productKey)->where('topic', $topic)->delete();

            return;
        }

        ProductQuestionState::query()->updateOrCreate(
            ['product_key' => $productKey, 'topic' => $topic],
            ['status' => $status, 'changed_by' => $staff, 'changed_at' => now()],
        );
    }

    /**
     * Text for the AI chat: the topic with the wordings, as a request to check a ProductForge entry.
     *
     * @param  array{product_name: string|null, topic: string, recent: int, total: int}  $topic
     * @param  Collection<int, ProductQuestion>  $entries
     */
    public function chatText(array $topic, Collection $entries): string
    {
        return implode("\n", [
            'Häufige Kundenfrage aus der Customer Service Assist App – bitte prüfen, ob das als Angabe in ProductForge (Beschreibung, Kundenservice-Wissen oder Bedienungsanleitung) ergänzt werden sollte.',
            '',
            'Produkt: '.($topic['product_name'] ?? 'ohne Produkt'),
            'Thema: '.$topic['topic'],
            'Anfragen: '.$topic['recent'].' in den letzten '.config('analysis.product_questions.window_months').' Monaten, '.$topic['total'].' insgesamt',
            'So haben Kunden gefragt:',
            ...$entries->take(10)->map(fn (ProductQuestion $entry): string => '- '.$entry->question)->all(),
        ]);
    }

    /**
     * Deletes the questions of topics that have been done for longer than the retention period (and the mark);
     * open and ignored topics stay.
     *
     * @return int Deleted questions.
     */
    public function purge(): int
    {
        $before = now()->subMonths((int) config('analysis.product_questions.done_retention_months'));
        $deleted = 0;

        foreach (ProductQuestionState::query()->where('status', 'done')->where('changed_at', '<', $before)->get() as $state) {
            $deleted += ProductQuestion::query()->where('product_key', $state->product_key)->where('topic', $state->topic)->where('created_at', '<=', $state->changed_at)->delete();
            $state->delete();
        }

        return $deleted;
    }

    /**
     * The key of a topic in the lists of this class.
     */
    public static function topicKey(string $productKey, string $topic): string
    {
        return $productKey."\u{1F}".$topic;
    }

    /**
     * A topic as a short lowercase keyword.
     */
    public static function normalize(string $topic): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($topic))), 0, 80);
    }
}
