# Inte-School — Architecture Document

**Stack:** Laravel 12 / PHP 8.4 · React 19 / TypeScript / Inertia.js v2 · PostgreSQL 16 + PGVector · Redis 7 · Laravel Reverb · Docker Compose
**Reference:** PRD v1.1 · REUSE-FROM-CRM.md

---

## 1. System Overview

```
                        ┌─────────────────────────────────────┐
                        │         Nginx Proxy Manager          │
                        │  *.school.inte.team / custom domain  │
                        └──────────────┬──────────────────────┘
                                       │
              ┌────────────────────────┼────────────────────────┐
              │                        │                        │
     ┌────────▼────────┐    ┌─────────▼────────┐    ┌─────────▼────────┐
     │  School Stack A  │    │  School Stack B  │    │  School Stack N  │
     │  Docker Compose  │    │  Docker Compose  │    │  Docker Compose  │
     └────────┬─────────┘    └──────────────────┘    └──────────────────┘
              │
     ┌────────▼─────────────────────────────────────────────┐
     │  Single School Stack (Docker Compose)                 │
     │                                                       │
     │  php-fpm · nginx · postgresql · redis                 │
     │  queue-worker · reverb · npm(dev) · mailpit(dev)      │
     │  caddy(prod)                                          │
     └───────────────────────────────────────────────────────┘
```

**Staging:** Dell R550 via Proxmox — one stack per school for testing before commitment
**Production:** UK-dedicated server — live tenant stacks
**Warm backup:** Dell R550 after production handover
**Provisioning:** inte-panel (Proxmox + Cloudflare + NPM automation)
**Media/backup:** GCS (UK/EU region, DPA required)

---

## 2. Docker Compose Services

| Service | Image | Purpose | Profile |
|---|---|---|---|
| `php-fpm` | php:8.4-fpm-alpine (custom) | Laravel application | both |
| `nginx` | nginx:1.27-alpine | Web server (internal) | both |
| `postgresql` | pgvector/pgvector:pg16 | Database + PGVector extension | both |
| `redis` | redis:7-alpine | Sessions, cache, queues, Reverb pub/sub | both |
| `queue-worker` | php:8.4 (custom) | Horizon — processes `high,default,low` | both |
| `reverb` | php:8.4 (custom) | WebSocket server | both |
| `npm` | node:22-alpine | Vite dev server | dev |
| `mailpit` | axllent/mailpit | Email testing | dev |
| `adminer` | adminer:latest | PostgreSQL admin UI (replaces phpMyAdmin) | dev |
| `caddy` | caddy:2-alpine | Reverse proxy + auto SSL | prod |

**PHP Dockerfile changes from CRM:**
- Swap `pdo_mysql` → `pdo_pgsql` + `pgsql`
- Add `pgvector` PHP extension support
- Keep all other extensions identical to CRM

**Environment variables (key additions over CRM):**
```
DB_CONNECTION=pgsql
DB_PORT=5432
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
OLLAMA_HOST=http://ollama:11434   # or external URL
RESEND_API_KEY=
MAIL_FALLBACK_DRIVER=mailgun      # or postmark
SSO_ENABLED=false
GCS_BUCKET=
GCS_KEY_FILE=
SMS_PROVIDER=
SMS_FALLBACK_ENABLED=true
```

---

## 3. Database Schema

> All tables use ULID primary keys (`HasUlids` trait). All tenant-scoped tables have `school_id` with `HasSchoolScope`. JSON columns use `JSONB`. Mandatory indexes: `idx_school_created` on every tenant table.

### 3.1 Core Tenancy

**`schools`**
```
id               ULID PK
name             VARCHAR
slug             VARCHAR UNIQUE          -- subdomain
custom_domain    VARCHAR UNIQUE NULLABLE
logo_path        VARCHAR NULLABLE
theme_config     JSONB                   -- colours, light/dark, accent
settings         JSONB                   -- general school settings
notification_settings JSONB             -- sms_fallback_enabled, sms_timeout_seconds (default 900)
security_policy  JSONB                   -- tier, ip_allowlist, token_lifetime
plan             VARCHAR                 -- starter/standard/pro/enterprise
rag_enabled      BOOLEAN DEFAULT false   -- RAG Q&A toggle per school
is_active        BOOLEAN DEFAULT true
created_at, updated_at, deleted_at
```

