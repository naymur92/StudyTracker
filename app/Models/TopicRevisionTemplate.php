<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicRevisionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'day_offset',
        'sequence_no',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getForUser(?int $userId): \Illuminate\Database\Eloquent\Collection
    {
        $templates = static::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sequence_no')
            ->get();

        if ($templates->isEmpty()) {
            $templates = static::whereNull('user_id')
                ->where('is_active', true)
                ->orderBy('sequence_no')
                ->get();
        }

        return $templates;
    }
}
