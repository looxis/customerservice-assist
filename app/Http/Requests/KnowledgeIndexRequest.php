<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KnowledgeIndexRequest extends FormRequest
{
    /**
     * An invalid filter sends the user back to the unfiltered overview.
     *
     * @var string
     */
    protected $redirectRoute = 'knowledge.index';

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
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', Rule::in(array_keys(config('knowledge.types')))],
            'status' => ['nullable', 'string', Rule::in(config('knowledge.statuses'))],
            'issues' => ['nullable', Rule::in(['1'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'q' => 'Suche',
            'type' => 'Typ',
            'status' => 'Status',
            'issues' => 'nur mit Meldungen',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.max' => 'Der Suchbegriff darf höchstens 100 Zeichen lang sein.',
            'q.string' => 'Der Suchbegriff ist ungültig.',
            'type.in' => 'Diesen Dokumenttyp gibt es nicht.',
            'status.in' => 'Diesen Status gibt es nicht.',
            'issues.in' => 'Der Filter „nur mit Meldungen" ist ungültig.',
        ];
    }

    /**
     * The validated filters with empty values removed.
     *
     * @return array{q?: string, type?: string, status?: string, issues?: string}
     */
    public function filters(): array
    {
        return array_filter($this->validated(), fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
