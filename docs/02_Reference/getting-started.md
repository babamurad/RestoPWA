# Getting Started (RestoPWA)

This guide provides instructions for setting up the development environment and running quality checks.

## Environment Setup

To initialize the project for the first time, run the following command:

```bash
composer setup
```

This command will:
1. Install PHP dependencies (`composer install`).
2. Create your `.env` file from `.env.example` (if it doesn't exist).
3. Generate the application key (`php artisan key:generate`).
4. Run database migrations (`php artisan migrate`).
5. Install JavaScript dependencies (`npm install`).
6. Build frontend assets (`npm run build`).

## Quality Gate

Before submitting any changes, ensure all checks pass by running the unified check script:

```bash
composer check
```

This runs both **Laravel Pint** (linting) and **PHPUnit** (testing).

### Individual Checks

- **Linting**: `./vendor/bin/pint --test` (to check style) or `./vendor/bin/pint` (to fix style).
- **Testing**: `php artisan test` or `composer test`.
- **Frontend Build**: `npm run build`.

## Development

To start the local development server and asset watcher:

```bash
composer dev
```

This uses `concurrently` to run `php artisan serve`, the queue listener, logs observer, and Vite dev server simultaneously.
