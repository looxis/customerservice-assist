<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeGapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'missing' => ['required', 'string', 'max:1000'],
            'solution' => ['nullable', 'string', 'max:4000'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'topic' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'missing.required' => 'Bitte angeben, was fehlt.',
            'missing.max' => '„Was fehlt?“ darf höchstens 1.000 Zeichen lang sein.',
            'solution.max' => '„So lösen wir das“ darf höchstens 4.000 Zeichen lang sein.',
            'comment.max' => 'Der Kommentar darf höchstens 2.000 Zeichen lang sein.',
        ];
    }
}
