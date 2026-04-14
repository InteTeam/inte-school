# Migration 014 — Create Messages Table

**File:** `2025_01_01_000014_create_messages_table.php`
**Depends on:** schools (005), users

## Purpose

Creates the core messaging table for school-to-parent communication. Supports threaded
conversations, multiple message types, and idempotent delivery via a deduplication key.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| sender_id | VARCHAR(26) | not null | FK to users — teacher or admin who sent the message |
| thread_id | VARCHAR(26) | nullable | FK to messages (self-referencing) — null means root of thread |
| transaction_id | VARCHAR(26) | unique, not null | ULID deduplication key for idempotent delivery |
| type | VARCHAR | not null | `announcement`, `attendance_alert`, `trip_permission`, `quick_reply` |
| body | TEXT | not null | Message content |
| requires_read_receipt | BOOLEAN | not null, default false | Whether recipients must acknowledge reading |
| sent_at | TIMESTAMP | nullable | When the message was dispatched (null = draft) |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |
| deleted_at | TIMESTAMP | nullable | Soft delete timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_messages_school_created | school_id, created_at | Default listing — all messages for a school sorted by date |
| idx_messages_school_thread | school_id, thread_id | Thread lookup — fetch all replies to a root message |
| idx_messages_school_type | school_id, type | Filter messages by type within a school |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| sender_id | users.id | CASCADE |
| thread_id | messages.id | CASCADE |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Self-referencing FK for `thread_id` is deferred to a separate `Schema::table()` call
  after table creation because PostgreSQL requires the primary key to exist before a
  foreign key can reference it.
- `transaction_id` (ULID) enables idempotent message delivery — clients can retry
  sending without creating duplicate messages. The unique constraint rejects duplicates
  at the database level.
- `requires_read_receipt` is set by message type at creation time:
  - `true` for `attendance_alert` and `trip_permission` (acknowledgement required)
  - `false` for `announcement` (informational only)
- `type` uses VARCHAR not enum for extensibility — new message types can be added
  without a migration.
- Soft deletes allow message removal from the UI while preserving audit history.
