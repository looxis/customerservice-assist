<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One version in the history of a settings group (PROJ-27); appended only.
 */
#[Fillable(['group_key', 'version', 'value', 'kind', 'changed_by'])]
class SettingVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'array', 'version' => 'integer'];
    }
}
