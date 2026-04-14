# Migration 015 — Create Message Recipients Table

**File:** `2025_01_01_000015_create_message_recipients_table.php`
**Depends on:** schools (005), messages (014), users

## Purpose

Tracks per-recipient delivery and read status for each message. Drives the SMS fallback
cascade and stores quick-reply responses from parents.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| message_id | VARCHAR(26) | not null | FK to messages — the message being delivered |
| recipient_id | VARCHAR(26) | not null | FK to users — the recipient (typically a parent) |
| read_at | TIMESTAMP | nullable | When the recipient read the message |
| delivered_at | TIMESTAMP | nullable | When push notification was confirmed delivered |
| quick_reply | VARCHAR | nullable | Stores the reply option chosen by the parent |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_recipients_school_user_read | school_id, recipient_id, read_at | Unread message count per user — covers "unread badge" queries |
| idx_recipients_school_created | school_id, created_at | Default listing for a school's recipient records |

## Unique Constraints

| Name | Columns | Notes |
|---|---|---|
| uq_message_recipient | message_id, recipient_id | Prevents duplicate fan-out — one row per message-recipient pair |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| message_id | messages.id | CASCADE |
| recipient_id | users.id | CASCADE |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- One row per message-recipient pair. The unique constraint on
  `(message_id, recipient_id)` prevents duplicate fan-out during message dispatch.
- `read_at` drives the SMS fallback cascade — `PromoteToSmsJob` checks this timestamp
  after the configured delay (`sms_timeout_seconds` from school notification settings,
  default 900 seconds). If `read_at` is still null, the job promotes to SMS.
- `delivered_at` tracks push delivery confirmation from the service worker. This is
  distinct from `read_at` — a message can be delivered but not yet read.
- `quick_reply` stores the parent's response for `quick_reply` type messages (e.g.
  "Yes" / "No" for trip permission). Null for message types that don't require a reply.
- No soft deletes — recipient records are tied to the parent message lifecycle.
