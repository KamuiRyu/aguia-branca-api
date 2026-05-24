<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'status',
    'idea_origin_id',
    'responsible_team',
    'budget',
    'deadline',
    'progress_percentage',
    'expected_roi',
    'description',
])]
class Project extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'progress_percentage' => 'integer',
            'expected_roi' => 'decimal:2',
            'deadline' => 'date',
        ];
    }

    /**
     * Get the idea that originated this project.
     */
    public function ideaOrigin(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'idea_origin_id');
    }
}