**`users`**
```
id                          ULID PK
name                        VARCHAR
email                       VARCHAR UNIQUE
password                    VARCHAR
phone                       VARCHAR NULLABLE
whatsapp_number             VARCHAR NULLABLE    -- shown only if WhatsApp enabled (post-MVP)
two_factor_secret           VARCHAR NULLABLE
two_factor_recovery_codes   TEXT NULLABLE
is_root_admin               BOOLEAN DEFAULT false   -- platform-level, first registered user
disabled_at                 TIMESTAMP NULLABLE
created_at, updated_at
```

**`school_user`** (pivot — links all roles to schools)
```
id                   ULID PK
school_id            ULID FK schools
user_id              ULID FK users
role                 VARCHAR     -- admin, teacher, support, student, parent
department_label     VARCHAR NULLABLE
invitation_token     VARCHAR NULLABLE
invitation_expires_at TIMESTAMP NULLABLE
accepted_at          TIMESTAMP NULLABLE
invited_by           ULID FK users NULLABLE
invited_at           TIMESTAMP NULLABLE
created_at, updated_at
INDEX idx_school_user (school_id, user_id)
INDEX idx_school_role (school_id, role)
```

**`guardian_student`** (parent ↔ child many-to-many)
```
id              ULID PK
school_id       ULID FK schools
guardian_id     ULID FK users
student_id      ULID FK users
is_primary      BOOLEAN DEFAULT true    -- primary guardian gets notifications first
created_at, updated_at
UNIQUE (school_id, guardian_id, student_id)
INDEX idx_school_student (school_id, student_id)
```

**`registered_devices`**
```
id                   ULID PK
school_id            ULID FK schools
user_id              ULID FK users
device_name          VARCHAR NULLABLE
device_fingerprint   VARCHAR
push_subscription    JSONB NULLABLE     -- VAPID endpoint + keys
last_seen_at         TIMESTAMP
trusted_at           TIMESTAMP
created_at, updated_at
INDEX idx_user_device (user_id, school_id)
```

**`action_tokens`** (quick reply in push notifications — 30-min lifetime)
```
id             ULID PK
school_id      ULID FK schools
message_id     ULID FK messages
recipient_id   ULID FK users
token          VARCHAR UNIQUE
action_type    VARCHAR    -- acknowledge, confirm_absence, trip_consent
expires_at     TIMESTAMP
used_at        TIMESTAMP NULLABLE
created_at
INDEX idx_token (token)
```

---

### 3.2 Classes & Curriculum

**`classes`**
```
id           ULID PK
school_id    ULID FK schools
name         VARCHAR        -- e.g. "Year 1A", "P3"
year_group   VARCHAR
teacher_id   ULID FK users NULLABLE
created_at, updated_at, deleted_at
INDEX idx_school_created
```

**`class_students`** (pivot)
```
class_id      ULID FK classes
student_id    ULID FK users
school_id     ULID FK schools
enrolled_at   TIMESTAMP
left_at       TIMESTAMP NULLABLE
PRIMARY KEY (class_id, student_id)
INDEX idx_school_student (school_id, student_id)
```

---

### 3.3 Messaging

**`messages`**
```
id                    ULID PK    -- this IS the Transaction ID
school_id             ULID FK schools
thread_id             ULID       -- groups a conversation (set to own id if root message)
parent_id             ULID FK messages NULLABLE    -- for threaded replies
type                  VARCHAR    -- announcement, attendance_alert, trip_permission, query_ticket
subject               VARCHAR NULLABLE
body                  TEXT
sender_id             ULID FK users
target_type           VARCHAR    -- individual, class
target_id             ULID NULLABLE
requires_read_receipt BOOLEAN DEFAULT false
forwarded_to_dept     VARCHAR NULLABLE    -- for RAG-fallback ticket routing
created_at, updated_at
INDEX idx_school_thread (school_id, thread_id)
INDEX idx_school_created
```

**`message_recipients`**
```
id             ULID PK
school_id      ULID FK schools
message_id     ULID FK messages
recipient_id   ULID FK users
delivered_at   TIMESTAMP NULLABLE
read_at        TIMESTAMP NULLABLE
replied_at     TIMESTAMP NULLABLE
sms_sent_at    TIMESTAMP NULLABLE
created_at, updated_at
INDEX idx_message_recipient (message_id, recipient_id)
INDEX idx_school_recipient (school_id, recipient_id)
```

