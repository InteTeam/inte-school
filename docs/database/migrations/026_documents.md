# Migration 026 — Create Documents Table

**File:** `2025_01_01_000026_create_documents_table.php`
**Depends on:** schools (005), users

## Purpose

Creates the documents table for storing school document metadata. Tracks file storage,
audience visibility, and RAG pipeline processing status for AI-powered document Q&A.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| name | VARCHAR | not null | Document display name |
| file_path | VARCHAR | not null | Storage path (GCS in production) |
| mime_type | VARCHAR | not null | File MIME type (validated server-side) |
| file_size | UNSIGNED BIGINT | not null | File size in bytes |
| uploaded_by | VARCHAR(26) | not null | FK to users — who uploaded the document |
| is_parent_facing | BOOLEAN | not null, default true | Visible to parents in the portal |
| is_staff_facing | BOOLEAN | not null, default true | Visible to staff in the admin UI |
| processing_status | VARCHAR | not null, default 'pending' | RAG pipeline state: `pending`, `processing`, `indexed`, `failed` |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |
| deleted_at | TIMESTAMP | nullable | Soft delete timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_document_school_created | school_id, created_at | Default listing — all documents for a school sorted by date |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| uploaded_by | users.id | RESTRICT |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- Files stored via `StorageService` (GCS in production, local disk in development).
  `file_path` stores the relative path within the configured storage disk.
- `processing_status` tracks the RAG pipeline state:
  - `pending` — document uploaded, awaiting processing
  - `processing` — chunking and embedding in progress
  - `indexed` — successfully chunked and embedded, ready for similarity search
  - `failed` — processing failed, triggers retry job + admin alert
  This follows the graceful fallback pattern — failure is handled, not ignored.
- `is_parent_facing` and `is_staff_facing` control document visibility per audience.
  Both default to `true` — most documents are visible to everyone. Setting both to
  `false` effectively hides the document (useful for draft/review states).
- `uploaded_by` uses RESTRICT — cannot delete a user who uploaded documents. The user
  should be soft-deleted instead, preserving the upload attribution.
- `mime_type` is validated server-side (never extension only) per security conventions.
  No SVG uploads (MVP) — JPG/PNG/WebP for images, PDF for documents.
- Soft deletes allow document removal from the UI while preserving the file and any
  associated document chunks for audit purposes.
