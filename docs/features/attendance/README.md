# Attendance

## Overview
Classroom attendance system with teacher register marking, admin overrides, NFC hardware reader API, and automated parent absence alerts with notification cascade.

## User Stories
- As a **teacher**, I can open a register for my class and mark each student present/absent/late
- As an **admin**, I can view all registers and override attendance marks
- As a **parent**, I can view my child's attendance history with percentages and trends
- As a **hardware device**, I can mark students present via NFC card tap through the REST API

## Key Flows

### Teacher Mark Register
1. Teacher selects class → opens register (creates/retrieves for today's date + period)
2. Student list displayed with current status badges (green/red/amber)
3. Teacher taps present/absent/late per student
4. If absent + not pre_notified → `SendAttendanceAlertJob` dispatched to guardians

### Pre-Notification
Parents can notify school in advance of an absence. When `pre_notified = true` on the attendance record, the absence alert is suppressed — no push notification or SMS cascade fires.

### Hardware NFC Reader
1. Device sends `POST /api/v1/attendance/mark` with `device_token`, `card_id`, `school_id`
2. Token validated via SHA-256 hash comparison
3. Student resolved via `users.nfc_card_id`
4. Register auto-created if needed, student marked present with `marked_via = nfc_card`
5. Returns JSON: student_name, attendance_status, timestamp

### Daily Stats
- Cached in Redis for 1 hour: `school:{id}:attendance:{date}`
- Aggregate: present/absent/late counts per school per date
- `AttendanceObserver` flushes cache on every record create/update

## Database Tables
- `attendance_registers` — one per class/date/period (unique constraint)
- `attendance_records` — individual student marks with status, marked_via, pre_notified
- `hardware_device_tokens` — NFC reader authentication (SHA-256 hashed)
- `users.nfc_card_id` — NFC card → student mapping (added in migration 019)

## Status Values
| Status | Badge Colour | Notes |
|---|---|---|
| `present` | Green | Default for NFC marks |
| `absent` | Red | Triggers alert if not pre_notified |
| `late` | Amber | No alert triggered |

## marked_via Values
`manual`, `nfc_card`, `nfc_phone`, `api`

## Routes
### Web (middleware: auth, not_disabled, school, legal)
- `GET /teacher/attendance` — class list
- `GET /teacher/attendance/register/{classId}` — register view
- `POST /teacher/attendance/mark` — mark student
- `GET /admin/attendance` — daily overview
- `POST /admin/attendance/override` — admin override

### API (middleware: throttle:60,1)
- `POST /api/v1/attendance/mark` — hardware NFC endpoint

## Security
- AttendancePolicy: teacher marks own registers only; admin/support mark any
- Hardware tokens: SHA-256 hashed, shown once at creation
- student_id and marked_by use RESTRICT on delete — cannot remove users with attendance history
- Rate limited: 60 req/min on hardware API
