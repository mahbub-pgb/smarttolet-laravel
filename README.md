# Smart To-Let — Laravel 11 REST API

A production-grade REST API backend for **Smart To-Let**, a rental marketplace
for Bangladesh. Built with **Laravel 11 (PHP 8.2+)**, **MySQL 8**, **Redis**
(cache / OTP / rate limiting) and **Laravel Reverb** (realtime chat).

The API is versioned under **`/api/v1`** and every response uses a standard
envelope:

```json
{ "success": true, "message": "OK", "data": {}, "meta": {} }
```

---

## Architecture

Strictly layered, dependencies pointing downward only:

```
Route → Middleware (auth, permission, validate) → Controller → Service → Repository → Eloquent
```

- **Controllers** are thin: resolve a Form Request, call one service method,
  return an API Resource wrapped in the envelope (`App\Support\ApiResponse`).
- **Services** (`app/Services/**`) hold all business logic and orchestration.
- **Repositories** (`app/Repositories/**`) wrap Eloquent behind interfaces
  (`BaseRepository` provides `create/find/findOne/update/delete/paginate`).
- **Form Requests** validate + authorize every write.
- **Policies / Gates** enforce ownership and the granular RBAC permission map.
- All configuration lives in `config/*.php`; `env()` is never read outside config.

### Key building blocks

| Concern | Where |
|---|---|
| Response envelope | `app/Support/ApiResponse.php`, trait `Concerns/RespondsWithApi` |
| Domain errors | `app/Exceptions/ApiException.php`, rendered in `bootstrap/app.php` |
| RBAC | `app/Enums/{Role,Permission}.php`, middleware `RequirePermission`/`RequireRole` |
| JWT auth | `app/Services/Auth/JwtService.php`, guard `app/Auth/JwtGuard.php` |
| OTP | `app/Services/Auth/OtpService.php` + `RedisOtpRepository` (hash + TTL + lockout) |
| Settings | `app/Services/Settings/SettingsService.php` (DB → env, Redis-cached 10 min) |
| Listings search | `app/Repositories/ListingRepository.php` (FULLTEXT + geo radius) |
| Realtime | `app/Events/*` (Reverb), channels in `routes/channels.php` |
| SMS / Payments | pluggable adapters under `app/Services/Sms` and `app/Services/Payment` |

---

## Requirements

- PHP **8.2+** with extensions: `pdo_mysql`, `redis` (or use `predis`), `gd`
  (Intervention Image), `mbstring`, `openssl`, `curl`, `bcmath`.
- **MySQL 8** (spatial `POINT` + `FULLTEXT` indexes are used).
- **Redis 6+**.
- Composer 2.

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure .env: MySQL connection, Redis, JWT/OTP, Cloudinary, Google Maps,
# SMS, payment-gateway credentials, and SUPER_ADMIN_* seed values.

