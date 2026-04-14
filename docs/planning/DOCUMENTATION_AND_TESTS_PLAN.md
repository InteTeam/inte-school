# Documentation & Tests — Gap Closure Plan

> Created: 2026-04-14
> Status: Planning

This plan sequences the work needed to bring documentation and test coverage in line with the SOP before Phase 5 (Hardening) begins.

---

## Principle

Documentation is written **per feature**, not in bulk at the end. However, Phases 1–4.3 shipped code ahead of docs. This plan closes that gap in priority order — features most likely to be demoed or changed first get documented first.

---

## Priority Order

| Priority | Feature | Why First |
|---|---|---|
| P0 | Messaging | Core selling point, most complex notification cascade |
| P1 | Attendance | Second demo feature, hardware API integration |
| P2 | Calendar & Events | Visible in every role dashboard |
| P3 | Tasks / Homework | Teacher + parent daily workflow |
| P4 | Documents & RAG | AI differentiator, pgvector specifics |
| P5 | Statistics & API | External API surface, needs security review |
| P6 | Root Admin | Internal tooling, lower demo priority |
| P7 | User Management | Stable, well-tested, lower churn |
| P8 | Settings & Legal | Configuration, rarely changes |
| P9 | Onboarding | 4-step wizard, done and stable |

---

## Per-Feature Documentation Deliverables

Each feature produces three files (per SOP):

```
docs/features/{feature}/
├── README.md              # Overview, user stories, acceptance criteria, graceful fallback, flexible values
├── architecture.md        # Models, services, jobs, observers, controllers, routes, frontend, caching, fallback
└── COMPONENT_INVENTORY.md # Reuse inventory (existing vs new React components)
```

### Checklist per feature doc

- [ ] Business requirements and user stories with acceptance criteria
- [ ] Graceful fallback path documented
- [ ] Flexible data values identified (JSONB settings, defaults)
- [ ] Database tables listed with key columns, indexes, FK cascades
- [ ] Service responsibilities and public methods
- [ ] Queue jobs with queue lane assignment and retry policy
- [ ] Observer cache invalidation triggers
- [ ] Controller actions with middleware stack
- [ ] Route definitions (method, URI, name, middleware)
- [ ] Frontend pages with props interfaces
- [ ] Component inventory (reused shadcn/ui, new atoms/molecules/organisms)
- [ ] Security considerations (rate limiting, file validation, tenant isolation)
- [ ] Feature design checklist ticked

---

## Migration Documentation

28 migrations need docs in `docs/database/migrations/`. These can be batched by domain:

| Batch | Migrations | Tables |
|---|---|---|
| Core | 001–006, 010 | pgvector, users, devices, tokens, schools, school_user |
| Legal | 007–009 | legal templates, school legal docs, user acceptances |
| Academic | 011–013 | classes, class_students, guardian_student |
| Messaging | 014–016 | messages, message_recipients, message_attachments |
| Attendance | 017–019 | attendance_registers, attendance_records, hardware_device_tokens |
| Calendar | 020–021 | calendars, calendar_events |
| Tasks | 022–025 | tasks, task_template_groups, task_templates, task_items |
| Documents | 026–027 | documents, document_chunks (pgvector) |
| Admin | 028–029 | school_api_keys, feature_requests |

Each migration doc includes: purpose, full schema, indexes, FK cascades, JSONB structure (if any), model config (traits, casts, fillable).

---

## Component Registry

`docs/COMPONENT_REUSE_CHECKLIST.md` needs population from actual codebase:

1. Scan `resources/js/components/Atoms/` — list all with props
2. Scan `resources/js/components/Molecules/` — list all with props
3. Scan `resources/js/components/Organisms/` — list all with props
4. Scan `resources/js/components/ui/` — confirm shadcn/ui inventory matches
5. Per-feature sections — list which components each feature uses

---

## Test Coverage Gaps

### Current state: 19 test files (16 feature, 2 unit, 1 base)

### Coverage audit checklist per feature

Each feature test must cover (per SOP):

