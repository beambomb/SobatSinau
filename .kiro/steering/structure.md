# Project structure

```text
app/
  Enums/                         # UserRole, PostType, SubmissionStatus
  Http/
    Controllers/
      Api/                       # REST API controllers
        Admin/                   # Admin user/dashboard endpoints
    Requests/                    # Form Request validation
  Models/                        # User, Classroom, Post, Comment, Assignment, Submission
  Policies/                      # Resource authorization policies
  Providers/                    # Laravel service providers
bootstrap/
  app.php                        # Application, route, middleware, exception wiring
config/                          # Laravel, Sanctum, permission, and app configuration
database/
  factories/                     # Model factories
  migrations/                   # Framework, permission, and LMS schema migrations
  seeders/                       # Database seeders
resources/
  css/app.css                   # Tailwind entrypoint
  js/app.js                     # Alpine/Vite entrypoint
  views/                         # Blade views (currently minimal/empty)
routes/
  api.php                        # Main authenticated REST API and role gates
  web.php                        # Web/scaffold routes
  console.php                    # Console routes
tests/
  Feature/                       # Feature/API tests
  Unit/                          # Unit tests
public/                          # Web root and built/public assets
storage/                         # Runtime logs, cache, and uploaded files
```

## Organization rules

- Follow PSR-4 `App\\` autoloading under `app/`; use `Database\\Factories\\`, `Database\\Seeders\\`, and `Tests\\` namespaces for their respective directories.
- Keep API route declarations in `routes/api.php` and group protected endpoints under `auth:sanctum`; use the existing `role:guru|admin` and `role:admin` gates where appropriate.
- Keep feature-specific request validation, models, policies, and controllers in their established directories rather than placing logic in routes.
- Add schema changes as timestamped migrations and keep factories/seeders aligned with model relationships.
- Inspect neighboring files before adding abstractions: the current codebase is controller/Eloquent oriented and does not show a dedicated service or repository layer.

## Current repository state

The structure is an evolving scaffold rather than a fully consistent release. Some classes referenced by `routes/api.php`, some web/auth files, and a seeder referenced by `DatabaseSeeder` are absent; migration coverage is also inconsistent with the model/controller expectations. Verify referenced files and run migrations/tests before assuming a feature is complete.
