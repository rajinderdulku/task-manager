<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['user_id', 'name', 'description'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @param  Builder<Project>  $query
     */
    public function scopeWithTaskCounts(Builder $query): void
    {
        $query->withCount([
            'tasks',
            'tasks as completed_tasks_count' => fn (Builder $tasks) => $tasks->where('status', TaskStatus::Completed),
        ]);
    }

    public function loadTaskCounts(): static
    {
        $this->loadCount([
            'tasks',
            'tasks as completed_tasks_count' => fn (Builder $tasks) => $tasks->where('status', TaskStatus::Completed),
        ]);

        return $this;
    }

    /**
     * @return MorphMany<ActivityLog, $this>
     */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
