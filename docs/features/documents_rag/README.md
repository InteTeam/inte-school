# Documents & RAG

## Overview

Document management with AI-powered question answering. Admin/teacher upload PDFs which are processed into searchable chunks via pgvector embeddings. Parents and students can ask natural language questions answered by RAG (Retrieval-Augmented Generation).

## User Stories

- As an **admin**, I can upload school documents (handbook, policies) for AI-powered search
- As a **teacher**, I can upload class-relevant documents
- As a **parent**, I can ask questions like "what is the nut allergy policy?" and get AI answers
- As a **student**, I can search school documents for information

## Key Flows

### Document Upload & Processing
1. Admin/teacher uploads PDF via `POST /documents`
2. `DocumentService::upload()` validates MIME type (PDF only), stores via StorageService
3. Creates `Document` record with `processing_status = pending`
4. Dispatches `ProcessDocumentJob` (default queue)
5. Job extracts text, chunks into ~2000-char segments (200-char overlap)
6. Each chunk embedded via `OllamaService` (768-dim nomic-embed-text)
7. Chunks stored as `DocumentChunk` rows with pgvector embeddings
8. Status updated: `pending → processing → indexed` (or `failed`)

### RAG Query (Parent/Student)
1. User submits question via `POST /ask`
2. `RagService::query()` embeds the question via OllamaService
3. Cosine similarity search against `document_chunks` (scoped by school_id)
4. If above threshold → generates answer from top chunks via Ollama
5. If below threshold → returns fallback: "contact school" or "create ticket"
6. If Ollama unreachable → graceful fallback (no exception thrown)

## Feature Gate

Entire feature gated by `schools.rag_enabled` boolean + `FeatureGate` middleware. Schools must opt in.

## Database Tables

- `documents` — file metadata, processing status, audience flags
- `document_chunks` — chunked text with pgvector VECTOR(768) embeddings + IVFFlat index

## Visibility Controls

| Flag | Default | Effect |
|---|---|---|
| `is_parent_facing` | true | Parents can see document in list |
| `is_staff_facing` | true | Staff can see document in list |

## Processing Status Values

`pending` → `processing` → `indexed` (success) or `failed` (retry + admin alert)

## Validation

- Upload: PDF only (MIME validated server-side), no SVG
- File stored via StorageService (GCS in production)

## Routes

### Web (middleware: auth, not_disabled, school, legal)
- `GET /documents` — document list (admin/teacher)
- `GET /documents/upload` — upload form
- `POST /documents` — handle upload
- `DELETE /documents/{document}` — soft delete (admin only)

### RAG (middleware: + feature:rag)
- `POST /ask` — query endpoint (parent/student)

## Security

- DocumentPolicy: upload by admin/teacher, view by all staff + parents (if is_parent_facing), delete by admin only
- uploaded_by uses RESTRICT — cannot delete users who uploaded documents
