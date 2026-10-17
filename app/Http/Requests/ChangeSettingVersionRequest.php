<?php

namespace App\Http\Requests;

use App\Staff\TestMode;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Restoring an earlier version of a settings group or going back to its default (PROJ-27): admins only.
 */
class ChangeSettingVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(TestMode::class)->isAvailable($this);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }
}
