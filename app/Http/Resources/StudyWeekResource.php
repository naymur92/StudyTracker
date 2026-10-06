<?php

namespace App\Http\Resources;

use App\Services\IdHasher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyWeekResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => IdHasher::encode($this->id),
            'week_start' => $this->week_start->format('Y-m-d'),
            'week_end' => $this->weekEnd()->format('Y-m-d'),
            'gear' => $this->gear,
            'major_focus' => $this->major_focus,
            'minor_focus' => $this->minor_focus,
            'reflection' => $this->reflection,
            'if_then_plan' => $this->if_then_plan,
            'output_note' => $this->output_note,
        ];
    }
}
