# 030 — sms_logs

> Created: 2026-04-15

## Purpose

Track every SMS sent via GOV.UK Notify for usage counting, delivery status, cost tracking, and audit compliance. Each record corresponds to one API call to `POST /v2/notifications/sms`.

## Schema

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| `id` | ULID (PK) | No | — | HasUlids trait |
| `school_id` | ULID (FK → schools) | No | — | HasSchoolScope, cascade delete |
| `recipient_id` | ULID (FK → users) | No | — | Restrict delete |
| `message_id` | ULID (FK → messages) | Yes | — | Null on delete; nullable for manual sends |
| `phone_number` | string(20) | No | — | Masked for display (+44***1234) |
| `notify_message_id` | string(100) | Yes | — | GOV.UK Notify delivery ID for status tracking |
| `status` | string(30) | No | `queued` | queued, delivered, failed, provider_error |
| `segments` | smallint unsigned | No | `1` | Number of SMS segments consumed |
| `cost_pence` | int unsigned | No | `0` | Cost in pence (0 within free allowance) |
| `sent_at` | timestamp | Yes | — | When the API call was made |
| `delivered_at` | timestamp | Yes | — | Set by delivery webhook (Phase 3) |
| `failed_at` | timestamp | Yes | — | Set on API error or delivery failure |
| `failure_reason` | string | Yes | — | Error message from Notify API |
| `created_at` | timestamp | No | — | |
| `updated_at` | timestamp | No | — | |

## Indexes

| Name | Columns | Purpose |
|---|---|---|
| `idx_sms_school_sent` | `(school_id, sent_at)` | Usage counting per school per year |
| `idx_sms_notify_id` | `(notify_message_id)` | Delivery webhook lookup (Phase 3) |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| `school_id` | `schools.id` | CASCADE |
| `recipient_id` | `users.id` | RESTRICT |
| `message_id` | `messages.id` | SET NULL |

## Model Config

- **Traits:** `HasSchoolScope`, `HasUlids`, `HasFactory`
- **Casts:** `segments` → integer, `cost_pence` → integer, `sent_at/delivered_at/failed_at` → datetime
- **Fillable:** All columns except `id`, `school_id`, `created_at`, `updated_at`
- **school_id** set by `HasSchoolScope` on creation, never in `$fillable`
