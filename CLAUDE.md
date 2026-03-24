# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.
**This file is authoritative. It overrides all other documentation when conflicts arise.**

## Session Setup

```bash
git config user.name "piotrfx" && git config user.email "shopscot@gmail.com"
```

## Project Overview

Inte-School is a multi-tenant school communication and management platform for UK schools.
Built with Laravel 12 / PHP 8.4, React 19 / TypeScript / Inertia.js v2, PostgreSQL 16 + PGVector, Redis 7, Laravel Reverb.

Target: multiple schools as isolated Docker Compose stacks, provisioned via inte-panel.

---

## Development Commands

```bash
# Start dev environment
docker compose --profile dev up -d
docker compose --profile dev down

# Run tests (ALWAYS before committing)
docker compose exec php-fpm php artisan test
docker compose exec php-fpm php artisan test --filter=FeatureName

# Code quality (ALWAYS before committing)
docker compose exec php-fpm ./vendor/bin/pint --dirty
docker compose exec php-fpm ./vendor/bin/phpstan analyse --memory-limit=512M

# Database
docker compose exec php-fpm php artisan migrate
docker compose exec php-fpm php artisan db:seed
docker compose exec php-fpm php artisan tinker

# Frontend (node_modules owned by Docker — never use host npm/npx)
docker compose run --rm npm run build
docker compose run --rm npm run dev

# Queue worker restart (after docker-compose.yml changes)
docker compose up -d --build queue-worker
```

## Dev URLs
- Application: http://localhost:8100
- Vite HMR: http://localhost:5180
- Adminer (PostgreSQL UI): http://localhost:8101
- Mailpit: http://localhost:8102
- Reverb: ws://localhost:8103

---

## Architecture

### Multi-Tenancy
All tenant-scoped models use `HasSchoolScope` trait:
- Applies `SchoolScope` global scope for automatic `school_id` filtering
- Auto-sets `school_id` on model creation from session context
- Provides `scopeForSchool()` to bypass when needed (root admin queries)

```php
use App\Models\Concerns\HasSchoolScope;

class MyModel extends Model
{
    use HasSchoolScope;
    use HasUlids;
}
```

Root admin check: `User::isRootAdmin()` checks `users.is_root_admin = true`
Shared to frontend via `HandleInertiaRequests::share()` as `auth.user.isRootAdmin`

### Backend Layers
- **Controllers** (`app/Http/Controllers/`): Thin orchestrators <150 lines, delegate to services
- **Services** (`app/Services/`): Business logic, `final class`, <250 lines, organised by domain
- **DTOs** (`app/DTO/`): Readonly data transfer objects with typed properties
- **Policies** (`app/Policies/`): Authorization via `#[UsePolicy]` attribute on **models** only
- **Form Requests** (`app/Http/Requests/`): Validation — always array syntax for rules, never pipe strings
- **Observers** (`app/Observers/`): Cache invalidation — one observer per model that has cached data
- **Jobs** (`app/Jobs/`): Queued work. Any operation >5s must be queued

### Frontend Structure
- **Pages** (`resources/js/Pages/`): Inertia pages by role module
  - `Auth/`, `RootAdmin/`, `Admin/`, `Teacher/`, `Parent/`, `Student/`, `Support/`
- **Components**: Atomic design
  - `Atoms/`: Smallest elements (Button, Badge, Input)
  - `Molecules/`: Combinations (FormField, Card)
  - `Organisms/`: Complex components (SchoolNavBar, Modals)
  - `ui/`: shadcn/ui base components — never modify directly
- **Layouts** (`resources/js/layouts/`):
  - `AuthLayout.tsx` — login/register pages
  - `SchoolLayout.tsx` — main school app layout
  - `ParentLayout.tsx` — minimal PWA layout for parents

### Route Files
Split by domain in `routes/`:
- `web.php` — auth, school, dashboard
- `api.php` — REST API (stats, attendance hardware)
- `messaging.php`, `attendance.php`, `calendar.php`, `tasks.php`
- `documents.php`, `users.php`, `settings.php`
- `channels.php` — WebSocket channel definitions

