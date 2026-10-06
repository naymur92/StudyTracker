<?php

namespace App\Models;

use App\Traits\HashesIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyBlock extends Model
{
    use HasFactory, HashesIds;

    public const SLOTS = ['morning', 'class_recap', 'deep', 'block_a', 'block_b', 'review', 'minor', 'other'];

    public const LANES = ['major', 'minor', 'review', 'work'];

    public const STATUSES = ['planned', 'done', 'partial', 'missed', 'red'];

    protected $fillable = [
        'user_id',
        'study_week_id',
        'block_date',
        'slot',
        'lane',
        'planned_task',
        'planned_minutes',
        'status',
        'note',
        'category_id',
        'topic_id',
    ];

    protected $casts = [
        'block_date' => 'date',
        'planned_minutes' => 'integer',
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
}
