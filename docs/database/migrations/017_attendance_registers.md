# Migration 017 — Create Attendance Registers Table

**File:** `2025_01_01_000017_create_attendance_registers_table.php`
**Depends on:** schools (005), classes (011), users

## Purpose

Represents a single attendance register session — one per class per date per period.
Teachers open a register, then mark individual student attendance records against it.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | ULID | not null | FK to schools — tenant scope |
| class_id | ULID | not null | FK to classes — the class being registered |
| teacher_id | ULID | not null | FK to users — the teacher taking the register |
| register_date | DATE | not null | The date of the register |
| period | VARCHAR | nullable | Null = daily register, or "morning", "period_1" etc. |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_register_school_created | school_id, created_at | Default listing for a school's registers |

## Unique Constraints

| Name | Columns | Notes |
|---|---|---|
| uq_register_class_date_period | school_id, class_id, register_date, period | One register per class per date per period |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| class_id | classes.id | CASCADE |
| teacher_id | users.id | RESTRICT |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- One register per class per date per period, enforced by the unique constraint on
  `(school_id, class_id, register_date, period)`.
- `teacher_id` uses RESTRICT on delete — cannot remove a teacher who has open registers.
  This preserves the audit trail of who took the register. Teachers must be reassigned
  or registers must be transferred before the teacher account can be deleted.
- `period` is nullable — null represents a full-day register for schools that take
  attendance once per day. String values allow multi-period attendance:
  - Daily: `null`
  - Morning/afternoon: `"morning"`, `"afternoon"`
  - Period-based: `"period_1"`, `"period_2"`, etc.
- `period` uses VARCHAR not enum for extensibility — schools can define their own
  period naming conventions without a migration.
- No soft deletes — attendance registers are permanent records for statutory compliance.
