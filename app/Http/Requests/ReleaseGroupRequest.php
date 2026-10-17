<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Prüfung abgeschlossen, Gruppe freigeben" (PROJ-50): admins only. The binding rules that stay excluded for the group
 * are confirmed one by one.
 */
class ReleaseGroupRequest extends FormRequest
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
            'version' => ['required', 'integer', 'min:0'],
            'confirmed' => ['sometimes', 'array'],
            'confirmed.*' => ['string', 'max:80'],
        ];
    }
}
