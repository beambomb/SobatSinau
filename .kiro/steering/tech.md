# Technology and development

## Stack

- PHP `^8.3` and Laravel Framework `^13.17` (the Composer manifest is authoritative for versions).
- Laravel Sanctum `^4.0` for bearer-token API authentication.
- Spatie Laravel Permission `^8.3` for role/permission middleware and RBAC.
- Eloquent ORM, Laravel Form Requests, Policies, backed enums, and filesystem Storage.
- Vite `^8`, laravel-vite-plugin, Tailwind CSS, PostCSS/autoprefixer, and Alpine.js for the minimal frontend asset pipeline.
- PHPUnit `^12.5` via Laravel's test runner; Laravel Pint `^1.27` for PHP formatting.
- SQLite is the `.env.example` default; MySQL/MariaDB, PostgreSQL, and SQL Server connections are also configured. PHPUnit uses in-memory SQLite.

## Architecture and conventions

- Configure application wiring in `bootstrap/app.php`; routes are split into `routes/api.php`, `routes/web.php`, and `routes/console.php`.
- Put API controllers under `app/Http/Controllers/Api` (admin controllers under its `Admin` subdirectory), validation in `app/Http/Requests`, domain persistence in `app/Models`, authorization in `app/Policies`, and shared constants/statuses in `app/Enums`.
- Use Sanctum and Spatie middleware at route boundaries, then enforce resource-specific ownership or membership in the controller/policy layer.
- Follow existing Eloquent relationships, enum casts, eager loading, JSON responses, and typed model attributes. No service/repository or API-resource layer is currently established; avoid introducing one without a clear need.
- Use public-disk storage paths consistently for post, assignment, and submission files.

## Common commands

```text
composer install                 # Install PHP dependencies
composer run setup                # Install, prepare .env/key, migrate, install npm deps, build assets
php artisan serve                 # Run the local Laravel server
npm run dev                       # Run the Vite development server
npm run build                     # Build production assets
composer run test                 # Clear config and run Laravel/PHPUnit tests
php artisan test                  # Run tests directly
vendor/bin/pint --format agent    # Format PHP code
php artisan storage:link          # Expose the public storage disk
php artisan migrate:fresh --seed # Rebuild and seed the database
php artisan route:list --path=api # Inspect API routes
```

Long-running server/watch commands should be run manually when needed. Check the actual manifests and current source before relying on older documentation: `CLAUDE.md` contains stale framework/version claims, and parts of the route/controller, migration, and seeder setup are currently incomplete. Add or update tests under `tests/Feature` or `tests/Unit` for behavior changes; the configured test environment uses in-memory SQLite.
