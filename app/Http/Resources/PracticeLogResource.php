<?php

namespace App\Http\Resources;

use App\Services\IdHasher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PracticeLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => IdHasher::encode($this->id),
            'user_id'  => IdHasher::encode($this->user_id),
            'topic_id' => IdHasher::encode($this->topic_id),
            'task_id'  => $this->task_id ? IdHasher::encode($this->task_id) : null,
            'practiced_on' => $this->practiced_on?->format('Y-m-d'),
            'practice_type' => $this->practice_type,
            'practice_type_label' => $this->practice_type_label,
            'details' => $this->details,
            'duration_minutes' => $this->duration_minutes,
            'outcome' => $this->outcome,
            'topic' => new TopicResource($this->whenLoaded('topic')),
            'task' => new StudyTaskResource($this->whenLoaded('task')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
