# Messaging

## Overview
Two-way messaging system for school-parent communication. Supports announcements, attendance alerts, trip permissions, and quick replies with a notification cascade (Reverb → VAPID push → SMS fallback).

## User Stories
- As a **school admin**, I can send announcements to all parents or specific classes
- As a **teacher**, I can send messages to parents of students in my classes
- As a **parent**, I can view messages, read receipts are tracked, and I can quick-reply
- As a **support staff**, I can send messages on behalf of the school

## Message Types
| Type | Read Receipt | Quick Reply | SMS Fallback |
|---|---|---|---|
| `announcement` | No | No | No |
| `attendance_alert` | Yes | Yes | Yes (15 min default) |
| `trip_permission` | Yes | Yes | Yes |
| `quick_reply` | No | N/A | No |

## Key Flows

### Send Message
1. Admin/teacher composes message with type, body, optional attachments
2. Targets: individual recipient OR entire class (fans out to students + guardians)
3. >10 recipients → dispatched as `SendBulkMessageJob` (chunked by 50)
4. `MessageSent` event fires → `HandleMessageSent` listener triggers notification cascade

### Notification Cascade
1. Check Reverb presence (Redis) → if online, WebSocket only
2. If offline → dispatch `SendPushNotificationJob` per registered device
3. If `requires_read_receipt` + SMS enabled → queue `PromoteToSmsJob` with delay from `notification_settings.sms_timeout_seconds` (default 900s)
4. SMS job checks `read_at` before sending — suppressed if already read

### Quick Reply (Parent)
1. Parent opens message → auto-marks as read via axios POST
2. If `requires_read_receipt`, quick reply buttons shown: "Acknowledged", "Yes, I consent", "No, I do not consent", "Please call me"
3. Reply stored in `message_recipients.quick_reply`

## Database Tables
- `messages` — core message with thread support (self-referencing `thread_id`)
- `message_recipients` — per-recipient read/delivery tracking + quick reply
- `message_attachments` — file metadata (stored via StorageService/GCS)

## Deduplication
Transaction ID (ULID) on messages table with unique constraint prevents duplicate sends on client retry.

## Validation
- Body: max 5000 chars
- Attachments: max 5 files, 10MB each, JPG/PNG/WebP/PDF only (MIME validated server-side)
- Type: must be one of the 4 defined types

## Routes
All under middleware: `auth`, `not_disabled`, `school`, `legal`
- `GET /messages` — inbox/outbox
- `POST /messages` — send
- `GET /messages/{message}` — thread view
- `POST /messages/{message}/read` — mark read
- `POST /messages/{message}/reply` — quick reply
- `GET /messages/attachments/{attachment}/download` — download

## Security
- MessagePolicy: admin/teacher/support can send; teachers scoped to own classes in controller
- View: admins see all, senders see own, recipients see if in MessageRecipient table
- Delete: admin only (soft delete)
- Attachments: MIME type validated server-side, no SVG uploads
