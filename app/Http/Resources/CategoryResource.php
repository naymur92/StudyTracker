<?php

namespace App\Http\Resources;

use App\Services\IdHasher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => IdHasher::encode($this->id),
            'name'      => $this->name,
            'color'     => $this->color,
            'icon'      => $this->icon,
            'user_id'   => $this->user_id ? IdHasher::encode($this->user_id) : null,
            'is_system' => is_null($this->user_id),
            'topics_count' => $this->whenCounted('topics'),
            // The requesting user's own schedule for this category (null = default).
            'review_schedule' => $this->whenLoaded('reviewSchedules', function () {
                $schedule = $this->reviewSchedules->first();

                return $schedule ? [
                    'preset_key'        => $schedule->preset_key,
                    'offsets'           => $schedule->offsets,
                    'repeat_every_days' => $schedule->repeat_every_days,
                    'repeat_until'      => $schedule->repeat_until?->format('Y-m-d'),
                ] : null;
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
