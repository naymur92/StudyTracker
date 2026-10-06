<?php

namespace App\Models;

use App\Traits\HashesIds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One timer run of a weekly-plan block. A run is running (active, resumed_at
 * set), paused (active, resumed_at null) or ended (ended_at set).
 */
class StudyBlockSession extends Model
{
    use HasFactory, HashesIds;

    public const END_FINISHED = 'finished';

    public const END_STOPPED = 'stopped';

    protected $fillable = [
        'user_id',
        'study_block_id',
        'active_user_id',
        'started_at',
        'resumed_at',
        'used_seconds',
        'ended_at',
        'end_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resumed_at' => 'datetime',
        'ended_at' => 'datetime',
        'used_seconds' => 'integer',
        'active_user_id' => 'integer',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(StudyBlock::class, 'study_block_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('active_user_id');
    }

    public function scopeEnded(Builder $query): Builder
    {
        return $query->whereNotNull('ended_at');
    }

    public function isActive(): bool
    {
        return $this->active_user_id !== null;
    }

    public function isRunning(): bool
    {
        return $this->isActive() && $this->resumed_at !== null;
    }

    public function isPaused(): bool
    {
        return $this->isActive() && $this->resumed_at === null;
    }
}
