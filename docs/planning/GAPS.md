# Documentation & Test Gaps — Task List

> Created: 2026-04-14
> Last updated: 2026-04-14
> Sequencing: see `DOCUMENTATION_AND_TESTS_PLAN.md` for priority order and weekly breakdown

---

## Feature Documentation

Each feature needs: `docs/features/{name}/README.md`, `architecture.md`, `COMPONENT_INVENTORY.md`

- [x] **Messaging** — compose, threads, attachments, bulk send, notification cascade, SMS fallback
- [x] **Attendance** — registers, records, hardware API, CSV export, absence alerts
- [x] **Calendar & Events** — calendars, events, recurring, cross-role visibility, observer cache
- [x] **Tasks / Homework** — create, templates, template groups, deadline alerts, parent/student view
- [x] **Documents & RAG** — upload, chunking, pgvector search, AI Q&A (parent/student Ask)
- [x] **Statistics & API** — dashboard analytics, REST API, API key auth, rate limiting
- [x] **Root Admin** — school CRUD, feature request lifecycle, legal template management
- [x] **User Management** — staff invite, student enrolment, CSV import, guardian linking, disable/enable
- [x] **Settings** — general, notifications, security, API keys, legal, feature requests
- [x] **Onboarding** — 4-step wizard, school creation, initial config

---

## Migration Documentation

Each migration needs a doc in `docs/database/migrations/NNN_{name}.md`

### Core (001–006, 010)
- [x] 001 — pgvector extension (exists, just needs review)
- [x] 003 — registered_devices
- [x] 004 — action_tokens
- [x] 005 — schools
- [x] 006 — school foreign keys
- [x] 010 — school_user pivot

### Legal (007–009)
- [x] 007 — legal_document_templates
- [x] 008 — school_legal_documents
- [x] 009 — user_legal_acceptances

### Academic (011–013)
- [x] 011 — classes
- [x] 012 — class_students
- [x] 013 — guardian_student

### Messaging (014–016)
- [x] 014 — messages
- [x] 015 — message_recipients
- [x] 016 — message_attachments

### Attendance (017–019)
- [x] 017 — attendance_registers
- [x] 018 — attendance_records
- [x] 019 — hardware_device_tokens

### Calendar (020–021)
- [x] 020 — calendars
- [x] 021 — calendar_events

### Tasks (022–025)
- [x] 022 — tasks
- [x] 023 — task_template_groups
- [x] 024 — task_templates
- [x] 025 — task_items

### Documents (026–027)
- [x] 026 — documents
- [x] 027 — document_chunks (pgvector)

### Admin (028–029)
- [x] 028 — school_api_keys
- [x] 029 — feature_requests

---

## Component Registry

- [x] Populate `docs/COMPONENT_REUSE_CHECKLIST.md` Atoms section from `resources/js/components/Atoms/`
- [x] Populate Molecules section from `resources/js/components/Molecules/`
- [x] Populate Organisms section from `resources/js/components/Organisms/`
- [x] Verify shadcn/ui inventory matches `resources/js/components/ui/`
- [x] Fill per-feature component sections (which components each feature uses)

---

## New Tests to Write

- [x] `AcceptInvitationTest.php` — token expiry, single-use, role assignment, password validation
- [x] `DeviceRegistrationTest.php` — fingerprint upsert, push subscription validation, disabled user
- [x] `CsvImportSecurityTest.php` — CSV injection protection (=, -, +, @, \t, \r prefixes), HTTP flow
- [x] `FileUploadValidationTest.php` — MIME type enforcement, SVG rejection, size limits, role gates
- [x] `ApiKeyLifecycleTest.php` — creation, hashing, rotation, scoping, expiry, cross-tenant
- [x] `RateLimitingTest.php` — API throttle (60/min), hardware throttle, rate limit headers
- [x] `LegalAcceptanceFlowTest.php` — middleware enforcement, version tracking, root admin bypass

---

## Existing Test Coverage Audit

Review each test file against the SOP coverage checklist (guest redirect, wrong role 403, valid data, invalid data, multi-tenant isolation):

- [ ] `Auth/LoginTest.php`
- [ ] `Messaging/MessagingTest.php`
- [ ] `Messaging/NotificationCascadeTest.php`
- [ ] `Attendance/AttendanceTest.php`
- [ ] `Calendar/CalendarTest.php`
- [ ] `Tasks/TaskTest.php`
- [ ] `Documents/DocumentRagTest.php`
- [ ] `Statistics/StatisticsApiTest.php`
- [ ] `Settings/SchoolSettingsTest.php`
- [ ] `UserManagement/UserManagementTest.php`
- [ ] `RootAdmin/RootAdminTest.php`
- [ ] `Onboarding/OnboardingTest.php`
- [ ] `Roles/RoleAccessTest.php`
- [ ] `Models/HasSchoolScopeTest.php`
- [ ] `Mail/MailServiceTest.php`
- [ ] `Push/VapidPushServiceTest.php`
- [ ] `FeatureRequests/FeatureRequestTest.php`
- [ ] `School/SchoolModelTest.php`

---

## Phase Planning

- [ ] Expand `docs/planning/PHASES.md` with granular Phase 2 task breakdown (Messaging, Attendance, Notification Cascade)
- [ ] Add Phase 3 granular tasks (Calendar, Tasks/Homework)
- [ ] Add Phase 4 granular tasks (Documents/RAG, Statistics/API, Settings)
- [ ] Add Phase 5 granular tasks (Hardening — the next phase)
- [ ] Add Phase 6 granular tasks (Post-MVP backlog)
