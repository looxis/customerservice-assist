<?php

namespace App\Http\Requests;

use App\Analysis\AnalysisStore;
use App\Analysis\Clarification;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Validator;

/**
 * A follow-up, an addition or the result of an internal clarification to a question without a ticket
 * (PROJ-40, PROJ-39). The stored analysis decides what the option means; only the newest version of
 * a question can be continued, and only by the person who asked it or an admin.
 */
class FollowUpQuestionRequest extends FormRequest
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $parent = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string|In>>
     */
    public function rules(): array
    {
        return [
            'klaerung_analyse' => ['required', 'string', 'max:64'],
            'klaerung_option' => ['nullable', 'string', 'max:100'],
            'klaerung_text' => ['nullable', 'string', 'max:'.config('analysis.max_question_length')],
            'antwortentwurf' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $parent = $this->parentAnalysis();

            if ($parent === null) {
                $validator->errors()->add('klaerung_analyse', 'Dieser Vorschlag ist nicht der neueste oder gehört dir nicht und kann nicht fortgesetzt werden.');
            } elseif (! $this->filled('klaerung_option') && trim((string) $this->input('klaerung_text')) === '') {
                $validator->errors()->add('klaerung_text', 'Bitte eine Option wählen oder eine Nachfrage bzw. das Ergebnis eingeben.');
            } elseif ($this->filled('klaerung_option') && ! Clarification::hasOption($parent, (string) $this->input('klaerung_option'))) {
                $validator->errors()->add('klaerung_option', 'Diese Option gibt es nicht.');
            }
        }];
    }

    /**
     * The stored analysis that is continued, or null when it is unknown, not the newest, or not the person's.
     *
     * @return array<string, mixed>|null
     */
    public function parentAnalysis(): ?array
    {
        if ($this->parent !== null || ! is_string($this->input('klaerung_analyse')) || $this->input('klaerung_analyse') === '') {
            return $this->parent;
        }

        $store = app(AnalysisStore::class);
        $id = $this->input('klaerung_analyse');
        $stored = $store->result($id);
        $number = (string) $this->route('number');

        if ($stored === null || ($stored['ticket'] ?? null) !== $number || $store->latest($number) !== $id) {
            return null;
        }

        $by = (string) app(StaffDirectory::class)->current($this);

        if (! app(TestMode::class)->isAvailable($this) && ! $store->questionBelongsTo($number, $by)) {
            return null;
        }

        return $this->parent = $stored;
    }

    /**
     * Whether a reply draft is wanted: as ticked, or as before when the form did not ask.
     */
    public function replyWanted(): bool
    {
        return $this->has('antwortentwurf') ? $this->boolean('antwortentwurf') : (bool) ($this->parentAnalysis()['question']['reply_wanted'] ?? false);
    }

    /**
     * @return list<array{question: string, answer: string, by: string, at: string}>
     */
    public function decisions(string $by): array
    {
        return Clarification::decisions((array) $this->parentAnalysis(), $this->filled('klaerung_option') ? (string) $this->input('klaerung_option') : null, (string) $this->input('klaerung_text', ''), $by);
    }

    protected function getRedirectUrl(): string
    {
        return route('questions.show', ['number' => $this->route('number')]).'#nachfrage';
    }
}
