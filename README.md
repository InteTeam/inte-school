# Inte-School

Multi-tenant school communication and management platform for UK schools.

**Stack:** Laravel 12 · PHP 8.4 · React 19 · TypeScript · Inertia.js v2 · PostgreSQL 16 + PGVector · Redis 7 · Laravel Reverb · Docker

---

## Features

- **Role-based access** — Admin, Teacher, Parent, Student, Support, Root Admin
- **Messaging** — real-time class and direct messaging via Reverb WebSockets
- **Attendance** — register-based attendance with hardware device API
- **Calendar & Tasks** — school-wide events and homework tracking
- **Document management** — PDF upload, background processing, RAG Q&A pipeline
- **Push notifications** — VAPID Web Push with SMS fallback
- **Statistics dashboard** — school analytics with a scoped REST API
- **Feature requests** — admin-submitted, root-admin managed
- **Multi-tenancy** — complete data isolation per school via `HasSchoolScope`
- **UK Children's Code compliant** — minimum data, privacy-first defaults for student data

---

## Development Setup

**Prerequisites:** Docker, Docker Compose v2

```bash
# 1. Clone
git clone git@github.com:piotrfx/inte-school.git
cd inte-school

# 2. Environment
cp .env.example .env

# 3. Start all dev services (PHP, nginx, PostgreSQL, Redis, Reverb, Mailpit, Adminer)
docker compose --profile dev up -d

# 4. Install PHP dependencies
docker compose exec php-fpm composer install

# 5. Generate app key & run migrations
docker compose exec php-fpm php artisan key:generate
docker compose exec php-fpm php artisan migrate
docker compose exec php-fpm php artisan db:seed

# 6. Install and build frontend
docker compose run --rm npm sh -c "npm install && npm run dev"
```

| Service | URL |
|---|---|
| Application | http://localhost:8100 |
| Adminer (DB) | http://localhost:8101 |
| Mailpit | http://localhost:8102 |
| Vite HMR | http://localhost:5180 |

---

## Before Every Commit

```bash
docker compose exec php-fpm php artisan test
docker compose exec php-fpm ./vendor/bin/pint --dirty
docker compose exec php-fpm ./vendor/bin/phpstan analyse --memory-limit=512M
```

---

## Testing

```bash
# Full suite
docker compose exec php-fpm php artisan test

# Single feature
docker compose exec php-fpm php artisan test --filter=FeatureName

# With coverage (requires Xdebug)
docker compose exec php-fpm php artisan test --coverage
```

PHPUnit only (no Pest). All feature tests use `RefreshDatabase` and model factories.

---

## Production Deployment

See [DEPLOY.md](DEPLOY.md) for the full guide.

One-command install for `school.inte.team`:

```bash
sudo bash install.sh
```

The installer handles: secrets generation, container build, migrations, VAPID keys, asset build, and cache warming.

---

## Documentation

```
docs/
  planning/           — PRD, architecture decisions, CRM reuse analysis
  features/           — per-feature: README, architecture, components
  database/           — migration docs (sequential numbering)
  architecture/       — multi-tenancy, scaling, system design patterns
  SECURITY.md         — full security reference (Children's Code, headers, tokens)
  WORKFLOW_ENFORCEMENT.md
  FEATURE_DESIGN_CHECKLIST.md
  DOCUMENTATION_STANDARDS.md
  DATABASE_CONVENTIONS.md
  COMPONENT_REUSE_CHECKLIST.md

.sop.md             — mandatory 9-step process for every feature
CLAUDE.md           — authoritative dev guidelines for AI-assisted development
```

---

## Architecture Overview

```
Caddy (TLS)
  ├── /app/*  →  Reverb (WebSockets)
  └── *       →  nginx → php-fpm

php-fpm → PostgreSQL 16 + pgvector
         → Redis 7 (sessions, cache, queues)

queue-worker  (Laravel Horizon)
```

All tenant models use `HasSchoolScope` for automatic `school_id` filtering. Root admin queries bypass this with explicit `withoutGlobalScope(SchoolScope::class)`.

---

## Licence

Copyright © 2026 Inte.Team. All rights reserved. See [LICENSE](LICENSE).
