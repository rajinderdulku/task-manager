<?php

namespace App\Data\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;

final readonly class TaskData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public TaskPriority $priority,
        public ?TaskStatus $status,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $description = trim($request->string('description')->toString());

        return new self(
            title: $request->string('title')->toString(),
            description: $description === '' ? null : $description,
            priority: $request->filled('priority')
                ? TaskPriority::from($request->string('priority')->toString())
                : TaskPriority::Medium,
            status: $request->filled('status')
                ? TaskStatus::from($request->string('status')->toString())
                : null,
        );
    }
}
