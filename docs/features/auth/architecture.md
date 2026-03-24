# Auth — Technical Architecture

---

## Controllers

| Controller | Responsibility |
|---|---|
| `Auth/LoginController` | `create` (show form), `store` (authenticate), `destroy` (logout) |
| `Auth/PasswordResetController` | `create/store` (request link), `edit/update` (reset password) |
| `Auth/TwoFactorController` | `create` (show challenge), `store` (verify TOTP code) |
| `Auth/DeviceRegistrationController` | `create` (show form), `store` (register + set cookie) |

All are thin — delegate to Laravel's built-in `Auth` facade and `Password` facade.

---

## Middleware Stack

```
guest       → LoginController, PasswordResetController, TwoFactorController
auth        → all authenticated routes
not_disabled → blocks disabled users after auth check
school      → EnsureSchoolContext (resolves school session context)
```

Auth routes sit outside `school` middleware — school context is resolved on the dashboard redirect after login.

---

## Login Flow

```
POST /login
  ├── RateLimiter: 10/min/IP
  ├── Validate credentials (email, password)
  ├── Auth::attempt()
  │    ├── Fail → back with error + increment rate limit counter
  │    └── Success →
  │         ├── User disabled? → logout + 403
  │         ├── 2FA enabled?
  │         │    ├── Trusted device cookie present + valid? → skip challenge
  │         │    └── Store pending_user_id in session → redirect to /two-factor-challenge
  │         └── No 2FA → regenerate session → redirect to /dashboard
  └── /dashboard → role-based redirect (admin/teacher/parent/student/support/root-admin)
```

---

## 2FA Flow

```
GET  /two-factor-challenge  → show TOTP input
POST /two-factor-challenge
  ├── RateLimiter: 5/min/user
  ├── Verify TOTP code against user's secret
  ├── Fail:
  │    ├── Increment failure counter
  │    ├── 5 failures → lock for 15 min + email alert
  │    └── back with error
  └── Success → clear pending_user_id → establish full session → /dashboard
```

---

## Device Registration

After first successful login + 2FA on a new device the user is prompted to trust it.

```
GET  /device-registration  → show "Trust this device?" form
POST /device-registration
  ├── Create RegisteredDevice record (school_id not applicable — user-level)
  ├── Set signed cookie (device_token, 30-day expiry)
  └── Redirect to /dashboard
```

Cookie validation in `TwoFactorController::store()` — token is hashed on storage, compared on retrieval.

---

## Models

| Model | Notes |
|---|---|
| `User` | `is_root_admin` bool, `two_factor_secret` (encrypted), `two_factor_confirmed_at` |
| `RegisteredDevice` | `user_id`, `token_hash`, `device_name`, `last_used_at`, `expires_at` |

`RegisteredDevice` is user-scoped, not school-scoped — no `HasSchoolScope`.

---

## Rate Limits (bootstrap/app.php)

```php
RateLimiter::for('login', fn (Request $request) =>
    Limit::perMinute(10)->by($request->ip())
);

RateLimiter::for('two-factor', fn (Request $request) =>
    Limit::perMinute(5)->by($request->user()?->id)
);

RateLimiter::for('password-reset', fn (Request $request) =>
    Limit::perMinute(5)->by($request->ip())
);
```

---

## Session Security (production)

```
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=.inte.team
```
