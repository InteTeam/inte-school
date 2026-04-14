# Migration 011 — Create Classes Table

**File:** `2025_01_01_000011_create_classes_table.php`
**Depends on:** schools (005), users

## Purpose

Stores school classes (e.g. "Year 1A", "P3") with an optional teacher assignment.
Classes are the primary grouping unit for students, attendance, and task records.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| name | VARCHAR | not null | Display name (e.g. "Year 1A", "P3") |
| year_group | VARCHAR | not null | Year/stage grouping (e.g. "Year 1", "P3") |
| teacher_id | VARCHAR(26) | nullable | FK to users — assigned teacher |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |
| deleted_at | TIMESTAMP | nullable | Soft delete timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_classes_school_created | school_id, created_at | Tenant-scoped listing with default sort order |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| teacher_id | users.id | SET NULL |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- `teacher_id` is nullable to allow creating classes before assigning staff. SET NULL
  on teacher deletion ensures the class persists even if the teacher account is removed.
- Uses SoftDeletes to preserve historical data — attendance records, task records, and
  class student enrolment records reference this table. Hard deleting a class would
  orphan those records.
- `year_group` is a free-text VARCHAR rather than an enum to support both English
  ("Year 1", "Year 2") and Scottish ("P1", "P2") naming conventions without migration
  changes. Follows the "Flexible Data Model, Constrained UI" principle.
