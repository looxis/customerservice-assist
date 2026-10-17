<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The current value of a settings group that differs from the default of the app (PROJ-27).
 */
#[Fillable(['group_key', 'value', 'version', 'changed_by'])]
class Setting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'array', 'version' => 'integer'];
    }
}
