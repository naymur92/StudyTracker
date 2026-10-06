<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryReviewSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'preset_key',
        'offsets',
        'repeat_every_days',
        'repeat_until',
    ];

    protected $casts = [
        'offsets' => 'array',
        'repeat_every_days' => 'integer',
        'repeat_until' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
