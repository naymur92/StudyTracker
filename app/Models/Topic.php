<?php

namespace App\Models;

use App\Traits\HashesIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use HasFactory, HashesIds, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'description',
        'source_link',
        'difficulty',
        'status',
        'first_study_date',
        'notes',
        'tags',
    ];

    protected $casts = [
        'first_study_date' => 'date',
        'tags'             => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function studyTasks(): HasMany
    {
        return $this->hasMany(StudyTask::class);
    }

    public function practiceLogs(): HasMany
    {
        return $this->hasMany(PracticeLog::class);
    }

    public function learnTask(): ?StudyTask
    {
        return $this->studyTasks()->where('task_type', 'learn')->first();
    }

    public function revisionTasks(): HasMany
    {
        return $this->hasMany(StudyTask::class)->where('task_type', 'revision')->orderBy('revision_no');
    }
}
