# Migration 022 — Create Tasks Table

**File:** `2025_01_01_000022_create_tasks_table.php`
**Depends on:** schools (005), users, classes (011), messages (014)

## Purpose

Creates the polymorphic tasks table supporting staff tasks, homework assignments, and
action items extracted from message threads. Centralises all task-like work into one table
with type-specific behaviour.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| type | VARCHAR | not null | `staff_task`, `homework`, `action_item` |
| title | VARCHAR | not null | Task title |
| description | TEXT | nullable | Task description or instructions |
| status | VARCHAR | not null, default 'todo' | `todo`, `in_progress`, `done`, `cancelled` |
| priority | VARCHAR | nullable | `low`, `medium`, `high`, `urgent` |
| assignee_id | VARCHAR(26) | nullable | FK to users — who the task is assigned to |
| assigned_by_id | VARCHAR(26) | nullable | FK to users — who created the assignment |
| department_label | VARCHAR | nullable | Department scope for staff tasks |
| class_id | VARCHAR(26) | nullable | FK to classes — only for homework type |
| due_at | TIMESTAMP | nullable | Task deadline |
| source_message_id | VARCHAR(26) | nullable | FK to messages — for action items from threads |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |
| deleted_at | TIMESTAMP | nullable | Soft delete timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_task_school_created | school_id, created_at | Default listing — all tasks for a school sorted by date |
| idx_task_school_assignee | school_id, assignee_id | "My tasks" view — tasks assigned to a specific user |
| idx_task_school_status | school_id, status | Filter tasks by status within a school |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| assignee_id | users.id | SET NULL |
| assigned_by_id | users.id | SET NULL |
| class_id | classes.id | SET NULL |
| source_message_id | messages.id | SET NULL |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Polymorphic `type` column determines task behaviour:
  - `staff_task` — internal tasks for teachers/staff (e.g. "Complete risk assessment")
  - `homework` — class assignments linked via `class_id`
  - `action_item` — tasks created from message threads, linked via `source_message_id`
- `class_id` is only populated for `homework` type — null for all other types.
- `source_message_id` links action items back to their origin message, enabling
  "jump to thread" functionality from the task view.
- All nullable FKs use SET NULL on delete to preserve task history — if a user is
  deleted, their tasks remain but the assignee/assigner reference is cleared.
- `type` and `status` use VARCHAR not enum for extensibility.
- `priority` is nullable because not all tasks require explicit prioritisation.
