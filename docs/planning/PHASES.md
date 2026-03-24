# Inte-School — Granular Phased Task Breakdown

**Convention:** Tasks follow SOP order within each feature.
**Reference:** `ARCHITECTURE.md` for schema · `REUSE-FROM-CRM.md` for ports · `CLAUDE.md` for standards
**Before any feature:** Complete SOP Step 0 (read CLAUDE.md + relevant docs, answer verification questions)

---

## Phase 1 — Foundation

**Goal:** A working multi-tenant Laravel app with auth, school provisioning, user management, legal docs, and PWA shell. No messaging, no AI, no scheduler. Schools can log in and see role-appropriate dashboards.

**Exit criteria:** A school admin can provision a school, invite staff, enrol students, link parents to children, and all users can log in to their role dashboard after accepting T&Cs.

---

### P1.1 — Project Scaffold & Docker Compose

- [ ] `laravel new inte-school --no-interaction` — fresh Laravel 12 install
- [ ] Write `docker-compose.yml` — services: php-fpm, nginx, postgresql (pgvector/pgvector:pg16), redis, queue-worker, reverb, npm, mailpit, adminer, caddy (prod profile)
- [ ] Write PHP Dockerfile — base php:8.4-fpm-alpine, add `pdo_pgsql`, `pgsql`, `pcntl`, `redis` extensions
- [ ] Write `docker/nginx/default.conf` — Laravel-compatible Nginx config
- [ ] Write `docker/php/php.ini` — memory limit, upload size, execution time
- [ ] Configure `.env.example` — all required keys (DB, Redis, Reverb, VAPID, Ollama, Resend, GCS, SMS, SSO_ENABLED=false)
- [ ] Configure `config/database.php` — PostgreSQL as default connection
- [ ] Configure `config/queue.php` — Redis driver, three queues: high, default, low
- [ ] Configure `config/broadcasting.php` — Reverb driver
- [ ] Configure `config/cache.php` — Redis driver with school-keyed prefix
- [ ] Configure `config/session.php` — Redis driver, secure cookie, same-site strict
- [ ] Add `bootstrap/app.php` middleware aliases: `school`, `root_admin`, `not_disabled`, `feature`
- [ ] Install Composer packages: `inertiajs/inertia-laravel`, `laravel/reverb`, `pragmarx/google2fa`, `league/flysystem-google-cloud-storage`, `resend/resend-php`, `minishlink/web-push`, `barryvdh/laravel-dompdf`
- [ ] Install npm packages: `@inertiajs/react`, `react`, `typescript`, `tailwindcss`, `@radix-ui/*` (full set), `lucide-react`, `laravel-echo`, `pusher-js`, `ziggy-js`, `date-fns`, `react-i18next`, `i18next`
- [ ] Install dev packages: `pestphp/pest` (skip — PHPUnit only), `larastan/larastan`, `laravel/pint`
- [ ] Configure `vite.config.ts` — Laravel Vite plugin, React, TypeScript
- [ ] Configure `tsconfig.json` — strict mode, path aliases (`@/` → `resources/js/`)
- [ ] Configure Tailwind v4 `@theme` in `resources/css/app.css`
- [ ] Set up `resources/js/app.tsx` — Inertia React setup
- [ ] Set up `resources/js/bootstrap.js` — Axios + CSRF (port from CRM)
- [ ] Set up `lang/en.json` — empty i18n file, add first keys as features are built
- [ ] Set up `react-i18next` provider in `app.tsx`
- [ ] Port `resources/js/Components/ui/` — full shadcn/ui set from CRM (verbatim)
- [ ] Port `resources/js/lib/utils.ts` — `cn()` utility from CRM
- [ ] Confirm `docker compose --profile dev up -d` starts all services cleanly
- [ ] Confirm `php artisan migrate` runs (empty migrations) without error
- [ ] Confirm `npm run build` produces assets without TypeScript errors

---

### P1.2 — Database Foundation & Core Traits

- [ ] Create migration 001: enable pgvector extension (`CREATE EXTENSION IF NOT EXISTS vector`)
- [ ] Port `app/Models/Concerns/HasSchoolScope.php` from CRM — rename `company_id` → `school_id`, `CompanyScope` → `SchoolScope`, `scopeForCompany` → `scopeForSchool`
- [ ] Port `app/Models/Scopes/SchoolScope.php` from CRM — same rename
- [ ] Confirm `HasSchoolScope` auto-sets `school_id` from session on creation
- [ ] Confirm `HasSchoolScope` applies global scope filtering on all queries
- [ ] Write unit tests: `HasSchoolScope` filters correctly, auto-sets on create, `scopeForSchool()` bypasses
- [ ] Create `docs/database/migrations/001_pgvector_extension.md`

---

### P1.3 — Auth System

