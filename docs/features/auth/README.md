# Feature: Authentication & Device Trust

**Status:** Complete (Phase 1)
**Roles affected:** All
**Route prefix:** `/` (auth), `/device-registration`

---

## Overview

Handles user authentication, password reset, two-factor authentication (TOTP), trusted device registration, and session management. All auth routes are outside the school middleware stack — the user resolves their school context after login.

---

## User Stories

| Story | Acceptance criteria |
|---|---|
| As any user, I can log in with email + password | Valid credentials → redirected to dashboard; invalid → error shown |
| As any user, I can reset my password via email | Reset link sent; token expires after 60 min; HIBP check on new password |
| As a user with 2FA enabled, I must complete a TOTP challenge after login | Correct code → session established; 5 failed attempts → 15-min lockout + email alert |
| As a trusted device owner, I can skip 2FA on registered devices | Device cookie present + valid → challenge skipped |
| As an admin, I am redirected to admin.dashboard after login | Dashboard route reads `currentSchoolRole()` and redirects accordingly |

---

## Business Requirements

- Password minimum: 12 characters, mixed case, at least one number, `Password::uncompromised()` (HIBP check)
- 2FA: TOTP codes (not SMS), 5 failed attempts triggers 15-minute lockout + email alert to user
- Trusted devices: cookie-based, server-side record, admin-revocable
- Login rate limit: 10 attempts / minute / IP
- Password reset rate limit: 5 attempts / minute / IP
- 2FA rate limit: 5 attempts / minute / user
- Session: Redis-backed, 480-minute lifetime, secure cookie in production

---

## Graceful Fallback

| Failure | Fallback |
|---|---|
| 2FA code delivery issue | User can use a backup code (future: recovery codes) |
| Redis session store down | Laravel falls back to file driver via config fallback |
| HIBP check timeout | Password is accepted (fail open — check is best-effort) |
| Email send failure | Error logged; user shown generic "check your email" message |

---

## Flexible Values

| Value | Storage | Default |
|---|---|---|
| Session lifetime | `config/session.php` + `.env SESSION_LIFETIME` | 480 min |
| 2FA lockout duration | `config/auth.php` (future: school JSONB setting) | 15 min |
| 2FA max attempts | `bootstrap/app.php` rate limiter | 5 |
| Login rate limit | `bootstrap/app.php` rate limiter | 10/min |

---

## Feature Design Checklist

- [x] Graceful fallback documented and implemented
- [x] All configurable values in config/env, not hardcoded
- [x] Multi-tenant isolation — auth is pre-school context, no `school_id` leak
- [x] Rate limits defined in `bootstrap/app.php`
- [x] Password policy enforced server-side (`Password` rule object)
- [x] 2FA lockout + email alert implemented
- [x] Device trust revocable by user (future: by admin)
- [x] Session secured: Redis, `SESSION_SECURE_COOKIE=true` in production
