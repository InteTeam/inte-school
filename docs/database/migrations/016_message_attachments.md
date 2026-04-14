# Migration 016 — Create Message Attachments Table

**File:** `2025_01_01_000016_create_message_attachments_table.php`
**Depends on:** schools (005), messages (014)

## Purpose

Stores file attachment metadata for messages. Actual files are stored via StorageService
(GCS in production); this table tracks the reference path and metadata.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| message_id | VARCHAR(26) | not null | FK to messages — the parent message |
| file_name | VARCHAR | not null | Original file name as uploaded |
| file_path | VARCHAR | not null | Storage path (GCS bucket key in production) |
| mime_type | VARCHAR | not null | Server-validated MIME type |
| file_size | UNSIGNED BIGINT | not null | File size in bytes |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_attachments_school_created | school_id, created_at | Default listing for a school's attachments |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| message_id | messages.id | CASCADE |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Files stored via `StorageService` (GCS in production). The `file_path` column holds
  the storage key, not a public URL — signed URLs are generated at request time.
- MIME type is validated server-side — no SVG uploads in MVP (XSS risk). Allowed types:
  - Images: JPG, PNG, WebP
  - Documents: PDF
- `file_size` is stored as unsigned big integer for quota tracking and UI display.
  Allows files up to 2^63 bytes, well beyond any practical upload limit.
- No soft deletes — attachments follow the parent message lifecycle. When a message
  is soft deleted, its attachments become inaccessible through the message relationship
  but the files remain in storage for compliance.