- [ ] Write PHPUnit feature tests (tests/Feature/Auth/LoginTest.php): guest redirect, wrong password 401, correct login, disabled user blocked, 2FA redirect, device registration flow, root admin first-user creation
- [ ] Create migration 002: `users` table — id (ULID), name, email (unique), password, phone, whatsapp_number (nullable), two_factor_secret (nullable), two_factor_recovery_codes (nullable), is_root_admin (boolean default false), disabled_at (nullable), timestamps
- [ ] Create migration 003: `registered_devices` table — full schema per ARCHITECTURE.md
- [ ] Create migration 004: `action_tokens` table — full schema per ARCHITECTURE.md
- [ ] Update `User` model — add `HasUlids`, 2FA columns to fillable/casts, `isRootAdmin()`, `currentSchool()`, relationships; add `disabled_at` cast as datetime
- [ ] Create `RegisteredDevice` model — `HasUlids`, `HasSchoolScope`, push_subscription cast as array
- [ ] Create `ActionToken` model — `HasUlids`, `HasSchoolScope`, expires_at cast as datetime
- [ ] Port `app/Http/Controllers/Auth/LoginController.php` from CRM — swap company → school, keep full 2FA flow
- [ ] Port `app/Http/Controllers/Auth/TwoFactorController.php` from CRM — verbatim
- [ ] Create `app/Http/Controllers/Auth/DeviceRegistrationController.php` — new, handles device fingerprint storage and push_subscription capture
- [ ] Create `app/Http/Controllers/Auth/PasswordResetController.php`
- [ ] Create `app/Http/Middleware/EnsureSchoolContext.php` — port from CRM, rename company → school
- [ ] Create `app/Http/Middleware/EnsureUserIsNotDisabled.php` — port from CRM verbatim
- [ ] Create `app/Http/Middleware/CheckRootAdmin.php` — port from CRM, check `is_root_admin`
- [ ] Create `app/Http/Middleware/HandleInertiaRequests.php` — share `auth.user`, `auth.user.isRootAdmin`, `auth.school`, flash alerts
- [ ] Register middleware aliases in `bootstrap/app.php`
- [ ] Add rate limiting in `bootstrap/app.php`: login (10/min/IP), password-reset (5/min/IP), two-factor (5/min/user)
- [ ] Create `app/Http/Requests/Auth/LoginRequest.php` — array validation syntax
- [ ] Add routes to `routes/web.php`: GET/POST login, logout, forgot-password, reset-password, two-factor, device-registration
- [ ] Create `resources/js/layouts/AuthLayout.tsx` — port from CRM, update branding
- [ ] Create `resources/js/Pages/Auth/Login.tsx` — email + password + remember me
- [ ] Create `resources/js/Pages/Auth/TwoFactor.tsx` — TOTP code entry
- [ ] Create `resources/js/Pages/Auth/DeviceRegistration.tsx` — device name + fingerprint capture
- [ ] Create `resources/js/Pages/Auth/ForgotPassword.tsx`
- [ ] Create `resources/js/Pages/Auth/ResetPassword.tsx`
- [ ] Port `resources/js/hooks/useSessionHeartbeat.ts` from CRM verbatim
- [ ] Run tests — all pass. Pint + PHPStan clean
- [ ] Create `docs/features/auth/README.md` + `architecture.md`
- [ ] Create `docs/database/migrations/002_users.md`, `003_registered_devices.md`, `004_action_tokens.md`

---

### P1.4 — School Model & Tenancy

- [ ] Write PHPUnit tests: school creation, `HasSchoolScope` isolation between two schools, school settings CRUD, root admin bypasses scope
- [ ] Create migration 005: `schools` table — full schema per ARCHITECTURE.md (id, name, slug, custom_domain, logo_path, theme_config JSONB, settings JSONB, notification_settings JSONB, security_policy JSONB, plan, rag_enabled, is_active, timestamps, soft deletes)
- [ ] Create `School` model — `HasUlids`, `SoftDeletes`, JSONB columns cast as array, logo_path accessor via `StorageService`
- [ ] Create `app/Services/SchoolService.php` — `final`, school CRUD, settings update, logo upload, theme update
- [ ] Create `app/Observers/SchoolSettingsObserver.php` — flush `school:{id}:settings`, `school:{id}:features`, `school:{id}:notification_settings` on save
- [ ] Register observer in `AppServiceProvider`
- [ ] Create `app/Http/Middleware/FeatureGate.php` — port from CRM, check school feature flags
- [ ] Register `feature` alias in `bootstrap/app.php`
- [ ] Port `app/Services/StorageService.php` from CRM — GCS-aware URL generation
- [ ] Run tests — all pass
- [ ] Create `docs/database/migrations/005_schools.md`

---

### P1.5 — Root Admin

- [ ] Write PHPUnit tests: first registered user becomes root admin, second user does not, root admin can access `/root-admin/*`, non-root cannot
- [ ] Create `app/Http/Controllers/RootAdmin/DashboardController.php` — cross-school stats skeleton
- [ ] Create `app/Http/Controllers/RootAdmin/SchoolController.php` — list + manage all schools
- [ ] Add routes: `routes/web.php` `/root-admin/*` group guarded by `root_admin` middleware
- [ ] Create `resources/js/layouts/RootAdminLayout.tsx` — root admin navigation
- [ ] Create `resources/js/Pages/RootAdmin/Dashboard.tsx` — school list, platform stats skeleton
- [ ] Create `resources/js/Pages/RootAdmin/Schools/Index.tsx` — all schools table
- [ ] Auto-set `is_root_admin = true` on first user registration (check in `RegisterController` or seeder)
- [ ] Run tests — all pass

---

### P1.6 — School Onboarding Wizard