**`message_attachments`**
```
id           ULID PK
school_id    ULID FK schools
message_id   ULID FK messages
file_path    VARCHAR
file_name    VARCHAR
file_size    INTEGER
mime_type    VARCHAR
created_at
```

---

### 3.4 Attendance

**`attendance_registers`** (one per class per day/period)
```
id              ULID PK
school_id       ULID FK schools
class_id        ULID FK classes
teacher_id      ULID FK users
register_date   DATE
period          VARCHAR NULLABLE    -- null = daily, or "morning", "period_1", etc.
created_at, updated_at
UNIQUE (school_id, class_id, register_date, period)
INDEX idx_school_created
```

**`attendance_records`** (individual student marks)
```
id              ULID PK
school_id       ULID FK schools
register_id     ULID FK attendance_registers
student_id      ULID FK users
status          VARCHAR    -- present, absent, late
marked_by       ULID FK users
marked_via      VARCHAR    -- manual, nfc_card, nfc_phone, api
pre_notified    BOOLEAN DEFAULT false    -- parent pre-notified absence
notes           VARCHAR NULLABLE
created_at, updated_at
INDEX idx_school_student_date (school_id, student_id)
INDEX idx_register (register_id)
```

---

### 3.5 Calendars & Scheduler

**`calendars`**
```
id                ULID PK
school_id         ULID FK schools
name              VARCHAR
type              VARCHAR    -- internal, external, department, holiday (flexible string)
department_label  VARCHAR NULLABLE
color             VARCHAR NULLABLE
is_public         BOOLEAN DEFAULT false    -- external calendars visible to parents
created_at, updated_at, deleted_at
INDEX idx_school_created
```

**`calendar_events`**
```
id            ULID PK
school_id     ULID FK schools
calendar_id   ULID FK calendars
title         VARCHAR
description   TEXT NULLABLE
starts_at     TIMESTAMP
ends_at       TIMESTAMP
all_day       BOOLEAN DEFAULT false
location      VARCHAR NULLABLE
meta          JSONB NULLABLE
created_by    ULID FK users
created_at, updated_at, deleted_at
INDEX idx_school_calendar (school_id, calendar_id)
INDEX idx_school_starts (school_id, starts_at)
```

---

### 3.6 Tasks & Todos

**`tasks`**
```
id                ULID PK
school_id         ULID FK schools
type              VARCHAR    -- staff_task, homework, action_item
title             VARCHAR
description       TEXT NULLABLE
status            VARCHAR    -- todo, in_progress, done, cancelled
priority          VARCHAR NULLABLE    -- low, medium, high, urgent
assignee_id       ULID FK users NULLABLE
assigned_by_id    ULID FK users NULLABLE
department_label  VARCHAR NULLABLE
class_id          ULID FK classes NULLABLE    -- for homework assignments
due_at            TIMESTAMP NULLABLE
source_message_id ULID FK messages NULLABLE    -- action items from messaging
created_at, updated_at, deleted_at
INDEX idx_school_created
INDEX idx_school_assignee (school_id, assignee_id)
INDEX idx_school_status (school_id, status)
```

**`task_template_groups`** (reuse from CRM)
```
id                ULID PK
school_id         ULID FK schools
name              VARCHAR
department_label  VARCHAR NULLABLE
task_type         VARCHAR    -- staff (homework doesn't use groups)
created_at, updated_at
INDEX idx_school_created
```

**`task_templates`** (reuse from CRM)
```
id           ULID PK
school_id    ULID FK schools
group_id     ULID FK task_template_groups NULLABLE
name         VARCHAR
sort_order   INTEGER DEFAULT 0
created_at, updated_at
```

**`task_items`** (checklist items — reuse from CRM)
```
id                      ULID PK
school_id               ULID FK schools
task_id                 ULID FK tasks
template_id             ULID FK task_templates NULLABLE
group_id                ULID FK task_template_groups NULLABLE
title                   VARCHAR
is_completed            BOOLEAN DEFAULT false
is_custom               BOOLEAN DEFAULT true
sort_order              INTEGER DEFAULT 0
deadline_at             TIMESTAMP NULLABLE
default_deadline_hours  INTEGER NULLABLE
completed_at            TIMESTAMP NULLABLE
created_at, updated_at
INDEX idx_task (task_id)
```

---

### 3.7 Documents & RAG

