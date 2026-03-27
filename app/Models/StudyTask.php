<?php

namespace App\Models;

use App\Traits\HashesIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyTask extends Model
{
    use HasFactory, HashesIds;

    protected $fillable = [
        'user_id',
        'topic_id',
        'task_type',
        'revision_no',
        'title',
        'scheduled_date',
        'status',
        'completed_at',
        'locked_at',
        'is_date_locked',
        'parent_task_id',
        'notes',
        'difficulty_feedback',
    ];

    protected $casts = [
        'scheduled_date'   => 'date',
        'completed_at'     => 'datetime',
        'locked_at'        => 'datetime',
        'is_date_locked'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(StudyTask::class, 'parent_task_id');
    }

    public function childTasks(): HasMany
    {
        return $this->hasMany(StudyTask::class, 'parent_task_id');
    }

    public function practiceLogs(): HasMany
    {
        return $this->hasMany(PracticeLog::class, 'task_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->scheduled_date->isPast() && $this->isPending();
    }

    public function canBeRescheduled(): bool
    {
        return ! $this->is_date_locked;
    }

    public function taskTypeLabel(): string
    {
        return match ($this->task_type) {
            'learn'    => 'Learn',
            'revision' => "Revision {$this->revision_no}",
            'practice' => 'Practice',
            'custom'   => 'Custom',
            default    => ucfirst($this->task_type),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'skipped'   => 'secondary',
            'missed'    => 'danger',
            default     => 'warning',
        };
    }
}
