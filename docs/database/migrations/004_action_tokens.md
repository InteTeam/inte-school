# Migration 004 — Create Action Tokens Table

**File:** `2025_01_01_000004_create_action_tokens_table.php`
**Depends on:** users table

## Purpose

Stores single-use tokens for parent actions triggered from push notifications.
When a parent taps a notification action (e.g. "Acknowledge" or "Confirm Absence"),
the service worker click handler resolves the token to perform the action.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK added in migration 006 (schools table doesn't exist yet) |
| message_id | VARCHAR(26) | nullable | FK added in P2.1 when messages table exists |
| recipient_id | VARCHAR(26) | not null | FK to users — the parent receiving the notification |
| token | VARCHAR | unique, not null | Unique lookup token for service worker handler |
| action_type | VARCHAR | not null | One of: acknowledge, confirm_absence, trip_consent |
| expires_at | TIMESTAMP | not null | Token expiry — checked before processing action |
| used_at | TIMESTAMP | nullable | Set when token is consumed (null = unused) |
| created_at | TIMESTAMP | nullable | Laravel timestamp (no updated_at) |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_action_tokens_token | token | Fast unique lookup from service worker click handler |
| idx_action_tokens_school_created | school_id, created_at | Tenant-scoped listing with default sort order |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| recipient_id | users.id | CASCADE |

## Notes

- `school_id` FK is deferred to migration 006 because the schools table doesn't
  exist yet at this point in the migration sequence.
- `message_id` FK is deferred to P2.1 when the messages table is created.
- `token` column is unique to enable O(1) lookup when the service worker forwards
  the action click to the backend.
- `action_type` uses VARCHAR (not enum) for extensibility — new action types can
  be added without a migration.
- Only `created_at` is stored (no `updated_at`) — tokens are write-once, then
  marked used via `used_at`.
