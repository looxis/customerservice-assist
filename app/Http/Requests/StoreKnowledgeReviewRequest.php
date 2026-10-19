<?php

namespace App\Http\Requests;

use App\Knowledge\KnowledgeReviewLog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Bitte prüfen" about a knowledge document (PROJ-56).
 */
class StoreKnowledgeReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'document_id' => ['required', 'string', 'regex:/^[A-Z][A-Z-]*-\d{3}$/'],
            'reason' => ['required', Rule::in(array_keys(KnowledgeReviewLog::REASONS))],
            'comment' => ['nullable', 'string', 'max:1000'],
            'analysis' => ['nullable', 'uuid'],
            'ticket' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Bitte einen Grund wählen.',
            'comment.max' => 'Der Kommentar darf höchstens 1.000 Zeichen lang sein.',
        ];
    }
}
