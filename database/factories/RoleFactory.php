<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => RoleName::TaskManager->value,
        ];
    }

    public function admin(): static
    {
        return $this->state([
            'name' => RoleName::Admin->value,
        ]);
    }

    public function taskManager(): static
    {
        return $this->state([
            'name' => RoleName::TaskManager->value,
        ]);
    }
}
