<?php

namespace App\Http\Requests;

use App\Staff\StaffDirectory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Starts a reply without a language model call (PROJ-69). Only who has a personal Zammad token, because the reply
 * is sent in their name; not while a rewound ticket is shown in the test mode.
 */
class StartManualReplyRequest extends FormRequest
{
    public function authorize(StaffDirectory $staff): bool
    {
        return $staff->zammadToken($staff->current($this)) !== null && ! $this->filled('stand');
    }

    /**
     * @return array<string, never>
     */
    public function rules(): array
    {
        return [];
    }
}
