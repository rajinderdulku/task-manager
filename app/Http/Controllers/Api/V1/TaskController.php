<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Task\TaskData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Task\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(Project $project, TaskService $tasks): AnonymousResourceCollection
    {
        Gate::authorize('view', $project);

        return TaskResource::collection($tasks->list($project));
    }

    public function store(TaskRequest $request, Project $project, TaskService $tasks): JsonResponse
    {
        Gate::authorize('update', $project);

        /** @var User $user */
        $user = $request->user();

        $task = $tasks->create($project, TaskData::fromRequest($request), $user);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(TaskRequest $request, Project $project, Task $task, TaskService $tasks): TaskResource
    {
        Gate::authorize('update', $project);
        $this->ensureTaskIsOnProject($project, $task);

        /** @var User $user */
        $user = $request->user();

        return new TaskResource($tasks->update($task, TaskData::fromRequest($request), $user));
    }

    public function destroy(Request $request, Project $project, Task $task, TaskService $tasks): JsonResponse
    {
        Gate::authorize('update', $project);
        $this->ensureTaskIsOnProject($project, $task);

        /** @var User $user */
        $user = $request->user();

        $tasks->delete($task, $user);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    private function ensureTaskIsOnProject(Project $project, Task $task): void
    {
        abort_unless($task->project_id === $project->id, JsonResponse::HTTP_NOT_FOUND);
    }
}
