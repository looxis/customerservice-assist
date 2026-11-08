<?php

namespace App\Labeling;

use App\Analysis\AnalysisException;
use App\Analysis\LanguageModel;
use App\Analysis\Prompt;
use App\Models\LabelPrediction;
use App\Models\LabelTicket;

/**
 * Asks the language model for the category of one ticket and files the answer (PROJ-78).
 */
class LabelClassifier
{
    public function __construct(
        private readonly LanguageModel $model,
        private readonly ExampleSelector $examples,
        private readonly LabelingSettings $settings,
    ) {}

    public static function modelName(): string
    {
        return (string) (config('labeling.model') ?: config('analysis.models.analysis'));
    }

    /**
     * @throws AnalysisException
     */
    public function classify(LabelTicket $ticket, ?int $runId = null): LabelPrediction
    {
        $settings = $this->settings->all();
        $prompt = Prompt::load('labeling');
        $examples = $this->examples->forPrompt($settings['examples']);
        $result = $this->model->ask(new LabelAgent($prompt->text.$examples['text']), $ticket->classifierInput((int) config('labeling.max_text_length')), self::modelName(), 'labeling', $ticket->number);
        $data = $result['data'];

        $category = LabelCategory::tryFrom((string) ($data['category'] ?? ''))?->value;
        $unclear = (bool) ($data['unclear'] ?? false);
        $multiple = (bool) ($data['multiple_concerns'] ?? false);
        $confidence = max(0.0, min(1.0, (float) ($data['confidence'] ?? 0)));

        $prediction = $ticket->predictions()->create([
            'label_run_id' => $runId,
            'category' => $category,
            'unclear' => $unclear,
            'multiple_concerns' => $multiple,
            'confidence' => $confidence,
            'reason' => trim((string) ($data['reason'] ?? '')),
            'evidence' => trim((string) ($data['evidence'] ?? '')),
            'queue' => $this->queue($ticket, $category, $unclear, $multiple, $confidence, $settings),
            'model' => self::modelName(),
            'prompt_version' => $prompt->version.($examples['ids'] === [] ? '' : '+B'.count($examples['ids'])),
            'example_ids' => $examples['ids'],
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
        ]);

        // Removed while the model answered: the removal wins and the proposal is dropped.
        if (LabelTicket::query()->whereKey($ticket->id)->where('state', LabelTicket::EXCLUDED)->exists()) {
            $prediction->delete();

            return $prediction;
        }

        $ticket->update(['state' => 'classified', 'problem' => null]);

        return $prediction;
    }

    /**
     * Which review queue a proposal belongs to, null for a clear one that no admin has to look at. A clear
     * proposal lands in the random sample with the chance set on the page. A decided ticket comes back only
     * when the new proposal differs from the decision; a ticket of the test set is reviewed in its own queue.
     *
     * @param  array<string, mixed>  $settings
     */
    private function queue(LabelTicket $ticket, ?string $category, bool $unclear, bool $multiple, float $confidence, array $settings): ?string
    {
        // A ticket that carried a tag already is a reference and is never reviewed again.
        if ($ticket->decision !== null) {
            return $ticket->decision_origin !== 'vorhanden' && $ticket->decision !== ($category ?? LabelTicket::NONE) ? 'abweichung' : null;
        }

        if ($ticket->isTest()) {
            return null;
        }

        return match (true) {
            $unclear || $multiple => 'zweifel',
            $confidence < $settings['confidence'] || $category === null => 'konfidenz',
            in_array($category, [LabelCategory::Storno->value, LabelCategory::Adressaenderung->value], true) => 'storno',
            $category !== LabelCategory::Wismo->value && str_contains(mb_strtolower((string) $ticket->text), 'storno') => 'storno-wort',
            random_int(1, 100) <= $settings['sample_share'] => 'stichprobe',
            default => null,
        };
    }
}
