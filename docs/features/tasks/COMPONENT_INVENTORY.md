# Tasks — Component Inventory

## Pages

| Page | Layout | Shared Components Used |
|---|---|---|
| `Teacher/Tasks/Index.tsx` | SchoolLayout | Card, Button, Badge, Input, Textarea, Select, Label, Checkbox |
| `Teacher/Tasks/HomeworkCreate.tsx` | SchoolLayout | Card, Button, Input, Textarea, Select, Label |
| `Admin/Tasks/TemplateGroups/Index.tsx` | SchoolLayout | Card, Button, Input, Textarea, Label |
| `Parent/Homework/Index.tsx` | ParentLayout | Card, Badge |
| `Student/Homework/Index.tsx` | SchoolLayout | Card, Badge |

## Shared Components (from `Components/ui/`)

| Component | Used For |
|---|---|
| `Card` | Task cards, homework cards, template group cards |
| `Button` | Create/save/delete actions, toggle complete, apply template |
| `Badge` | Task type labels (staff_task, homework, action_item), status badges (pending, in_progress, completed) |
| `Input` | Task title, template name, deadline hours, due date picker |
| `Textarea` | Task description, template group description |
| `Select` | Task type selector, class selector (homework), template group selector (apply) |
| `Label` | Form field labels across all create/edit forms |
| `Checkbox` | Task item completion toggle in the task item list |

## TodoList Organism (Planned)

**Not yet built.** A `TodoList` organism with `dnd-kit` sortable is to be ported from the CRM codebase per `PHASES.md`. This component will encapsulate:

- Sortable item list with drag handles
- Checkbox toggle per item
- Inline deadline display
- Add new item inline
- Reorder via `dnd-kit` (`@dnd-kit/core` + `@dnd-kit/sortable`)

Until ported, task item lists will use a basic `Checkbox` + `Card` combination without drag-reorder support.

## Component Reuse Notes

- `Badge` colour mapping for task types and statuses should be defined in shared constants (e.g. `taskTypeColors`, `taskStatusColors`) for consistency across teacher, admin, parent, and student views
- `Checkbox` toggle fires an Inertia `PUT` request inline — no separate edit form needed for completion toggling
- Parent and Student homework views share the same read-only list structure — consider extracting a `HomeworkList` organism if duplication exceeds ~50 lines
- Template group management (Admin) reuses the same `Card` + `Input` + `Button` pattern as other admin CRUD pages in the project
