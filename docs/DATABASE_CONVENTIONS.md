# Database Conventions

**Stack:** PostgreSQL 16 + PGVector extension
**ORM:** Laravel 12 Eloquent

This document is mandatory reading before writing any migration. PostgreSQL has different conventions from MySQL/MariaDB — do not assume CRM patterns carry over without checking here first.

---

## Core Rules

| Rule | PostgreSQL (this project) | MySQL/MariaDB (CRM) |
|---|---|---|
| JSON columns | `JSONB` always | `JSON` |
| Primary keys | ULID via `$table->ulid('id')->primary()` | ULID same |
| Boolean | `BOOLEAN` | `TINYINT(1)` |
| Auto-increment | `$table->id()` works (bigint) but we use ULIDs | same |
| Enum-style fields | `VARCHAR` with app-level validation | `ENUM` sometimes |
| Full-text | Built-in via `tsvector` | Separate FT index |
| Vectors | `VECTOR(n)` via pgvector extension | Not available |

---

## Naming Conventions

### Tables
- Plural snake_case: `school_users`, `guardian_students`, `calendar_events`
- Pivot tables: alphabetical order of the two models — `guardian_student`, `class_student`
- No prefix (no `inteschool_` prefix)

### Columns
- Snake_case always
- Foreign keys: `{model}_id` (e.g., `school_id`, `teacher_id`, `sender_id`)
- Booleans: `is_{state}` or `has_{thing}` prefix (e.g., `is_active`, `is_primary`, `is_completed`)
- Timestamps: `{action}_at` suffix (e.g., `accepted_at`, `read_at`, `disabled_at`, `deleted_at`)
- Status fields: `status` (VARCHAR, not enum)
- JSON settings: `settings`, `{domain}_settings` (e.g., `notification_settings`, `security_policy`)

### Indexes
- `idx_{table}_{columns}` pattern
- Mandatory on every tenant table:
  ```sql
  idx_{table}_school_created → (school_id, created_at)
  idx_{table}_school_status  → (school_id, status)  -- only if status column exists
  ```
- Query-specific: `idx_{table}_{column}` or `idx_{table}_{col1}_{col2}`
- Unique: `uniq_{table}_{columns}`
- PGVector: `idx_{table}_embedding` (IVFFlat)

### Foreign Key Constraints
- Always named: `fk_{table}_{referenced_table}`
- Always explicit cascade rule — never leave it to default
- Standard school FK:
  ```sql
  CONSTRAINT fk_{table}_school FOREIGN KEY (school_id)
    REFERENCES schools(id) ON DELETE CASCADE
  ```

---

## Laravel Migration Patterns

### Standard tenant table
```php
Schema::create('calendar_events', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('school_id')->constrained('schools')->cascadeOnDelete();
    $table->foreignUlid('calendar_id')->constrained('calendars')->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->timestamp('starts_at');
    $table->timestamp('ends_at');
    $table->boolean('is_all_day')->default(false);
    $table->jsonb('meta')->nullable();          // JSONB — never json()
    $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();

    // Mandatory indexes
    $table->index(['school_id', 'created_at'], 'idx_calendar_events_school_created');
    $table->index(['school_id', 'starts_at'], 'idx_calendar_events_school_starts');
});
```

### JSONB column (settings pattern)
```php
// In migration
$table->jsonb('notification_settings')->default(json_encode([
    'sms_fallback_enabled' => true,
    'sms_timeout_seconds'  => 900,
]));

// In model casts()
protected function casts(): array
{
    return [
        'notification_settings' => 'array',   // JSONB → PHP array
    ];
}

// Reading with default
$timeout = $school->notification_settings['sms_timeout_seconds'] ?? 900;
```

### Nullable foreign key (optional relationship)
```php
$table->foreignUlid('teacher_id')->nullable()->constrained('users')->nullOnDelete();
```

### Self-referencing (parent/thread pattern)
```php
$table->foreignUlid('parent_id')->nullable()->references('id')->on('messages')->nullOnDelete();
```

