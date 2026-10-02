<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_tasks_require_a_token(): void
    {
        $project = Project::factory()->create();

        $this->getJson("/api/v1/projects/{$project->id}/tasks")->assertUnauthorized();
    }

    public function test_a_task_manager_can_manage_tasks_on_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $token = $user->createToken('api')->plainTextToken;

        $created = $this->withToken($token)
            ->postJson("/api/v1/projects/{$project->id}/tasks", [
                'title' => 'Write the homepage',
                'description' => 'Hero and footer',
                'priority' => 'high',
            ]);

        $created->assertCreated()
            ->assertJsonPath('data.title', 'Write the homepage')
            ->assertJsonPath('data.status', TaskStatus::Todo->value)
            ->assertJsonPath('data.priority', 'high');

        $taskId = $created->json('data.id');

        $this->withToken($token)
            ->getJson("/api/v1/projects/{$project->id}/tasks")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Write the homepage');

        $this->withToken($token)
            ->putJson("/api/v1/projects/{$project->id}/tasks/{$taskId}", [
                'title' => 'Write the homepage',
                'description' => 'Hero and footer',
                'priority' => 'high',
                'status' => TaskStatus::InProgress->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatus::InProgress->value);

        $this->withToken($token)
            ->deleteJson("/api/v1/projects/{$project->id}/tasks/{$taskId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    }

    public function test_create_rejects_a_missing_title(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson("/api/v1/projects/{$project->id}/tasks", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'priority']);
    }

    public function test_a_task_manager_cannot_manage_tasks_on_someone_elses_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $token = $user->createToken('api')->plainTextToken;
        $task = Task::factory()->for($project)->create([
            'title' => 'Private',
            'status' => TaskStatus::Todo,
        ]);

        $this->withToken($token)
            ->getJson("/api/v1/projects/{$project->id}/tasks")
            ->assertForbidden();

        $this->withToken($token)
            ->postJson("/api/v1/projects/{$project->id}/tasks", [
                'title' => 'Not mine',
                'priority' => 'low',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->putJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Taken',
                'priority' => 'low',
                'status' => TaskStatus::Completed->value,
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/v1/projects/{$project->id}/tasks/{$task->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Private',
            'status' => TaskStatus::Todo->value,
        ]);
    }

    public function test_an_admin_can_move_a_task_on_any_project(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->for($project)->create([
            'title' => 'Ship it',
            'status' => TaskStatus::Todo,
            'priority' => 'low',
        ]);

        $this->withToken($admin->createToken('api')->plainTextToken)
            ->putJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Ship it',
                'priority' => 'low',
                'status' => TaskStatus::Completed->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatus::Completed->value);
    }

    public function test_a_task_cannot_be_changed_through_another_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $other = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->putJson("/api/v1/projects/{$other->id}/tasks/{$task->id}", [
                'title' => 'Moved',
                'priority' => 'medium',
                'status' => TaskStatus::InProgress->value,
            ])
            ->assertNotFound();

        $this->assertSame(TaskStatus::Todo, $task->refresh()->status);
    }
}
