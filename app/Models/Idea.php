<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'title',
    'description',
    'category',
    'sector',
    'expected_impact',
    'urgency',
    'status',
    'user_id',
    'author_name',
    'author_registration_id',
    'evaluated_by',
    'manager_responsible_name',
    'evaluation_notes',
])]
class Idea extends Model
{
    use HasFactory;

    /**
     * Get the user that created the idea.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the manager that evaluated the idea.
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * Get the project associated with the idea.
     */
    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'idea_origin_id');
    }
}