- [ ] Write PHPUnit tests: wizard creates school with minimum fields (name + slug + admin + logo), pre-fills legal docs from templates, validates slug uniqueness, blocks go-live without published legal docs
- [ ] Create migration 006: `legal_document_templates` table — platform level, not school scoped
- [ ] Create migration 007: `school_legal_documents` table — full schema per ARCHITECTURE.md
- [ ] Create migration 008: `user_legal_acceptances` table — full schema per ARCHITECTURE.md (append-only)
- [ ] Create `LegalDocumentTemplate` model — `HasUlids`, content cast as string
- [ ] Create `SchoolLegalDocument` model — `HasUlids`, `HasSchoolScope`, `SoftDeletes` omitted (legal docs never hard deleted)
- [ ] Create `UserLegalAcceptance` model — `HasUlids`, `HasSchoolScope`, no `update()` or `delete()` — append-only enforced in service
- [ ] Create `app/Services/OnboardingService.php` — wizard step management, school creation, legal doc pre-fill from templates
- [ ] Create `app/Services/LegalDocumentService.php` — publish document (version required), record acceptance (IP + user agent), check if user needs to re-accept
- [ ] Create `app/Http/Middleware/EnsureLegalAcceptance.php` — redirect to acceptance screen if user has not accepted current published version of both documents
- [ ] Register middleware alias `legal` in `bootstrap/app.php`, apply to all school routes
- [ ] Create `app/Http/Controllers/School/OnboardingController.php` — 4-step wizard: (1) school details, (2) logo + theme, (3) legal docs review + edit, (4) first admin account
- [ ] Create `app/Http/Controllers/School/LegalDocumentController.php` — show, edit (admin), publish, acceptance record
- [ ] Add routes: onboarding wizard, legal doc CRUD, acceptance POST
- [ ] Create `resources/js/Pages/School/Onboarding/` — Step1.tsx through Step4.tsx + wizard shell with progress indicator
- [ ] Create `resources/js/Pages/School/Legal/Show.tsx` — rich text display
- [ ] Create `resources/js/Pages/School/Legal/Edit.tsx` — rich text editor (TipTap or similar)
- [ ] Create `resources/js/Pages/Legal/Accept.tsx` — acceptance screen shown before app access
- [ ] Install TipTap rich text editor: `@tiptap/react`, `@tiptap/starter-kit`
- [ ] Add root admin: `resources/js/Pages/RootAdmin/LegalTemplates/` — manage platform default templates
- [ ] Run tests — all pass
- [ ] Create `docs/features/school_onboarding/README.md` + `architecture.md`
- [ ] Create `docs/database/migrations/006_legal_document_templates.md`, `007_school_legal_documents.md`, `008_user_legal_acceptances.md`

---

### P1.7 — Roles & Per-Role Dashboards

- [ ] Write PHPUnit tests: each role redirects to correct dashboard post-login, role cannot access other role routes, root admin can access all
- [ ] Create migration 009: `school_user` pivot — full schema per ARCHITECTURE.md (id, school_id, user_id, role VARCHAR, department_label, invitation_token, invitation_expires_at, accepted_at, invited_by, invited_at, timestamps)
- [ ] Add `school_user` relationship to `User` and `School` models: BelongsToMany with pivot columns
- [ ] Add `User::getRoleInSchool()`, `User::hasRoleInCurrentSchool()`, `User::currentSchoolRole()` methods
- [ ] Create `app/Http/Middleware/RoleMiddleware.php` — gate by role for route groups
- [ ] Register `role` alias in `bootstrap/app.php`
- [ ] Create dashboard controllers: `Admin/DashboardController`, `Teacher/DashboardController`, `Parent/DashboardController`, `Student/DashboardController`, `Support/DashboardController` — all return skeleton Inertia view
- [ ] Add role-based route groups in `routes/web.php`
- [ ] Create `resources/js/layouts/SchoolLayout.tsx` — adapted from CRM CompanyLayout, includes `SchoolNavBar`
- [ ] Create `resources/js/layouts/ParentLayout.tsx` — minimal mobile-first PWA layout
- [ ] Create `resources/js/Components/Organisms/SchoolNavBar.tsx` — adapted from CRM AdminNavBar, role-aware navigation
- [ ] Create skeleton dashboard pages: `Pages/Admin/Dashboard.tsx`, `Pages/Teacher/Dashboard.tsx`, `Pages/Parent/Dashboard.tsx`, `Pages/Student/Dashboard.tsx`, `Pages/Support/Dashboard.tsx`
- [ ] Port `resources/js/hooks/useIsMobile.ts` from CRM verbatim
- [ ] Port `resources/js/hooks/use-toast.ts` from CRM verbatim
- [ ] Run tests — all pass

---

### P1.8 — User Management

