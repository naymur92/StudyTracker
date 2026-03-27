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
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
