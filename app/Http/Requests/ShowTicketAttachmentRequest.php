<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowTicketAttachmentRequest extends FormRequest
{
    /**
     * Anyone who may see the ticket page may look at its attachments; the staff name is checked by the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'download' => ['sometimes', 'boolean'],
        ];
    }
}