### Soft deletes
```php
$table->softDeletes();  // Adds deleted_at TIMESTAMP nullable
// Model must use SoftDeletes trait
```

---

## PGVector Conventions

### Setup (migration 001 only — do not repeat)
```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

### Embedding column
```php
// In migration — use raw statement (Blueprint doesn't have vector() by default)
$table->rawColumn('embedding', 'VECTOR(768)')->nullable();
// Or via pgvector/laravel-pgvector package:
$table->vector('embedding', 768)->nullable();
```

### IVFFlat index (approximate nearest neighbour)
```php
// After table creation
DB::statement('CREATE INDEX idx_document_chunks_embedding
    ON document_chunks USING ivfflat (embedding vector_cosine_ops)
    WITH (lists = 100)');
```

### Similarity query pattern
```php
// Always scope by school_id BEFORE the vector search
// The school_id filter dramatically reduces the search space
$chunks = DocumentChunk::query()
    ->where('school_id', $schoolId)
    ->whereNotNull('embedding')
    ->orderByRaw('embedding <=> ?::vector', [$vectorJson])
    ->limit(5)
    ->get();
```

### Embedding dimensions
- Model: `nomic-embed-text` (Ollama) → **768 dimensions** → `VECTOR(768)`
- If switching to OpenAI `text-embedding-3-small` → 1536 dimensions → update column

---

## JSONB Best Practices

### Always use JSONB, never JSON
```php
// CORRECT
$table->jsonb('settings');

// WRONG — no indexing support in PostgreSQL
$table->json('settings');
```

### Default values
```php
// Provide a default so the column is never NULL on creation
$table->jsonb('theme_config')->default(json_encode([
    'mode'   => 'light',
    'accent' => '#1e3a5f',
]));
```

### Querying JSONB in PostgreSQL
```php
// Laravel whereJsonContains works with JSONB
School::whereJsonContains('notification_settings->sms_fallback_enabled', true)->get();

// Raw JSONB operator
School::whereRaw("security_policy->>'tier' = ?", ['security_plus'])->get();

// JSONB path exists
School::whereRaw("security_policy ? 'ip_allowlist'")->get();
```

### Casting in models
```php
// Always cast JSONB to array
protected function casts(): array
{
    return [
        'settings'             => 'array',
        'notification_settings' => 'array',
        'security_policy'      => 'array',
        'theme_config'         => 'array',
        'push_subscription'    => 'array',
    ];
}
```

---

## Multi-Tenancy in Migrations

Every tenant table must:
1. Have `school_id` as ULID FK with `cascadeOnDelete()`
2. Have `idx_{table}_school_created` composite index
3. Use `HasSchoolScope` trait on the model

**Never use raw `company_id`** — this project uses `school_id`. If copying migrations from inteteam_crm, find-replace before running.

---

## Sequential Migration Numbering

Migration docs in `docs/database/migrations/` use sequential numbers:
```
001_schools_and_users.md
002_school_user_pivot.md
003_guardian_student.md
...
```

Actual Laravel migration files use timestamps as normal — the doc numbering is for documentation order only.

---

## Common PostgreSQL Gotchas

| Gotcha | Details |
|---|---|
| `ENUM` types | Avoid — use VARCHAR + app validation. PostgreSQL ENUM requires `ALTER TYPE` to add values, which locks the table. |
| Case sensitivity | PostgreSQL column/table names are case-insensitive but stored lowercase. Never use camelCase in schema. |
| `boolean` vs `tinyint` | PostgreSQL uses real `BOOLEAN`. Laravel migrations `$table->boolean()` works correctly. |
| `text` vs `varchar` | PostgreSQL `TEXT` has no performance penalty vs `VARCHAR`. Use `TEXT` for long content, `VARCHAR(n)` only when you need to enforce length at DB level. |
| Transactions | PostgreSQL DDL (CREATE TABLE, etc.) is transactional. Migrations can be rolled back cleanly. |
| `json_encode` in defaults | Always use `json_encode([...])` for JSONB defaults in migrations — never a raw string. |
| pgvector extension | Must be enabled before any migration that creates a `VECTOR` column. Handled in migration 001. |