- [ ] Write PHPUnit tests: staff invite (both methods), student bulk CSV import, guardian invite code generation, guardian-student linking, sibling support (one guardian → multiple students), divorced parents (two guardians → same student), CSV sanitization (injection characters stripped)
- [ ] Create migration 010: `classes` table — full schema per ARCHITECTURE.md
- [ ] Create migration 011: `class_students` pivot — full schema per ARCHITECTURE.md
- [ ] Create migration 012: `guardian_student` pivot — full schema per ARCHITECTURE.md
- [ ] Create `SchoolClass` model — `HasUlids`, `HasSchoolScope`
- [ ] Create `ClassStudent` model — pivot
- [ ] Create `GuardianStudent` model — `HasUlids`, `HasSchoolScope`, `is_primary` cast boolean
- [ ] Create `app/Services/UserManagementService.php` — staff invite (token + manual), student enrol (single + bulk CSV), guardian invite code generation, guardian-student link, CSV import (with injection sanitization), CSV export template generation
- [ ] Create `app/Jobs/ProcessStudentCsvImportJob.php` — default queue, chunk processing
- [ ] Create `app/Http/Controllers/Admin/StaffController.php` — invite, list, disable
- [ ] Create `app/Http/Controllers/Admin/StudentController.php` — enrol, list, bulk import, export
- [ ] Create `app/Http/Controllers/Admin/GuardianController.php` — invite code generation, list, link to student
- [ ] Create `app/Http/Controllers/Admin/ClassController.php` — CRUD classes, assign teacher, enrol students
- [ ] Add routes to `routes/users.php`
- [ ] Create `resources/js/Pages/Admin/Staff/Index.tsx` — list + invite modal
- [ ] Create `resources/js/Pages/Admin/Staff/Invite.tsx` — two-option invite form
- [ ] Create `resources/js/Pages/Admin/Students/Index.tsx` — list with bulk import button
- [ ] Create `resources/js/Pages/Admin/Students/Import.tsx` — CSV upload + template download
- [ ] Create `resources/js/Pages/Admin/Guardians/Index.tsx` — list + invite code generator
- [ ] Create `resources/js/Pages/Admin/Classes/Index.tsx` + `Show.tsx` — class management
- [ ] Create `resources/js/Pages/Auth/AcceptInvitation.tsx` — guardian self-registration via invite code
- [ ] Run tests — all pass. Verify CSV injection sanitization test specifically
- [ ] Create `docs/features/user_management/README.md` + `architecture.md`
- [ ] Create `docs/database/migrations/010_classes.md`, `011_class_students.md`, `012_guardian_student.md`

---

### P1.9 — School Settings & Security Policy

- [ ] Write PHPUnit tests: school admin updates settings, security tier stored in JSONB, feature flag toggle by root admin, notification settings update
- [ ] Create `app/Http/Controllers/Admin/SettingsController.php` — school settings CRUD (theme, notification_settings, security_policy via JSONB)
- [ ] Create `app/Http/Requests/Admin/UpdateSettingsRequest.php`
- [ ] Add routes to `routes/settings.php`
- [ ] Create `resources/js/Pages/Admin/Settings/General.tsx` — school name, logo, theme colour picker, light/dark toggle
- [ ] Create `resources/js/Pages/Admin/Settings/Notifications.tsx` — SMS fallback toggle (reads `notification_settings` JSONB, no raw timeout UI in MVP)
- [ ] Create `resources/js/Pages/Admin/Settings/Security.tsx` — security tier display (Security+ gated via FeatureGate)
- [ ] Create `resources/js/Pages/Admin/Settings/Legal.tsx` — edit + publish Privacy Policy + T&Cs
- [ ] Run tests — all pass

---

### P1.10 — PWA Shell & Service Worker

- [ ] Create `public/manifest.json` — PWA manifest (name, icons, start_url, display: standalone, theme_color)
- [ ] Create `resources/js/service-worker.ts` — push event handler, notification click handler (consume action token), CSRF token injection on background fetch, basic asset caching
- [ ] Register service worker in `app.tsx` on mount
- [ ] Create `resources/js/hooks/useVapidPush.ts` — request push permission, subscribe browser to VAPID endpoint, POST subscription to `registered_devices`
- [ ] Generate VAPID key pair: `php artisan vapid:generate` (custom command) — writes `VAPID_PUBLIC_KEY` + `VAPID_PRIVATE_KEY` to `.env`
- [ ] Create `app/Console/Commands/GenerateVapidKeys.php` — artisan command
- [ ] Verify PWA installs on Chrome Android + Safari iOS
- [ ] Run Lighthouse PWA audit — score > 90

---

## Phase 2 — Communication Core

**Goal:** Schools can send messages to parents. Parents receive push notifications, can quick-reply. SMS fires after 15 min no-read. Attendance register works. Absence notifications loop with pre-notification awareness.

**Exit criteria:** Teacher marks a student absent → parent receives push notification → taps quick reply → acknowledgment recorded. If no reply in 15 min, SMS fires.

---

### P2.1 — Mail Service (Dual Provider)

- [ ] Write PHPUnit tests: email sends via Resend, falls back to secondary on Resend failure, root admin alert fires on fallback, graceful return (no exception to caller) on both failure
- [ ] Create `app/Services/MailService.php` — `final`, primary (Resend), secondary (Mailgun or Postmark), try primary → catch → switch to secondary → fire root admin alert notification → return gracefully
- [ ] Create `app/Notifications/MailProviderDownNotification.php` — fires to root admin when fallback is triggered
- [ ] Configure `config/mail.php` — two mailers: `resend` + `fallback`
- [ ] Add `MAIL_FALLBACK_DRIVER` to `.env.example`
- [ ] Run tests — fallback path specifically tested with mocked Resend failure
- [ ] Create `docs/features/mail_service/README.md` + `architecture.md`

---

### P2.2 — VAPID Push Notification Service

