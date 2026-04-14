# Migration 025 — Create Task Items Table

**File:** `2025_01_01_000025_create_task_items_table.php`
**Depends on:** schools (005), tasks (022), task_templates (024), task_template_groups (023)

## Purpose

Creates the task items table for individual checklist items within a task. Items can be
created manually or generated from templates with cascade deadlines.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| task_id | VARCHAR(26) | not null | FK to tasks — parent task |
| template_id | VARCHAR(26) | nullable | FK to task_templates — source template (if generated) |
| group_id | VARCHAR(26) | nullable | FK to task_template_groups — source group (if generated) |
| title | VARCHAR | not null | Checklist item text |
| is_completed | BOOLEAN | not null, default false | Whether this item is checked off |
| is_custom | BOOLEAN | not null, default true | `false` = created from template, `true` = user-created |
| sort_order | INTEGER | not null, default 0 | Display order within the task |
| deadline_at | TIMESTAMP | nullable | Item-level deadline (calculated from template or set manually) |
| default_deadline_hours | INTEGER | nullable | Copied from template for reference |
| completed_at | TIMESTAMP | nullable | When the item was marked complete |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_item_task | task_id | Fast lookup of all items belonging to a task |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| task_id | tasks.id | CASCADE |
| template_id | task_templates.id | SET NULL |
| group_id | task_template_groups.id | SET NULL |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Ported from CRM `todo_items` — proven pattern for checklist items with deadlines.
- `is_custom` distinguishes user-created items (`true`) from template-generated ones
  (`false`). This allows the UI to show which items came from a template vs. were
  added manually.
- `deadline_at` is calculated from `default_deadline_hours` + task creation time when
  applying a template group (cascade deadline logic). For manually created items,
  `deadline_at` can be set directly.
- Unchecking a completed item does NOT reverse the `deadline_at` — the deadline remains
  as originally calculated. `completed_at` is cleared when unchecked.
- `sort_order` supports drag-and-drop reordering via dnd-kit on the frontend.
- `template_id` and `group_id` use SET NULL on delete — if the source template is
  deleted, the task item remains but loses its template reference. The item's title
  and deadline are independent copies, not live references.
- No soft deletes — items are hard-deleted when removed from a task.
