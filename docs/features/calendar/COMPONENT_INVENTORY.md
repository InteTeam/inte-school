# Calendar — Component Inventory

## Pages

| Page | Layout | Shared Components Used |
|---|---|---|
| `Admin/Calendar/Index.tsx` | SchoolLayout | Card, Button, Badge, Input, Select, Label |
| `Teacher/Calendar/Index.tsx` | SchoolLayout | Card, Button, Badge, Input, Select, Label |
| `Parent/Calendar/Index.tsx` | ParentLayout | Card, Badge |
| `Student/Calendar/Index.tsx` | SchoolLayout | Card, Badge |

## Shared Components (from `Components/ui/`)

| Component | Used For |
|---|---|
| `Card` | Calendar list cards, event detail panels |
| `Button` | Create/edit/delete actions, month navigation |
| `Badge` | Calendar type labels (internal, external, department, holiday) |
| `Input` | Event title, date/time inputs in create/edit forms |
| `Select` | Calendar type selector, department filter dropdown |
| `Label` | Form field labels in event create/edit forms |

## Calendar UI Library

**Not yet installed.** The calendar rendering library (FullCalendar or react-big-calendar) is TBD per `PHASES.md`. Initial implementation will use a simple list/card view. The `serializeEvent` method in `CalendarService` already outputs FullCalendar-compatible JSON format to ease future migration.

## Component Reuse Notes

- All form components (`Input`, `Select`, `Label`, `Button`) follow the same patterns used in `messaging/` and `attendance/` features
- `Badge` colour mapping for calendar types should be defined in a shared constant (e.g. `calendarTypeColors`) to stay consistent across admin and teacher views
- Parent and Student pages share the same read-only view logic — consider extracting a `CalendarReadOnlyView` organism if duplication exceeds ~50 lines
