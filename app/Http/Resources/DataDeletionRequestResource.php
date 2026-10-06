<?php

namespace App\Http\Resources;

use App\Services\IdHasher;
use App\Services\StudyTracker\DataDeletion\DataCategoryRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A user's own data deletion request. Archive location and checksum are
 * admin-only and never exposed here.
 */
class DataDeletionRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $registry = app(DataCategoryRegistry::class);

        return [
            'id' => IdHasher::encode($this->id),
            'reference' => $this->reference(),
            'categories' => collect($this->categories)->map(fn (string $key) => [
                'key' => $key,
                'label' => $registry->has($key) ? $registry->label($key) : $key,
            ])->values(),
            'reason' => $this->reason,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'is_open' => $this->isOpen(),
            'rejection_reason' => $this->rejection_reason,
            'request_counts' => $this->labelled($registry, $this->request_counts['records'] ?? []),
            'deleted_counts' => $this->deleted_counts === null ? null : $this->labelled($registry, $this->deleted_counts),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'restored_at' => $this->restored_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function labelled(DataCategoryRegistry $registry, array $counts): array
    {
        return collect($counts)->map(fn (int $count, string $key) => [
            'key' => $key,
            'label' => $registry->recordLabel($key),
            'count' => $count,
        ])->values()->all();
    }
}
