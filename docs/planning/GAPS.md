# Documentation & Test Gaps — Task List

> Created: 2026-04-14
> Sequencing: see `DOCUMENTATION_AND_TESTS_PLAN.md` for priority order and weekly breakdown

---

## Feature Documentation

Each feature needs: `docs/features/{name}/README.md`, `architecture.md`, `COMPONENT_INVENTORY.md`

- [ ] **Messaging** — compose, threads, attachments, bulk send, notification cascade, SMS fallback
- [ ] **Attendance** — registers, records, hardware API, CSV export, absence alerts
- [ ] **Calendar & Events** — calendars, events, recurring, cross-role visibility, observer cache
- [ ] **Tasks / Homework** — create, templates, template groups, deadline alerts, parent/student view
- [ ] **Documents & RAG** — upload, chunking, pgvector search, AI Q&A (parent/student Ask)
- [ ] **Statistics & API** — dashboard analytics, REST API, API key auth, rate limiting
- [ ] **Root Admin** — school CRUD, feature request lifecycle, legal template management
- [ ] **User Management** — staff invite, student enrolment, CSV import, guardian linking, disable/enable
- [ ] **Settings** — general, notifications, security, API keys, legal, feature requests
- [ ] **Onboarding** — 4-step wizard, school creation, initial config

---

## Migration Documentation

Each migration needs a doc in `docs/database/migrations/NNN_{name}.md`

### Core (001–006, 010)
- [ ] 001 — pgvector extension (exists, just needs review)
- [ ] 003 — registered_devices
- [ ] 004 — action_tokens
- [ ] 005 — schools
- [ ] 006 — school foreign keys
- [ ] 010 — school_user pivot

### Legal (007–009)
- [ ] 007 — legal_document_templates
- [ ] 008 — school_legal_documents
- [ ] 009 — user_legal_acceptances

### Academic (011–013)
- [ ] 011 — classes
- [ ] 012 — class_students
- [ ] 013 — guardian_student

### Messaging (014–016)
- [ ] 014 — messages
- [ ] 015 — message_recipients
- [ ] 016 — message_attachments

### Attendance (017–019)
- [ ] 017 — attendance_registers
- [ ] 018 — attendance_records
- [ ] 019 — hardware_device_tokens

### Calendar (020–021)
- [ ] 020 — calendars
- [ ] 021 — calendar_events

### Tasks (022–025)
- [ ] 022 — tasks
- [ ] 023 — task_template_groups
- [ ] 024 — task_templates
- [ ] 025 — task_items

### Documents (026–027)
- [ ] 026 — documents
- [ ] 027 — document_chunks (pgvector)

### Admin (028–029)
- [ ] 028 — school_api_keys
- [ ] 029 — feature_requests

---

## Component Registry

- [ ] Populate `docs/COMPONENT_REUSE_CHECKLIST.md` Atoms section from `resources/js/components/Atoms/`
- [ ] Populate Molecules section from `resources/js/components/Molecules/`
- [ ] Populate Organisms section from `resources/js/components/Organisms/`
- [ ] Verify shadcn/ui inventory matches `resources/js/components/ui/`
- [ ] Fill per-feature component sections (which components each feature uses)

---

## New Tests to Write

- [ ] `AcceptInvitationTest.php` — token expiry, single-use, role assignment
- [ ] `DeviceRegistrationTest.php` — cookie signing, device trust
- [ ] `CsvImportSecurityTest.php` — CSV injection protection (=, -, +, @ prefixes)
- [ ] `FileUploadValidationTest.php` — MIME type enforcement, SVG rejection, size limits
- [ ] `ApiKeyLifecycleTest.php` — creation, hashing, rotation, scoping, expiry
- [ ] `RateLimitingTest.php` — login, password reset, 2FA, API endpoints
- [ ] `LegalAcceptanceFlowTest.php` — middleware enforcement, version tracking

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
