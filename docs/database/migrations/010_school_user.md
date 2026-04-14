# Migration 010 — Create School User Pivot Table

**File:** `2025_01_01_000010_create_school_user_table.php`
**Depends on:** schools (005), users

## Purpose

Multi-tenancy pivot table linking users to schools with role assignments.
A user can belong to multiple schools with a different role in each, enabling
scenarios like a teacher at one school who is also a parent at another.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools |
| user_id | VARCHAR(26) | not null | FK to users |
| role | VARCHAR | not null | One of: admin, teacher, support, student, parent |
| department_label | VARCHAR | nullable | Optional department/year group label |
| invitation_token | VARCHAR | nullable, unique | Single-use token for email invite flow |
| invitation_expires_at | TIMESTAMP | nullable | Expiry for invitation_token |
| accepted_at | TIMESTAMP | nullable | When the user accepted the invitation |
| invited_by | VARCHAR(26) | nullable | FK to users — who sent the invitation |
| invited_at | TIMESTAMP | nullable | When the invitation was sent |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_school_user | school_id, user_id | Fast lookup of user membership per school |
| idx_school_role | school_id, role | Filtered queries by role within a school (e.g. "all teachers") |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| user_id | users.id | CASCADE |
| invited_by | users.id | SET NULL |

## Notes

- `role` uses VARCHAR not enum for extensibility — new roles can be added without
  a migration.
- `invitation_token` enables the email-based staff/guardian invite flow: an admin
  generates a token, the invitee clicks the link, and the token is validated against
  `invitation_expires_at` before activating the membership.
- `invited_by` uses `SET NULL` on delete (not `CASCADE`) because the invitation
  record should survive even if the inviting user is removed.
- The composite index on `(school_id, user_id)` supports the `HasSchoolScope`
  global scope which filters by `school_id` on every query.
- `department_label` is a free-text field rather than a normalised table — schools
  structure departments differently, and a simple label is sufficient for MVP.