Middleware aliases defined in `bootstrap/app.php`:
- `school` — `EnsureSchoolContext`
- `root_admin` — `CheckRootAdmin`
- `not_disabled` — `EnsureUserIsNotDisabled`
- `feature` — `FeatureGate`

---

## Code Conventions

### PHP — Non-Negotiable Rules
- `declare(strict_types=1);` on every PHP file, no exceptions
- PHP 8.4 constructor property promotion
- Explicit return type declarations on all methods
- `casts()` method, never `$casts` property
- ULIDs for primary keys — `HasUlids` trait on every model
- `HasSchoolScope` on every tenant-scoped model
- Validation rules: **array syntax only** — never pipe strings
  ```php
  // CORRECT
  'email' => ['required', 'email', 'max:255'],
  // WRONG
  'email' => 'required|email|max:255',
  ```
- `#[UsePolicy]` on **models** only — never on controllers
- Controllers use `auth()->user()->can()` + `abort(403)` — never `$this->authorize()`
- Services: `final class`, constructor injection, <250 lines
- Controllers: <150 lines — if longer, extract to service

### Internationalisation (i18n) — Foundation Rules
Multi-language is post-MVP but two rules apply from day one at zero cost:

1. **All system strings use `__()`** — never hardcode display strings in PHP or blade
   ```php
   // CORRECT
   __('attendance.marked_present')
   __('notifications.absence_alert_title')

   // WRONG — impossible to translate later without a full audit
   'Attendance marked as present'
   ```
   All translation keys live in `lang/en.json`. When a language is added, one file is created — no code changes.

2. **DB stores machine keys, never display strings**
   ```php
   // CORRECT — translate at display layer
   status: 'present' | 'absent' | 'late'
   type:   'privacy_policy' | 'terms_conditions'

   // WRONG — untranslatable without a migration
   status: 'Present' | 'Absent' | 'Late'
   ```

Frontend: use `react-i18next` with `en.json` as the single locale file. All UI strings via `t('key')`. RTL, locale date formatting, and language switcher are deferred to post-MVP.

### PostgreSQL-Specific Conventions
- Use `JSONB` for all JSON columns (never `JSON`) — JSONB is indexed and faster
- Use `$table->vector(768)` for PGVector embedding columns
- Mandatory indexes on all tenant tables:
  - `idx_school_created` — `(school_id, created_at)`
  - `idx_school_status` — `(school_id, status)` where status column exists
- Foreign keys: always define cascade rule explicitly

### Testing
- **PHPUnit only** — never Pest
- `final class {Feature}Test extends TestCase`
- `use RefreshDatabase;` on all feature tests
- Feature tests in `tests/Feature/`, unit in `tests/Unit/`
- Always use factories for model creation
- Test auth, wrong role, correct role, multi-tenant isolation on every feature
- Run before every commit: `docker compose exec php-fpm php artisan test`

### Frontend
- TypeScript strict mode — all props interfaces defined, no `any`
- Tailwind v4 CSS-first `@theme` configuration
- Dark mode via `dark:` classes
- Gap utilities for spacing, not margins
- Inertia v2 file upload forms:
  - `Route::match(['PUT', 'POST'], ...)` on backend
  - `router.post()` on frontend — never `router.put()` for file forms
- Flash format: `->with(['alert' => '...', 'type' => 'success'])`

---

## Security Conventions

> Full reference: `docs/SECURITY.md` — read it before any feature touching auth, uploads, APIs, or child data.

- **Sort order:** all user-facing lists default to `orderBy('created_at', 'desc')` — no exceptions without explicit documentation
- **Mass assignment:** `school_id` is never in `$fillable` — set by `HasSchoolScope`, never from user input
- **Password policy:** minimum 12 chars, mixed case, number, `Password::uncompromised()` (HIBP check)
- **Rate limiting:** login (10/min/IP), password reset (5/min/IP), 2FA (5/min/user), API endpoints — defined in `bootstrap/app.php`
- **2FA lockout:** 5 failed attempts → 15-min lock + email alert to user
- **File uploads:** always validate MIME type server-side, never extension only. No SVG uploads (MVP) — JPG/PNG/WebP only for logos. PDF uploads processed in queue, never inline
- **CSV import/export:** sanitize fields starting with `=`, `-`, `+`, `@` (CSV injection)
- **API keys:** stored as `hash('sha256', $key)`, shown once, scoped, rotatable. Same for hardware device tokens
- **Security headers:** HSTS, CSP, X-Frame-Options, X-Content-Type-Options — set in Nginx/Caddy config before go-live
- **UK Children's Code:** any feature touching student data must default to most private option and collect minimum data
- **CSRF in service worker:** all non-GET background sync requests must attach XSRF token from cookie
- **VAPID payloads:** always encrypted (AESGCM via minishlink — requires `p256dh` + `auth` keys in subscription)
- **Dependency audit:** `composer audit` + `npm audit` before committing any package changes

