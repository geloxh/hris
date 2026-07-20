# HRIS Backend

Plain PHP (no framework), MySQL, deployed via Docker. Frontend is expected to be
static HTML/CSS/JS calling this as a JSON API.

## Architecture

```
public/index.php        <- single entry point, everything routes through here
bootstrap.php            <- autoloader, .env loading, error handling
src/routes.php            <- every URL -> Controller mapping lives here
src/Core/                 <- framework-ish primitives (Router, Database, Model, Auth...)
src/Middleware/            <- AuthMiddleware, RoleMiddleware, CorsMiddleware
src/Modules/<Name>/        <- one folder per business domain (Model + Controller + Service)
database/migrations/       <- numbered, immutable .sql files, applied by migrate.php
database/seed.php          <- baseline roles/departments/leave types/admin login
docker/                    <- Dockerfile, nginx config, mysql init
```

Built modules: **Auth**, **Employee**, **EmployeeData** (departments/positions),
**TimeAttendance** (clock in/out + leave). Every other module
(Payroll, Recruitment, Performance, Learning, SelfService, Analytics, Compliance)
has a `STUB.md` in its folder describing the suggested tables/files - follow the
same Model -> Service -> Controller pattern already used by Employee/TimeAttendance.

## Why these choices

- **No session cookies.** Auth is opaque bearer tokens hashed in MySQL
  (`personal_access_tokens`) - stateless-ish, works cleanly with a vanilla JS
  frontend calling `fetch()`, and revokes instantly on termination.
- **Every module owns its table(s).** A Model only touches its own table;
  cross-table workflows (e.g. onboarding an employee + creating their login)
  live in a `*Service` class, wrapped in a DB transaction.
- **No ORM.** `Core/Model.php` gives generic CRUD + a query builder-lite; anything
  more complex is a hand-written SQL method on that module's Model (see
  `EmployeeModel::searchPaginate()`), so nothing is hidden behind magic.

## Local setup

```bash
cp .env.example .env
# edit .env if you want different DB credentials, otherwise the defaults work out of the box

docker compose up -d --build

docker compose exec php php database/migrate.php
docker compose exec php php database/seed.php
```

API is now at `http://localhost:8080/api/...`
phpMyAdmin (dev only) at `http://localhost:8081`

Default admin login (seeded, **change immediately**):
```
email: admin@hris.local
password: ChangeMe123!
```

## Auth flow (for the frontend)

```
POST /api/auth/login   { "email": "...", "password": "..." }  -> { data: { token, user } }
```
Store the token client-side, send it on every subsequent request:
```
Authorization: Bearer <token>
```

## Adding a new module

1. `mkdir src/Modules/YourModule`
2. Write `YourModuleModel.php` extending `App\Core\Model` (set `$table`, `$fillable`)
3. If it touches more than one table, add `YourModuleService.php`
4. Write `YourModuleController.php` extending `App\Core\Controller`
5. Add a migration file `database/migrations/0XX_create_your_table.sql`
6. Register routes in `src/routes.php`, wrapping protected ones with
   `[AuthMiddleware::class, RoleMiddleware::only(['admin','hr'])]`

## Security notes

- Change `APP_KEY` and all `.env` passwords before any real deployment.
- The MySQL `DB_USERNAME` should never be `root` in production - the compose
  file already provisions a scoped `DB_USERNAME`/`DB_PASSWORD` app user.
- `docker/nginx/default.conf` blocks direct access to `src/`, `database/`,
  `storage/`, and `.env` - only `public/` is served.
- Put this whole stack behind HTTPS (reverse proxy / load balancer) before
  exposing it beyond localhost, then uncomment the HSTS header in the nginx config.