**`documents`**
```
id                  ULID PK
school_id           ULID FK schools
name                VARCHAR
file_path           VARCHAR
mime_type           VARCHAR
file_size           INTEGER
uploaded_by         ULID FK users
is_parent_facing    BOOLEAN DEFAULT true
is_staff_facing     BOOLEAN DEFAULT true
processing_status   VARCHAR    -- pending, processing, indexed, failed
created_at, updated_at, deleted_at
INDEX idx_school_created
```

**`document_chunks`** (PGVector table)
```
id              ULID PK
school_id       ULID FK schools
document_id     ULID FK documents
chunk_index     INTEGER
content         TEXT
embedding       VECTOR(768)    -- nomic-embed-text output dimension
created_at
INDEX idx_document (document_id)
-- IVFFlat index for ANN search:
CREATE INDEX idx_embedding ON document_chunks
  USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100);
```

---

### 3.8 Legal Documents

**`legal_document_templates`** (platform level — root admin managed, not school scoped)
```
id            ULID PK
type          VARCHAR    -- privacy_policy, terms_conditions
name          VARCHAR    -- e.g. "UK School Privacy Policy Template v1"
content       TEXT       -- rich text HTML — default starting point for schools
is_active     BOOLEAN DEFAULT true
created_at, updated_at
```

**`school_legal_documents`**
```
id             ULID PK
school_id      ULID FK schools CASCADE
type           VARCHAR    -- privacy_policy, terms_conditions
content        TEXT       -- rich text HTML, editable by school admin
version        VARCHAR    -- e.g. "1.0", "1.1", "2.0"
is_published   BOOLEAN DEFAULT false
published_at   TIMESTAMP NULLABLE
published_by   ULID FK users NULLABLE
created_by     ULID FK users
created_at, updated_at
INDEX idx_school_legal_school_type (school_id, type)
```

**`user_legal_acceptances`** (audit trail — append only, never deleted)
```
id                ULID PK
school_id         ULID FK schools CASCADE
user_id           ULID FK users CASCADE
document_id       ULID FK school_legal_documents CASCADE
document_type     VARCHAR    -- privacy_policy, terms_conditions
document_version  VARCHAR    -- snapshot of version at time of acceptance
accepted_at       TIMESTAMP
ip_address        VARCHAR
user_agent        TEXT
INDEX idx_legal_accept_user (school_id, user_id)
INDEX idx_legal_accept_document (document_id)
```

### Legal Document Flow
```
Root admin creates/updates platform templates (legal_document_templates)
        │
        ▼
New school provisioned via onboarding wizard
→ wizard pre-fills school_legal_documents from active template for each type
→ school admin edits content in rich text editor
→ school admin publishes (is_published = true, version = "1.0", published_at = now)
        │
        ▼
User first login (or new version published since last acceptance)
→ shown Privacy Policy + T&Cs before accessing any school content
→ must accept both to continue
→ user_legal_acceptances row created (with IP + user agent for audit)
        │
        ▼
School admin updates document → new version string required → re-publishes
→ all users flagged for re-acceptance on next login
→ notification sent to all school users: "Our policies have been updated"
```

### 3.10 Statistics & API

**`school_api_keys`**
```
id            ULID PK
school_id     ULID FK schools
name          VARCHAR        -- e.g. "Council Reporting Key"
key_hash      VARCHAR UNIQUE -- hashed API key
permissions   JSONB          -- which stats are exposed
last_used_at  TIMESTAMP NULLABLE
created_at, updated_at, deleted_at
```

**`feature_requests`**
```
id              ULID PK
school_id       ULID FK schools
submitted_by    ULID FK users
body            TEXT           -- max 2000 chars (validated in FormRequest)
created_at
INDEX idx_school_created
```

---

## 4. Multi-Tenancy Model

