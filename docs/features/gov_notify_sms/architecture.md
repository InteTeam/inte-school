# GOV.UK Notify SMS — Technical Architecture

> Status: Planning
> Created: 2026-04-15

## Integration Point

The notification cascade already works end-to-end:

```
Message sent
  → HandleMessageSent event
    → NotificationService::notifyRecipient()
      → Step 1: Reverb broadcast (already fired)
      → Step 2: VAPID push (if user offline)
      → Step 3: PromoteToSmsJob dispatched with delay
        → Checks read_at (skip if already read)
        → Checks sms_fallback_enabled
        → Calls SmsService::send()  ← THIS IS THE STUB TO REPLACE
```

**The only code change needed in the cascade: replace the `SmsService` stub with a GOV.UK Notify client call.** No changes to `NotificationService`, `PromoteToSmsJob`, or any event/listener.

## Package

**`alphagov/notifications-php-client`** — official GOV.UK Notify PHP SDK.

```bash
composer require alphagov/notifications-php-client
```

## Configuration

### Environment Variables

```env
GOVUK_NOTIFY_API_KEY=           # API key from GOV.UK Notify dashboard
GOVUK_NOTIFY_SMS_TEMPLATE_ID=   # Template ID for generic SMS
```

### Why .env (not DB)

For MVP, one API key serves all schools (platform-level key). This is simpler and matches how GOV.UK Notify intends their free tier to work — one service, one key. Per-school keys are out of scope.

The API key is sensitive but not school-specific, so `.env` is appropriate (same as `MAIL_*`, `REVERB_*` keys). DB-encrypted storage would be needed if we supported per-school keys (future).

## Service Changes

### SmsService (rewrite)

```
app/Services/SmsService.php
```

**Current:** Stub that logs and returns `true`.

**New behaviour:**
1. Construct `Alphagov\Notifications\Client` with API key from config
2. Call `$client->sendSms($phone, $templateId, ['body' => $text])`
3. Return delivery ID on success, log + alert root admin on failure
4. Track usage count in cache for dashboard display

**Method signature stays the same:** `send(string $phoneNumber, string $body): bool`

This means `PromoteToSmsJob` needs zero changes.

### Additional methods to add:

```php
public function getUsageThisYear(string $schoolId): int
    // Count SMS records for this school since April 1

public function getRemainingFreeTexts(string $schoolId): int
    // 5000 - getUsageThisYear()

public function isApproachingLimit(string $schoolId): bool
    // usage >= threshold (default 80% = 4000)
```

## New Model: SmsLog

Track every SMS sent for usage counting, delivery status, and audit.

```
Table: sms_logs
- id (ULID)
- school_id (FK → schools, indexed)
- recipient_id (FK → users)
- message_id (FK → messages, nullable — not all SMS may be cascade-triggered)
- phone_number (string, masked for display: +44***1234)
- notify_message_id (string — GOV.UK Notify delivery ID)
- status (string: queued, delivered, failed, provider_error)
- segments (integer — number of SMS segments consumed)
- cost_pence (integer — cost in pence, for tracking)
- sent_at (datetime)
- delivered_at (datetime, nullable)
- failed_at (datetime, nullable)
- failure_reason (string, nullable)
- created_at, updated_at
```

**Indexes:**
- `idx_school_sent` — `(school_id, sent_at)` for usage counting
- `idx_notify_id` — `(notify_message_id)` for delivery callback lookup

**Model traits:** `HasSchoolScope`, `HasUlids`

## Controller / UI Changes

### Admin Settings — SMS Usage Panel

Add to existing `SettingsController::notifications()` response:

```php
'sms_usage' => [
    'sent_this_year' => $smsService->getUsageThisYear($schoolId),
    'free_remaining' => $smsService->getRemainingFreeTexts($schoolId),
    'free_allowance' => 5000,
    'renewal_date' => $this->nextAprilFirst(),
    'approaching_limit' => $smsService->isApproachingLimit($schoolId),
],
```

### Admin Settings — SMS Fallback Type Toggles

Add to existing notification settings form:
- Toggle per message type: `attendance_alert`, `trip_permission`, `announcement`
- Stored in `notification_settings.sms_fallback_types` JSONB array

### PromoteToSmsJob — Type Check

Add one line to `handle()`:

```php
// After sms_fallback_enabled check, add:
$allowedTypes = (array) $school->getNotificationSetting('sms_fallback_types', ['attendance_alert', 'trip_permission']);
$message = Message::find($this->messageId);
if (! in_array($message?->type, $allowedTypes, true)) {
    return;
}
```

## Delivery Status Webhook (Deferred)

GOV.UK Notify can POST delivery receipts to a callback URL. This is useful for tracking `delivered` vs `failed` status but requires a publicly accessible endpoint. Deferred to after initial deployment — for MVP, we track `queued` status and assume delivery.

## Testing Strategy

### Unit Tests
- `SmsService::send()` with mocked Notify client — success, failure, invalid number
- `SmsService::getUsageThisYear()` — counts correctly, respects April 1 boundary
- `SmsService::getRemainingFreeTexts()` — arithmetic correct

### Feature Tests
- `PromoteToSmsJob` with real `SmsService` (mocked HTTP) — sends when unread, skips when read
- `PromoteToSmsJob` respects `sms_fallback_types` setting
- Admin notification settings page shows usage stats
- SMS log created on successful send
- Multi-tenant: school A's usage count doesn't include school B's texts

## Implementation Phases

### Phase 1: Wire up the service (smallest useful change)
1. `composer require alphagov/notifications-php-client`
2. Rewrite `SmsService::send()` to call Notify API
3. Create `sms_logs` migration + model
4. Log every send attempt
5. Tests for send success/failure

### Phase 2: Usage tracking + admin UI
1. Add `getUsageThisYear()`, `getRemainingFreeTexts()` to `SmsService`
2. Add usage data to notification settings page
3. Add SMS fallback type toggles to settings form
4. Update `PromoteToSmsJob` to check allowed types
5. Alert admin at 80% / 100% of free allowance

### Phase 3: Delivery status (future)
1. Webhook endpoint for Notify delivery receipts
2. Update `sms_logs.status` on receipt
3. Show delivery status in admin message thread view

## Dependencies

| Dependency | Purpose | Risk |
|---|---|---|
| `alphagov/notifications-php-client` | Official SDK | Low — maintained by GDS, stable API |
| GOV.UK Notify account | API key + template | Need to register the service and create a text template |
| Outbound HTTPS from Docker | API calls to `api.notifications.service.gov.uk` | Already works (HIBP checks, Ollama calls use outbound HTTPS) |
