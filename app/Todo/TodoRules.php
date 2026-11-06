<?php

namespace App\Todo;

use App\Settings\SettingsStore;
use App\Settings\TodoReplyTexts;
use App\Staff\StaffDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The rules for the fields of a ToDo (PROJ-70), shared by the ToDo forms and the ToDo rows of the reply form.
 */
class TodoRules
{
    /** The assignee "everyone": any employee may take the ToDo. */
    public const EVERYONE = 'Alle';

    /**
     * Frequent ToDos offered in the forms, most frequent first. Choosing one fills in the usual settings and the
     * draft of the later reply (a text from the settings); the employee can change everything afterwards.
     *
     * @param  array{name?: string|null, kind?: string|null, number?: string|null}  $context  What the case already tells for the placeholders of the texts.
     * @return list<array{label: string, tracking?: bool, waiting: bool, waiting_for: string, notify: bool, hint: string}>
     */
    public static function presets(array $context = []): array
    {
        $settings = app(SettingsStore::class);

        return [
            [
                'label' => 'Sendungsnummer nachreichen',
                'tracking' => true,
                'waiting' => true,
                'waiting_for' => 'Sendungsnummer des Pakets',
                'notify' => true,
                'hint' => TodoReplyTexts::fill((string) $settings->value('todo_reply_shipping'), $context),
            ],
            [
                'label' => '(Teil-)Erstattung vornehmen',
                'waiting' => false,
                'waiting_for' => 'Durchführung der Erstattung',
                'notify' => true,
                'hint' => TodoReplyTexts::fill((string) $settings->value('todo_reply_refund'), $context),
            ],
            [
                'label' => 'Nachforschungsauftrag Versand',
                'waiting' => false,
                'waiting_for' => 'Ergebnis des Nachforschungsauftrags',
                'notify' => false,
                'hint' => '',
            ],
        ];
    }

    /**
     * The names a ToDo can be assigned to: the staff list and "everyone".
     *
     * @return list<string>
     */
    public static function assignees(): array
    {
        return [...app(StaffDirectory::class)->names(), self::EVERYONE];
    }

    /**
     * @param  string  $prefix  "" for a single ToDo, "todos.*." for the rows of the reply form.
     * @param  bool  $keepDue  Whether "keep" is a valid choice of the due date (editing).
     * @return array<string, list<ValidationRule|string>>
     */
    public static function fields(string $prefix = '', bool $keepDue = false): array
    {
        return [
            $prefix.'description' => ['required', 'string', 'max:1000'],
            $prefix.'assignee' => ['nullable', 'string', Rule::in(self::assignees())],
            $prefix.'due_in' => ['nullable', Rule::in($keepDue ? ['keep', '1', '3', '7'] : ['1', '3', '7'])],
            $prefix.'kind' => ['required', Rule::enum(TodoKind::class)],
            $prefix.'waiting_for' => ['nullable', 'required_if:'.$prefix.'kind,'.TodoKind::Waiting->value, 'string', 'max:255'],
            $prefix.'notify_customer' => ['nullable', 'boolean'],
            $prefix.'notify_hint' => ['nullable', 'string', 'max:1000'],
            $prefix.'reference' => ['nullable', 'string', 'max:120'],
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
