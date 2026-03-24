# Documentation Standards

All features must be documented using the templates below. Docs are written during implementation, not after.

---

## Feature README Template

**File:** `docs/features/{feature_name}/README.md`

```markdown
# {Feature Name}

## Overview
One paragraph: what this feature does and why it exists.

## Affected Roles
- root_admin: [what they can do]
- admin: [what they can do]
- teacher: [if applicable]
- parent: [if applicable]
- student: [if applicable]
- support: [if applicable]

## User Stories
- As a [role], I can [action] so that [outcome]
- As a [role], I can [action] so that [outcome]

## Acceptance Criteria
- [ ] [Specific, testable criterion]
- [ ] [Specific, testable criterion]

## Business Requirements
- [Requirement 1]
- [Requirement 2]

## Graceful Fallback
**Primary path:** [describe it]
**Failure mode:** [what can fail]
**Fallback:** [what happens when it does]
**Who is notified:** [root admin / school admin / user / silent]

## Flexible Values (JSONB Settings)
| Setting | Default | Location | UI exposed? |
|---|---|---|---|
| [setting_name] | [default] | schools.{column} JSONB | [MVP / post-MVP] |

## Feature Design Checklist
[Copy from docs/FEATURE_DESIGN_CHECKLIST.md and tick as you go]
```

---

## Architecture Doc Template

**File:** `docs/features/{feature_name}/architecture.md`

```markdown
# {Feature Name} — Architecture

## Database Changes
[List tables created or modified — reference migration doc]

## Models
| Model | New/Existing | Traits | Notes |
|---|---|---|---|
| {ModelName} | New | HasSchoolScope, HasUlids | [purpose] |

## Services
| Service | Responsibility | Queue? |
|---|---|---|
| {ServiceName} | [what it does] | [yes/no — which lane] |

## Jobs
| Job | Queue Lane | Trigger | Fallback |
|---|---|---|---|
| {JobName} | high/default/low | [what triggers it] | [what happens on failure] |

## Observers
| Observer | Model | Cache keys flushed |
|---|---|---|
| {ObserverName} | {Model} | [cache key patterns] |

## Controllers & Routes
| Method | Route | Controller | Policy check |
|---|---|---|---|
| GET | /school/{slug}/[path] | {Controller}@index | can('viewAny', Model) |

## Frontend
| Page | Layout | Route |
|---|---|---|
| {PageName}.tsx | SchoolLayout | /[path] |

## Notification / Queue Flow
[Describe the async flow if applicable — what fires what, in what order]

## Caching Strategy
[What is cached, TTL, which Observer clears it, cache key pattern]

## Fallback Path Detail
[Step-by-step: primary fails at step X → fallback does Y → user/admin sees Z]

## GDPR Considerations
[What data is stored, who can access it, how is child data protected]
```

---

## Migration Doc Template

**File:** `docs/database/migrations/{NNN}_{table_name}.md`

Sequential numbering — find the last number in `docs/database/migrations/` and increment by 1.

```markdown
# Migration {NNN} — {table_name}

## Purpose
[Why this table/column exists]

## Table: {table_name}

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| id | ULID | No | — | Primary key |
| school_id | ULID | No | — | FK schools, CASCADE DELETE |
| [column] | [type] | [Yes/No] | [value] | [notes] |
| created_at | TIMESTAMP | No | — | |
| updated_at | TIMESTAMP | No | — | |
| deleted_at | TIMESTAMP | Yes | NULL | Soft delete |

## Indexes
```sql
-- Mandatory on all tenant tables
CREATE INDEX idx_school_created ON {table_name} (school_id, created_at);

-- Add if status column exists
CREATE INDEX idx_school_status ON {table_name} (school_id, status);

-- Add any query-specific indexes
CREATE INDEX idx_{table}_{column} ON {table_name} ({column});
```

## Foreign Keys
```sql
-- Always explicit cascade rule
ALTER TABLE {table_name}
  ADD CONSTRAINT fk_{table}_school
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE;
```

## JSONB Columns
[List any JSONB columns with their expected structure]
```json
{
  "key": "value description",
  "nested": { "key": "value" }
}
```

## Model Configuration
```php
// Traits
use HasSchoolScope, HasUlids;
// If soft deletes:
use SoftDeletes;

// Fillable
protected $fillable = ['school_id', ...];

// Casts (JSONB → array)
protected function casts(): array
{
    return [
        'settings' => 'array',  // JSONB columns always cast as array
    ];
}
```

## PGVector (if applicable)
```sql
-- Embedding column
ALTER TABLE {table_name} ADD COLUMN embedding VECTOR(768);

-- IVFFlat approximate nearest neighbour index
CREATE INDEX idx_{table}_embedding ON {table_name}
  USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100);
```

## Relationships
- belongs to: [Model via FK]
- has many: [Model]

## Performance Notes
[Expected query volume, any special considerations]
```

---

## Component Doc Template

**File:** `docs/features/{feature_name}/components.md`

```markdown
# {Feature Name} — Component Inventory

## Existing Components to REUSE

### shadcn/ui (`resources/js/Components/ui/`)
- [ ] Button — `@/Components/ui/button`
- [ ] Input — `@/Components/ui/input`
- [ ] [others used]

### Atoms (`resources/js/Components/Atoms/`)
- [ ] [ComponentName] — `@/Components/Atoms/{Name}`

### Molecules (`resources/js/Components/Molecules/`)
- [ ] [ComponentName] — `@/Components/Molecules/{Name}`

### Organisms (`resources/js/Components/Organisms/`)
- [ ] [ComponentName] — `@/Components/Organisms/{Name}`

## New Components to CREATE

### Pages
| File | Location | Layout | Purpose |
|---|---|---|---|
| {Name}.tsx | `Pages/{Module}/` | SchoolLayout | [purpose] |

### Components
| File | Location | Type | Props interface | Why can't reuse existing |
|---|---|---|---|---|
| {Name}.tsx | `Components/{Atoms|Molecules|Organisms}/` | [type] | [interface] | [reason] |

## Import Map
```typescript
// Reused (existing)
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

// New
import { NewComponent } from '@/Components/Molecules/NewComponent';
```
```
