<?php

namespace App\Data\Project;

use Illuminate\Foundation\Http\FormRequest;

final readonly class ProjectData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?int $taskManagerId,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $description = trim($request->string('description')->toString());

        return new self(
            name: $request->string('name')->toString(),
            description: $description === '' ? null : $description,
            taskManagerId: $request->filled('task_manager_id') ? $request->integer('task_manager_id') : null,
        );
    }
}
