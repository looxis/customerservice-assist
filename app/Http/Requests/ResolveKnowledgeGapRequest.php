<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveKnowledgeGapRequest extends FormRequest
{
    /**
     * Only admins work through reported gaps.
     */
    public function authorize(TestMode $testMode): bool
    {
        return $testMode->isAvailable($this);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['open', 'done', 'discarded'])],
            'knowledge_id' => ['nullable', 'string', 'max:40'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
