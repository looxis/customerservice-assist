<?php

namespace App\Http\Requests;

use App\Labeling\LabelRunner;
use App\Models\LabelRun;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartLabelRunRequest extends FormRequest
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
            'kind' => ['required', Rule::in(array_keys(LabelRun::KINDS))],
            'dry_run' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'excluded_groups' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $kind = (string) $this->input('kind');
            $admin = app(StaffDirectory::class)->current($this);

            if (app(LabelRunner::class)->active($kind) !== null) {
                $validator->errors()->add('kind', 'Dieser Lauf ist schon gestartet. Bitte abwarten oder abbrechen.');
            } elseif ($kind === 'tags' && ! $this->boolean('dry_run') && app(StaffDirectory::class)->zammadToken($admin) === null) {
                $validator->errors()->add('kind', 'Für das Schreiben der Tags ist für dich kein Zammad-Token hinterlegt.');
            }
        }];
    }
}