**Trait:** `HasSchoolScope` (ported from CRM's `HasCompanyScope`)
- Auto-applies global scope filtering by `school_id` on all tenant models
- Auto-sets `school_id` on model creation from session context
- Provides `scopeForSchool()` to bypass when needed (root admin queries)

**Middleware:** `EnsureSchoolContext`
- Ensures user has school context before accessing protected routes
- Sets session school from user's first accepted school if not set
- Verifies user still belongs to session school
- Redirects to school creation if user has no schools

**Root admin:** `users.is_root_admin = true` (first registered user)
- `User::isRootAdmin()` checks this flag
- Shared via `HandleInertiaRequests::share()` as `auth.user.isRootAdmin`
- Bypasses `HasSchoolScope` for cross-school queries

---

## 5. Authentication & Security

### Login flow
```
POST /login
  → Validate email + password
  → Check disabled_at
  → If 2FA enabled → redirect to /two-factor
  → Check registered_devices for current device fingerprint
  → If unrecognised device → device registration flow
  → Set session school_id from user's first accepted school
  → Redirect to role-appropriate dashboard
```

### Security tiers (stored in `schools.security_policy` JSONB)
```json
{
  "tier": "standard",
  "token_lifetime_seconds": 28800,
  "ip_allowlist_enabled": false,
  "trusted_ips": [],
  "unknown_ip_lifetime_seconds": 3600
}
```
- Tier 2 (Security+) gated via `FeatureGate` middleware — root admin enables per school
- Default: tier 1, 8h rolling session

### Action tokens (quick reply)
- Generated per message per recipient on send
- Stored in `action_tokens`, 30-min expiry
- Single-use (`used_at` set on first use)
- Tied to `registered_devices` — unregistered device cannot consume

---

## 6. Service Map

| Service | Responsibility | Notes |
|---|---|---|
| `SchoolService` | School CRUD, settings, theme, onboarding wizard | |
| `UserManagementService` | Invite staff, enrol students, link guardians | CSV import here |
| `AuthService` | Login, 2FA, device registration, token lifecycle | |
| `MessagingService` | Create thread, send, target (individual/class) | Transaction ID = message ULID |
| `NotificationService` | Orchestrate Reverb → VAPID → SMS cascade | Reads `notification_settings` JSONB |
| `VapidPushService` | Web Push delivery via `minishlink/web-push` | No Firebase dependency |
| `SmsService` | SMS dispatch with graceful fallback | Provider behind interface |
| `MailService` | Dual provider email — Resend primary, fallback secondary | Fires root admin alert on switch |
| `AttendanceService` | Mark register, aggregate stats, trigger alerts | Feeds `AttendanceObserver` |
| `CalendarService` | CRUD events, iCal (post-MVP) | Cache via `CalendarEventObserver` |
| `TaskService` | CRUD tasks, grouped todos, cascade deadlines | Port `cascadeDeadline()` from CRM |
| `DocumentService` | Upload → chunk → queue embedding | Stores chunks in `document_chunks` |
| `OllamaService` | HTTP client for Ollama API (embed + generate) | Graceful fallback to API provider |
| `RagService` | Retrieve chunks via PGVector + generate answer | Returns answer or triggers ticket |
| `StatisticsService` | Aggregate school stats, cache, API serialisation | |
| `StorageService` | GCS wrapper — reuse from CRM | |
| `FeatureRequestService` | Submit, list per school, root admin cross-school feed | |

**All services:** `final class`, constructor injection, <250 lines. Controllers <150 lines.

---

## 7. Queue & Horizon Architecture

### Priority lanes

**`high`** — time-sensitive
- `SendPushNotificationJob`
- `PromoteToSmsJob` (dispatched with 15-min delay)
- `SendAttendanceAlertJob`
- `SendAbsenceReminderJob` (homework deadline missed)

**`default`** — standard async
- `SendEmailJob`
- `ProcessDocumentJob` (chunk → embed → store in PGVector)
- `SendBulkMessageJob` (class-wide targeting)
- `SendActionTokenJob` (generate + attach to push payload)

**`low`** — deferrable
- `AggregateStatisticsJob`
- `ExportCsvJob` (student export)
- `GenerateAttendanceReportJob`

### Failed job policy
1. Retry 3× with exponential backoff on all queues
2. On final failure → root admin notification fires (always, non-negotiable)
3. SMS graceful fallback: read from `notification_settings.sms_fallback_enabled` — if `false`, failed push logs but does not promote to SMS

### Horizon config
- Queues processed: `high,default,low`
- Dashboard: root admin only (MVP)
- Worker restart: `docker compose up -d --build queue-worker`

---

## 8. Notification Cascade

```
School sends message
        │
        ▼
message stored — Transaction ID (ULID) assigned
message_recipients rows created
action_token generated (30-min TTL)
        │
        ▼
NotificationService: is Reverb WebSocket active for recipient?
   ├── YES → broadcast on private channel
   │          mark message_recipients.delivered_at
   │          skip push
   └── NO  → VapidPushService::send()
                    │
                    ▼
             Service Worker intercepts:
             ├── App open → post to UI, suppress OS banner
             └── App closed → show OS push notification
                    │
                    ▼
             Parent taps → action_token consumed → read_at set
                    │
                    ▼
PromoteToSmsJob (dispatched at send time, delay = school sms_timeout_seconds)
   └── On execution: check message_recipients.read_at
       ├── read_at IS SET → cancel, do nothing
       └── read_at NULL + sms_fallback_enabled → SmsService::send()
                                                  set sms_sent_at
```

**Read receipt rule:** `requires_read_receipt` on message type
- `attendance_alert`, `trip_permission` → `true`
- `announcement`, `query_ticket` → `false` (reply is the signal)

---

## 9. Redis Caching Strategy

```
school:{id}:settings                    TTL 24h   → SchoolSettingsObserver
school:{id}:features                    TTL 1h    → SchoolSettingsObserver
school:{id}:notification_settings       TTL 24h   → SchoolSettingsObserver
school:{id}:calendar:{cal_id}:{Y}-{M}  TTL 1h    → CalendarEventObserver
school:{id}:attendance:{date}           TTL 24h   → AttendanceObserver
user:{id}:school_context                TTL session → on school switch / logout
```

**Observers (same pattern as CRM's `StorefrontCacheObserver`):**
- `CalendarEventObserver` — flushes month window on create/update/delete
- `SchoolSettingsObserver` — flushes settings + features + notification cache
- `AttendanceObserver` — flushes daily stats, triggers notification cascade

**Not cached in Redis:** document embeddings (PGVector), message threads (PostgreSQL + Reverb), auth tokens (session-backed)

---

## 10. RAG Pipeline

### Document ingestion (async via `ProcessDocumentJob`)
```
Upload → DocumentService::store()
       → file saved to GCS
       → document record created (status: pending)
       → ProcessDocumentJob dispatched (default queue)
              │
              ▼
       PDF → text extraction (pdftotext or similar)
       → chunk into ~500 token segments with overlap
       → OllamaService::embed(chunk) → vector(768)
       → document_chunks rows inserted with embedding
       → document.processing_status = indexed
```

### Query flow
```
Parent submits question
       │
       ▼
RagService::query(school_id, question)
  → OllamaService::embed(question) → query vector
  → PGVector cosine similarity search on document_chunks
    WHERE school_id = ? ORDER BY embedding <=> query_vector LIMIT 5
  → if top similarity < threshold → "no confident answer"
       ├── Option 1: contact school (link to messaging)
       └── Option 2: create query_ticket → MessagingService
                     ticket routed to department via forwarded_to_dept
  → else → OllamaService::generate(question, chunks) → answer
```

### Graceful fallback
- Ollama unreachable → log error → return "no confident answer" UI (never crash)
- Falls through to the two-choice fallback above

---

## 11. Frontend Architecture (PWA)

### Structure (mirrors CRM, adapted for school context)
```
resources/js/
├── app.tsx                    -- Inertia setup
├── Pages/
│   ├── Auth/                  -- Login, 2FA, device registration
│   ├── RootAdmin/             -- Cross-school management
│   ├── Admin/                 -- School admin dashboard + settings
│   ├── Teacher/               -- Teacher dashboard, register, tasks, calendar
│   ├── Parent/                -- Parent PWA: messages, homework log, attendance
│   ├── Student/               -- Student dashboard: homework, calendar, timetable
│   └── Support/               -- Support staff dashboard
├── Components/
│   ├── Atoms/                 -- Button, Input, Badge (port from CRM)
│   ├── Molecules/
│   ├── Organisms/
│   │   ├── SchoolNavBar.tsx   -- Adapted from CRM AdminNavBar
│   │   └── ...
│   └── ui/                    -- shadcn/ui (port from CRM verbatim)
├── layouts/
│   ├── AuthLayout.tsx         -- Port from CRM
│   ├── SchoolLayout.tsx       -- Adapted from CRM CompanyLayout
│   └── ParentLayout.tsx       -- Minimal PWA layout for parents
├── hooks/
│   ├── useSessionHeartbeat.ts -- Port from CRM
│   ├── useVapidPush.ts        -- Adapted from CRM usePushNotifications
│   ├── useIsMobile.ts         -- Port from CRM
│   └── use-toast.ts           -- Port from CRM
└── service-worker.ts          -- VAPID push, offline, background sync
```

### PWA Service Worker responsibilities
- Intercept push events — show OS notification or post to open app
- Consume action tokens on notification tap (quick reply)
- Cache static assets for offline shell
- Background sync for queued replies when offline

### Role-based routing
- Login → `User.role` → redirect to role dashboard
- Middleware `EnsureSchoolContext` guards all school routes
- `CheckRootAdmin` guards `/root-admin/*`

---

## 12. External APIs

### Statistics API (opt-in per school)
```
GET /api/v1/stats/{school_slug}/attendance
GET /api/v1/stats/{school_slug}/engagement
GET /api/v1/stats/{school_slug}/overview
Authorization: Bearer {school_api_key}
```
- Key verified against `school_api_keys.key_hash`
- Permissions checked from `school_api_keys.permissions` JSONB
- Rate limited per key

### Attendance Hardware API (NFC device endpoint)
```
POST /api/v1/attendance/mark
{
  "device_token": "...",
  "card_id": "...",
  "school_id": "...",
  "timestamp": "..."
}
```
- `marked_via`: `nfc_card` or `nfc_phone`
- Same `AttendanceService::mark()` method used by manual UI
- Device token authenticated per school (pre-configured on hardware)

---

## 13. GDPR & Data Compliance

- All data: UK or EU region only (GCS bucket regional config enforced)
- GCS DPA: required before production
- Child data access: scoped to verified guardians + authorised school staff only (`guardian_student` pivot enforces this)
- Right to erasure: `deleted_at` soft deletes + scheduled hard-delete job (post-MVP: define retention policy)
- Audit trail: `message_recipients` + `attendance_records` + `action_tokens` provide full interaction log
- ICO registration: required before go-live
- API keys for stats sharing: school opt-in only, off by default

---

## 14. Phase Breakdown

### Phase 1 — Foundation
Docker Compose · PostgreSQL + PGVector · Redis · `HasSchoolScope` · `HasUlids` · Auth (email + password + 2FA + device registration) · Root admin (first user) · School onboarding wizard (name + subdomain + admin + logo + legal docs pre-fill) · Legal documents (Privacy Policy + T&Cs per school, editable rich text, versioned, acceptance tracking) · Platform legal templates (root admin managed) · Per-role dashboards (skeleton) · School settings · User management (staff invite + student enrol + guardian linking + CSV) · Basic PWA shell + service worker

### Phase 2 — Communication Core
Two-way messaging (all 4 types) · Notification cascade (Reverb → VAPID → SMS) · MailService (dual provider + fallback alert) · File attachments · Action tokens · Attendance register (manual + hardware API endpoint) · Absence alert loop

### Phase 3 — Scheduler & Tasks
Multi-calendar (4 types) · Calendar events · Task system (3 types) · Grouped todo templates (CRM port) · Cascade deadlines · Homework visibility for parents/students

### Phase 4 — Intelligence & Stats
Document upload + PGVector ingestion pipeline · RAG Q&A (per-school toggle) · Statistics dashboard · Stats API (opt-in) · Feature requests log

### Phase 6 — Multi-Language (post-MVP)
Language switcher UI · Additional locale files (`lang/{locale}.json`) · RTL CSS support (`rtl:` Tailwind variants) · Locale-aware date formatting · Translation management interface · User-generated content translation (optional — school responsibility)

Note: foundation is already in place from day one (`__()` helpers, machine keys in DB, `react-i18next` with `en.json`). Adding a language requires only a new translation file — no code changes.

### Phase 5 — Hardening & Post-MVP Prep
Horizon dashboard (school admin view) · Security+ tier UI · iCal export · Authorised/unauthorised absence (Ofsted) · Cloudflare API domain automation · WhatsApp channel · Enhanced permission forms

---

## 15. Post-MVP Backlog (documented, not built)

| Item | Notes |
|---|---|
| Building entry presence (card/fob/AI camera) | Hardware tier 2+3 |
| Parent-initiated message threads | Messaging enhancement |
| Full targeting (year group, whole school) | Messaging enhancement |
| iCal subscribe / Google Calendar sync | Calendar enhancement |
| Homework submission + grading | Task enhancement |
| Authorised vs unauthorised absence | HMIE/Ofsted compliance |
| WhatsApp channel | `whatsapp_number` field already in schema |
| NPM API automation | inte-panel already has hooks |
| Show presence (timetable live view) | Requires presence hardware first |
| Cloudflare API automated domain + email DNS | Domain management |
| School admin Horizon view | Queue health per school |
| Report cards / grades | Curriculum feature |
