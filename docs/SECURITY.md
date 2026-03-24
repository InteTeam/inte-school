# Security Reference

This document is the authoritative security reference for inte-school. Read it when planning any feature that touches auth, user data, file uploads, APIs, or child data.

---

## UK Children's Code (Age Appropriate Design Code)

**Legal requirement — not optional.** Inte-school is an online service likely to be accessed by children under 18. The UK Children's Code applies.

### Key obligations
- **Privacy by default:** privacy settings must default to high — do not default to sharing or open access
- **Data minimisation:** only collect what is strictly necessary for the feature
- **No profiling of children** without explicit parental consent
- **Geolocation off by default:** never enable location tracking without explicit opt-in
- **Parental controls:** parents must be able to see and control what their child's account can access
- **No nudge techniques:** UI must not use dark patterns to push children toward less private options
- **Transparent:** age-appropriate language in privacy notices

### How to apply per feature
Before building any feature that involves student data:
- [ ] Is this data strictly necessary? If not, don't collect it
- [ ] Does this feature expose student data to anyone beyond verified guardians and authorised staff?
- [ ] Does this feature default to the most private option?
- [ ] Would a parent understand what this does from plain language?

**Reference:** https://ico.org.uk/for-organisations/childrens-code-hub/

---

## Security Headers

Must be configured in the Nginx/Caddy config before any school goes live. Not optional.

```nginx
# Nginx — add to server block
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
add_header Content-Security-Policy "
    default-src 'self';
    script-src 'self' 'nonce-{nonce}';
    style-src 'self' 'unsafe-inline';
    img-src 'self' data: https://storage.googleapis.com;
    connect-src 'self' wss://;
    font-src 'self';
    frame-ancestors 'none';
" always;
```

**Laravel side:** set `SESSION_SECURE_COOKIE=true` and `SESSION_SAME_SITE=strict` in `.env` for production.

---

## Rate Limiting

Define in `bootstrap/app.php` using Laravel's built-in rate limiter.

### Standard limits

| Endpoint | Limit | Window |
|---|---|---|
| `POST /login` | 10 attempts | per minute per IP |
| `POST /forgot-password` | 5 attempts | per minute per IP |
| `POST /two-factor` | 5 attempts | per minute per user |
| `POST /api/v1/stats/*` | 60 requests | per minute per API key |
| `POST /api/v1/attendance/mark` | 120 requests | per minute per device token |
| `POST /school/*/documents/ask` | 20 requests | per minute per user |
| `POST /school/*/messages` | 30 requests | per minute per user |

### Implementation pattern
```php
// bootstrap/app.php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip())
        ->response(fn() => response()->json(['message' => 'Too many attempts.'], 429));
});
```

### 2FA lockout
After 5 failed 2FA attempts: lock the user's 2FA for 15 minutes. Log the event. Notify the user via email that someone attempted to access their account.

---

## Password Policy

Minimum requirements (enforced in `PasswordRequest` validation):
```php
Password::min(12)
    ->letters()
    ->mixedCase()
    ->numbers()
    ->uncompromised()   // checks against known breach databases via HIBP
```

- Minimum 12 characters (UK NCSC recommendation)
- Mixed case + at least one number
- `uncompromised()` checks Have I Been Pwned — rejects passwords in known breach lists
- No maximum length (hash storage means length is irrelevant)

---

## Mass Assignment Protection

**`school_id` must never appear in `$fillable`.**

`HasSchoolScope` sets `school_id` automatically on model creation from the session context. If `school_id` is in `$fillable`, a crafted request can write data into a different school's tenant — a GDPR violation.

```php
// CORRECT — school_id not in fillable
protected $fillable = ['title', 'body', 'sender_id', 'type'];

// WRONG — never do this
protected $fillable = ['school_id', 'title', 'body'];
```

**In controllers:** never use `$request->all()` or `$request->validated()` directly on a create if it could contain `school_id`. Use only the fields you explicitly need.

---

## File Upload Security

### MIME type validation (server-side only — never trust client)
```php
// In Form Request
'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],

// For logos — validate MIME server-side
'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
```

**Never validate by extension alone.** A file named `malicious.pdf` with a PHP MIME type will pass extension validation but must fail MIME validation.

### SVG sanitization
SVGs can contain JavaScript. **Never serve user-uploaded SVGs directly.** Options:
- Disallow SVG uploads entirely (simplest for MVP)
- Sanitize via a whitelist-based SVG sanitizer before storage
- Convert to PNG server-side

**For MVP:** disallow SVG in logo uploads. Accept JPG, PNG, WebP only.

