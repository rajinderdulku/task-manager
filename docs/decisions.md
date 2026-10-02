# Design decisions and trade-offs

## React calls a JSON API

The UI could have been Inertia, with Laravel rendering pages and passing props. The assignment asks for an API and a UI, so the React app is a separate client. That keeps the HTTP contract explicit and makes the same API usable without the screens. The cost is two running processes during UI work (`php artisan serve` and `npm run dev`) and a hand-written API client in `resources/js/lib/api/client.ts`.

## Services, DTOs, and form requests

Business rules sit in service classes. Form requests validate HTTP input. DTOs stop the service from depending on the request object.

A repository layer was not added. The services are thin Eloquent calls plus a few rules, such as who owns a new project. A repository would have duplicated the query builder without a second persistence implementation.

Actions and a command bus were also skipped. Each use case is one public method on a service. That is enough at this size and keeps a feature easy to find.

## One role column, not a pivot

A user has exactly one role, so `users.role_id` is a required foreign key. A `role_user` pivot would allow many roles per user, which this product does not need. Storing the role name as a string on `users` would skip the roles table and make renaming or listing roles harder.

Registration ignores any role sent by the client and always stores `task_manager`. The admin user is created only by `AdminUserSeeder`.

## Project ownership

An admin does not own the projects they create. The create form requires a task manager, and that user becomes `projects.user_id`. A task manager who sends `task_manager_id` is ignored and remains the owner. This keeps “my projects” meaningful for a task manager and stops them from handing a project to someone else.

The trade-off is that an admin’s own dashboard counts include those projects only because the admin is unscoped, not because the admin is the owner.

## Tasks reuse the project policy

Task routes are nested under a project. If you may update the project, you may change its tasks. A separate task policy would repeat the same owner-or-admin check. A mismatched project and task id returns 404 so one project’s URL cannot address another project’s task.

Dragging a card sends the same update request as the popup. Only a real status change is logged as `status_changed`. Title, description, and priority changes are logged as `updated`.

## Activity is written in the request

`RecordActivity` is a queue job, but `TaskService` calls `dispatchSync`. The activity sidebar would otherwise stay empty until a worker ran, because `.env.example` sets `QUEUE_CONNECTION=database`.

The trade-off is that the log write adds a little time to the task request and will not retry on failure the way a queued job would. Switching the call to `dispatch()` is enough to make it asynchronous once a queue worker is part of the setup.

Deleting a task writes the log first, then deletes the task. The log row remains, because `activity_logs` does not cascade from the task. The popup is gone with the task, so that delete entry is an audit row rather than something the UI shows.

## Pagination

The project list returns five projects per page. The screen shows page numbers because the whole list is one table. Task activity returns eight entries per page and the sidebar asks for the next page when the reader reaches the bottom. Eight is small enough that a busy task scrolls, and large enough that a new task does not request a second page immediately.

## API docs are generated

Scramble builds the OpenAPI document from the code, and Stoplight Elements renders it at `/docs`. A committed `docs/openapi.json` gives reviewers the contract without starting the app. That file can fall behind the routes if it is not exported again after an API change. The README includes the export command.

Docs access uses Scramble’s `RestrictedDocsAccess` middleware, so the `/docs` page is available in the local environment and closed in production unless that middleware is changed.
