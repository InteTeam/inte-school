# Calendar — Architecture

## Models

| Model | Traits | Policy | Relations |
|---|---|---|---|
| `Calendar` | HasUlids, HasSchoolScope, SoftDeletes | CalendarPolicy | `school()` BelongsTo School, `calendarEvents()` HasMany CalendarEvent |
| `CalendarEvent` | HasUlids, HasSchoolScope, SoftDeletes | CalendarPolicy | `calendar()` BelongsTo Calendar, `creator()` BelongsTo User |

### Key Model Notes

- Both models use `HasSchoolScope` for automatic tenant filtering
- `CalendarEvent` shares `CalendarPolicy` (event-level actions are authorized through the parent calendar's policy)
- `SoftDeletes` on both — deleted calendars cascade soft-delete visibility (events remain but parent is trashed)

## Service

### CalendarService (final, ~191 lines)

| Method | Purpose |
|---|---|
| `createCalendar(School, array): Calendar` | Create a new calendar for a school |
| `updateCalendar(Calendar, array): Calendar` | Update calendar name, type, department_label, is_public |
| `deleteCalendar(Calendar): void` | Soft-delete a calendar |
| `createEvent(Calendar, User, array): CalendarEvent` | Create event, flush cache |
| `updateEvent(CalendarEvent, array): CalendarEvent` | Update event, flush cache |
| `deleteEvent(CalendarEvent): void` | Soft-delete event, flush cache |
| `getMonthEvents(Calendar, int $year, int $month): Collection` | Cached month query, orders by `starts_at ASC` |
| `getPublicMonthEvents(School, int $year, int $month): Collection` | Public calendars only (parents/students), cached |
| `flushCalendarCache(CalendarEvent): void` | Flush month-window cache for the event's calendar + month |
| `serializeEvent(CalendarEvent): array` | Transform to FullCalendar-compatible format (`id`, `title`, `start`, `end`, `allDay`, `extendedProps`) |

### Cache Strategy

- `getMonthEvents` and `getPublicMonthEvents` cache results per calendar per month
- Cache key: `school:{id}:calendar:{cal_id}:{Y}-{m}`
- `flushCalendarCache` is called by `CalendarEventObserver` on created/updated/deleted
- `serializeEvent` produces the JSON shape expected by FullCalendar (or equivalent frontend library)

## Policy

### CalendarPolicy

| Method | Logic |
|---|---|
| `before(User, string)` | Root admin bypasses all checks (returns `true`) |
| `create(User)` | Admin or Teacher role |
| `update(User, Calendar)` | Admin: all calendars. Teacher: only calendars they created events on (own events only) |
| `delete(User, Calendar)` | Admin: all calendars. Teacher: only calendars they created events on (own events only) |
| `viewExternal(User)` | Parent or Student role — grants read-only access to public calendars |

## Controllers

### School\CalendarController (~88 lines)

| Method | Route | Notes |
|---|---|---|
| `index()` | GET `/school/calendars` | Returns all calendars with event counts. Admin/Teacher see all types; filters by role |
| `store(Request)` | POST `/school/calendars` | Validates name, type, department_label, is_public. Delegates to CalendarService |
| `destroy(Calendar)` | DELETE `/school/calendars/{calendar}` | Authorizes via policy, delegates to CalendarService |

### School\CalendarEventController (~120 lines)

| Method | Route | Notes |
|---|---|---|
| `store(Request, Calendar)` | POST `/school/calendars/{calendar}/events` | Validates title, starts_at, ends_at, all_day, meta. Creator = auth user |
| `update(Request, Calendar, CalendarEvent)` | PUT `/school/calendars/{calendar}/events/{event}` | Authorizes update, validates same fields |
| `destroy(Calendar, CalendarEvent)` | DELETE `/school/calendars/{calendar}/events/{event}` | Authorizes delete, soft-deletes via service |
| `externalIndex(Request)` | GET `/school/calendars/external` | Public calendars for parents/students. Uses `getPublicMonthEvents`. Accepts `year` and `month` query params |

## Frontend Pages

| Page | Layout | Role | Mode |
|---|---|---|---|
| `Admin/Calendar/Index.tsx` | SchoolLayout | Admin | Full CRUD — create/edit/delete calendars and events |
| `Teacher/Calendar/Index.tsx` | SchoolLayout | Teacher | CRUD own events, view department calendars |
| `Parent/Calendar/Index.tsx` | ParentLayout | Parent | Read-only — external + holiday calendars |
| `Student/Calendar/Index.tsx` | SchoolLayout | Student | Read-only — external + holiday calendars |

## Observer

### CalendarEventObserver

Registered on `CalendarEvent`. Calls `CalendarService::flushCalendarCache()` on:
- `created`
- `updated`
- `deleted`

Ensures month-window cache stays consistent without manual intervention.
