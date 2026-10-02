<?php

namespace App\Services\Activity;

use App\Models\ActivityLog;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ActivityLogService
{
    public const PER_PAGE = 8;

    /**
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    public function forTask(Task $task): LengthAwarePaginator
    {
        return $task->activityLogs()
            ->with('user')
            ->latest('id')
            ->paginate(self::PER_PAGE);
    }
}