### CSV injection protection
When importing students or exporting data, any field starting with `=`, `-`, `+`, `@`, `\t`, or `\r` must be prefixed with a tab character or wrapped in quotes to prevent formula injection when opened in Excel/Google Sheets.

```php
// In CSV export service
private function sanitizeCsvField(string $value): string
{
    $dangerousChars = ['=', '-', '+', '@', "\t", "\r"];
    if (in_array($value[0] ?? '', $dangerousChars, true)) {
        return "\t" . $value;
    }
    return $value;
}
```

### PDF processing for RAG
Before text extraction from uploaded PDFs:
- Validate MIME type is `application/pdf`
- Set a file size limit (e.g., 50MB max)
- Run extraction in a sandboxed process (queue job, not inline)
- If extraction fails → `processing_status = failed`, log error, do not expose raw error to user

---

## CSRF in Service Worker

The service worker intercepts fetch requests including background sync. It must attach the CSRF token to all non-GET requests.

```javascript
// In service-worker.ts
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        // Attach CSRF token from cookie
        const csrfToken = getCookieValue('XSRF-TOKEN');
        const modifiedRequest = new Request(event.request, {
            headers: {
                ...Object.fromEntries(event.request.headers),
                'X-XSRF-TOKEN': decodeURIComponent(csrfToken),
            },
        });
        event.respondWith(fetch(modifiedRequest));
    }
});
```

The `useSessionHeartbeat` hook handles reactive 419 recovery for the main app. The service worker handles it for background requests.

---

## VAPID Push Payload Encryption

VAPID Web Push supports end-to-end encrypted payloads (RFC 8291). Always use encrypted payloads — never send action tokens or sensitive data in plaintext push payloads.

`minishlink/web-push` handles AESGCM encryption automatically when the subscription includes `p256dh` and `auth` keys — which the browser provides as part of the push subscription object. Ensure both keys are stored in `registered_devices.push_subscription` JSONB:

```json
{
    "endpoint": "https://...",
    "keys": {
        "p256dh": "...",
        "auth": "..."
    }
}
```

Never send a push without both keys present — fall back to a generic "you have a new message" notification without the action token payload if keys are missing.

---

## Attendance Hardware Token

The device token used by NFC readers to authenticate to the attendance API must be:
- Generated with `Str::random(64)` (cryptographically secure)
- Stored **hashed** in the database (`hardware_device_tokens` table, `token_hash` column)
- Rotatable by school admin from the settings UI (generates new token, invalidates old)
- Scoped to a single school — cannot be used to mark attendance at another school
- Rate limited: 120 requests per minute per token (see rate limiting section)

On the hardware device, the raw token is stored in firmware/config. The server verifies `hash('sha256', $incomingToken)` against `token_hash` in DB.

---

## API Key Lifecycle (Stats API)

School API keys for the statistics sharing feature:
- Generated with `Str::random(64)`
- Stored as `hash('sha256', $key)` in `school_api_keys.key_hash`
- Shown to the admin **once** on creation — never again (same pattern as Laravel Sanctum)
- Scope defined in `permissions` JSONB at creation — cannot be escalated later
- Expiry: optional `expires_at` column — school admin can set, root admin can enforce
- Rotation: admin generates new key, old key immediately invalidated
- On use: update `last_used_at` for audit purposes

---

## Dependency Auditing

Run on every session where packages are added or updated:

```bash
# PHP
docker compose exec php-fpm composer audit

# JavaScript
docker compose run --rm npm audit
```

Add to the pre-commit check alongside Pint and PHPStan. A high-severity vulnerability in a dependency blocks the commit.

---

## Invitation Token Security

`school_user.invitation_token`:
- Generated with `Str::random(64)` — cryptographically secure
- **Not hashed** in DB (must be readable to send in email link) — acceptable because tokens expire
- `invitation_expires_at`: 72 hours from generation
- Single-use: cleared from DB on acceptance (`accepted_at` set, `invitation_token` nulled)
- If a token is used after `invitation_expires_at`, reject and offer to resend

---

## Default Sort Order

**All user-facing lists default to descending order by `created_at`.**

```php
// CORRECT — always explicit, always descending
Model::query()
    ->where('school_id', $schoolId)
    ->orderBy('created_at', 'desc')
    ->get();

// WRONG — never rely on implicit/unspecified order
Model::query()
    ->where('school_id', $schoolId)
    ->get();
```

This applies to: message threads, task lists, homework lists, attendance records, feature requests, document lists, audit logs, notification history — everything shown to a user. Most recent first, always.

If a feature requires a different sort (e.g., calendar events by `starts_at`, tasks by `sort_order` for drag-reorder), document it explicitly in the feature's `architecture.md`. The default assumption is always `created_at DESC`.
