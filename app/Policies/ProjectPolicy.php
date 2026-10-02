<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->canManage($user, $project);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->canManage($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->canManage($user, $project);
    }

    private function canManage(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->user_id === $user->id;
    }
}
