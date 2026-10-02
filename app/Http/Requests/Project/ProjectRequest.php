<?php

namespace App\Http\Requests\Project;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];

        $user = $this->user();

        if ($user instanceof User && $user->isAdmin()) {
            $rules['task_manager_id'] = [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    'role_id',
                    Role::query()->where('name', RoleName::TaskManager->value)->value('id'),
                ),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'task_manager_id' => 'task manager',
        ];
    }
}