php artisan migrate --seed       # runs SuperAdminSeeder + demo data
php artisan storage:link         # for the local image fallback disk
```

> The migrations are **driver-aware**: spatial `POINT`/`SPATIAL` and `FULLTEXT`
> indexes are created only on MySQL. On SQLite (used by the test suite) the
> decimal `latitude`/`longitude` columns and `LIKE` keyword search are used,
> so the suite runs anywhere with `pdo_sqlite`.

### Run

```bash
php artisan serve                # http://localhost:8000  (API at /api/v1)
php artisan reverb:start         # websocket server for realtime chat
php artisan queue:work           # broadcast events + notifications
```

API docs (Swagger UI): **`/api/v1/docs`**
(regenerate with `php artisan l5-swagger:generate`).

Health check: `GET /api/v1/health`.

---

## Authentication flow

Phone-first, two-step registration backed by a Redis-stored OTP:

1. `POST /api/v1/auth/otp/request { mobile }` — generates a cryptographically
   random numeric OTP, stores **only its SHA-256 hash + attempt counter** in
   Redis with a TTL, enforces a per-number resend cooldown, and sends the code
   **via SMS only**. The code is never returned by the API in any environment.
2. `POST /api/v1/auth/otp/verify { mobile, code }` — on success, creates a
   phone-verified user with a **Free** subscription and returns an
   **access + refresh** token pair.
3. `PUT /api/v1/auth/profile` — complete the profile (name, email, photo, geo,
   area preferences) and optionally set a password.

Sessions: short-lived **access token** (`Authorization: Bearer`), long-lived
**refresh token** set as an httpOnly cookie (and returned in the body for
non-browser clients). `POST /auth/refresh` rotates tokens; a `token_version`
column invalidates all refresh tokens on logout/suspension/role change.
Login by **mobile or email** via `POST /auth/login`.

---

## Authorization (RBAC)

Roles ascend in power: `user → moderator → admin → super_admin`. Routes are
guarded on **granular permissions**, never raw roles
(`->middleware('permission:manage_users')`). Higher roles inherit lower roles'
permissions (`Permission::forRole()`). A **rank guard** prevents acting on, or
assigning a role to, a target of equal-or-higher rank
(`UserManagementService` + `User::outranks()`).

---

## Realtime chat

- Authenticate the websocket via the JWT access token; the broadcasting auth
  route uses the `jwt.auth` middleware (`bootstrap/app.php → withBroadcasting`).
- Channels: `user.{id}` (private, per-user) and `conversation.{id}`
  (authorised to its two participants in `routes/channels.php`).
- Events: `message.new`, `message.status` (delivered/read), `typing`, and
  `notification.new`.

Point Laravel Echo at the Reverb server using the `REVERB_*` env values.

---

## Payments

Pluggable gateway adapters (`bkash`, `nagad`, `rocket`) under
`app/Services/Payment/Gateways`. Each ships a working **sandbox** flow
(`initiate` → `verify`) needing no real credentials; drop in live credentials
and set `*_MODE=live` to go live. Flow:
`POST /payments/initiate` → checkout `redirect_url` → `POST /payments/verify`
(idempotent via `intent_id`) → subscription activated. Per-plan listing limits
are enforced in `ListingService`.

---

## Testing

```bash
php artisan test
```

The suite (`tests/Feature`, `tests/Unit`) covers the auth/OTP flow (including
SMS-only delivery, attempt lockout and resend cooldown), the RBAC + rank guard,
listing search (keyword + geo radius + plan limits) and the response envelope.
It runs against **SQLite in-memory** with in-memory test doubles for the OTP
store (`Tests\Support\ArrayOtpRepository`) and SMS client
(`Tests\Support\SpySmsClient`) — no MySQL or Redis required.

---

## Production notes

- **Stateless app behind a load balancer.** `TrustProxies` is enabled so client
  IPs are correct behind Nginx; set `JWT_REFRESH_COOKIE_SECURE=true` (HTTPS).
- **Queue workers** process broadcast + notification jobs; use a Redis-backed
  queue with a distributed lock for any scheduled work.
- **Google Maps:** the browser key is exposed via `GET /public/settings` (the
  SDK needs it) — **restrict it by HTTP referrer** in the Google Cloud console.
  The server key (geocoding/places) is secret and never returned publicly.
- **Secrets** (`sms_api_key`, `cloudinary_api_secret`, Maps server key, …) are
  hidden from public responses and masked in the admin settings view.
- **CORS:** any `localhost` origin is allowed in dev; only the configured
  `CLIENT_URL`(s) in production (`config/cors.php`), with credentials enabled.
- **Rate limiting** is Redis-backed: a global API limiter plus tight limiters on
  auth/OTP routes (`AppServiceProvider::configureRateLimiters`).
- **Maintenance mode** (`SettingsService`) blocks normal users but lets staff
  through; the middleware runs after auth so it can see the user's role.

---

## Route map (high level)

| Area | Base |
|---|---|
| Auth | `/api/v1/auth/*` |
| Listings | `/api/v1/listings/*` |
| Me (favorites, saved searches, notifications) | `/api/v1/me/*` |
| Chat | `/api/v1/chat/*` |
| Payments / subscriptions | `/api/v1/payments/*` |
| Blog | `/api/v1/blog/*` |
| Media | `/api/v1/media/*` |
| Public (settings, places, ads) | `/api/v1/public/*` |
| Admin (dashboard, users, moderation, reports, payments, ads, settings) | `/api/v1/admin/*` |
