<?php

namespace App\Models;

use App\Traits\HashesIds;
use Illuminate\Database\Eloquent\Builder;
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
        'recall_questions',
        'summary',
        'practice_prompt',
        'lane',
        'kind',
        'parent_topic_id',
        'mistake_details',
        'merged_at',
        'srs_step',
        'srs_lapses',
        'last_reviewed_on',
        'srs_offsets',
        'srs_repeat_every_days',
        'srs_repeat_until',
        'srs_schedule_source',
    ];

    public const KIND_TOPIC = 'topic';
    public const KIND_MISTAKE = 'mistake';
    public const KINDS = [self::KIND_TOPIC, self::KIND_MISTAKE];

    public const LANES = ['major', 'minor', 'work'];

    public const MISTAKE_CAUSES = ['concept', 'memory', 'careless'];

    protected $casts = [
        'first_study_date' => 'date',
        'tags'             => 'array',
        'recall_questions' => 'array',
        'mistake_details'  => 'array',
        'merged_at'        => 'datetime',
        'srs_step'         => 'integer',
        'srs_lapses'       => 'integer',
        'last_reviewed_on' => 'date',
        'srs_offsets'      => 'array',
        'srs_repeat_every_days' => 'integer',
        'srs_repeat_until' => 'date',
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

    public function parentTopic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'parent_topic_id');
    }

    public function mistakes(): HasMany
    {
        return $this->hasMany(Topic::class, 'parent_topic_id')->where('kind', self::KIND_MISTAKE);
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_TOPIC);
    }

    public function scopeMistakeEntries(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_MISTAKE);
    }

    /** Adds `next_review_date`: the earliest pending or missed revision date. */
    public function scopeWithNextReviewDate(Builder $query): Builder
    {
        return $query->withMin(['studyTasks as next_review_date' => fn ($q) => $q
            ->where('task_type', 'revision')
            ->whereIn('status', ['pending', 'missed'])], 'scheduled_date');
    }

    public function loadNextReviewDate(): static
    {
        return $this->loadMin(['studyTasks as next_review_date' => fn ($q) => $q
            ->where('task_type', 'revision')
            ->whereIn('status', ['pending', 'missed'])], 'scheduled_date');
    }

    public function isMistake(): bool
    {
        return $this->kind === self::KIND_MISTAKE;
    }

    public function revisionTasks(): HasMany
    {
        return $this->hasMany(StudyTask::class)->where('task_type', 'revision')->orderBy('revision_no');
    }
}
