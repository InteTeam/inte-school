# Settings — Architecture

## Backend Layers

### Controller: `Admin/SettingsController`

| Method | Purpose |
|---|---|
| `general()` | Render form with school name, slug, logo, theme_config |
| `updateGeneral()` | Update name, theme_config, upload logo via SchoolService |
| `notifications()` | Render notification settings form |
| `updateNotifications()` | Update notification_settings JSONB |
| `security()` | Render security policy form with plan display |
| `updateSecurity()` | Update security_policy JSONB |
| `legal()` | List published legal documents with version/status |

### Form Requests

| Request | Key Rules |
|---|---|
| `UpdateGeneralSettingsRequest` | name (required, max 255), logo (image, jpg/png/webp, max 2MB), theme_config.primary_color (hex), theme_config.dark_mode (bool) |
| `UpdateNotificationSettingsRequest` | sms_fallback_enabled (required, bool) |
| `UpdateSecuritySettingsRequest` | require_2fa (required, bool), session_timeout_minutes (required, int 15-1440) |

### Service: `SchoolService` (final)

- `updateSettings()` — merges settings JSONB
- `updateNotificationSettings()` — merges notification_settings JSONB
- `updateTheme()` — merges theme_config JSONB
- `uploadLogo()` — stores to `schools/{id}/logos/`, updates logo_path

### Observer: `SchoolSettingsObserver`

- Observes `School` model on `saved` event
- Flushes: `school:{id}:settings`, `school:{id}:features`, `school:{id}:notification_settings`

## Frontend Structure

### Pages

| Page | Layout | Purpose |
|---|---|---|
| `Admin/Settings/General.tsx` | SchoolLayout | Name, slug (readonly), logo upload, colour picker, dark mode toggle |
| `Admin/Settings/Notifications.tsx` | SchoolLayout | SMS fallback toggle |
| `Admin/Settings/Security.tsx` | SchoolLayout | 2FA toggle, session timeout, plan badge |
| `Admin/Settings/Legal.tsx` | SchoolLayout | Legal document list with version/status badges |