- [ ] Guest → redirected to login (401/302)
- [ ] Wrong role → 403 Forbidden
- [ ] Correct role → success (200/201)
- [ ] Invalid data → validation errors
- [ ] Multi-tenant isolation → school A cannot see school B data
- [ ] Graceful fallback → degraded path works when primary fails

### Features needing test review

| Feature | Test File Exists | Gaps to Check |
|---|---|---|
| Auth | `LoginTest.php` | Password reset flow, invitation acceptance, device registration |
| Messaging | `MessagingTest.php`, `NotificationCascadeTest.php` | Attachment validation, bulk send limits, thread replies |
| Attendance | `AttendanceTest.php` | Hardware API auth, CSV export, register locking |
| Calendar | `CalendarTest.php` | Recurring events, cross-role visibility, observer cache |
| Tasks | `TaskTest.php` | Template groups, deadline alerts job, parent view |
| Documents | `DocumentRagTest.php` | Upload MIME validation, chunk generation, similarity search accuracy |
| Statistics | `StatisticsApiTest.php` | API key auth, rate limiting, cross-tenant isolation |
| Settings | `SchoolSettingsTest.php` | Each settings section, observer invalidation |
| User Mgmt | `UserManagementTest.php` | CSV import (injection protection), invitation flow, disable/enable |
| Root Admin | `RootAdminTest.php` | School CRUD, feature request lifecycle |
| Onboarding | `OnboardingTest.php` | Step sequence enforcement, partial completion recovery |
| Roles | `RoleAccessTest.php` | Every role × every route matrix |
| Multi-tenancy | `HasSchoolScopeTest.php` (unit) | Scope bypass for root admin |

### New tests to add

| Test | Priority | Reason |
|---|---|---|
| `AcceptInvitationTest.php` | P0 | Token expiry, single-use, role assignment |
| `DeviceRegistrationTest.php` | P1 | Cookie signing, device trust |
| `CsvImportSecurityTest.php` | P0 | CSV injection protection (=, -, +, @ prefixes) |
| `FileUploadValidationTest.php` | P0 | MIME type enforcement, SVG rejection, size limits |
| `ApiKeyLifecycleTest.php` | P1 | Creation, hashing, rotation, scoping, expiry |
| `RateLimitingTest.php` | P1 | Login, password reset, 2FA, API endpoints |
| `LegalAcceptanceFlowTest.php` | P2 | Middleware enforcement, version tracking |

---

## Phase 2–6 Granular Breakdown

`docs/planning/PHASES.md` has Phase 1 in detail. Phases 2–6 need the same granular task-checklist format. This should be done as a separate planning pass once the feature docs exist (they inform the task breakdown).

---

## Execution Order

```
Week 1:  Feature docs — Messaging, Attendance (P0–P1)
         Migration docs — Messaging batch (014–016), Attendance batch (017–019)
         New tests — CsvImportSecurityTest, FileUploadValidationTest, AcceptInvitationTest

Week 2:  Feature docs — Calendar, Tasks (P2–P3)
         Migration docs — Calendar batch (020–021), Tasks batch (022–025)
         New tests — ApiKeyLifecycleTest, RateLimitingTest

Week 3:  Feature docs — Documents & RAG, Statistics (P4–P5)
         Migration docs — Documents batch (026–027), Admin batch (028–029)
         New tests — DeviceRegistrationTest, LegalAcceptanceFlowTest

Week 4:  Feature docs — Root Admin, User Mgmt, Settings, Onboarding (P6–P9)
         Migration docs — Core batch (001–006, 010), Legal batch (007–009), Academic batch (011–013)
         Component registry population
         Phase 2–6 granular task breakdown

Week 5:  Test coverage audit — run each feature's checklist, fill gaps
         Review and cross-reference all docs
```

---

## Definition of Done

- [ ] Every implemented feature has README + architecture + COMPONENT_INVENTORY in `docs/features/`
- [ ] Every migration has a doc in `docs/database/migrations/`
- [ ] `docs/COMPONENT_REUSE_CHECKLIST.md` populated with actual components
- [ ] All 7 new test files written and passing
- [ ] Existing test files reviewed against coverage checklist
- [ ] `docs/planning/PHASES.md` updated with Phase 2–6 granular tasks
- [ ] All docs cross-referenced and consistent with actual codebase
