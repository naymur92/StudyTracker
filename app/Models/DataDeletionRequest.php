<?php

namespace App\Models;

use App\Traits\HashesIds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A user's request to delete chosen categories of their study data.
 * Approved requests are archived first, then deleted; see
 * App\Services\StudyTracker\DataDeletion.
 */
class DataDeletionRequest extends Model
{
    use HashesIds;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RESTORED = 'restored';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_REJECTED,
        self::STATUS_RESTORED,
    ];

    /** A user may hold only one request in one of these states. */
    public const OPEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_PROCESSING,
        self::STATUS_FAILED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending review',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_RESTORED => 'Restored',
    ];

    protected $fillable = [
        'user_id',
        'categories',
        'reason',
        'status',
        'request_counts',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'processing_started_at',
        'completed_at',
        'failed_at',
        'error_message',
        'archive_path',
        'archive_size',
        'archive_checksum',
        'archive_purged_at',
        'deleted_counts',
        'restored_by',
        'restored_at',
        'restore_report',
    ];

    protected $casts = [
        'categories' => 'array',
        'request_counts' => 'array',
        'deleted_counts' => 'array',
        'restore_report' => 'array',
        'archive_size' => 'integer',
        'reviewed_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'archive_purged_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            $request->uuid ??= (string) Str::uuid();
            $request->status ??= self::STATUS_PENDING;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    /** True while an archive file exists that can be downloaded or restored. */
    public function hasArchive(): bool
    {
        return $this->archive_path !== null && $this->archive_purged_at === null;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Short human reference, e.g. "DDR-1A2B3C4D". */
    public function reference(): string
    {
        return 'DDR-'.strtoupper(substr(str_replace('-', '', (string) $this->uuid), 0, 8));
    }
}
