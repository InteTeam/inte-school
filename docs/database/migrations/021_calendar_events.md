# Migration 021 — Create Calendar Events Table

**File:** `2025_01_01_000021_create_calendar_events_table.php`
**Depends on:** schools (005), calendars (020), users

## Purpose

Creates the calendar events table for storing school events. Supports all-day events,
location tracking, and extensible metadata for recurrence rules and external integrations.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| calendar_id | VARCHAR(26) | not null | FK to calendars — which calendar this event belongs to |
| title | VARCHAR | not null | Event title |
| description | TEXT | nullable | Event description or notes |
| starts_at | TIMESTAMP | not null | Event start time |
| ends_at | TIMESTAMP | not null | Event end time |
| all_day | BOOLEAN | not null, default false | Whether this is an all-day event |
| location | VARCHAR | nullable | Event location (free text) |
| meta | JSONB | nullable | Extensible event data (recurrence rules, external IDs, etc.) |
| created_by | VARCHAR(26) | not null | FK to users — who created the event |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |
| deleted_at | TIMESTAMP | nullable | Soft delete timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_event_school_starts_at | school_id, starts_at | Time-range queries scoped to a school |
| idx_event_calendar_starts_at | calendar_id, starts_at | Time-range queries within a specific calendar |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| calendar_id | calendars.id | CASCADE |
| created_by | users.id | RESTRICT |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- **DOCUMENTED EXCEPTION** to the global `orderBy('created_at', 'desc')` rule — events
  are queried `orderBy('starts_at', 'asc')` for future event display. This is the
  correct sort for calendar views where upcoming events appear first.
- `meta` JSONB stores extensible event data (recurrence rules, external IDs, integration
  metadata, etc.). Cast as `array` in the Eloquent model.
- Indexed on `starts_at` (not `created_at`) for efficient time-range queries — the
  primary access pattern is "show me events between date X and date Y".
- `created_by` uses RESTRICT — cannot delete a user who created events. Events should
  be reassigned or the user should be soft-deleted instead.
- Soft deletes allow event removal from the UI while preserving audit history.