## Standing Principles (apply to every feature)

### 1. Graceful Fallback — Non-Negotiable
Every feature must have a documented fallback path. If the primary path fails, the system must fall back silently or notify the appropriate party.
- Notification cascade: Reverb → VAPID push → SMS (if enabled)
- Email: Resend → fallback provider → root admin alert
- RAG: Ollama → "no confident answer" UI → ticket creation option
- Document processing: failure → `processing_status = failed` → retry job + admin alert
- **Before marking any feature complete: verify the fallback is implemented and tested**

### 2. Flexible Data Model, Constrained UI
Never hardcode values that could vary per school or change over time.
- Store in JSONB settings columns with sensible defaults
- Code always reads from stored value, never from constants
- Build the UI control only when it's actually needed — not before
- Examples: `sms_timeout_seconds` (default 900), calendar types (string not enum)

### 3. Multi-Tenant Isolation
- Every query on a tenant model must go through `HasSchoolScope`
- Never query across schools without root admin context
- Test cross-tenant isolation on every feature — a school must never see another school's data

---

## Key Patterns

### Authorization
```php
// On the MODEL:
#[UsePolicy(MessagePolicy::class)]
class Message extends Model { }

// In the CONTROLLER:
if (! auth()->user()->can('create', Message::class)) {
    abort(403);
}
```

### Queued Jobs
```php
// Dispatch with delay (e.g. SMS fallback after 15 min)
PromoteToSmsJob::dispatch($messageRecipientId)
    ->delay(now()->addSeconds($school->notification_settings['sms_timeout_seconds'] ?? 900))
    ->onQueue('high');
```

### Cache with Observer invalidation
```php
// Read from cache
$settings = Cache::remember("school:{$schoolId}:settings", 86400, fn() =>
    School::find($schoolId)->settings
);

// Observer clears on save (SchoolSettingsObserver)
Cache::forget("school:{$schoolId}:settings");
```

### PGVector similarity search
```php
// Cosine similarity search — always scope by school_id first
$chunks = DocumentChunk::query()
    ->where('school_id', $schoolId)
    ->orderByRaw('embedding <=> ?', [$queryEmbedding])
    ->limit(5)
    ->get();
```

### VAPID Web Push
```php
// Via VapidPushService — never call minishlink directly in controllers
$pushService->send($registeredDevice, $payload);
```

---

## Documentation Structure

```
docs/
  planning/           — PRD, architecture, reuse analysis (done)
  features/           — one subdir per feature (README + architecture + components)
  database/           — migration docs with sequential numbering (001_, 002_, ...)
  architecture/       — multi-tenancy, scaling, system design
  WORKFLOW_ENFORCEMENT.md
  FEATURE_DESIGN_CHECKLIST.md
  DOCUMENTATION_STANDARDS.md
  DATABASE_CONVENTIONS.md
  COMPONENT_REUSE_CHECKLIST.md
.sop.md               — mandatory process for every feature
CLAUDE.md             — this file (authoritative)
```

---

## Critical Gotchas

- PHPStan requires `--memory-limit=512M` to avoid OOM
- Node modules owned by Docker — never run `npm` on host (EACCES on `.vite-temp`)
- After `git pull` + `npm run build` on production: use `docker compose up -d --build` — plain `up -d` does NOT reload new static assets
- `pgvector` extension must be enabled before running migrations: `CREATE EXTENSION IF NOT EXISTS vector;` (handled in migration 001)
- JSONB columns: always cast as `array` in model casts, never as `json`
- All new PHP files: `declare(strict_types=1);` on line 1 after `<?php`
