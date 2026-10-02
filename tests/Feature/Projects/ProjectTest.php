<?php

namespace Tests\Feature\Projects;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Project\ProjectService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_projects_require_a_token(): void
    {
        $this->getJson('/api/v1/projects')->assertUnauthorized();
    }

    public function test_task_manager_lists_only_their_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->for($user)->create(['name' => 'Mine']);
        Project::factory()->create(['name' => 'Theirs']);

        $this->withToken($user->createToken('api')->plainTextToken)
            ->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mine')
            ->assertJsonPath('data.0.owner.id', $user->id)
            ->assertJsonPath('data.0.tasks_count', 0)
            ->assertJsonPath('data.0.completed_tasks_count', 0);
    }

    public function test_project_list_counts_tasks_and_completed_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->for($project)->create(['status' => TaskStatus::Todo]);
        Task::factory()->for($project)->create(['status' => TaskStatus::Completed]);
        Task::factory()->for($project)->create(['status' => TaskStatus::InProgress]);

        $this->withToken($user->createToken('api')->plainTextToken)
            ->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonPath('data.0.tasks_count', 3)
            ->assertJsonPath('data.0.completed_tasks_count', 1);
    }

    public function test_project_list_is_paginated(): void
    {
        $user = User::factory()->create();
        Project::factory()->for($user)->count(ProjectService::PER_PAGE + 1)->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->getJson('/api/v1/projects?page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', ProjectService::PER_PAGE)
            ->assertJsonPath('meta.total', ProjectService::PER_PAGE + 1);
    }

    public function test_admin_lists_every_project(): void
    {
        $admin = User::factory()->admin()->create();
        Project::factory()->for($admin)->create(['name' => 'Admin project']);
        Project::factory()->create(['name' => 'Someone else']);

        $this->withToken($admin->createToken('api')->plainTextToken)
            ->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_a_user_can_create_a_project(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson('/api/v1/projects', [
                'name' => 'Website refresh',
                'description' => 'New marketing site',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Website refresh')
            ->assertJsonPath('data.description', 'New marketing site')
            ->assertJsonPath('data.owner.id', $user->id);

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Website refresh',
        ]);
    }

    public function test_create_rejects_a_missing_name(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson('/api/v1/projects', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_a_user_can_view_and_update_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['name' => 'Old name']);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Old name');

        $this->withToken($token)
            ->putJson("/api/v1/projects/{$project->id}", [
                'name' => 'New name',
                'description' => '',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New name')
            ->assertJsonPath('data.description', null);
    }

    public function test_a_task_manager_cannot_change_someone_elses_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/projects/{$project->id}")
            ->assertForbidden();

        $this->withToken($token)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'Nope'])
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/v1/projects/{$project->id}")
            ->assertForbidden();

        $this->assertModelExists($project);
    }

    public function test_an_admin_can_update_and_delete_any_project(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['name' => 'Borrowed']);
        $token = $admin->createToken('api')->plainTextToken;

        $manager = User::factory()->create();

        $this->withToken($token)
            ->putJson("/api/v1/projects/{$project->id}", [
                'name' => 'Taken over',
                'task_manager_id' => $manager->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Taken over')
            ->assertJsonPath('data.owner.id', $manager->id);

        $this->withToken($token)
            ->deleteJson("/api/v1/projects/{$project->id}")
            ->assertNoContent();

        $this->assertModelMissing($project);
    }

    public function test_an_admin_must_choose_a_task_manager_when_creating_a_project(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->create(['name' => 'Asha']);
        $token = $admin->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/projects', ['name' => 'Assigned work'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['task_manager_id']);

        $this->withToken($token)
            ->postJson('/api/v1/projects', [
                'name' => 'Assigned work',
                'task_manager_id' => $admin->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['task_manager_id']);

        $this->withToken($token)
            ->postJson('/api/v1/projects', [
                'name' => 'Assigned work',
                'task_manager_id' => $manager->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.owner.id', $manager->id)
            ->assertJsonPath('data.owner.name', 'Asha');
    }

    public function test_a_task_manager_cannot_assign_a_project_to_someone_else(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->withToken($owner->createToken('api')->plainTextToken)
            ->postJson('/api/v1/projects', [
                'name' => 'Mine',
                'task_manager_id' => $other->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.owner.id', $owner->id);
    }

    public function test_only_an_admin_can_list_task_managers(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->create(['name' => 'Asha']);

        $this->withToken($manager->createToken('api')->plainTextToken)
            ->getJson('/api/v1/task-managers')
            ->assertForbidden();

        $this->app['auth']->forgetGuards();

        $this->withToken($admin->createToken('api')->plainTextToken)
            ->getJson('/api/v1/task-managers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $manager->id)
            ->assertJsonPath('data.0.name', 'Asha');
    }

    public function test_a_user_can_delete_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->deleteJson("/api/v1/projects/{$project->id}")
            ->assertNoContent();

        $this->assertModelMissing($project);
    }
}
