# Tasks

## Overview

Task system with three task types: **staff tasks** (internal team todos), **homework** (class-linked assignments with due dates), and **action items** (spawned from messages). Includes a grouped todo template system ported from CRM with cascade deadline logic, allowing admins to define reusable task checklists that auto-calculate item deadlines relative to task creation time.

## User Stories

### Teacher
- As a teacher, I can create staff tasks and assign them to other staff members
- As a teacher, I can create homework assignments linked to a class with a due date
- As a teacher, I can toggle task items as complete/incomplete
- As a teacher, I can drag-reorder task items within a task
- As a teacher, I can view all tasks assigned to me or created by me

### Admin
- As an admin, I can create and manage task template groups (reusable checklists)
- As an admin, I can apply a template group to a task, auto-generating items with cascade deadlines
- As an admin, I can view and manage all tasks across the school

### Parent
- As a parent, I can view my child's homework assignments and their due dates (read-only)

### Student
- As a student, I can view my homework due list ordered by due date (read-only)

## Task Types

| Type | Slug | Scope | Key Fields | Purpose |
|---|---|---|---|---|
| Staff Task | `staff_task` | Internal (staff only) | assignee_id, assigned_by_id | Internal team todos, follow-ups |
| Homework | `homework` | Class-linked | school_class_id, due_at | Assignments with deadlines, visible to parents/students |
| Action Item | `action_item` | Linked to message | source_message_id | Tasks spawned from messaging conversations |

## Template System

### Structure

```
TaskTemplateGroup (e.g. "New Student Onboarding")
  -> TaskTemplate (e.g. "Send welcome pack" — default_deadline_hours: 24)
  -> TaskTemplate (e.g. "Schedule parent meeting" — default_deadline_hours: 48)
  -> TaskTemplate (e.g. "Add to class register" — default_deadline_hours: 72)
```

### Cascade Deadline Logic

When a template group is applied to a task:

1. Each `TaskTemplate` becomes a `TaskItem` on the task
2. `TaskItem.deadline` = task `created_at` + template `default_deadline_hours`
3. Deadlines cascade forward: items are ordered by `default_deadline_hours` ascending
4. **Unchecking a completed item does NOT reverse the deadline** — the original deadline remains. This is intentional to prevent timeline confusion.

## Homework Deadline Alerts

### HomeworkDeadlineAlertJob

- **Queue:** `low` (non-blocking, runs after higher-priority jobs)
- **Schedule:** Runs on scheduler (daily or as configured)
- **Logic:** Finds homework tasks past `due_at` that have not been marked complete, then notifies guardians via `MessagingService`
- **Notification target:** Parent/guardian of the student, not the student directly

## Drag Reorder

Task items support drag-and-drop reordering via `sort_order` column. Frontend uses `dnd-kit` for the sortable interaction. The `reorder` endpoint accepts an ordered array of item IDs and bulk-updates `sort_order`.

## Database Tables

### `tasks`
| Column | Type | Notes |
|---|---|---|
| id | ULID (PK) | |
| school_id | ULID (FK) | Tenant scoping |
| type | varchar(50) | `staff_task`, `homework`, `action_item` |
| title | varchar(255) | |
| description | text, nullable | |
| status | varchar(50) | `pending`, `in_progress`, `completed` |
| assignee_id | ULID (FK), nullable | User assigned to the task |
| assigned_by_id | ULID (FK) | User who created/assigned the task |
| school_class_id | ULID (FK), nullable | Only for `homework` type |
| source_message_id | ULID (FK), nullable | Only for `action_item` type |
| due_at | timestamp, nullable | Deadline (required for homework) |
| sort_order | integer | For ordering within a list |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp, nullable | Soft delete |

### `task_template_groups`
| Column | Type | Notes |
|---|---|---|
| id | ULID (PK) | |
| school_id | ULID (FK) | Tenant scoping |
| name | varchar(255) | Group name (e.g. "New Student Onboarding") |
| description | text, nullable | |
| created_at | timestamp | |
| updated_at | timestamp | |

### `task_templates`
| Column | Type | Notes |
|---|---|---|
| id | ULID (PK) | |
| school_id | ULID (FK) | Tenant scoping |
| task_template_group_id | ULID (FK) | Belongs to group |
| title | varchar(255) | Template item title |
| default_deadline_hours | integer, nullable | Hours after task creation for deadline |
| sort_order | integer | Order within group |
| created_at | timestamp | |
| updated_at | timestamp | |

### `task_items`
| Column | Type | Notes |
|---|---|---|
| id | ULID (PK) | |
| school_id | ULID (FK) | Tenant scoping |
| task_id | ULID (FK) | Belongs to task |
| title | varchar(255) | Item title (from template or manual) |
| is_completed | boolean | Default false |
| completed_at | timestamp, nullable | When item was marked complete |
| deadline | timestamp, nullable | Calculated from template or set manually |
| sort_order | integer | For drag-reorder |
| created_at | timestamp | |
| updated_at | timestamp | |

## Routes

Routes defined in `tasks.php`, under middleware stack: `auth`, `not_disabled`, `school`, `legal`.

### Teacher
| Method | URI | Action |
|---|---|---|
| GET | `/school/tasks` | TaskController@index |
| POST | `/school/tasks` | TaskController@store |
| GET | `/school/tasks/homework/create` | TaskController@createHomework |
| POST | `/school/tasks/homework` | TaskController@storeHomework |
| PUT | `/school/tasks/{task}/items/{item}/toggle` | TaskController@toggleItem |
| PUT | `/school/tasks/{task}/reorder` | TaskController@reorder |

### Admin
| Method | URI | Action |
|---|---|---|
| GET | `/school/tasks/template-groups` | TaskController@templateGroupsIndex |
| POST | `/school/tasks/template-groups` | TaskController@storeTemplateGroup |
| POST | `/school/tasks/{task}/apply-template` | TaskController@applyTemplate |

## Ordering

- **Tasks list:** `created_at DESC` (newest first) — follows global convention
- **Homework for students:** `due_at ASC` (soonest deadline first) — **documented exception** to global `orderBy created_at desc` rule. Students need to see what is due next, not what was assigned most recently.
- **Task items within a task:** `sort_order ASC` (drag-reorder position)
