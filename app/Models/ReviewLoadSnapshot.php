<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLoadSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'snapshot_date',
        'due_topics',
        'estimated_minutes',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'due_topics' => 'integer',
        'estimated_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
