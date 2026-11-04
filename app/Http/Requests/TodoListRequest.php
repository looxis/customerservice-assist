<?php

namespace App\Http\Requests;

use App\Staff\StaffDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The filter of the ToDo page (PROJ-70).
 */
class TodoListRequest extends FormRequest
{
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
            'zeige' => ['nullable', Rule::in(['alle', 'offen', 'erledigt'])],
            'zustaendig' => ['nullable', 'string', Rule::in(app(StaffDirectory::class)->names())],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
