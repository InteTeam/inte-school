# Migration 008 — Create School Legal Documents Table

**File:** `2025_01_01_000008_create_school_legal_documents_table.php`
**Depends on:** schools (005), users

## Purpose

Stores each school's own copy of legal documents (privacy policy, terms & conditions),
versioned so that publishing a new version triggers re-acceptance by all users.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| type | VARCHAR | not null | `privacy_policy` or `terms_conditions` — machine key |
| content | TEXT | not null | Rich text HTML, editable by school admin |
| version | VARCHAR | not null | Semantic version string (e.g. "1.0", "2.0") |
| is_published | BOOLEAN | not null, default false | Gates visibility to end users |
| published_at | TIMESTAMP | nullable | When document was published |
| published_by | VARCHAR(26) | nullable | FK to users — who published it |
| created_by | VARCHAR(26) | not null | FK to users — who created the draft |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_school_legal_school_type | school_id, type | Fast lookup by school and document type |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| published_by | users.id | SET NULL |
| created_by | users.id | CASCADE |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Each school maintains their own copy of legal documents, initially pre-filled from
  the active `legal_document_templates` record during onboarding.
- Versioned: publishing a new version requires all users to re-accept. The version
  string is stored in `user_legal_acceptances` at acceptance time as a snapshot.
- No SoftDeletes — legal documents should never be hard deleted. Historical versions
  must remain available for compliance audit.
- `is_published` gates visibility: unpublished documents are drafts visible only to
  school admins. Only published documents are presented to users for acceptance.
- `published_by` is SET NULL on user deletion because the document itself must persist
  even if the publishing admin's account is removed.
- `created_by` is CASCADE because a draft created by a deleted user has no value if
  it was never published.
