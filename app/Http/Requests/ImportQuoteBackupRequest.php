<?php

namespace App\Http\Requests;

use App\Staff\StaffDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload of the backup file of the old browser calculator (PROJ-34). Only admins.
 */
class ImportQuoteBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $staff = app(StaffDirectory::class);

        return $staff->isAdmin($staff->current($this));
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'backup' => ['required', 'file', 'extensions:json', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'backup.required' => 'Bitte die Sicherungsdatei (JSON) auswählen.',
            'backup.file' => 'Bitte die Sicherungsdatei (JSON) auswählen.',
            'backup.extensions' => 'Die Datei muss eine JSON-Datei sein (z. B. rabattdata.json).',
            'backup.max' => 'Die Datei ist größer als 5 MB.',
        ];
    }
}
