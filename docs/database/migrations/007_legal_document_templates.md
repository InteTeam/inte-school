# Migration 007 — Create Legal Document Templates Table

**File:** `2025_01_01_000007_create_legal_document_templates_table.php`
**Depends on:** none (platform-level, not school-scoped)

## Purpose

Stores root-admin-managed legal document templates (privacy policy, terms & conditions)
that serve as the default starting point when onboarding a new school. Schools receive
a copy of the active template, which they can then customise.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| type | VARCHAR | not null | `privacy_policy` or `terms_conditions` — machine key, translated at display layer |
| name | VARCHAR | not null | Human-readable label (e.g. "UK School Privacy Policy Template v1") |
| content | TEXT | not null | Rich text HTML — default starting point for schools |
| is_active | BOOLEAN | not null, default true | Only active templates are offered during onboarding |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_legal_templates_type | type | Fast lookup by document type |

## Foreign Keys

None — this is a platform-level table, not tenant-scoped.

## Notes

- Not tenant-scoped — no `school_id` column, no `HasSchoolScope` trait. Shared across
  all schools and managed exclusively by root admin.
- When a school is onboarded, the system copies the `content` from the active template
  of each type into `school_legal_documents` for that school.
- `type` stores a machine key (`privacy_policy`, `terms_conditions`) — never a display
  string. Translation happens at the UI layer via `__()` / `t()`.
- `is_active` allows deactivating old template versions without deleting them.
