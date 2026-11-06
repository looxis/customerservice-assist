<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Whether tickets of a category may be tagged without an individual look (PROJ-78).
 */
#[Fillable(['category', 'released', 'released_by', 'released_at', 'locked_at'])]
class LabelRelease extends Model
{
    protected $table = 'labeling_releases';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released' => 'boolean',
            'released_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }
}
