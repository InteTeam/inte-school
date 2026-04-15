# GOV.UK Notify SMS Integration

> Status: Planning
> Priority: High — referenced on landing page, replaces MVP stub
> Created: 2026-04-15

## Summary

Replace the current `SmsService` stub with a real GOV.UK Notify integration. This gives state-funded schools 5,000 free texts per year with zero markup from us. After the free allowance, texts cost 2.4p + VAT each at GOV.UK's rate — we pass through at cost.

## Why GOV.UK Notify

- **Free tier for schools**: 5,000 texts/year for state-funded schools (renews April 1)
- **No markup**: We don't profit on messaging — we profit on time saved
- **Trusted infrastructure**: Same platform used by NHS, HMRC, DWP
- **UK data residency**: Messages processed entirely within UK
- **Already on our landing page**: We've committed to this publicly

## User Stories

### US-1: Emergency SMS fallback
**As a** school admin,
**I want** critical messages to automatically fall back to SMS when parents don't read the push notification,
**So that** no parent misses an emergency communication.

**Acceptance criteria:**
- Unread message after configurable timeout (default 15 min) triggers SMS
- SMS sent via GOV.UK Notify API
- SMS delivery status tracked per recipient
- Admin dashboard shows SMS usage vs free allowance

### US-2: SMS usage visibility
**As a** school admin,
**I want** to see how many of my 5,000 free texts I've used this year,
**So that** I can budget and decide whether to enable SMS fallback for non-critical messages.

**Acceptance criteria:**
- Settings page shows: texts sent this year, free remaining, estimated cost for additional
- Alert at 80% and 100% of free allowance
- Usage resets display on April 1 (GOV.UK Notify allowance renewal)

### US-3: SMS opt-out per message type
**As a** school admin,
**I want** to control which message types can fall back to SMS,
**So that** I don't burn free texts on low-priority announcements.

**Acceptance criteria:**
- Per-type toggle: attendance_alert (default ON), trip_permission (default ON), announcement (default OFF)
- Stored in school `notification_settings` JSONB
- `PromoteToSmsJob` checks toggle before sending

### US-4: Root admin Notify configuration
**As a** root admin,
**I want** to configure the GOV.UK Notify API key and template IDs,
**So that** each school stack can send SMS without per-school API keys.

**Acceptance criteria:**
- API key stored encrypted (not in .env — use DB with encryption)
- Template ID for generic SMS configurable
- Health check: test send to a verified number
- Root admin alerted if API key expires or quota exceeded

## Graceful Fallback

| Scenario | Behaviour |
|---|---|
| GOV.UK Notify API unreachable | Log error, alert root admin, SMS silently skipped — push notification already delivered |
| API key invalid/expired | Log + root admin alert, SMS skipped |
| School exceeds free allowance | Continue sending (GOV.UK bills the service), alert admin at 80% and 100% thresholds |
| Phone number invalid | Notify returns error, mark recipient as `sms_failed`, log reason |
| Rate limit hit (3,000 msgs/min) | Queue backpressure — Laravel queue handles retry with exponential backoff |

## Flexible Data (JSONB Settings)

These values live in `schools.notification_settings` — never hardcoded:

| Key | Default | Notes |
|---|---|---|
| `sms_fallback_enabled` | `false` | Already exists in codebase |
| `sms_timeout_seconds` | `900` | Already exists — delay before SMS promotion |
| `sms_fallback_types` | `["attendance_alert", "trip_permission"]` | Which message types can trigger SMS |
| `sms_usage_alert_threshold` | `0.8` | Alert admin at this fraction of free allowance |

## GOV.UK Notify API Reference

- **Base URL**: `https://api.notifications.service.gov.uk`
- **Auth**: Bearer token (API key)
- **Send SMS**: `POST /v2/notifications/sms`
- **Check status**: `GET /v2/notifications/{id}`
- **Rate limit**: 3,000 messages per minute
- **PHP SDK**: `alphagov/notifications-php-client` (official)
- **Pricing**: https://www.notifications.service.gov.uk/pricing/text-messages

## Out of Scope (Future)

- Per-school Notify API keys (all schools share the platform key for MVP)
- Email via Notify (we use Resend / SMTP per company already)
- International SMS multipliers (UK numbers only for MVP)
- Bulk SMS campaigns (SMS is fallback only, not a broadcast channel)

## Design Checklist

- [x] Business requirements documented
- [x] User stories with acceptance criteria
- [x] Graceful fallback paths documented
- [x] Flexible data values identified (JSONB defaults)
- [ ] Technical architecture (`architecture.md`)
- [ ] Component inventory (`COMPONENT_INVENTORY.md`)
- [ ] Database migrations documented
- [ ] Tests written (TDD)
- [ ] Implementation complete
- [ ] Quality gates passed