- [ ] Write PHPUnit tests: push sends to registered device, graceful return if no subscription, graceful return if push service unreachable, encrypted payload verified
- [ ] Create `app/Services/VapidPushService.php` — wraps `minishlink/web-push`, sends encrypted AESGCM payloads, requires `p256dh` + `auth` keys, falls back to generic payload if keys missing
- [ ] Create `app/Jobs/SendPushNotificationJob.php` — high queue, single device, handles failure gracefully (logs, does not throw)
- [ ] Run tests — all pass

---

### P2.3 — Laravel Reverb Setup

- [ ] Configure `config/reverb.php` — app ID, key, secret from `.env`
- [ ] Create `routes/channels.php` — private channel auth: `school.{schoolId}`, `user.{userId}`
- [ ] Configure `laravel-echo` in `resources/js/bootstrap.js` — connect to Reverb WebSocket
- [ ] Test WebSocket connection in dev environment
- [ ] Confirm `reverb` Docker service starts and accepts connections

---

### P2.4 — Two-Way Messaging System

- [ ] Write PHPUnit tests: school sends announcement (targeting individual + class), thread created with Transaction ID, recipient rows created, read receipt tracked on attendance_alert + trip_permission, not tracked on announcement, attachment stored, RAG ticket creation on fallback
- [ ] Create migration 013: `messages` table — full schema per ARCHITECTURE.md
- [ ] Create migration 014: `message_recipients` table — full schema per ARCHITECTURE.md
- [ ] Create migration 015: `message_attachments` table — full schema per ARCHITECTURE.md
- [ ] Create `Message` model — `HasUlids`, `HasSchoolScope`, `requires_read_receipt` cast boolean, relationships (recipients, attachments, thread, parent)
- [ ] Create `MessageRecipient` model — `HasUlids`, `HasSchoolScope`, timestamps cast
- [ ] Create `MessageAttachment` model — `HasUlids`, `HasSchoolScope`
- [ ] Create `MessagePolicy` — `#[UsePolicy]` on `Message` model, role-based send permissions (admin/teacher/support can send; teacher scoped to own classes)
- [ ] Create `app/Services/MessagingService.php` — `final`, create thread (generates Transaction ID = ULID), target resolution (individual/class), recipient row creation, `requires_read_receipt` set by message type, attachment handling via `StorageService`
- [ ] Create `app/Jobs/SendBulkMessageJob.php` — default queue, class-wide targeting fan-out
- [ ] Create `app/Http/Controllers/School/MessageController.php` — send, thread view, mark read, attachment download
- [ ] Add routes to `routes/messaging.php`
- [ ] Create `resources/js/Pages/Admin/Messages/Index.tsx` — inbox/outbox with threads
- [ ] Create `resources/js/Pages/Admin/Messages/Compose.tsx` — message type selector, targeting (individual/class), body, file attach
- [ ] Create `resources/js/Pages/Parent/Messages/Index.tsx` — parent inbox (threads list)
- [ ] Create `resources/js/Pages/Parent/Messages/Thread.tsx` — thread view + quick reply
- [ ] Create `resources/js/Pages/Teacher/Messages/Compose.tsx` — scoped to teacher's own classes
- [ ] Run tests — all pass, Transaction ID deduplication verified
- [ ] Create `docs/features/messaging/README.md` + `architecture.md`
- [ ] Create `docs/database/migrations/013_messages.md`, `014_message_recipients.md`, `015_message_attachments.md`

---

### P2.5 — Notification Cascade (Reverb → VAPID → SMS)

- [ ] Write PHPUnit tests: online user receives via Reverb only (no push), offline user receives push, SMS fires after timeout if no read_at, SMS suppressed if `sms_fallback_enabled = false`, root admin notified on cascade failure
- [ ] Create `app/Services/NotificationService.php` — `final`, checks Reverb presence (via Redis), dispatches push or Reverb, dispatches `PromoteToSmsJob` with delay from `notification_settings.sms_timeout_seconds`
- [ ] Create `app/Services/SmsService.php` — `final`, provider behind interface, graceful fallback if provider fails, root admin alert on failure
- [ ] Create `app/Jobs/PromoteToSmsJob.php` — high queue, dispatched with delay, checks `read_at` before sending, respects `sms_fallback_enabled`
- [ ] Create `app/Jobs/SendAttendanceAlertJob.php` — high queue
- [ ] Create `app/Events/MessageSent.php` — broadcast on `school.{schoolId}` + `user.{userId}` channels
- [ ] Create `app/Listeners/HandleMessageSent.php` — triggers `NotificationService`
- [ ] Update service worker: handle `notificationclick` event, consume action token via background fetch (with CSRF)
- [ ] Run tests — SMS fallback path specifically tested with mocked timeout
- [ ] Create `docs/features/notifications/README.md` + `architecture.md`

---

### P2.6 — Attendance Register

