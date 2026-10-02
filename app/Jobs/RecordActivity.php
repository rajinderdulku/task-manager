<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordActivity implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>|null  $properties
     */
    public function __construct(
        public ?int $userId,
        public string $action,
        public string $subjectType,
        public int $subjectId,
        public ?array $properties,
    ) {}

    public function handle(): void
    {
        ActivityLog::query()->create([
            'user_id' => $this->userId,
            'action' => $this->action,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'properties' => $this->properties,
        ]);
    }
}
