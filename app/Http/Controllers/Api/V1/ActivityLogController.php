<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\Activity\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ActivityLogController extends Controller
{
    public function index(Project $project, Task $task, ActivityLogService $logs): AnonymousResourceCollection
    {
        Gate::authorize('view', $project);
        abort_unless($task->project_id === $project->id, JsonResponse::HTTP_NOT_FOUND);

        return ActivityLogResource::collection($logs->forTask($task));
    }
}
