<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BackTranslateReplyRequest extends FormRequest
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
            'text' => ['required', 'string', 'max:'.config('analysis.max_reply_length')],
        ];
    }
}
