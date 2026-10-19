<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The decision about a document in its trial period (PROJ-56), admins only.
 */
class ChangeDocumentTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(TestMode::class)->isAvailable($this);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:permanent,extend,end'],
            'fingerprint' => ['required', 'string', 'size:64'],
            'until' => ['required_if:decision,extend', 'nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'until.required_if' => 'Bitte das neue Enddatum der Testphase angeben.',
            'until.date_format' => 'Bitte ein Datum im Format JJJJ-MM-TT angeben.',
        ];
    }
}
