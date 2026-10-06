<?php

namespace App\Models;

use App\Traits\HashesIds;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyWeek extends Model
{
    use HasFactory, HashesIds;

    public const GEARS = ['green', 'yellow', 'red'];

    protected $fillable = [
        'user_id',
        'week_start',
        'gear',
        'major_focus',
        'minor_focus',
        'reflection',
        'if_then_plan',
        'output_note',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(StudyBlock::class);
    }

    public function weekEnd(): Carbon
    {
        return $this->week_start->copy()->addDays(6);
    }
}
