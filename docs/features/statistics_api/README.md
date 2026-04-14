# Statistics & API

## Overview

School statistics dashboard with attendance rates, message engagement, homework completion, and user metrics. Includes an external REST API for data sharing with councils and third parties, authenticated via scoped API keys.

## User Stories

- As an **admin**, I can view school statistics across configurable time periods
- As an **admin**, I can generate API keys with specific permissions for external consumers
- As an **external consumer** (council), I can query school stats via authenticated REST API

## Dashboard Metrics

| Metric | Source | Calculation |
|---|---|---|
| Attendance rate | attendance_records | present / total * 100 |
| Message engagement | message_recipients | read / total * 100 |
| Homework completion | tasks (homework type) | done / total * 100 |
| Active users | school_user | users with recent activity |

## Time Periods

- `week` — last 7 days
- `month` — last 30 days
- `term` — last 90 days

## Caching

- Key pattern: `school:{id}:stats:{type}:{period}`
- TTL: 3600s (1 hour)
- Flushed via `StatisticsService::flushCache()`

## API Keys

- Raw key shown once at creation, stored as SHA-256 hash
- Permissions JSONB scopes data access: `["attendance", "messages", "homework", "users"]`
- Optional `expires_at` for automatic key rotation
- `last_used_at` tracked for audit and stale key detection
- Rate limited: 60 requests/minute/key

## Database Tables

- `school_api_keys` — key hash, permissions, expiry, usage metadata

## Routes

### Web (middleware: auth, not_disabled, school, legal, role:admin)
- `GET /admin/statistics` — dashboard
- `GET /admin/settings/api-keys` — key management
- `POST /admin/settings/api-keys` — generate key
- `DELETE /admin/settings/api-keys/{key}` — revoke key

### API (middleware: api_key, throttle:60,1)
- `GET /api/v1/stats/{schoolSlug}?period=week|month|term` — stats endpoint

## Security

- API keys authenticated via `AuthenticateApiKey` middleware (hashes bearer token, validates)
- Response filtered by key's permissions — only permitted data types returned
- Cross-school rejection: key is school-scoped
- Rate limiting enforced per key
