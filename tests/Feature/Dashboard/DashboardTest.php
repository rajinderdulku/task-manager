<?php

namespace Tests\Feature\Dashboard;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_dashboard_requires_a_token(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_a_task_manager_sees_only_their_projects_and_tasks(): void
    {
        $manager = User::factory()->create();
        $mine = Project::factory()->for($manager)->create();
        $theirs = Project::factory()->create();

        Task::factory()->for($mine)->create(['status' => TaskStatus::Todo]);
        Task::factory()->for($mine)->create(['status' => TaskStatus::Completed]);
        Task::factory()->for($theirs)->create(['status' => TaskStatus::InProgress]);

        $this->withToken($manager->createToken('api')->plainTextToken)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.projects', 1)
            ->assertJsonPath('data.tasks', 2)
            ->assertJsonPath('data.todo', 1)
            ->assertJsonPath('data.in_progress', 0)
            ->assertJsonPath('data.completed', 1);
    }

    public function test_an_admin_sees_every_project_and_task(): void
    {
        $admin = User::factory()->admin()->create();
        $first = Project::factory()->create();
        $second = Project::factory()->create();

        Task::factory()->for($first)->create(['status' => TaskStatus::Todo]);
        Task::factory()->for($second)->create(['status' => TaskStatus::Todo]);
        Task::factory()->for($second)->create(['status' => TaskStatus::Completed]);

        $this->withToken($admin->createToken('api')->plainTextToken)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.projects', 2)
            ->assertJsonPath('data.tasks', 3)
            ->assertJsonPath('data.todo', 2)
            ->assertJsonPath('data.in_progress', 0)
            ->assertJsonPath('data.completed', 1);
    }
}
