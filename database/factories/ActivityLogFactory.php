<?php

namespace Database\Factories;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => ActivityAction::Created,
            'subject_id' => Project::factory(),
            'subject_type' => 'project',
            'properties' => null,
        ];
    }
}
