<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'owner' => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
            ],
            'tasks_count' => (int) $this->tasks_count,
            'completed_tasks_count' => (int) $this->completed_tasks_count,
        ];
    }
}
