# Tasks — Architecture

## Models

| Model | Traits | Policy | Relations |
|---|---|---|---|
| `Task` | HasUlids, HasSchoolScope, SoftDeletes | TaskPolicy | `assignee()` BelongsTo User, `assignedBy()` BelongsTo User, `schoolClass()` BelongsTo SchoolClass, `items()` HasMany TaskItem |
| `TaskTemplateGroup` | HasUlids, HasSchoolScope | — | `school()` BelongsTo School, `templates()` HasMany TaskTemplate |
| `TaskTemplate` | HasUlids, HasSchoolScope | — | `group()` BelongsTo TaskTemplateGroup |
| `TaskItem` | HasUlids, HasSchoolScope | — | `task()` BelongsTo Task |

### Key Model Notes

- `Task` is the only model with a dedicated policy; template models and items are authorized through the parent task or admin role checks in controllers
- `TaskItem.is_completed` + `completed_at` are managed together via `TaskService::toggleItem`
- `TaskTemplateGroup` and `TaskTemplate` do not use `SoftDeletes` — deleting a template group does not affect already-applied task items

## Service

### TaskService (final, ~175 lines)

| Method | Visibility | Purpose |
|---|---|---|
| `createTask(User, array): Task` | public | Create a staff task, sets assigned_by to creator |
| `createHomework(User, array): Task` | public | Create homework task, validates school_class_id and due_at |
| `createActionItem(User, Message, array): Task` | public | Create action item linked to source message |
| `applyTemplateGroup(Task, TaskTemplateGroup): Task` | public | Generate TaskItems from templates with cascade deadlines |
| `toggleItem(TaskItem): TaskItem` | public | Toggle is_completed, set/clear completed_at, cascade deadline to next item |
| `reorder(Task, array $itemIds): void` | public | Bulk-update sort_order from ordered ID array |
| `updateStatus(Task, string $status): Task` | public | Update task status (pending/in_progress/completed) |
| `cascadeDeadline(TaskItem): void` | private | When an item is completed, recalculate next incomplete item's deadline relative to completion time |

### Cascade Deadline Detail

`cascadeDeadline` is called internally by `toggleItem` when an item is marked complete:

1. Find the next incomplete item (by `sort_order`)
2. If the next item has a `deadline` and the completed item's original `default_deadline_hours` gap is known, adjust the next item's deadline relative to `now()`
3. If the item is unchecked (marked incomplete again), **no deadline reversal occurs** — the existing deadline stands

## Policy

### TaskPolicy

| Method | Logic |
|---|---|
| `before(User, string)` | Root admin bypasses all checks (returns `true`) |
| `create(User)` | Admin, Teacher, or Support role |
| `update(User, Task)` | Admin: all tasks. Teacher/Support: only if `assignee_id` or `assigned_by_id` matches auth user |
| `delete(User, Task)` | Admin only |

## Controllers

### Teacher\TaskController (~144 lines)

| Method | Route | Notes |
|---|---|---|
| `index()` | GET `/school/tasks` | List tasks assigned to or created by auth teacher. Eager loads items |
| `store(Request)` | POST `/school/tasks` | Validate title, description, type, assignee_id. Delegates to TaskService::createTask |
| `createHomework()` | GET `/school/tasks/homework/create` | Render homework creation form with class list |
| `storeHomework(Request)` | POST `/school/tasks/homework` | Validate title, school_class_id, due_at, description. Delegates to TaskService::createHomework |
| `toggleItem(Task, TaskItem)` | PUT `/school/tasks/{task}/items/{item}/toggle` | Authorize task update, delegate to TaskService::toggleItem |
| `reorder(Task)` | PUT `/school/tasks/{task}/reorder` | Accepts `item_ids` array, delegates to TaskService::reorder |

### Admin\TaskController (~90 lines)

| Method | Route | Notes |
|---|---|---|
| `templateGroupsIndex()` | GET `/school/tasks/template-groups` | List all template groups with templates. Admin only |
| `storeTemplateGroup(Request)` | POST `/school/tasks/template-groups` | Validate name, description, templates array (title + default_deadline_hours). Creates group + templates |
| `applyTemplate(Task)` | POST `/school/tasks/{task}/apply-template` | Validate template_group_id, delegate to TaskService::applyTemplateGroup |

## Jobs

### HomeworkDeadlineAlertJob

| Property | Value |
|---|---|
| Queue | `low` |
| Schedule | Daily (via Laravel scheduler) |
| Logic | Query `tasks` where `type = homework`, `due_at < now()`, `status != completed`. For each, find student's guardian(s) via class enrollment. Send notification via `MessagingService` |
| Idempotency | Tracks last notification sent per task to avoid duplicate alerts |

## Frontend Pages

| Page | Layout | Role | Mode |
|---|---|---|---|
| `Teacher/Tasks/Index.tsx` | SchoolLayout | Teacher | Full task management — create, toggle, reorder, view |
| `Teacher/Tasks/HomeworkCreate.tsx` | SchoolLayout | Teacher | Homework creation form with class selector and due date |
| `Admin/Tasks/TemplateGroups/Index.tsx` | SchoolLayout | Admin | Template group CRUD, apply templates to tasks |
| `Parent/Homework/Index.tsx` | ParentLayout | Parent | Read-only homework list for their children |
| `Student/Homework/Index.tsx` | SchoolLayout | Student | Read-only homework due list, ordered by due_at ASC |
