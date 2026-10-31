<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class ZammadWebhookRequest extends FormRequest
{
    private const string SIGNATURE_PREFIX = 'sha1=';

    /**
     * Only Zammad knows the secret: the signature is checked over the unchanged raw
     * body, before anything of the request is used. Without a secret nothing passes.
     */
    public function authorize(): bool
    {
        $secret = (string) config('services.zammad.webhook_secret');

        if ($secret === '') {
            Log::error('Zammad webhook refused: ZAMMAD_WEBHOOK_SECRET is not set');

            return false;
        }

        $signature = (string) $this->header('X-Hub-Signature');

        if (! str_starts_with($signature, self::SIGNATURE_PREFIX)) {
            return false;
        }

        return hash_equals(hash_hmac('sha1', $this->getContent(), $secret), substr($signature, strlen(self::SIGNATURE_PREFIX)));
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'ticket_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
