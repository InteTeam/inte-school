# Workflow Enforcement

## Why This Document Exists

On inteteam_crm, skipping Step 0 of the SOP caused 5+ critical compliance failures that required complete rewrites of finished features. Each failure cost more time than the original feature took to build.

The pattern was always the same:
1. Feature looked done and worked in the browser
2. Code review or audit revealed it violated a core convention (wrong auth pattern, pipe validation, missing multi-tenant scope, Pest instead of PHPUnit, etc.)
3. Rewrite required

This document exists to make that pattern impossible on inte-school.

---

## The Three Violations That Cause Rewrites

### 1. Wrong authorization pattern
**Violation:** `$this->authorize()` in controllers, or `#[UsePolicy]` on controllers.
**Correct:** `#[UsePolicy]` on the **model**. Controller uses `auth()->user()->can() + abort(403)`.
**Why it matters:** Inconsistent auth is a security surface. One wrong pattern means one unprotected endpoint.

### 2. Missing `HasSchoolScope`
**Violation:** A model that belongs to a school but doesn't use `HasSchoolScope`.
**Correct:** Every tenant-scoped model uses `HasSchoolScope` + `HasUlids`.
**Why it matters:** A model without the scope can leak data across schools. This is a GDPR violation.

### 3. Missing graceful fallback
**Violation:** A feature that throws an exception or shows a blank screen when its primary path fails.
**Correct:** Every feature has a documented and tested fallback — silent where possible, notification to admin where not.
**Why it matters:** Schools depend on this platform for attendance and safeguarding communications. Failure must degrade gracefully, never silently break.

---

## Enforcement Mechanism

The SOP Step 0 verification questions exist to catch violations before they're written. If you cannot answer them from memory, you have not read the guidelines.

**The questions (must answer before writing anything):**
1. Where does `#[UsePolicy]` go? → **Model only**
2. What syntax for validation rules? → **Array syntax only**
3. What test framework? → **PHPUnit only — `final class extends TestCase`**
4. What is the multi-tenancy trait? → **`HasSchoolScope`**
5. What JSON column type in PostgreSQL? → **`JSONB` always**
6. What is the graceful fallback for this feature? → **Must be defined before writing**
7. Which values belong in JSONB settings vs hardcoded? → **Must be identified before writing**

**If you skipped Step 0 and are reading this mid-implementation:** stop, read the guidelines, then continue. The cost of pausing now is lower than the cost of a rewrite later.

---

## The Four Additional Violations Worth Calling Out

### 4. `school_id` in `$fillable`
**Violation:** A model with `school_id` in `$fillable`.
**Correct:** `school_id` is never in `$fillable`. `HasSchoolScope` sets it from session context.
**Why it matters:** A crafted API request can write data into another school's tenant. GDPR violation.

### 5. List query without explicit sort order
**Violation:** `Model::query()->where(...)->get()` with no `orderBy`.
**Correct:** `->orderBy('created_at', 'desc')` on every user-facing list query.
**Why it matters:** Implicit ordering is undefined and inconsistent across PostgreSQL versions and query plans. Explicit descending is the project standard.

### 6. File upload validated by extension only
**Violation:** `'file' => ['mimes:pdf']` relying on file extension.
**Correct:** MIME type validated server-side. `mimes:` in Laravel validates the actual MIME, but explicitly test this. No SVG allowed.
**Why it matters:** A malicious file with a valid extension bypasses extension-only checks.

### 7. Student data feature not defaulting to private
**Violation:** A feature involving student data that defaults to open/sharing.
**Correct:** UK Children's Code requires privacy-by-default for all features accessible by under-18s.
**Why it matters:** Legal requirement, not a guideline.

---

## Non-Negotiable Rules (no exceptions)

| Rule | Rationale |
|---|---|
| `declare(strict_types=1)` on every PHP file | Type safety — prevents silent type coercion bugs |
| `HasSchoolScope` on every tenant model | GDPR — cross-tenant data leak is a regulatory violation |
| `school_id` never in `$fillable` | Mass assignment — prevents cross-tenant write via crafted request |
| All lists ordered `created_at DESC` | Consistency — undefined order is a bug waiting to happen |
| MIME validation on all uploads | Security — extension alone is bypassable |
| Student features default to private | UK Children's Code — legal requirement |
| `composer audit` + `npm audit` on dependency changes | Supply chain — high-severity vulnerabilities block commits |
| `HasUlids` on every model | Security — sequential IDs are enumerable |
| PHPUnit only, never Pest | Consistency — one test framework, one pattern |
| Array syntax for validation, never pipe strings | Readability + IDE support for complex rules |
| JSONB not JSON in PostgreSQL | Performance — JSONB is indexed, JSON is not |
| Services `final`, <250 lines | Maintainability — prevents service classes becoming god objects |
| Controllers <150 lines | Single responsibility — anything longer belongs in a service |
| Graceful fallback on every feature | Reliability — schools use this for safeguarding communications |
| Flexible data model, constrained UI | Avoids hardcoding values that will need changing in 3 months |

---

## What "Done" Means

A feature is not done until:
- [ ] All PHPUnit tests pass (including fallback path tests)
- [ ] Pint reports no violations
- [ ] PHPStan reports no errors
- [ ] Frontend builds without TypeScript errors
- [ ] Feature README has all acceptance criteria ticked
- [ ] Graceful fallback is implemented, not just planned
- [ ] Migration doc exists in `docs/database/migrations/`
- [ ] Component doc exists in `docs/features/{name}/components.md`

A feature that works in the browser but fails this checklist is not done.
