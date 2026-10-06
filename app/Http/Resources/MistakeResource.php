<?php

namespace App\Http\Resources;

use App\Services\IdHasher;
use App\Services\StudyTracker\MistakeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MistakeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = app(MistakeService::class);
        $details = $this->mistake_details ?? [];
        $next = $this->resource->getAttribute('next_review_date');

        return [
            'id' => IdHasher::encode($this->id),
            'question' => $service->question($this->resource),
            'correct_answer' => $details['correct_answer'] ?? null,
            'my_answer' => $details['my_answer'] ?? null,
            'cause' => $details['cause'] ?? null,
            'source' => $details['source'] ?? null,
            'logged_on' => $this->first_study_date?->format('Y-m-d'),
            'state' => $service->state($this->resource),
            'next_review_date' => $next ? substr((string) $next, 0, 10) : null,
            'srs_step' => (int) $this->srs_step,
            'srs_steps_total' => $this->srs_offsets ? count($this->srs_offsets) : null,
            'srs_lapses' => (int) $this->srs_lapses,
            'lane' => $this->lane,
            'parent_topic' => $this->whenLoaded('parentTopic', fn () => $this->parentTopic ? [
                'id' => IdHasher::encode($this->parentTopic->id),
                'title' => $this->parentTopic->title,
            ] : null),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'merged_at' => $this->merged_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
