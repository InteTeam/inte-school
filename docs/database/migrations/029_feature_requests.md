# Migration 029 — Create Feature Requests Table

**File:** `2025_01_01_000029_create_feature_requests_table.php`
**Depends on:** schools (005), users

## Purpose

Creates the feature requests table for school admins to submit product feedback. Requests
are visible to the root admin as a cross-school feed for prioritisation.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| submitted_by | VARCHAR(26) | not null | FK to users — who submitted the request |
| title | VARCHAR(150) | not null | Request title (max 150 chars) |
| body | TEXT | not null | Request description (max 2000 chars enforced at app layer) |
| status | VARCHAR | not null, default 'open' | `open`, `under_review`, `planned`, `done`, `declined` |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_feature_request_school_created | school_id, created_at | Default listing — all requests for a school sorted by date |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| submitted_by | users.id | RESTRICT |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- School admins submit feature requests that are visible to the root admin. Each school
  sees only its own requests; the root admin sees a cross-school feed.
- `body` max 2000 chars is enforced in the form request validation, not at the DB level.
  This keeps the constraint in the application layer where it can be changed without a
  migration:
  ```php
  'body' => ['required', 'string', 'max:2000'],
  ```
- `title` has a VARCHAR(150) constraint at the DB level as a safety net, with the same
  limit enforced in validation.
- `status` lifecycle is managed by the root admin:
  - `open` — newly submitted, awaiting review
  - `under_review` — root admin is evaluating the request
  - `planned` — accepted and scheduled for implementation
  - `done` — implemented and deployed
  - `declined` — not planned (with optional explanation via a separate note)
- Ordered by `created_at desc` for both the school admin view (own school's requests)
  and the root admin view (cross-school feed). Follows the standard sort convention.
- `submitted_by` uses RESTRICT — cannot delete a user who submitted feature requests.
  The user should be soft-deleted instead.
- No soft deletes — feature requests are permanent records. Declined requests remain
  visible with their status for transparency.
