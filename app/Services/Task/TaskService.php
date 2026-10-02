<?php

namespace App\Services\Task;

use App\Data\Task\TaskData;
use App\Enums\ActivityAction;
use App\Enums\TaskStatus;
use App\Jobs\RecordActivity;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection;

final class TaskService
{
    /**
     * @return Collection<int, Task>
     */
    public function list(Project $project): Collection
    {
        return $project->tasks()->orderBy('id')->get();
    }

    public function create(Project $project, TaskData $data, User $actor): Task
    {
        $task = $project->tasks()->create([
            'title' => $data->title,
            'description' => $data->description,
            'priority' => $data->priority,
            'status' => TaskStatus::Todo,
        ]);

        $this->record($actor, ActivityAction::Created, $task, [
            'title' => $task->title,
        ]);

        return $task;
    }

    public function update(Task $task, TaskData $data, User $actor): Task
    {
        $before = clone $task;

        $task->update([
            'title' => $data->title,
            'description' => $data->description,
            'priority' => $data->priority,
            'status' => $data->status ?? $task->status,
        ]);

        $task->refresh();
        $this->recordChanges($before, $task, $actor);

        return $task;
    }

    public function delete(Task $task, User $actor): void
    {
        $this->record($actor, ActivityAction::Deleted, $task, [
            'title' => $task->title,
        ]);

        $task->delete();
    }

    private function recordChanges(Task $before, Task $after, User $actor): void
    {
        $changes = [];

        foreach (['title', 'description', 'priority'] as $field) {
            $from = $this->value($before->{$field});
            $to = $this->value($after->{$field});

            if ($from !== $to) {
                $changes[$field] = ['from' => $from, 'to' => $to];
            }
        }

        if ($changes !== []) {
            $this->record($actor, ActivityAction::Updated, $after, [
                'changes' => $changes,
            ]);
        }

        if ($before->status !== $after->status) {
            $this->record($actor, ActivityAction::StatusChanged, $after, [
                'from' => $before->status->value,
                'to' => $after->status->value,
            ]);
        }
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    /**
     * @param  array<string, mixed>|null  $properties
     */
    private function record(User $actor, ActivityAction $action, Task $task, ?array $properties): void
    {
        RecordActivity::dispatchSync(
            $actor->id,
            $action->value,
            $task->getMorphClass(),
            $task->id,
            $properties,
        );
    }
}
