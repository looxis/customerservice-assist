<?php

namespace App\Todo;

use App\Staff\StaffDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The rules for the fields of a ToDo (PROJ-70), shared by the ToDo forms and the ToDo rows of the reply form.
 */
class TodoRules
{
    /**
     * @param  string  $prefix  "" for a single ToDo, "todos.*." for the rows of the reply form.
     * @param  bool  $keepDue  Whether "keep" is a valid choice of the due date (editing).
     * @return array<string, list<ValidationRule|string>>
     */
    public static function fields(string $prefix = '', bool $keepDue = false): array
    {
        return [
            $prefix.'description' => ['required', 'string', 'max:1000'],
            $prefix.'assignee' => ['nullable', 'string', Rule::in(app(StaffDirectory::class)->names())],
            $prefix.'due_in' => ['nullable', Rule::in($keepDue ? ['keep', '1', '3', '7'] : ['1', '3', '7'])],
            $prefix.'kind' => ['required', Rule::enum(TodoKind::class)],
            $prefix.'waiting_for' => ['nullable', 'required_if:'.$prefix.'kind,'.TodoKind::Waiting->value, 'string', 'max:255'],
            $prefix.'notify_customer' => ['nullable', 'boolean'],
            $prefix.'notify_hint' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(string $prefix = ''): array
    {
        return [
            $prefix.'description.required' => 'Bitte eine Beschreibung für das ToDo eingeben.',
            $prefix.'assignee.in' => 'Bitte einen Namen aus der Liste wählen.',
            $prefix.'waiting_for.required_if' => 'Bitte angeben, worauf gewartet wird.',
        ];
    }
}
