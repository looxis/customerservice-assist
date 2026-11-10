<?php

namespace App\Http\Requests;

use App\Labeling\LabelCategory;
use App\Labeling\ReviewQueue;
use App\Staff\TestMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExcludeLabelTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(TestMode::class)->isAvailable($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'delete_in_zammad' => ['nullable', 'boolean'],
            'queue' => ['nullable', 'string', 'max:20'],
            'bereich' => ['nullable', Rule::in(array_keys(ReviewQueue::PARTS))],
            'kategorie' => ['nullable', Rule::enum(LabelCategory::class)],
        ];
    }
}
