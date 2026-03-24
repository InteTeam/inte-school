# Inte-School — Reuse Analysis from inteteam_crm

**Reference repo:** `/home/piotrfx/Desktop/WebApps/inteteam_crm`
**Stack overlap:** Laravel 12, PHP 8.4, React 19, TypeScript, Inertia.js v2, Redis, Laravel Reverb, Docker Compose, Shadcn/ui, GCS, Tailwind CSS v4
**Key difference:** inte-school uses **PostgreSQL + PGVector** (CRM uses MariaDB 11.8)

---

## Verdict: Fresh Scaffold + Cherry-Pick (Not Strip-Down)

**Do not clone and strip inteteam_crm.** Reasoning:

- PostgreSQL must be first-class from day 1 (PGVector depends on it — bolting it on later is painful)
- Domain models are fundamentally different (School/Guardian/Child vs Company/Booking/Invoice)
- CRM has ~60+ models, 20 route files, and repair-shop-specific migrations — stripping leaves ghost code and orphaned migrations
- inteteam_cms used this same "standalone, share no code" approach and it's the right call here too

**Correct approach:** `laravel new inte-school`, then port the specific files and patterns listed below.

---

## Tier 1 — Copy Verbatim (zero or near-zero changes)

### Backend Traits
| File in CRM | Copy to inte-school | Notes |
|---|---|---|
| `app/Models/Concerns/HasCompanyScope.php` | `app/Models/Concerns/HasSchoolScope.php` | Rename `company_id` → `school_id`, class name only |
| `app/Models/Scopes/CompanyScope.php` | `app/Models/Scopes/SchoolScope.php` | Same rename |

These two files are the entire multi-tenancy engine. They auto-filter all queries by tenant ID and auto-set it on creation.

### Backend Middleware
| File in CRM | Copy to inte-school | Notes |
|---|---|---|
| `app/Http/Middleware/HandleInertiaRequests.php` | Same path | Adapt shared props to inte-school context |
| `app/Http/Middleware/EnsureCompanyContext.php` | `EnsureSchoolContext.php` | Rename company → school throughout |
| `app/Http/Middleware/EnsureUserIsNotDisabled.php` | Same path | Copy verbatim |
| `app/Http/Middleware/CheckRootAdmin.php` | Same path | Copy verbatim |
| `app/Http/Middleware/FeatureGate.php` | Same path | Copy verbatim — needed for feature flags per school |

### SSO Integration (3 files, copy when ready for Phase 2)
| File in CRM | Copy to inte-school |
|---|---|
| `app/Services/SsoService.php` | Same path |
| `app/Http/Controllers/Auth/SsoController.php` | Same path |
| `app/Http/Middleware/HandleSsoToken.php` | Same path |

Set `SSO_ENABLED=false` in `.env` until Phase 2. No changes to inteteam_sso needed — just register inte-school as a new OAuth client.

### Frontend Hooks
| File in CRM | Copy to inte-school | Notes |
|---|---|---|
| `resources/js/hooks/useSessionHeartbeat.ts` | Same path | Prevents 419 errors on long sessions |
| `resources/js/hooks/useIsMobile.ts` | Same path | Copy verbatim |
| `resources/js/hooks/use-toast.ts` | Same path | Copy verbatim |
| `resources/js/hooks/usePushNotifications.ts` | Same path | Adapt for VAPID Web Push (see Section below) |
| `resources/js/lib/utils.ts` | Same path | Copy verbatim |

### Frontend UI Components
| Directory in CRM | Copy to inte-school | Notes |
|---|---|---|
| `resources/js/Components/ui/` | Same path | Entire shadcn/ui set — copy verbatim |
| `resources/js/Components/Atoms/` | Same path | Basic atoms (Button, Input, Badge, etc.) |
| `resources/js/bootstrap.js` | Same path | Axios + CSRF setup — copy verbatim |

---

## Tier 2 — Copy & Adapt (moderate changes)

### Auth System
The CRM auth is nearly identical to what inte-school needs: email + password + 2FA + company context.

| File in CRM | Adapt for inte-school |
|---|---|
| `app/Http/Controllers/Auth/LoginController.php` | Swap `company` → `school`; keep 2FA logic intact |
| `app/Http/Controllers/Auth/TwoFactorController.php` | Copy — no changes needed |
| `app/Models/User.php` | Keep role/permission structure; rename company relationships to school; add `guardian_of` relationship |
| `app/Models/Company.php` | Rename to `School.php`; remove repair-shop fields (locations, warehouses etc.); keep plan/settings/ULID pattern |
| `database/migrations/*_create_users_table.php` | Keep `disabled_at`, `two_factor_secret`, `two_factor_recovery_codes` columns |
| `database/migrations/*_create_company_user_table.php` | Rename to `school_user`; keep role, invitation_token, accepted_at columns |

### Docker Compose
The CRM's `docker-compose.yml` is the exact template to use. Changes needed:

- Replace `mariadb:11.8` service with `postgres:16-alpine`
- Add `pgvector/pgvector:pg16` image instead (includes the extension)
- Replace `phpmyadmin` with `adminer` (supports PostgreSQL)
- Replace `my.cnf` MariaDB config with `postgresql.conf` equivalent
- Update PHP Dockerfile: swap `pdo_mysql` extension for `pdo_pgsql` + `pgsql`
- Update `DB_HOST`, `DB_PORT` (5432), `DB_CONNECTION=pgsql` env vars
- Keep all other services unchanged: Nginx, Redis, queue-worker, Reverb, npm (Vite), Mailpit, Caddy

