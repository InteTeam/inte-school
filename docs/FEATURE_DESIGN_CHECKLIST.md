# Feature Design Checklist

**How to use:** Copy the entire checklist section into your `docs/features/{name}/README.md` and tick items as you complete them — during planning and implementation, not after.

---

## Checklist Template (copy into feature README)

### Planning Compliance
- [ ] Step 0 complete — all guidelines read before writing anything
- [ ] Business requirements documented (overview, user stories, acceptance criteria)
- [ ] All affected roles identified (root_admin / admin / teacher / parent / student / support)
- [ ] Graceful fallback defined — primary path failure mode documented
- [ ] Flexible values identified — settings that belong in JSONB rather than hardcoded
- [ ] Multi-tenant isolation considered — what prevents school A seeing school B data?

### Database
- [ ] Schema cross-referenced with `docs/planning/ARCHITECTURE.md` — does the table already exist?
- [ ] Migration doc created in `docs/database/migrations/{next_number}_{name}.md`
- [ ] All JSON columns use `JSONB` (not `JSON`)
- [ ] Mandatory index present: `idx_school_created (school_id, created_at)`
- [ ] `idx_school_status` present if status column exists
- [ ] All foreign keys have explicit CASCADE rule documented
- [ ] ULID primary key (`HasUlids` trait on model)
- [ ] PGVector column uses `VECTOR(768)` with IVFFlat index (if embedding column)

### Backend
- [ ] `declare(strict_types=1)` on every new PHP file
- [ ] `HasSchoolScope` applied to every new tenant-scoped model
- [ ] `HasUlids` applied to every new model
- [ ] `#[UsePolicy]` on model (not controller)
- [ ] Controller uses `auth()->user()->can() + abort(403)` (not `$this->authorize()`)
- [ ] Validation rules use array syntax (not pipe strings)
- [ ] Service is `final class`, <250 lines
- [ ] Controller is <150 lines
- [ ] Operations >5s are queued — correct queue lane assigned (high/default/low)
- [ ] Observer created for cache invalidation (if caching involved)
- [ ] JSON columns cast as `array` in model `casts()` (not `json`)
- [ ] File upload routes use `Route::match(['PUT', 'POST'], ...)`

### Tests (PHPUnit only)
- [ ] Test file: `final class extends TestCase`, `use RefreshDatabase`
- [ ] Guest access tested (redirect/401)
- [ ] Wrong role tested (403)
- [ ] Correct role tested (success)
- [ ] Multi-tenant isolation tested (school A cannot access school B's data)
- [ ] Valid input → expected outcome tested
- [ ] Invalid input → validation errors tested
- [ ] Business logic edge cases tested
- [ ] **Graceful fallback path tested** (mock primary failure → fallback fires)
- [ ] All tests pass before marking complete

### Internationalisation
- [ ] All system strings in PHP use `__('key')` — no hardcoded display strings
- [ ] All status/type values stored in DB as machine keys (`present` not `"Present"`)
- [ ] Frontend strings use `t('key')` via react-i18next — no hardcoded UI text

### Frontend
- [ ] Component inventory done — existing components checked before planning new ones
- [ ] `docs/features/{name}/components.md` created with reuse vs new analysis
- [ ] All props interfaces defined — no `any`
- [ ] Correct layout used (`AuthLayout` / `SchoolLayout` / `ParentLayout`)
- [ ] File upload forms use `router.post()` (not `router.put()`)
- [ ] Flash format: `->with(['alert' => '...', 'type' => 'success'])`
- [ ] TypeScript build passes with no errors
- [ ] Mobile view checked (PWA-first — always check on small screen)

### Security
- [ ] Sort order: all lists use `orderBy('created_at', 'desc')` — or explicitly documented alternative
- [ ] Mass assignment: `school_id` not in `$fillable` on any new model
- [ ] Rate limiting: any new endpoint has a rate limit defined in `bootstrap/app.php`
- [ ] File uploads: MIME type validated server-side (not extension). No SVG. PDF processed in queue
- [ ] CSV import/export: fields sanitized against CSV injection (`=`, `-`, `+`, `@` prefixes)
- [ ] Student data: feature defaults to most private option (UK Children's Code)
- [ ] API keys / tokens: stored hashed, shown once, scoped, expiry defined
- [ ] If touches auth: rate limiting + lockout behaviour documented
- [ ] If uses service worker: CSRF token attached to non-GET background requests
- [ ] Security reference consulted: `docs/SECURITY.md`

### Principles
- [ ] Graceful fallback **implemented** (not just planned)
- [ ] Graceful fallback **tested** (failure path covered in tests)
- [ ] Configurable values stored in JSONB settings with defaults (not hardcoded)
- [ ] No new enums for type fields that could reasonably grow — use VARCHAR strings
- [ ] GDPR: child data access scoped correctly (guardian_student pivot respected)

### Documentation
- [ ] Feature README complete — all acceptance criteria ticked
- [ ] `architecture.md` complete — all decisions recorded
- [ ] `components.md` complete — reuse vs new documented
- [ ] Migration doc complete in `docs/database/migrations/`
- [ ] Any new reusable patterns extracted to `CLAUDE.md` or `docs/architecture/`
- [ ] Any new reusable components added to `docs/COMPONENT_REUSE_CHECKLIST.md`

### Final Gate
- [ ] `php artisan test` — all tests pass
- [ ] `pint --dirty` — no violations
- [ ] `phpstan analyse --memory-limit=512M` — no errors
- [ ] `npm run build` — no TypeScript errors
- [ ] Conventional commit message written

---

## Common Failure Points (learn from inteteam_crm)

| What went wrong | Root cause | How to avoid |
|---|---|---|
| Wrong auth pattern throughout feature | Didn't read Step 0 guidelines | Answer: "where does `#[UsePolicy]` go?" before writing |
| Pest tests in a PHPUnit project | Assumed test framework without checking | Answer: "what test framework?" before writing |
| Missing `HasSchoolScope` on new model | Model created from artisan, trait not added | Checklist item: always add trait immediately after `make:model` |
| JSON column not JSONB | Muscle memory from MySQL projects | Checklist item: every JSON column type is JSONB |
| Hardcoded timeout value in job | Seemed simple at the time | Checklist item: all configurable values go in JSONB settings |
| No fallback when Ollama unreachable | RAG assumed always available | Checklist item: fallback must be tested, not just planned |
