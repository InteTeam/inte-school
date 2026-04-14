# Migration 027 — Create Document Chunks Table

**File:** `2025_01_01_000027_create_document_chunks_table.php`
**Depends on:** schools (005), documents (026), pgvector extension (001)

## Purpose

Creates the document chunks table for RAG (Retrieval-Augmented Generation) embeddings.
Each document is split into chunks with vector embeddings for cosine similarity search,
enabling AI-powered document Q&A scoped per school.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | not null | FK to schools — tenant scope |
| document_id | VARCHAR(26) | not null | FK to documents — parent document |
| chunk_index | UNSIGNED INTEGER | not null | Position within the document (0-based) |
| content | TEXT | not null | Chunk text content |
| embedding | VECTOR(768) / TEXT | PostgreSQL: VECTOR(768), SQLite: TEXT nullable | Vector embedding for similarity search |
| created_at | TIMESTAMP | not null, default CURRENT_TIMESTAMP | Creation timestamp (no updated_at — chunks are immutable) |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_chunk_document | document_id | Fast lookup of all chunks for a document |
| idx_embedding | embedding (IVFFlat, cosine) | Approximate nearest neighbour search — PostgreSQL only, lists=100 |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| school_id | schools.id | CASCADE |
| document_id | documents.id | CASCADE |

## Notes

- Tenant-scoped via `school_id` — model uses `HasSchoolScope` trait.
- `VECTOR(768)` matches the output dimensions of `nomic-embed-text` (the embedding
  model used by the RAG pipeline). If the embedding model changes, this column size
  must be updated via a new migration.
- IVFFlat index with 100 lists for approximate nearest neighbour search using cosine
  similarity operator (`<=>`). Created via raw `DB::statement` since Laravel Schema
  doesn't support vector index creation natively:
  ```sql
  CREATE INDEX idx_embedding ON document_chunks
  USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100);
  ```
- Migration includes SQLite fallback — the `embedding` column is created as nullable
  `TEXT` on SQLite so tests can run without pgvector. The IVFFlat index creation is
  wrapped in a PostgreSQL driver check and skipped for SQLite.
- `chunk_index` preserves document order for context reconstruction — when displaying
  RAG results, surrounding chunks can be fetched by `document_id` and adjacent
  `chunk_index` values to provide broader context.
- No `updated_at` column — chunks are immutable. When a document is reprocessed, all
  existing chunks are deleted and recreated. Only `created_at` is needed.
- `document_id` cascades on delete — when a document is deleted, all its chunks
  (and their embeddings) are automatically removed.
- Similarity search pattern (always scope by `school_id` first):
  ```php
  DocumentChunk::query()
      ->where('school_id', $schoolId)
      ->orderByRaw('embedding <=> ?', [$queryEmbedding])
      ->limit(5)
      ->get();
  ```
