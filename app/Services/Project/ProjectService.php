<?php

namespace App\Services\Project;

use App\Data\Project\ProjectData;
use App\Enums\RoleName;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class ProjectService
{
    public const PER_PAGE = 5;

    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function list(User $user): LengthAwarePaginator
    {
        $query = Project::query()->with('user')->withTaskCounts()->latest();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->paginate(self::PER_PAGE);
    }

    /**
     * @return Collection<int, User>
     */
    public function taskManagers(): Collection
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', RoleName::TaskManager))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function create(User $actor, ProjectData $data): Project
    {
        return Project::query()->create([
            'user_id' => $this->ownerId($actor, $data),
            'name' => $data->name,
            'description' => $data->description,
        ])->load('user')->loadTaskCounts();
    }

    public function update(Project $project, ProjectData $data, User $actor): Project
    {
        $attributes = [
            'name' => $data->name,
            'description' => $data->description,
        ];

        if ($actor->isAdmin()) {
            $attributes['user_id'] = $data->taskManagerId;
        }

        $project->update($attributes);

        return $project->refresh()->load('user')->loadTaskCounts();
    }

    private function ownerId(User $actor, ProjectData $data): int
    {
        if ($actor->isAdmin()) {
            return (int) $data->taskManagerId;
        }

        return $actor->id;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
