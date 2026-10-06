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
            'kind' => $this->kind ?? 'topic',
            'parent_topic_id' => $this->parent_topic_id ? IdHasher::encode($this->parent_topic_id) : null,
            'recall_questions' => $this->recall_questions ?? [],
            'summary' => $this->summary,
            'practice_prompt' => $this->practice_prompt,
            'lane' => $this->lane,
            'srs_step' => (int) $this->srs_step,
            'srs_steps_total' => $this->srs_offsets ? count($this->srs_offsets) : null,
            'srs_lapses' => (int) $this->srs_lapses,
            'last_reviewed_on' => $this->last_reviewed_on?->format('Y-m-d'),
            'next_review_date' => $this->when(
                array_key_exists('next_review_date', $this->resource->getAttributes()),
                fn () => $this->next_review_date ? substr((string) $this->next_review_date, 0, 10) : null,
            ),
            'review_schedule' => $this->srs_offsets ? [
                'offsets' => $this->srs_offsets,
                'repeat_every_days' => $this->srs_repeat_every_days,
                'repeat_until' => $this->srs_repeat_until?->format('Y-m-d'),
                'source' => $this->srs_schedule_source,
            ] : null,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'task_count' => $this->whenCounted('studyTasks'),
            'practice_log_count' => $this->whenCounted('practiceLogs'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
