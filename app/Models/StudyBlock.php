<?php

namespace App\Models;

use App\Traits\HashesIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudyBlock extends Model
{
    use HasFactory, HashesIds;

    public const SLOTS = ['morning', 'class_recap', 'deep', 'block_a', 'block_b', 'review', 'minor', 'other'];

    public const LANES = ['major', 'minor', 'review', 'work'];

    public const STATUSES = ['planned', 'done', 'partial', 'missed', 'red'];

    /** Display names of the slots (mirrors weeklyMeta.js). */
    public const SLOT_LABELS = [
        'morning' => 'Morning deep block',
        'class_recap' => 'Class recap',
        'deep' => 'Deep block',
        'block_a' => 'Block A',
        'block_b' => 'Block B',
        'review' => 'Reviews',
        'minor' => 'Minor slot',
        'other' => 'Other',
    ];

    /** Outcomes that record what happened; only allowed once the day has come. */
    public const OUTCOME_STATUSES = ['done', 'partial', 'missed'];

    /** Statuses a block with recorded timer time can have. */
    public const RECORDED_STATUSES = ['done', 'partial'];

    protected $fillable = [
        'user_id',
        'study_week_id',
        'block_date',
        'slot',
        'lane',
        'planned_task',
        'planned_minutes',
        'break_every_minutes',
        'break_minutes',
        'status',
        'note',
        'category_id',
        'topic_id',
    ];

    protected $casts = [
        'block_date' => 'date',
        'planned_minutes' => 'integer',
        'break_every_minutes' => 'integer',
        'break_minutes' => 'integer',
    ];

    public function week(): BelongsTo
    {
        return $this->belongsTo(StudyWeek::class, 'study_week_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function slotLabel(): string
    {
        return self::SLOT_LABELS[$this->slot] ?? ucfirst($this->slot);
    }

    /**
     * Seconds recorded by the block's ended timer runs. Uses the `ended_seconds`
     * aggregate when it was eager-loaded (see WeeklyPlanService::orderedBlocks).
     */
    public function endedSeconds(): int
    {
        if (array_key_exists('ended_seconds', $this->attributes)) {
            return (int) $this->attributes['ended_seconds'];
        }

        return (int) $this->sessions()->whereNotNull('ended_at')->sum('used_seconds');
    }

    /** Recorded minutes rounded up: the least the block's planned minutes can be. */
    public function recordedMinutesCeil(): int
    {
        return (int) ceil($this->endedSeconds() / 60);
    }

    /** Timer runs of this block. */
    public function sessions(): HasMany
    {
        return $this->hasMany(StudyBlockSession::class);
    }

    /** The block's running or paused timer run, if any. */
    public function activeSession(): HasOne
    {
        return $this->hasOne(StudyBlockSession::class)->whereNotNull('active_user_id');
    }
}
