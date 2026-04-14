# Migration 013 — Create Guardian Student Table

**File:** `2025_01_01_000013_create_guardian_student_table.php`
**Depends on:** schools (005), users

## Purpose

Links guardian (parent/carer) accounts to student accounts within a school, supporting
multiple guardians per student (e.g. divorced parents) and multiple students per
guardian (e.g. siblings).

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| guardian_id | VARCHAR(26) | not null | FK to users — the parent/carer |
| student_id | VARCHAR(26) | not null | FK to users — the student |
| is_primary | BOOLEAN | not null, default true | Primary guardian receives notifications first |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| uq_guardian_student | school_id, guardian_id, student_id | UNIQUE — prevents duplicate links within the same school |
| idx_guardian_student_school_student | school_id, student_id | Fast lookup of all guardians for a student within a school |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| guardian_id | users.id | CASCADE |
| student_id | users.id | CASCADE |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Supports divorced parents: multiple guardians can be linked to the same student
  within a school. The unique constraint `uq_guardian_student` prevents duplicate
  links but allows different guardians for the same student.
- Supports siblings: one guardian can be linked to multiple students within the same
  school.
- `is_primary` determines notification priority — the primary guardian receives
  absence alerts, messages, and other notifications first. Non-primary guardians
  may still receive notifications depending on school settings.
- Both `guardian_id` and `student_id` reference the same `users` table. Role
  differentiation is handled by the user's role attribute, not by separate tables.
- The unique constraint ensures data integrity at the database level, preventing
  accidental duplicate guardian-student relationships within a school.
