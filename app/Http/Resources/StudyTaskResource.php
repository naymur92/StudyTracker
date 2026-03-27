<?php

namespace App\Http\Resources;

use App\Services\IdHasher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyTaskResource extends JsonResource
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
            'title' => $this->title,
            'task_type' => $this->task_type,
            'task_type_label' => $this->taskTypeLabel(),
            'revision_no' => $this->revision_no,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'status' => $this->status,
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'locked_at' => $this->locked_at?->format('Y-m-d H:i:s'),
            'is_date_locked' => (bool) $this->is_date_locked,
            'is_completed' => $this->isCompleted(),
            'is_overdue' => $this->isOverdue(),
            'can_be_rescheduled' => $this->canBeRescheduled(),
            'parent_task_id' => $this->parent_task_id ? IdHasher::encode($this->parent_task_id) : null,
            'notes' => $this->notes,
            'difficulty_feedback' => $this->difficulty_feedback,
            'topic' => new TopicResource($this->whenLoaded('topic')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
