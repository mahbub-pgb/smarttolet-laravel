# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Smart To-Let — a rental marketplace for Bangladesh. Laravel 11 / PHP 8.2+. The codebase serves **two front doors from one app**:

- A versioned **REST API** under `/api/v1` (stateless, JWT-authenticated). Controllers in `app/Http/Controllers/Api/V1`, routes split per-module in `routes/api/*.php`.
- A **server-rendered Blade web UI** (session-authenticated, `web` guard). Controllers in `app/Http/Controllers/Web`, routes in `routes/web.php`, views in `resources/views`.

The two layers share Services, Repositories, Models, and Enums but have separate controllers, auth guards, and middleware. When changing business logic, change the **Service** so both front doors stay consistent — don't duplicate logic in a controller.

Backing services: **MySQL 8** (spatial `POINT` + `FULLTEXT`), **Redis** (cache / OTP store / rate limiting), **Laravel Reverb** (realtime chat websockets).

## Commands

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # runs SuperAdminSeeder + demo data
php artisan storage:link

composer dev                        # all-in-one: serve + queue + pail logs + vite (concurrently)
# or individually:
php artisan serve                   # http://localhost:8000  (API at /api/v1, web UI at /)
php artisan reverb:start            # websocket server for realtime chat
php artisan queue:work              # broadcast events + notifications
npm run dev                         # vite (Tailwind) for the Blade UI

php artisan test                    # full suite (SQLite in-memory)
php artisan test --filter=OtpTest   # single test class/method
php artisan test tests/Feature/Auth # single directory
vendor/bin/pint                     # format (Laravel Pint)
php artisan l5-swagger:generate     # regenerate Swagger docs at /api/v1/docs
```

Health check: `GET /api/v1/health`. API docs (Swagger UI): `/api/v1/docs`.

## Architecture

Strictly layered, dependencies point downward only:

```
Route → Middleware (auth, permission, validate) → Controller → Service → Repository → Eloquent
```

- **Controllers are thin**: resolve a Form Request, call one Service method, return an API Resource wrapped in the envelope. No business logic.
- **Services** (`app/Services/{Admin,Auth,Blog,Chat,Engagement,Geo,Listing,Media,Notification,Payment,Settings,Sms,Subscription}`) hold all business logic.
- **Repositories** (`app/Repositories`) wrap Eloquent behind interfaces; `BaseRepository` provides `create/find/findOne/update/delete/paginate`.
- **Config-only env**: `env()` is read only inside `config/*.php`; everywhere else use `config(...)`.

### API response envelope (critical convention)

Every API response — success or error — goes through `App\Support\ApiResponse` (`success` / `error` / `paginated`). Never `response()->json()` raw shapes in API controllers. Paginated lists put items in `data` and pagination in `meta`. Domain errors are thrown as `App\Exceptions\ApiException` and all framework exceptions (validation, auth, 404, throttle) are mapped to the envelope centrally in `bootstrap/app.php` — add new global error mappings there, not in controllers.

### Auth (two systems)

- **API**: phone-first OTP signup. `POST /auth/otp/request` → `POST /auth/otp/verify` (issues JWT access + refresh pair) → `PUT /auth/profile`. OTP stores **only a SHA-256 hash + attempt counter** in Redis with TTL, resend cooldown, and lockout (`app/Services/Auth/OtpService.php` + `RedisOtpRepository`). JWT via `app/Services/Auth/JwtService.php` and the `jwt.auth` guard. A `token_version` column invalidates all refresh tokens on logout/suspension/role change.
- **Web**: session-based `web` guard with the same phone+OTP signup flow rendered in Blade (`routes/web.php`, `app/Http/Controllers/Web/Auth`).

### RBAC

Roles ascend: `user → moderator → admin → super_admin` (`app/Enums/Role.php`). Routes are guarded on **granular permissions**, never raw roles: `->middleware('permission:manage_users')` (`app/Enums/Permission.php`, `RequirePermission` middleware). Higher roles inherit lower permissions via `Permission::forRole()`. A **rank guard** (`User::outranks()`, enforced in `UserManagementService`) blocks acting on, or assigning a role to, a target of equal-or-higher rank.

### Driver-aware migrations / tests

Migrations branch on DB driver: spatial `POINT`/`SPATIAL` + `FULLTEXT` indexes only on MySQL; on SQLite, decimal lat/long columns and `LIKE` keyword search are used. This lets the test suite run on **SQLite in-memory** (see `phpunit.xml`) with no MySQL/Redis. Tests swap in-memory doubles for external deps: `Tests\Support\ArrayOtpRepository` (OTP) and `Tests\Support\SpySmsClient` (SMS). Keep listing-search and geo changes working under both drivers.

### Pluggable adapters

- **SMS** (`app/Services/Sms`) and **Payment gateways** (`app/Services/Payment/Gateways`: bkash, nagad, rocket) are adapter-based. Payment gateways ship working **sandbox** flows (`initiate` → `verify`) needing no real credentials; set `*_MODE=live` + real creds to go live. Payment verify is idempotent via `intent_id`. Per-plan listing limits enforced in `ListingService`.

### Settings

`app/Services/Settings/SettingsService.php` resolves config from the DB (overriding env), Redis-cached ~10 min. Secrets are masked in admin views and hidden from public responses. The Google Maps **browser** key is exposed via `GET /public/settings` (restrict by HTTP referrer); the server key stays secret.

### Realtime chat

Reverb websockets authenticated by the JWT access token (`bootstrap/app.php → withBroadcasting` uses `jwt.auth`). Channels in `routes/channels.php`: `user.{id}` (private) and `conversation.{id}` (its two participants). Events (`app/Events`): `message.new`, `message.status`, `typing`, `notification.new` — processed by the queue worker.

## API route modules

`routes/api.php` requires per-area files in `routes/api/`: `auth, settings, listings, me, chat, payments, blog, media, public, admin`. Add a route to the matching module file, not the root.