### Layouts (Frontend)
| File in CRM | Adapt for inte-school |
|---|---|
| `resources/js/layouts/AuthLayout.tsx` | Copy — minimal changes (branding only) |
| `resources/js/layouts/CompanyLayout.tsx` | Rename to `SchoolLayout.tsx`; replace AdminNavBar with school-specific nav |
| `resources/js/Components/Organisms/AdminNavBar.tsx` | Build `SchoolNavBar.tsx` using this as structural template |

### Inertia v2 Form Upload Pattern
This pattern is already proven in CRM — copy it into the inte-school CLAUDE.md:
```
Route::match(['PUT', 'POST'], ...) on backend
router.post() on frontend (never router.put() for file forms)
```

---

## Tier 3 — Reference Only (don't copy, use as pattern guide)

| CRM Component | What to Learn From It |
|---|---|
| `app/Http/Middleware/HandleInertiaRequests.php` | How to share auth data, company list, notifications to React via `share()` |
| `app/Policies/*` | Policy structure pattern — `#[UsePolicy]` on models, `auth()->user()->can()` in controllers |
| `app/Services/*` | Service-layer architecture: `final class`, <250 lines, constructor injection |
| `app/Jobs/*` | Queue job structure for Horizon — use for SMS fallback promotion job |
| `resources/js/Pages/Auth/` | Login, 2FA, password reset page structure (adapt visually) |
| `docs/FEATURE_DESIGN_CHECKLIST.md` | Reuse the 9-step SOP process for inte-school (create own `.sop.md`) |
| `docs/DATABASE_CONVENTIONS.md` | Copy naming conventions (adapted for PostgreSQL) |
| `docs/COMPONENT_REUSE_CHECKLIST.md` | Copy the checklist structure |

---

## New in inte-school (no CRM equivalent)

These are net-new and have no reference implementation to borrow from:

| Feature | Why New |
|---|---|
| `PGVector` document ingestion pipeline | CRM has no AI/semantic search |
| `OllamaService` (embedding + RAG generation) | New |
| VAPID Web Push service (`VapidPushService`) | CRM uses push via Firebase SDK; need to replace with `minishlink/web-push` |
| `ServiceWorker` for push + offline | CRM PWA is basic; VAPID needs a proper service worker |
| Transaction ID threading model | CRM conversations are flat; inte-school needs parent↔school thread model |
| `GuardianChild` pivot model | Multi-guardian per child relationship not in CRM |
| `SchoolDocument` + embedding pipeline | PDF → chunks → embeddings → PGVector |
| Three-tier notification cascade (Reverb → Push → SMS) | CRM has WhatsApp/SMS but no cascade logic |
| Horizon 15-min SMS promotion job | New job type |

---

## Composer Packages to Carry Over

```json
{
    "inertiajs/inertia-laravel": "^2.0",
    "laravel/reverb": "^1.8",
    "pragmarx/google2fa": "^8.0",
    "league/flysystem-google-cloud-storage": "^3.31",
    "barryvdh/laravel-dompdf": "^3.1",
    "resend/resend-php": "^1.1"
}
```

New packages needed for inte-school (not in CRM):
```json
{
    "minishlink/web-push": "^9.0",
    "pgvector/pgvector": "^0.2"
}
```

---

## npm Packages to Carry Over

```json
{
    "@inertiajs/react": "^2.0.0",
    "react": "^19.0.0",
    "typescript": "^5.7.0",
    "tailwindcss": "^4.0.0",
    "@radix-ui/react-*": "full set",
    "lucide-react": "latest",
    "laravel-echo": "^2.3.1",
    "pusher-js": "^8.4.0",
    "ziggy-js": "^2.6.1",
    "date-fns": "^4.1.0",
    "axios": "^1.x"
}
```

---

## SOP & Documentation Structure

Copy the **documentation scaffolding pattern** from CRM — not the content, the structure:

```
docs/
  planning/           ← already started (this file lives here)
  features/           ← one subdir per feature with README + architecture.md
  database/           ← migration docs with sequential numbering
  architecture/       ← multi-tenancy, scaling, system design
  WORKFLOW_ENFORCEMENT.md
  FEATURE_DESIGN_CHECKLIST.md
  DOCUMENTATION_STANDARDS.md
  DATABASE_CONVENTIONS.md     ← adapt for PostgreSQL conventions
  COMPONENT_REUSE_CHECKLIST.md
.sop.md               ← root-level SOP (create before first feature)
CLAUDE.md             ← authoritative dev guidelines (create before first feature)
```

---

## Migration Note: MariaDB → PostgreSQL for ULIDs

CRM uses `HasUlids` with `char(26)` columns. PostgreSQL handles this identically — no change needed to the trait or migration column definitions. `$table->ulid()` and `$table->foreignUlid()` work the same in both drivers.

The only PostgreSQL-specific migration consideration: use `$table->jsonb()` instead of `$table->json()` for JSON columns — JSONB is indexed and far more performant in PostgreSQL.
