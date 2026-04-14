# Messaging — Architecture

## Backend Layers

### Models
| Model | Traits | Policy | Key Relations |
|---|---|---|---|
| `Message` | HasUlids, HasSchoolScope, SoftDeletes | MessagePolicy | sender, parent, thread, recipients, attachments |
| `MessageRecipient` | HasUlids, HasSchoolScope | — | message, recipient |
| `MessageAttachment` | HasUlids, HasSchoolScope | — | message |

### Service: `MessagingService` (final)
- `send()` — creates message, fan-out recipients, stores attachments, fires `MessageSent` event
- `resolveClassRecipients()` — resolves class → student + guardian user IDs
- `markRead()` — sets `read_at` on MessageRecipient
- `recordQuickReply()` — stores reply text + marks as read
- Constant: `RECEIPT_TYPES = ['attendance_alert', 'trip_permission']`

### Service: `NotificationService` (final)
- `notifyRecipient()` — orchestrates push + SMS cascade
- `isOnline()` — checks Redis for Reverb presence (120s TTL)
- Dispatches `SendPushNotificationJob` per device (high queue)
- Dispatches `PromoteToSmsJob` with configurable delay (high queue)

### Jobs
| Job | Queue | Purpose |
|---|---|---|
| `SendBulkMessageJob` | default | Fan-out for >10 recipients, chunked by 50 |
| `PromoteToSmsJob` | high | SMS fallback after delay, checks read_at before sending |
| `SendPushNotificationJob` | high | VAPID push to registered device |

### Events & Listeners
- `MessageSent` (ShouldBroadcast) → broadcasts on `school.{id}` + `user.{id}` channels
- `HandleMessageSent` → loops recipients, calls `NotificationService::notifyRecipient()`

### Form Request: `SendMessageRequest`
- Validates type, body, recipient_id/class_id, thread_id, attachments (array syntax)

## Frontend Structure

### Pages
| Page | Layout | Role |
|---|---|---|
| `Admin/Messages/Index.tsx` | SchoolLayout | Admin — inbox + outbox with type badges |
| `Admin/Messages/Compose.tsx` | SchoolLayout | Admin — type selector, class/individual targeting, attachments |
| `Teacher/Messages/Compose.tsx` | SchoolLayout | Teacher — class selector only, no attachments |
| `Parent/Messages/Index.tsx` | ParentLayout | Parent — inbox only, unread indicators |
| `Parent/Messages/Thread.tsx` | ParentLayout | Parent — auto-mark-read, quick reply buttons |

## Data Flow Diagram
```
Compose → POST /messages → MessagingService::send()
  → Create Message + Recipients + Attachments
  → Fire MessageSent event
    → HandleMessageSent listener
      → NotificationService::notifyRecipient() per recipient
        → If online: Reverb only
        → If offline: SendPushNotificationJob
        → If requires_receipt + sms_enabled: PromoteToSmsJob (delayed)
```