- [ ] Write PHPUnit tests: teacher marks register (present/absent/late), office marks on behalf, hardware API endpoint marks via `nfc_card`, pre-notification suppresses parent alert, attendance stats cached and invalidated on mark, daily aggregate correct
- [ ] Create migration 016: `attendance_registers` table — full schema per ARCHITECTURE.md
- [ ] Create migration 017: `attendance_records` table — full schema per ARCHITECTURE.md
- [ ] Create `AttendanceRegister` model — `HasUlids`, `HasSchoolScope`
- [ ] Create `AttendanceRecord` model — `HasUlids`, `HasSchoolScope`, `marked_via` as VARCHAR
- [ ] Create `AttendancePolicy` — mark permission: teacher (own class) + support + admin
- [ ] Create `app/Services/AttendanceService.php` — `final`, open/get register, mark student (checks `pre_notified`), trigger notification if absent + not pre-notified, aggregate daily stats
- [ ] Create `app/Observers/AttendanceObserver.php` — flush `school:{id}:attendance:{date}` cache on mark, trigger notification cascade
- [ ] Create hardware API endpoint: `POST /api/v1/attendance/mark` — token auth, calls `AttendanceService::mark()` with `marked_via = nfc_card`
- [ ] Create `app/Http/Controllers/Teacher/AttendanceController.php` — open register, mark, view
- [ ] Create `app/Http/Controllers/Admin/AttendanceController.php` — override, view all classes
- [ ] Create `app/Http/Controllers/Api/AttendanceHardwareController.php` — hardware endpoint
- [ ] Add routes to `routes/attendance.php` + `routes/api.php`
- [ ] Create `resources/js/Pages/Teacher/Attendance/Register.tsx` — class list, tap to mark present/absent/late, ordered by name A-Z
- [ ] Create `resources/js/Pages/Parent/Attendance/History.tsx` — child's attendance calendar heatmap + log, percentage, trend — `orderBy('created_at', 'desc')`
- [ ] Run tests — all pass, hardware API auth tested
- [ ] Create `docs/features/attendance/README.md` + `architecture.md`
- [ ] Create `docs/database/migrations/016_attendance_registers.md`, `017_attendance_records.md`
- [ ] Add hardware device token tables + `docs/database/migrations/` entry

---

## Phase 3 — Scheduler & Tasks

**Goal:** Schools have multi-calendar scheduling and a full task system with grouped todos ported from CRM.

**Exit criteria:** Admin creates calendar events (all 4 types), teacher assigns homework to a class, staff tasks have grouped todo templates with cascade deadlines.

---

### P3.1 — Calendar System

- [ ] Write PHPUnit tests: create calendar (4 types), events CRUD, external calendar read-only for parents, cache invalidated on event change, `orderBy starts_at asc` for future events (documented exception to default desc), iCal export placeholder (post-MVP stub)
- [ ] Create migration 018: `calendars` table — full schema per ARCHITECTURE.md
- [ ] Create migration 019: `calendar_events` table — full schema per ARCHITECTURE.md
- [ ] Create `Calendar` model — `HasUlids`, `HasSchoolScope`, `SoftDeletes`, `meta` cast as array
- [ ] Create `CalendarEvent` model — `HasUlids`, `HasSchoolScope`, `SoftDeletes`, timestamps cast
- [ ] Create `CalendarPolicy` — create/edit: admin/teacher; view external: parent/student
- [ ] Create `app/Services/CalendarService.php` — `final`, CRUD events, read from cache (key: `school:{id}:calendar:{cal_id}:{Y}-{m}`), 4 calendar types as string (not enum), department label as string
- [ ] Create `app/Observers/CalendarEventObserver.php` — flush month-window cache on create/update/delete
- [ ] Create `app/Http/Controllers/School/CalendarController.php` + `CalendarEventController.php`
- [ ] Add routes to `routes/calendar.php`
- [ ] Create `resources/js/Pages/Admin/Calendar/Index.tsx` — full calendar view (month/week/day), multi-calendar sidebar
- [ ] Create `resources/js/Pages/Teacher/Calendar/Index.tsx` — teacher view (own + department + external)
- [ ] Create `resources/js/Pages/Parent/Calendar/Index.tsx` — read-only external + holiday calendar
- [ ] Create `resources/js/Pages/Student/Calendar/Index.tsx` — read-only external + holiday + homework due dates
- [ ] Install calendar UI library: `@fullcalendar/react` or `react-big-calendar` — mobile day/week view required
- [ ] Run tests — all pass, cache invalidation verified
- [ ] Create `docs/features/calendar/README.md` + `architecture.md`
- [ ] Create `docs/database/migrations/018_calendars.md`, `019_calendar_events.md`

---

### P3.2 — Task System & Grouped Todos

