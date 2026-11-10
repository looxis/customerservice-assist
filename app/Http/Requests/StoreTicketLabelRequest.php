<?php

namespace App\Http\Requests;

use App\Labeling\LabelCategory;
use App\Models\LabelTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketLabelRequest extends FormRequest
{
    /**
     * Every chosen member of the staff may classify; the route requires a chosen name.
     */
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
            'entscheidung' => ['required', 'string', Rule::in([...array_column(LabelCategory::cases(), 'value'), LabelTicket::UNSURE])],
        ];
    }
}
