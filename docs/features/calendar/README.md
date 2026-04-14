# Calendar

## Overview

Multi-calendar system with four calendar types: **internal**, **external**, **department**, and **holiday**. Admins and teachers create and manage events; parents and students see only public calendars (external + holiday). Each school can maintain multiple calendars to separate staff-only scheduling from parent-facing term dates and holidays.

## User Stories

### Admin
- As an admin, I can create, update, and delete any calendar (all four types)
- As an admin, I can create, update, and delete events on any calendar
- As an admin, I can view all calendars and their events in a single unified view

### Teacher
- As a teacher, I can create, update, and delete events I created
- As a teacher, I can view my department calendar and all internal calendars
- As a teacher, I cannot modify events created by other teachers (admin override only)

### Parent
- As a parent, I can view external and holiday calendars (read-only)
- As a parent, I cannot see internal or department calendars

### Student
- As a student, I can view external and holiday calendars (read-only)
- As a student, I cannot see internal or department calendars

## Calendar Types

| Type | Slug | Visibility | Purpose |
|---|---|---|---|
| Internal | `internal` | Staff only (admin + teacher) | Staff meetings, training days, internal deadlines |
| External | `external` | Parent-visible via `is_public` flag | Term dates, parent evenings, school events |
| Department | `department` | Filtered by `department_label` | Department-specific scheduling (e.g. "Science", "PE") |
| Holiday | `holiday` | School-wide (all roles) | Bank holidays, school closures, half-terms |

## Event Ordering

Events are ordered by `starts_at ASC` — this is a **documented exception** to the global `orderBy created_at desc` rule. Chronological ordering is essential for calendar UX; users expect upcoming events first.

## Caching

Month-window cache key pattern:

```
school:{school_id}:calendar:{calendar_id}:{Y}-{m}
```

- Cached per calendar per month (e.g. `school:01j...abc:calendar:01j...xyz:2026-04`)
- Flushed automatically by `CalendarEventObserver` on event create, update, or delete
- Cache store: default (Redis in production, array in testing)

## Database Tables

### `calendars`
| Column | Type | Notes |
|---|---|---|
| id | ULID (PK) | |
| school_id | ULID (FK) | Tenant scoping |
| name | varchar(255) | Display name |
| type | varchar(50) | `internal`, `external`, `department`, `holiday` |
| department_label | varchar(255), nullable | Only for `department` type |
| is_public | boolean | Controls parent/student visibility for `external` type |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp, nullable | Soft delete |

### `calendar_events`
| Column | Type | Notes |
|---|---|---|
| id | ULID (PK) | |
| school_id | ULID (FK) | Tenant scoping |
| calendar_id | ULID (FK) | Belongs to calendar |
| creator_id | ULID (FK) | User who created the event |
| title | varchar(255) | |
| description | text, nullable | |
| starts_at | timestamp | Event start — used for ordering |
| ends_at | timestamp, nullable | Null = all-day or open-ended |
| all_day | boolean | Default false |
| meta | jsonb, nullable | Extensibility: recurrence rules, external IDs, colour overrides |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp, nullable | Soft delete |

## Routes

All routes under middleware stack: `auth`, `not_disabled`, `school`, `legal`.

### Admin / Teacher (CRUD)
| Method | URI | Action |
|---|---|---|
| GET | `/school/calendars` | CalendarController@index |
| POST | `/school/calendars` | CalendarController@store |
| DELETE | `/school/calendars/{calendar}` | CalendarController@destroy |
| POST | `/school/calendars/{calendar}/events` | CalendarEventController@store |
| PUT | `/school/calendars/{calendar}/events/{event}` | CalendarEventController@update |
| DELETE | `/school/calendars/{calendar}/events/{event}` | CalendarEventController@destroy |

### Parent / Student (read-only)
| Method | URI | Action |
|---|---|---|
| GET | `/school/calendars/external` | CalendarEventController@externalIndex |

## meta JSONB

The `meta` column on `calendar_events` is reserved for extensibility. Planned uses:

- **Recurrence rules** — `{ "rrule": "FREQ=WEEKLY;BYDAY=MO" }` (iCal RRULE format)
- **External IDs** — `{ "google_event_id": "..." }` for future sync integrations
- **Colour overrides** — `{ "color": "#FF5733" }` per-event colour in calendar UI

No application logic depends on `meta` in the initial implementation. It is purely forward-looking.
