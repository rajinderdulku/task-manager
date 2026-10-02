# Architecture overview

One Laravel application serves the React UI and the JSON API. The UI is not Inertia. `resources/js` is a React TypeScript app, built by Vite, and mounted from `resources/views/app.blade.php` at `/`.

```text
Browser
  React screens in resources/js
        |
        |  Authorization: Bearer {token}
        v
  /api/v1
        |
        v
  Form request  ->  DTO  ->  Service  ->  Eloquent model
        |
        v
  JSON resource
```

## Roles

Roles live in a `roles` table. `users.role_id` is required and points at one role. There is no role string on the user and no pivot table.

| Role | What they can do |
|---|---|
| `admin` | See and change every project and task. When creating a project, they must choose the task manager who owns it. |
| `task_manager` | Create and change only their own projects, and the tasks on those projects. Registration always assigns this role. |

Access is decided in `App\Policies\ProjectPolicy`. A task does not have its own policy. Creating, updating, or deleting a task authorizes `update` on the parent project. A task id that does not belong to that project returns 404.

## Request flow

Each area follows the same path:

1. The route is declared in `routes/api.php` under `/api/v1`.
2. A form request validates the input.
3. A small DTO in `app/Data` carries the validated values into the service.
4. A service in `app/Services` performs the work with Eloquent.
5. A JSON resource shapes the response.

There is no repository layer and no command or query bus.

| Area | Service | HTTP |
|---|---|---|
| Auth | `App\Services\Auth\AuthService` | register, login, current user, logout |
| Projects | `App\Services\Project\ProjectService` | project CRUD and the admin task-manager list |
| Tasks | `App\Services\Task\TaskService` | task CRUD nested under a project |
| Dashboard | `App\Services\Dashboard\DashboardService` | counts for the home page |
| Activity | `App\Services\Activity\ActivityLogService` | paginated log for one task |

Auth uses Laravel Sanctum personal access tokens. Logout deletes the token that made the request.

## Activity log

`TaskService` records create, update, delete, and status changes by dispatching `App\Jobs\RecordActivity`. The job writes one row to `activity_logs`. The subject is a morph to the task (`subject_type` is `task` because of the morph map in `AppServiceProvider`).

The job is dispatched with `dispatchSync`, so the row exists before the API response returns and the task popup can show it immediately. The class still implements `ShouldQueue`, which is the seam for moving that write onto a real queue. See [Design decisions](decisions.md).

## UI

`resources/js/app.tsx` loads the current user when a token is stored. Signed-out visitors see the auth screen. Signed-in visitors see the workspace:

- **Dashboard** reads `GET /api/v1/dashboard`. Admin counts are global. Task manager counts are limited to projects they own.
- **Projects** lists projects, five per page, with task count and completion. Descriptions in the table are shortened to the first 12 words.
- **Board** opens from a project. Tasks move between To do, In progress, and Done by dragging.
- **Task popup** opens when a task card is clicked. Title, description, status, and priority can be edited. The right side loads activity and requests the next page as the list scrolls.

## API documentation

Scramble reads the routes, form requests, and resources and builds an OpenAPI 3.1 document. The interactive page is `GET /docs`. The generated file in this repository is `docs/openapi.json`.
