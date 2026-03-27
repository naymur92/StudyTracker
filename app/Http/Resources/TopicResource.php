<?php

namespace App\Http\Resources;

use App\Services\IdHasher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => IdHasher::encode($this->id),
            'user_id'     => IdHasher::encode($this->user_id),
            'category_id' => $this->category_id ? IdHasher::encode($this->category_id) : null,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'source_link' => $this->source_link,
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'first_study_date' => $this->first_study_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'tags' => $this->tags ?? [],
            'category' => new CategoryResource($this->whenLoaded('category')),
            'task_count' => $this->whenCounted('studyTasks'),
            'practice_log_count' => $this->whenCounted('practiceLogs'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