- [ ] Write PHPUnit tests: staff task CRUD, homework assign to class, action item created from message, grouped todo template apply (cascade deadlines), drag reorder persists, unchecking does NOT reverse deadline, template items marked `is_custom = false`, parent sees homework log ordered desc, parent notified when deadline passes with no submission
- [ ] Create migration 020: `tasks` table — full schema per ARCHITECTURE.md
- [ ] Create migration 021: `task_template_groups` table — port from CRM `todo_template_groups`
- [ ] Create migration 022: `task_templates` table — port from CRM `todo_templates`
- [ ] Create migration 023: `task_items` table — port from CRM `todo_items` + `default_deadline_hours`
- [ ] Create `Task` model — `HasUlids`, `HasSchoolScope`, `SoftDeletes`, polymorphic `taskable`
- [ ] Create `TaskTemplateGroup` model — port from CRM, `HasSchoolScope`, `task_type` scoping
- [ ] Create `TaskTemplate` model — port from CRM, `HasSchoolScope`
- [ ] Create `TaskItem` model — port from CRM, `HasSchoolScope`, `deadline_at` cast datetime
- [ ] Create `TaskPolicy` — staff tasks: admin/teacher/support; homework: teacher (own class only)
- [ ] Create `app/Services/TaskService.php` — `final`, CRUD tasks, apply template group (cascade deadline logic ported from CRM `cascadeDeadline()`), toggle item, reorder items (sort_order), create action item from message Transaction ID
- [ ] Create `app/Jobs/HomeworkDeadlineAlertJob.php` — low queue, scheduled check: `due_at < now()` + no submission flag → notify parent via NotificationService
- [ ] Create `app/Http/Controllers/Teacher/TaskController.php` + `TaskTemplateGroupController.php`
- [ ] Create `app/Http/Controllers/Admin/TaskController.php`
- [ ] Add routes to `routes/tasks.php`
- [ ] Create `resources/js/Pages/Teacher/Tasks/Index.tsx` — task list, `orderBy('created_at', 'desc')`
- [ ] Create `resources/js/Pages/Teacher/Tasks/HomeworkCreate.tsx` — assign to class/student, due date
- [ ] Create `resources/js/Pages/Admin/Tasks/TemplateGroups/Index.tsx` — port CRM settings page pattern, per-group tabs
- [ ] Create `resources/js/Pages/Student/Homework/Index.tsx` — homework due list, `orderBy due_at asc` (documented exception), status display
- [ ] Create `resources/js/Pages/Parent/Homework/Index.tsx` — child's homework log, `orderBy('created_at', 'desc')`
- [ ] Port `resources/js/Components/Organisms/TodoList.tsx` from CRM — dnd-kit sortable, optimistic reorder
- [ ] Run tests — cascade deadline chain specifically tested end-to-end
- [ ] Create `docs/features/tasks/README.md` + `architecture.md`
- [ ] Create `docs/database/migrations/020_tasks.md` through `023_task_items.md`

---

## Phase 4 — Intelligence & Stats

**Goal:** Schools can upload documents, parents can ask questions answered by RAG, schools have a statistics dashboard with opt-in API sharing.

**Exit criteria:** School uploads handbook PDF → processing completes → parent asks "what is the nut allergy policy?" → answer returned. School enables stats API → council key generated → API returns attendance data.

---

### P4.1 — Document Management + RAG Pipeline

- [ ] Write PHPUnit tests: upload valid PDF (stored, queued for processing), upload invalid MIME rejected, `ProcessDocumentJob` chunks + embeds + stores, RAG query returns answer above threshold, RAG query below threshold returns two-choice fallback (contact / create ticket), Ollama unreachable returns graceful fallback (no exception), ticket created from RAG fallback links to MessagingService
- [ ] Create migration 024: `documents` table — full schema per ARCHITECTURE.md
- [ ] Create migration 025: `document_chunks` table — `VECTOR(768)` column + IVFFlat index
- [ ] Create `Document` model — `HasUlids`, `HasSchoolScope`, `SoftDeletes`, `processing_status` VARCHAR
- [ ] Create `DocumentChunk` model — `HasUlids`, `HasSchoolScope`, embedding column
- [ ] Create `DocumentPolicy` — upload: admin + teacher; view: all school users
- [ ] Create `app/Services/DocumentService.php` — `final`, upload to GCS, create DB record, dispatch `ProcessDocumentJob`
- [ ] Create `app/Services/OllamaService.php` — `final`, HTTP client to Ollama API, `embed(text)` → vector, `generate(prompt, chunks)` → answer, graceful fallback on connection failure (returns null, does not throw)
- [ ] Create `app/Services/RagService.php` — `final`, embed query → PGVector cosine search (scoped by school_id first) → threshold check → generate answer or return fallback options
- [ ] Create `app/Jobs/ProcessDocumentJob.php` — default queue, PDF text extraction, chunk (≈500 tokens, overlap), embed each chunk via `OllamaService`, store `document_chunks` rows, update `processing_status`
- [ ] Create `app/Http/Controllers/School/DocumentController.php` — upload, list (`orderBy created_at desc`), delete, RAG query endpoint
- [ ] Add routes to `routes/documents.php`
- [ ] Create `resources/js/Pages/Admin/Documents/Index.tsx` — document list with processing status badges
- [ ] Create `resources/js/Pages/Admin/Documents/Upload.tsx` — file upload, MIME validation feedback
- [ ] Create `resources/js/Pages/Parent/Ask/Index.tsx` — "Ask a question" input, answer display, two-choice fallback UI (contact school / create ticket)
- [ ] Create `resources/js/Pages/Student/Ask/Index.tsx` — same RAG interface for students
- [ ] Toggle: `rag_enabled` on `schools` table gates the entire feature via `FeatureGate` middleware
- [ ] Run tests — Ollama graceful fallback path specifically tested with mocked failure
- [ ] Create `docs/features/documents_rag/README.md` + `architecture.md`
- [ ] Create `docs/database/migrations/024_documents.md`, `025_document_chunks.md`

---

### P4.2 — Statistics Dashboard & API

