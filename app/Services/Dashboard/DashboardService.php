<?php

namespace App\Services\Dashboard;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

final class DashboardService
{
    /**
     * @return array{projects: int, tasks: int, todo: int, in_progress: int, completed: int}
     */
    public function stats(User $user): array
    {
        $projects = Project::query();
        $tasks = Task::query();

        if (! $user->isAdmin()) {
            $projects->where('user_id', $user->id);
            $tasks->whereHas('project', fn ($query) => $query->where('user_id', $user->id));
        }

        $byStatus = $tasks
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $todo = (int) ($byStatus[TaskStatus::Todo->value] ?? 0);
        $inProgress = (int) ($byStatus[TaskStatus::InProgress->value] ?? 0);
        $completed = (int) ($byStatus[TaskStatus::Completed->value] ?? 0);

        return [
            'projects' => $projects->count(),
            'tasks' => $todo + $inProgress + $completed,
            'todo' => $todo,
            'in_progress' => $inProgress,
            'completed' => $completed,
        ];
    }
}
