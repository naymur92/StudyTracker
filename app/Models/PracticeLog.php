<?php

namespace App\Models;

use App\Traits\HashesIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeLog extends Model
{
    use HasFactory, HashesIds;

    protected $fillable = [
        'user_id',
        'topic_id',
        'task_id',
        'practiced_on',
        'practice_type',
        'details',
        'duration_minutes',
        'outcome',
    ];

    protected $casts = [
        'practiced_on' => 'date',
    ];

    public static array $practiceTypes = [
        'problem_solving' => 'Problem Solving',
        'implementation'  => 'Implementation',
        'reading'         => 'Reading',
        'note_making'     => 'Note Making',
        'mock_interview'  => 'Mock Interview',
        'other'           => 'Other',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(StudyTask::class, 'task_id');
    }

    public function getPracticeTypeLabelAttribute(): string
    {
        return static::$practiceTypes[$this->practice_type] ?? ucfirst($this->practice_type);
    }
}
