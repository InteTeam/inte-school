# Migration 024 — Create Task Templates Table

**File:** `2025_01_01_000024_create_task_templates_table.php`
**Depends on:** schools (005), task_template_groups (023)

## Purpose

Creates the task templates table for reusable checklist items within template groups.
Each template defines a single checklist item with an optional cascade deadline.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| group_id | VARCHAR(26) | nullable | FK to task_template_groups — parent group |
| name | VARCHAR | not null | Template item name (e.g. "Send welcome email") |
| sort_order | INTEGER | not null, default 0 | Display order within a group |
| default_deadline_hours | INTEGER | nullable | Hours after task creation for the deadline |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

None beyond the default primary key index. Templates are always loaded via their
`group_id` relationship, which benefits from the FK index.

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| group_id | task_template_groups.id | CASCADE |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Ported from CRM `todo_templates` — proven pattern for reusable checklist items.
- `default_deadline_hours` enables cascade deadline logic — when a template group is
  applied to a task, each item's deadline is calculated relative to the task creation
  time. For example, if `default_deadline_hours = 48`, the item's `deadline_at` is
  set to task creation time + 48 hours.
- `sort_order` controls display order within a group — supports drag-and-drop
  reordering in the admin UI.
- `group_id` cascades on delete — if a template group is deleted, all its templates
  are also removed.
- No soft deletes — templates can be hard-deleted. Existing task items created from
  a template retain their data independently (via copied values, not live references).
