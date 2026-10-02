<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Project\ProjectData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Services\Project\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectService $projects): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Project::class);

        /** @var User $user */
        $user = $request->user();

        return ProjectResource::collection($projects->list($user));
    }

    public function taskManagers(Request $request, ProjectService $projects): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->isAdmin(), JsonResponse::HTTP_FORBIDDEN);

        return response()->json([
            'data' => $projects->taskManagers()->map(fn (User $manager): array => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
            ])->values(),
        ]);
    }

    public function store(ProjectRequest $request, ProjectService $projects): JsonResponse
    {
        Gate::authorize('create', Project::class);

        /** @var User $user */
        $user = $request->user();

        $project = $projects->create($user, ProjectData::fromRequest($request));

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(Project $project): ProjectResource
    {
        Gate::authorize('view', $project);

        return new ProjectResource($project->load('user')->loadTaskCounts());
    }

    public function update(ProjectRequest $request, Project $project, ProjectService $projects): ProjectResource
    {
        Gate::authorize('update', $project);

        /** @var User $user */
        $user = $request->user();

        return new ProjectResource(
            $projects->update($project, ProjectData::fromRequest($request), $user),
        );
    }

    public function destroy(Project $project, ProjectService $projects): JsonResponse
    {
        Gate::authorize('delete', $project);

        $projects->delete($project);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
