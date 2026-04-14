# Migration 005 — Create Schools Table

**File:** `2025_01_01_000005_create_schools_table.php`
**Depends on:** none

## Purpose

Creates the central tenant table for the multi-tenancy model. Every school is an
isolated tenant; all tenant-scoped models reference `school_id` back to this table.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| name | VARCHAR | not null | School display name |
| slug | VARCHAR | unique, not null | URL-safe identifier (e.g. "st-andrews-primary") |
| custom_domain | VARCHAR | unique, nullable | Optional vanity domain (e.g. "app.school.sch.uk") |
| logo_path | VARCHAR | nullable | Path to uploaded school logo |
| theme_config | JSONB | default '{}' | UI customisation (colours, branding) |
| settings | JSONB | default '{}' | General school configuration |
| notification_settings | JSONB | default '{}' | SMS fallback timeout, notification preferences |
| security_policy | JSONB | default '{}' | Tier-specific security rules |
| plan | VARCHAR | default 'standard', not null | One of: starter, standard, pro, enterprise |
| rag_enabled | BOOLEAN | default false, not null | Gates AI document Q&A feature via FeatureGate middleware |
| is_active | BOOLEAN | default true, not null | Soft toggle to disable a school without deleting |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |
| deleted_at | TIMESTAMP | nullable | Soft delete timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_schools_slug | slug | Fast lookup by slug (unique constraint also creates index) |
| idx_schools_active_created | is_active, created_at | Filtered listing of active schools with default sort |

## Foreign Keys

None — this is the root tenant table.

## Notes

- Four JSONB columns store flexible per-school configuration, following the
  "Flexible Data Model, Constrained UI" principle — code reads from stored values
  with sensible defaults, never from constants.
  - `theme_config`: UI customisation (primary colour, logo position, etc.)
  - `settings`: general config (timezone, academic year dates, etc.)
  - `notification_settings`: SMS fallback timeout (`sms_timeout_seconds` default 900),
    notification channel preferences
  - `security_policy`: tier-specific security rules (password policy overrides, 2FA
    enforcement, etc.)
- `plan` uses VARCHAR not enum for extensibility — new plans can be added without
  a migration.
- `rag_enabled` gates the AI document Q&A feature; checked by the `FeatureGate`
  middleware (`feature:rag`).
- Soft deletes (`deleted_at`) allow school deactivation while preserving data for
  audit and potential reactivation.
- All JSONB columns must be cast as `array` in the Eloquent model (never `json`).
