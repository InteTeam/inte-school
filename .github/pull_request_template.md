## Summary

<!-- What does this PR do and why? -->

## Type

- [ ] Feature
- [ ] Bug fix
- [ ] Refactor
- [ ] Docs / tests only

## Checklist

### PHP
- [ ] `declare(strict_types=1);` on all new files
- [ ] `HasSchoolScope` + `HasUlids` on all new tenant models
- [ ] `#[UsePolicy]` on model, not controller
- [ ] Validation uses array syntax (not pipe strings)
- [ ] Services are `final`, under 250 lines
- [ ] Controllers under 150 lines
- [ ] Operations > 5s are queued
- [ ] `school_id` not in `$fillable`
- [ ] All user-facing lists use `orderBy('created_at', 'desc')`

### Security
- [ ] New endpoints have rate limits in `bootstrap/app.php`
- [ ] File uploads validated by MIME type (not extension)
- [ ] New tokens/keys stored hashed in DB
- [ ] Student data features default to most private option

### Database
- [ ] JSON columns use `JSONB`
- [ ] `idx_school_created` index present on all new tenant tables
- [ ] Foreign key cascade rules explicit

### Tests
- [ ] PHPUnit, `final class`, `use RefreshDatabase`
- [ ] Multi-tenant isolation tested
- [ ] Wrong-role (403) tested
- [ ] Graceful fallback path tested

### Frontend
- [ ] All props interfaces defined — no `any`
- [ ] No components created that already exist in `ui/` or `Atoms/`

### Principles
- [ ] Graceful fallback implemented and tested
- [ ] Configurable values in JSONB, not hardcoded
- [ ] Feature docs complete (`docs/features/{name}/`)

## CI
<!-- CI must be green before merge. -->
