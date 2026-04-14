# Migration 023 — Create Task Template Groups Table

**File:** `2025_01_01_000023_create_task_template_groups_table.php`
**Depends on:** schools (005)

## Purpose

Creates the task template groups table for organising reusable task templates by department
or function. Ported from CRM `todo_template_groups` pattern.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| name | VARCHAR | not null | Group name (e.g. "New Starter Onboarding", "Trip Preparation") |
| department_label | VARCHAR | nullable | Department scope for filtering |
| task_type | VARCHAR | not null, default 'staff' | Scopes which context shows this group |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_template_group_school_created | school_id, created_at | Default listing — all template groups for a school sorted by date |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Ported from CRM `todo_template_groups` — proven pattern for reusable checklists.
- Groups organise templates by department or function (e.g. "New Starter Onboarding"
  contains a group of template items that get applied when onboarding a new staff member).
- `task_type` scopes which template groups appear in which context — only `'staff'`
  groups are shown for staff tasks. Homework uses direct assignment without templates,
  so homework template groups are not needed.
- No soft deletes — template groups can be hard-deleted if no longer needed.
  Associated templates cascade via FK.
