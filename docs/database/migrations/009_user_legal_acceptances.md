# Migration 009 — Create User Legal Acceptances Table

**File:** `2025_01_01_000009_create_user_legal_acceptances_table.php`
**Depends on:** schools (005), users, school_legal_documents (008)

## Purpose

Append-only audit trail recording each user's acceptance of a legal document version,
including IP address and user agent for UK Children's Code compliance.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| user_id | VARCHAR(26) | not null | FK to users — who accepted |
| document_id | VARCHAR(26) | not null | FK to school_legal_documents — which document |
| document_type | VARCHAR | not null | `privacy_policy` or `terms_conditions` — snapshot at acceptance time |
| document_version | VARCHAR | not null | Version string snapshot (e.g. "1.0") at acceptance time |
| accepted_at | TIMESTAMP | not null | When the user accepted |
| ip_address | VARCHAR | not null | Client IP at acceptance time |
| user_agent | TEXT | not null | Browser user agent string at acceptance time |
| created_at | TIMESTAMP | nullable | Laravel timestamp — only `created_at`, no `updated_at` |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_legal_accept_user | school_id, user_id | Fast lookup of all acceptances for a user within a school |
| idx_legal_accept_document | document_id | Fast lookup of all acceptances for a specific document |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| user_id | users.id | CASCADE |
| document_id | school_legal_documents.id | CASCADE |

## Notes

- **APPEND-ONLY** — no `updated_at` column. The application layer must never update
  or delete records in this table. This is a compliance audit trail.
- `document_type` and `document_version` are snapshotted at acceptance time so the
  record remains valid and self-contained even if the source document is later updated
  or a new version is published.
- Records IP address and user agent for compliance with UK Children's Code (Age
  Appropriate Design Code) which requires demonstrable proof of consent.
- When a new document version is published, users must re-accept — the system checks
  whether the user has an acceptance record matching the current published version.
- Model should use `const UPDATED_AT = null;` to prevent Laravel from writing to a
  non-existent `updated_at` column.
