# Migration 020 — Create Calendars Table

**File:** `2025_01_01_000020_create_calendars_table.php`
**Depends on:** schools (005)

## Purpose

Creates the calendars table for organising school events by type and department.
Each school can have multiple calendars with visibility controls for staff vs. parents.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| name | VARCHAR | not null | Calendar display name (e.g. "Term Dates", "Science Department") |
| type | VARCHAR | not null | `internal`, `external`, `department`, `holiday` |
| department_label | VARCHAR | nullable | Per-department calendars (e.g. "Science Department") |
| color | VARCHAR | nullable | Hex colour for calendar UI display (e.g. `#3b82f6`) |
| is_public | BOOLEAN | not null, default false | External calendars visible to parents/students |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |
| deleted_at | TIMESTAMP | nullable | Soft delete timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_calendar_school_created | school_id, created_at | Default listing — all calendars for a school sorted by date |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- `type` uses VARCHAR not enum for extensibility — new calendar types can be added
  without a migration. Follows the "Flexible Data Model, Constrained UI" principle.
- `is_public` controls parent/student visibility — internal calendars are staff-only.
  When `is_public = true`, events on that calendar are visible in the parent portal.
- `department_label` enables per-department calendars (e.g. "Science Department",
  "PE Department"). Nullable because not all calendars are department-scoped.
- `color` is optional hex for calendar UI display — allows colour-coding in the
  calendar view. Frontend validates hex format; DB stores as plain VARCHAR.
- Soft deletes allow calendar removal from the UI while preserving event history.
