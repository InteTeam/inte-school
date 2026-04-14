# Attendance — Architecture

## Backend Layers

### Models
| Model | Traits | Policy | Key Relations |
|---|---|---|---|
| `AttendanceRegister` | HasUlids, HasFactory, HasSchoolScope | AttendancePolicy | school, schoolClass, teacher, records |
| `AttendanceRecord` | HasUlids, HasFactory, HasSchoolScope | — | register, student, markedBy |
| `HardwareDeviceToken` | HasUlids, HasSchoolScope | — | school |

### Service: `AttendanceService` (final, 167 lines)
- `openOrGetRegister()` — creates or retrieves register for class/date/period
- `mark()` — upserts attendance record, dispatches absence alerts if needed
- `getDailyStats()` — returns cached present/absent/late counts
- `flushStatsCache()` — invalidates daily stats cache
- `dispatchAbsenceAlerts()` — sends queued alerts to guardians (private)

### Observer: `AttendanceObserver`
- Observes `AttendanceRecord` model
- Hooks: `created()`, `updated()` → calls `flushStatsCache()`
- Registered in `AppServiceProvider`

### Jobs
| Job | Queue | Purpose |
|---|---|---|
| `SendAttendanceAlertJob` | high | Sends attendance_alert message to guardians via MessagingService |

### Controllers
| Controller | Methods | Role |
|---|---|---|
| `Teacher/AttendanceController` | index, register, mark | Teacher — own classes |
| `Admin/AttendanceController` | index, override | Admin — all classes |
| `Api/AttendanceHardwareController` | __invoke | Stateless NFC reader endpoint |

### Validation
Inline in controllers: status (in:present,absent,late), register_id, student_id, notes, pre_notified

## Frontend Structure

### Pages
| Page | Layout | Role |
|---|---|---|
| `Teacher/Attendance/Register.tsx` | SchoolLayout | Interactive register with status buttons |
| `Parent/Attendance/History.tsx` | ParentLayout | Calendar heatmap + log with % stats |

### Missing Pages
- `Admin/Attendance/` — directory exists but no index page yet

## Caching Strategy
- Key: `school:{schoolId}:attendance:{Y-m-d}`
- TTL: 3600s (1 hour)
- Invalidation: AttendanceObserver on record create/update
- Source: aggregate COUNT by status from attendance_records

## Hardware Integration Flow
```
NFC Card Tap → POST /api/v1/attendance/mark
  → Validate device_token (SHA-256 hash match)
  → Resolve student by nfc_card_id
  → openOrGetRegister() for today
  → mark(status: present, marked_via: nfc_card)
  → Return JSON: { student_name, attendance_status, timestamp }
```
