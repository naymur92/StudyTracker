<?php

namespace App\Models;

use App\Traits\HashesIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailedStudyReport extends Model
{
    use HasFactory, HashesIds;

    protected $fillable = [
        'user_id',
        'months',
        'status',
        'generated_file_name',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'months' => 'array',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
