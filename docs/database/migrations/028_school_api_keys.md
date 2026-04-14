# Migration 028 — Create School API Keys Table

**File:** `2025_01_01_000028_create_school_api_keys_table.php`
**Depends on:** schools (005), users

## Purpose

Creates the school API keys table for external integrations (council data sharing, MIS
imports, etc.). Keys are stored as SHA-256 hashes with scoped permissions and expiry.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| name | VARCHAR | not null | Human-readable key name (e.g. "Council Attendance Feed") |
| key_hash | VARCHAR | unique, not null | SHA-256 hash of the raw API key |
| permissions | JSONB | not null, default '[]' | Scoped permissions (e.g. `["attendance", "messages", "homework"]`) |
| created_by | VARCHAR(26) | not null | FK to users — who created the key |
| last_used_at | TIMESTAMP | nullable | Last time the key was used for authentication |
| expires_at | TIMESTAMP | nullable | Key expiry date (null = no expiry) |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_api_key_school_created | school_id, created_at | Default listing — all API keys for a school sorted by date |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| created_by | users.id | RESTRICT |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Raw API key is shown once at creation and never stored. Only the SHA-256 hash is
  persisted in `key_hash`. This follows the same security pattern used for hardware
  device tokens (see security conventions).
- `permissions` JSONB scopes what data each key can access. Cast as `array` in the
  Eloquent model. Example values: `["attendance"]`, `["attendance", "messages"]`,
  `["homework"]`. An empty array `[]` means no permissions (key is effectively disabled).
- `expires_at` enables automatic key rotation — keys can be created with a fixed
  expiry date. The `AuthenticateApiKey` middleware checks expiry before authenticating.
  Null means the key does not expire.
- `last_used_at` tracked for audit and stale key detection — school admins can see
  which keys are actively used and revoke unused ones.
- `AuthenticateApiKey` middleware hashes the incoming bearer token with SHA-256 and
  matches against `key_hash`:
  ```php
  $key = SchoolApiKey::where('key_hash', hash('sha256', $bearerToken))
      ->whereNull('expires_at')
      ->orWhere('expires_at', '>', now())
      ->first();
  ```
- `created_by` uses RESTRICT — cannot delete a user who created API keys. The user
  should be soft-deleted instead, preserving the creation attribution.
- No soft deletes — API keys are hard-deleted when revoked. Once revoked, the key
  immediately stops working (no grace period).