- [ ] Write PHPUnit tests: statistics aggregate correctly per school, API key generates + hashes + shown once, API endpoint returns correct data for key's permissions, API key from different school rejected, rate limiting 60/min enforced
- [ ] Create migration 026: `school_api_keys` table — full schema per ARCHITECTURE.md
- [ ] Create `SchoolApiKey` model — `HasUlids`, `HasSchoolScope`, `permissions` cast as array
- [ ] Create `app/Services/StatisticsService.php` — `final`, aggregate: attendance rate (by class/school/date range), message engagement, homework completion rate, active users; results cached in Redis `school:{id}:stats:{type}:{period}` TTL 1h
- [ ] Create `app/Jobs/AggregateStatisticsJob.php` — low queue, scheduled daily
- [ ] Create `app/Http/Controllers/Admin/StatisticsController.php` — dashboard data
- [ ] Create `app/Http/Controllers/Admin/ApiKeyController.php` — generate (show raw key once), list (show last_used_at only), revoke
- [ ] Create `app/Http/Controllers/Api/StatsApiController.php` — authenticated by API key hash, rate limited 60/min/key, returns JSON per permissions JSONB
- [ ] Create `app/Http/Middleware/AuthenticateApiKey.php` — hash incoming key, find matching `school_api_keys` record, set school context
- [ ] Add routes to `routes/api.php` under `/api/v1/stats/{school_slug}/`
- [ ] Create `resources/js/Pages/Admin/Statistics/Dashboard.tsx` — attendance rate chart, message engagement, homework completion, `orderBy created_at desc` for recent events
- [ ] Create `resources/js/Pages/Admin/Settings/ApiKeys.tsx` — key management, one-time raw key display, permissions matrix
- [ ] Run tests — rate limiting tested, cross-school key rejection tested
- [ ] Create `docs/features/statistics_api/README.md` + `architecture.md`
- [ ] Create `docs/database/migrations/026_school_api_keys.md`

---

### P4.3 — Feature Requests Log

- [ ] Write PHPUnit tests: school admin submits request (max 2000 chars enforced), root admin sees all requests across all schools ordered desc, school admin sees only own school requests ordered desc
- [ ] Create migration 027: `feature_requests` table — full schema per ARCHITECTURE.md
- [ ] Create `FeatureRequest` model — `HasUlids`, `HasSchoolScope`
- [ ] Create `app/Services/FeatureRequestService.php` — submit (2000 char max), list per school, list all (root admin, bypass scope)
- [ ] Create `app/Http/Controllers/Admin/FeatureRequestController.php` — submit + list for school admin
- [ ] Create `app/Http/Controllers/RootAdmin/FeatureRequestController.php` — cross-school feed, `orderBy created_at desc`
- [ ] Add routes to `routes/settings.php` + root admin routes
- [ ] Create `resources/js/Pages/Admin/Settings/FeatureRequests.tsx` — textarea (max 2000), submission history `orderBy desc`
- [ ] Create `resources/js/Pages/RootAdmin/FeatureRequests/Index.tsx` — cross-school feed, school name badge, `orderBy desc`
- [ ] Run tests — all pass
- [ ] Create `docs/database/migrations/027_feature_requests.md`

---

## Phase 5 — Hardening

**Goal:** Security headers deployed, rate limiting verified across all endpoints, dependency audits clean, performance reviewed, i18n foundation verified.

---

### P5.1 — Security Headers & Config Hardening

- [ ] Add security headers to Nginx config: HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- [ ] Set `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=strict` in production `.env`
- [ ] Verify CSP does not break PWA service worker or Vite assets
- [ ] Run `composer audit` — resolve any high/critical findings
- [ ] Run `npm audit` — resolve any high/critical findings
- [ ] Verify all rate limits are in place: login, password-reset, 2FA, messaging, RAG query, stats API, attendance hardware
- [ ] Verify `school_id` absent from `$fillable` on all 15+ models — automated PHPStan rule if possible
- [ ] Verify all list queries have explicit `orderBy` — grep codebase for `->get()` and `->paginate()` without `orderBy`
- [ ] Run full PHPUnit suite — all tests pass
- [ ] Run Pint — zero violations
- [ ] Run PHPStan level 8 — zero errors
- [ ] Run Lighthouse audit on PWA — performance > 80, PWA > 90, accessibility > 90

---

### P5.2 — i18n Foundation Verification

- [ ] Grep codebase for hardcoded display strings in PHP — replace any found with `__()`
- [ ] Grep frontend for hardcoded UI strings — replace any found with `t()`
- [ ] Verify all `status` and `type` DB values are machine keys (no capitalised display values in DB)
- [ ] Verify `lang/en.json` contains all translation keys used in PHP
- [ ] Verify `react-i18next` `en.json` contains all keys used in frontend
- [ ] Document: adding a new language requires only new locale files — confirm no code changes needed

---

## Phase 6 — Multi-Language (Post-MVP)

**Goal:** Add second language. Foundation already in place.

- [ ] Decide target language(s) with school(s)
- [ ] Create `lang/{locale}.json` — translated system strings
- [ ] Create `resources/js/locales/{locale}.json` — translated UI strings
- [ ] Add language switcher to school settings + user profile
- [ ] Add `locale` to `school_user` pivot or `users` table
- [ ] Configure Carbon locale for date formatting
- [ ] Add `rtl:` Tailwind variant support if RTL language selected
- [ ] Test all notification templates in new locale
- [ ] Test legal document display in new locale (user-generated content remains school's responsibility)
