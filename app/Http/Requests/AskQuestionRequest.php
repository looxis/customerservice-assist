<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ChecksProductChoice;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelector;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Validator;

/**
 * A question to the knowledge base without a ticket (PROJ-40).
 */
class AskQuestionRequest extends FormRequest
{
    use ChecksProductChoice;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string|In>>
     */
    public function rules(): array
    {
        $selector = app(KnowledgeSelector::class);

        return [
            'text' => ['required', 'string', 'max:'.config('analysis.max_question_length')],
            'kundengruppe' => ['required', 'string', Rule::in(array_map(fn (CustomerGroup $group): string => $group->key, $selector->customerGroups()))],
            'produkte' => ['sometimes', 'array', 'max:10'],
            'produkte.*' => ['string', Rule::in(array_column($selector->products(), 'slug'))],
            'antwortentwurf' => ['nullable', 'boolean'],
            ...$this->productRules(),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkProductChoice($validator)];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['text' => 'Frage', 'kundengruppe' => 'Kundengruppe', 'produkte.*' => 'Produkt'];
    }

    protected function getRedirectUrl(): string
    {
        return route('questions.create');
    }
}
