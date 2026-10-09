<?php

namespace App\Models;

use Database\Factories\MessageTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The German translation of one Zammad message (PROJ-28), created once and
 * kept for everyone. The fingerprint of the cleaned original text tells when
 * the message changed. Status "german" marks a message that needs none.
 */
#[Fillable(['ticket_number', 'article_id', 'fingerprint', 'status', 'language', 'content', 'staff_name', 'model', 'prompt_version'])]
class MessageTranslation extends Model
{
    /** @use HasFactory<MessageTranslationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'encrypted:array',
        ];
    }
}
