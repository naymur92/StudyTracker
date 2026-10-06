<?php

namespace App\Http\Resources;

use App\Services\IdHasher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => IdHasher::encode($this->id),
            'week_id' => IdHasher::encode($this->study_week_id),
            'block_date' => $this->block_date->format('Y-m-d'),
            'weekday' => $this->block_date->format('l'),
            'slot' => $this->slot,
            'lane' => $this->lane,
            'planned_task' => $this->planned_task,
            'planned_minutes' => $this->planned_minutes,
            'status' => $this->status,
            'note' => $this->note,
            'category_id' => $this->category_id ? IdHasher::encode($this->category_id) : null,
            'topic' => $this->topic ? ['id' => IdHasher::encode($this->topic->id), 'title' => $this->topic->title] : null,
        ];
    }
}
