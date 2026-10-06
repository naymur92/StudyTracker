<?php

namespace App\Http\Resources;

use App\Services\StudyTracker\BlockTimerService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** An active timer run with its block (wraps a StudyBlockSession). */
class BlockTimerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return app(BlockTimerService::class)->timerFields($this->resource, $this->block) + [
            'block' => (new StudyBlockResource($this->block))->resolve($request),
        ];
    }
}
