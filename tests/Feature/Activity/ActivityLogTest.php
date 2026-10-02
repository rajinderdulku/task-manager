<?php

namespace Tests\Feature\Activity;

use App\Enums\ActivityAction;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ActivityLogService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_task_changes_are_written_to_the_activity_log(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $token = $user->createToken('api')->plainTextToken;

        $created = $this->withToken($token)
            ->postJson("/api/v1/projects/{$project->id}/tasks", [
                'title' => 'Write the homepage',
                'description' => 'Hero',
                'priority' => 'high',
            ])
            ->assertCreated();

        $taskId = $created->json('data.id');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => ActivityAction::Created->value,
            'subject_type' => 'task',
            'subject_id' => $taskId,
        ]);

        $this->withToken($token)
            ->putJson("/api/v1/projects/{$project->id}/tasks/{$taskId}", [
                'title' => 'Write the homepage',
                'description' => 'Hero and footer',
                'priority' => 'high',
                'status' => TaskStatus::InProgress->value,
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityAction::Updated->value,
            'subject_id' => $taskId,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityAction::StatusChanged->value,
            'subject_id' => $taskId,
        ]);

        $this->withToken($token)
            ->getJson("/api/v1/projects/{$project->id}/tasks/{$taskId}/activity")
            ->assertOk()
            ->assertJsonPath('data.0.action', ActivityAction::StatusChanged->value)
            ->assertJsonPath('data.0.user.name', $user->name)
            ->assertJsonPath('data.1.action', ActivityAction::Updated->value)
            ->assertJsonPath('data.2.action', ActivityAction::Created->value);

        $this->withToken($token)
            ->deleteJson("/api/v1/projects/{$project->id}/tasks/{$taskId}")
            ->assertNoContent();

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityAction::Deleted->value,
            'subject_id' => $taskId,
        ]);
    }

    public function test_moving_a_task_records_only_the_status_change(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->create([
            'title' => 'Ship it',
            'status' => TaskStatus::Todo,
            'priority' => 'low',
        ]);

        $this->withToken($user->createToken('api')->plainTextToken)
            ->putJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Ship it',
                'description' => $task->description,
                'priority' => 'low',
                'status' => TaskStatus::Completed->value,
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityAction::StatusChanged->value,
            'subject_id' => $task->id,
        ]);
        $this->assertDatabaseMissing('activity_logs', [
            'action' => ActivityAction::Updated->value,
            'subject_id' => $task->id,
        ]);
    }

    public function test_activity_is_paginated_and_hidden_from_other_task_managers(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner)->create();
        $task = Task::factory()->for($project)->create();
        $token = $owner->createToken('api')->plainTextToken;

        for ($index = 0; $index <= ActivityLogService::PER_PAGE; $index++) {
            $this->withToken($token)
                ->putJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [
                    'title' => "Revision {$index}",
                    'priority' => 'medium',
                    'status' => TaskStatus::Todo->value,
                ])
                ->assertOk();
        }

        $this->withToken($token)
            ->getJson("/api/v1/projects/{$project->id}/tasks/{$task->id}/activity?page=2")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', ActivityLogService::PER_PAGE + 1);

        $other = User::factory()->create();

        $this->app['auth']->forgetGuards();

        $this->withToken($other->createToken('api')->plainTextToken)
            ->getJson("/api/v1/projects/{$project->id}/tasks/{$task->id}/activity")
            ->assertForbidden();
    }
}
