# Migration 018 — Create Attendance Records Table

**File:** `2025_01_01_000018_create_attendance_records_table.php`
**Depends on:** schools (005), attendance_registers (017), users

## Purpose

Stores individual student attendance marks within a register. Each row represents one
student's attendance status for a specific register session, with audit metadata tracking
how and by whom the mark was recorded.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | ULID | not null | FK to schools — tenant scope |
| register_id | ULID | not null | FK to attendance_registers — the parent register |
| student_id | ULID | not null | FK to users — the student being marked |
| status | VARCHAR | not null | `present`, `absent`, or `late` — machine key |
| marked_by | ULID | not null | FK to users — teacher or system that recorded the mark |
| marked_via | VARCHAR | not null, default 'manual' | Input source: `manual`, `nfc_card`, `nfc_phone`, `api` |
| pre_notified | BOOLEAN | not null, default false | Whether parent pre-notified the school of absence |
| notes | VARCHAR | nullable | Optional note (e.g. "arrived 10 minutes late", "dentist appointment") |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_record_school_student | school_id, student_id | Student attendance history — covers per-student queries and reports |
| idx_record_register | register_id | All records in a register — covers register detail view |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| register_id | attendance_registers.id | CASCADE |
| student_id | users.id | RESTRICT |
| marked_by | users.id | RESTRICT |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- `pre_notified` flag suppresses parent absence alerts — if a parent has already
  notified the school of an absence (via the app or phone call logged by admin), no
  notification cascade fires for that student. This prevents unnecessary SMS costs and
  parent anxiety.
- `marked_via` tracks the input source for audit purposes:
  - `manual` — teacher tapped the student's status in the UI
  - `nfc_card` — student tapped their NFC card on a hardware reader
  - `nfc_phone` — student tapped their phone on an NFC reader
  - `api` — recorded via external system integration
- `student_id` and `marked_by` both use RESTRICT on delete — cannot delete users with
  attendance history. This preserves statutory attendance records. Users must be
  soft-disabled rather than hard deleted.
- `status` uses VARCHAR not enum for extensibility — additional statuses (e.g.
  `authorised_absence`, `medical`) can be added without a migration.
- No soft deletes — attendance records are permanent for statutory compliance and
  safeguarding audit.
