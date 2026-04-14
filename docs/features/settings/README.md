# Settings

## Overview

School settings management with four sections: General (branding), Notifications (SMS config), Security (2FA, session), and Legal (document management). All settings stored in JSONB columns on the `schools` table.

## Sections

### General
- School name (editable)
- School slug (read-only display)
- Logo upload (JPG/PNG/WebP, max 2MB)
- Theme: primary colour picker + dark mode toggle
- Stored in `schools.theme_config` JSONB

### Notifications
- SMS fallback toggle (enable/disable)
- SMS timeout configured at infrastructure level
- Stored in `schools.notification_settings` JSONB

### Security
- Require 2FA toggle
- Session timeout (15–1440 minutes)
- Plan display (badge showing current plan tier)
- Security+ features locked for lower plans
- Stored in `schools.security_policy` JSONB

### Legal
- List published legal documents (privacy_policy, terms_conditions)
- Version badge and published/draft status per document
- Edit link to legal document editor

## JSONB Storage

| Column | Contents |
|---|---|
| `theme_config` | `{ primary_color: "#hex", dark_mode: bool }` |
| `notification_settings` | `{ sms_fallback_enabled: bool, sms_timeout_seconds: int }` |
| `security_policy` | `{ require_2fa: bool, session_timeout_minutes: int }` |

## Cache Invalidation

`SchoolSettingsObserver` flushes `school:{id}:settings`, `school:{id}:features`, `school:{id}:notification_settings` on save.

## Routes (middleware: auth, not_disabled, school, legal, role:admin)

- `GET /admin/settings/general` — general settings form
- `PUT|POST /admin/settings/general` — update general
- `GET /admin/settings/notifications` — notification settings
- `PUT /admin/settings/notifications` — update notifications
- `GET /admin/settings/security` — security settings
- `PUT /admin/settings/security` — update security
- `GET /admin/settings/legal` — legal documents list
