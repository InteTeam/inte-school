# Documents & RAG — Architecture

## Backend Layers

### Models

| Model | Traits | Policy | Key Relations |
|---|---|---|---|
| `Document` | HasUlids, HasSchoolScope, SoftDeletes | DocumentPolicy | school, uploader (User), chunks |
| `DocumentChunk` | HasUlids, HasSchoolScope | — | document |

### Services

| Service | Methods |
|---|---|
| `DocumentService` (final) | `upload()` — validate MIME, store file, create record, dispatch job; `delete()` — soft delete |
| `RagService` (final) | `query()` — embed question, cosine similarity search, generate answer or fallback |
| `OllamaService` (final) | `embed(text)` → 768-dim vector; `generate(prompt, chunks)` → answer; graceful null on failure |

### Jobs

| Job | Queue | Purpose |
|---|---|---|
| `ProcessDocumentJob` | default | PDF text extraction → chunking (~2000 chars, 200 overlap) → embedding → store chunks |

One attempt only (no retry on Ollama timeout). Logs failures, sets `processing_status = failed`.

### Policy: `DocumentPolicy`

- `before()` — root admin bypass
- `create()` — admin and teacher
- `view()` — admin/teacher/support (all); parent (only if `is_parent_facing = true`)
- `delete()` — admin only

### Controller: `School/DocumentController` (116 lines)

- `index()` — lists documents ordered by `created_at desc`
- `create()` — upload form
- `store()` — validates, calls `DocumentService::upload()`
- `destroy()` — soft delete via service
- `query()` — RAG endpoint, calls `RagService::query()`, returns JSON

## Frontend Structure

### Pages

| Page | Layout | Role |
|---|---|---|
| `Admin/Documents/Index.tsx` | SchoolLayout | Document list with processing status badges |
| `Admin/Documents/Upload.tsx` | SchoolLayout | File upload form with MIME validation |
| `Parent/Ask/Index.tsx` | ParentLayout | "Ask a question" input + answer display |
| `Student/Ask/Index.tsx` | SchoolLayout | Same RAG interface for students |

## pgvector Integration

- Column: `VECTOR(768)` on `document_chunks.embedding`
- Index: IVFFlat with 100 lists for approximate nearest neighbour
- Operator: `<=>` (cosine similarity)
- SQLite fallback: TEXT column for test suite (no vector operations)
- Query always scoped by `school_id` first, then similarity search
