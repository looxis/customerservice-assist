<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * "Antwort versenden" (PROJ-19). The recipient is not part of it: it is read from the ticket on the server.
 */
class SendReplyRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:300', 'regex:/^[^\x00-\x1F\x7F]*$/u'],
            'text' => ['required', 'string', 'max:'.config('analysis.max_reply_length')],
            'signature' => ['nullable', 'string', 'max:600'],
            'after' => ['required', Rule::in(['close', 'reminder'])],
            'days' => ['required_if:after,reminder', 'nullable', 'integer', Rule::in([1, 3, 7])],
            'confirm_newer' => ['sometimes', 'boolean'],
            'files' => ['sometimes', 'array', 'max:'.self::MAX_FILES],
            'files.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'mimetypes:application/pdf,image/png,image/jpeg,image/webp', 'max:'.(self::MAX_BYTES / 1024)],
        ];
    }

    /**
     * Files that go with the reply (PROJ-61): PDF and images, up to five, together at most 5 MB.
     */
    public const int MAX_FILES = 5;

    public const int MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $total = collect($this->file('files', []))->sum(fn (mixed $file): int => $file instanceof UploadedFile ? (int) $file->getSize() : 0);

            if ($total > self::MAX_BYTES) {
                $validator->errors()->add('files', 'Die Anhänge sind zusammen größer als 5 MB.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Bitte einen Betreff eingeben.',
            'subject.regex' => 'Der Betreff darf keine Zeilenumbrüche oder Steuerzeichen enthalten.',
            'text.required' => 'Der Antworttext ist leer.',
            'days.required_if' => 'Bitte 1, 3 oder 7 Tage wählen.',
            'files.max' => 'Es sind höchstens 5 Anhänge möglich.',
            'files.*.mimes' => 'Ein Anhang hat kein erlaubtes Format. Erlaubt sind PDF, PNG, JPG, JPEG und WebP.',
            'files.*.mimetypes' => 'Der Anhang entspricht nicht dem erlaubten Format (PDF, PNG, JPG, JPEG, WebP).',
            'files.*.max' => 'Ein einzelner Anhang darf höchstens 5 MB groß sein.',
            'files.*.file' => 'Ein Anhang konnte nicht hochgeladen werden.',
        ];
    }
}
