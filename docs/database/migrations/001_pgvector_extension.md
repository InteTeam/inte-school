# Migration 001 — Enable pgvector Extension

**File:** `2025_01_01_000001_enable_pgvector_extension.php`
**Depends on:** PostgreSQL 16 with `pgvector/pgvector:pg16` image

## Purpose

Enables the `vector` PostgreSQL extension required for storing and querying
embedding vectors (PGVector). This must run before any migration that creates
a column using `$table->vector(768)`.

## SQL

```sql
-- up
CREATE EXTENSION IF NOT EXISTS vector;

-- down
DROP EXTENSION IF EXISTS vector;
```

## Notes

- `IF NOT EXISTS` makes this idempotent — safe to run on a database that
  already has the extension enabled (e.g. after a container rebuild).
- The `pgvector/pgvector:pg16` Docker image ships with the extension pre-built;
  only `CREATE EXTENSION` is needed, no system package install.
- All embedding columns use `VECTOR(768)` to match `nomic-embed-text` output
  dimensions. IVFFlat indexes are added in the individual table migrations.
