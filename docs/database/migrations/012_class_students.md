# Migration 012 — Create Class Students Table

**File:** `2025_01_01_000012_create_class_students_table.php`
**Depends on:** classes (011), users, schools (005)

## Purpose

Pivot table linking students to classes with enrolment tracking. Uses a composite
primary key since a student can only be enrolled in one instance of a given class.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| class_id | VARCHAR(26) | PK (composite), not null | FK to classes |
| student_id | VARCHAR(26) | PK (composite), not null | FK to users |
| school_id | VARCHAR(26) | not null | FK to schools — denormalised for tenant-scoped queries |
| enrolled_at | TIMESTAMP | not null | When the student was enrolled in the class |
| left_at | TIMESTAMP | nullable | When the student left the class (null = currently enrolled) |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_class_students_school_student | school_id, student_id | Fast lookup of all classes for a student within a school |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| class_id | classes.id | CASCADE |
| student_id | users.id | CASCADE |
| school_id | schools.id | CASCADE |

## Notes

- **No ULID primary key** — uses a composite PK of `(class_id, student_id)` since a
  student can only be in one instance of a class. The composite PK enforces this at
  the database level.
- `school_id` is denormalised from `classes.school_id` for efficient tenant-scoped
  queries. Without it, every query would need to join through `classes` to filter by
  school. The `HasSchoolScope` trait on the model uses this column directly.
- `left_at` tracks when a student leaves a class without deleting the record. This
  preserves the enrolment history — useful for attendance reports that span the full
  academic year.
- No `created_at` / `updated_at` columns — `enrolled_at` and `left_at` provide all
  the temporal tracking needed for this pivot table.
- Model should not use `HasUlids` trait since there is no ULID primary key.
