# Task manager

A small task manager with two roles. An admin sees every project and task. A task manager works only on the projects they own.

The browser UI is a React application served by Laravel. It talks to a JSON API under `/api/v1`.

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js 22 or newer
- SQLite, which is the default in `.env.example`

MySQL works as well. Set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` before migrating.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

While changing the React UI, run `npm run dev` in a second terminal and leave `php artisan serve` running.

The seeded admin account is:

- Email: `admin@taskmanager.test`
- Password: `password`

Change `ADMIN_NAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` in `.env` before seeding if you want different values. Registration from the sign-up screen always creates a task manager.

## Tests

```bash
php artisan test
```

PHPUnit uses an in-memory SQLite database. It does not touch the database from `.env`.

## API documentation

With the app running locally, open [http://127.0.0.1:8000/docs](http://127.0.0.1:8000/docs). That page renders the OpenAPI document with Stoplight Elements.

A copy of the same document is committed at [docs/openapi.json](docs/openapi.json). After changing routes or request rules, export it again:

```bash
php artisan scramble:export --path=docs/openapi.json
```

Authenticated requests use a Sanctum bearer token: `Authorization: Bearer {token}`. The token is returned by register and login.

## Further reading

- [Architecture overview](docs/architecture.md)
- [Design decisions and trade-offs](docs/decisions.md)
