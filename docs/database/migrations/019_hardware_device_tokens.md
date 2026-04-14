# Migration 019 — Create Hardware Device Tokens Table

**File:** `2025_01_01_000019_create_hardware_device_tokens_table.php`
**Depends on:** schools (005), users (for nfc_card_id column)

## Purpose

Creates the `hardware_device_tokens` table for NFC reader authentication and adds the
`nfc_card_id` column to the `users` table. Together these enable hardware-based attendance
marking via physical NFC card readers installed in classrooms.

## Schema (hardware_device_tokens)

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | ULID | not null | FK to schools — tenant scope |
| name | VARCHAR | not null | Human-readable device name (e.g. "Room 12 Reader") |
| token_hash | VARCHAR | unique, not null | SHA-256 hash of the raw API token |
| last_used_at | TIMESTAMP | nullable | Tracks last successful API call from this device |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Schema (users ALTER)

| Column | Type | Constraints | Notes |
|---|---|---|---|
| nfc_card_id | VARCHAR | unique, nullable | Added after `phone` column — maps a physical NFC card to a student |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_hw_token_school_created | school_id, created_at | Default listing for a school's hardware tokens |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |

## Notes

- Two operations in one migration — creates the `hardware_device_tokens` table AND
  alters the `users` table to add `nfc_card_id`. These are logically coupled: the
  hardware token authenticates the reader device, and `nfc_card_id` identifies which
  student tapped.
- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- `token_hash` stores SHA-256 of the raw API token, following the same pattern as
  API key storage elsewhere in the platform. The raw token is shown once at creation
  time and cannot be retrieved afterwards — only compared via hash.
- `nfc_card_id` on users maps a physical NFC card to a student. The hardware API
  endpoint flow:
  1. Reader sends request with raw token + NFC card UID
  2. Server validates `token_hash` (SHA-256 of raw token) against `hardware_device_tokens`
  3. Server looks up student by `nfc_card_id` on `users`
  4. Server creates attendance record with `marked_via = 'nfc_card'`
- `last_used_at` enables monitoring of device health — a reader that hasn't called
  in recently may need attention.
- No soft deletes on hardware tokens — revoking a token means deleting the row.
  The token_hash becomes immediately invalid.
