# Auth — Frontend Components

**Layout:** `AuthLayout.tsx` wraps all auth pages.

---

## Pages

| Page | Path | Notes |
|---|---|---|
| Login | `Pages/Auth/Login.tsx` | Email + password form, error flash |
| Forgot Password | `Pages/Auth/ForgotPassword.tsx` | Email input, success state |
| Reset Password | `Pages/Auth/ResetPassword.tsx` | New password + confirm, HIBP note |
| Two-Factor Challenge | `Pages/Auth/TwoFactorChallenge.tsx` | 6-digit TOTP input, lockout message |
| Device Registration | `Pages/Auth/DeviceRegistration.tsx` | "Trust this device?" confirm/skip |

---

## Reused Components

| Component | Import path | Used for |
|---|---|---|
| `Button` | `@/Components/ui/button` | All form submit buttons |
| `Input` (via html) | native `<input>` | Form fields (no custom wrapper needed) |
| `Badge` | `@/Components/ui/badge` | Error state indicators |

---

## New Components (auth-specific)

None — all auth pages are self-contained single-form pages that use native HTML inputs wrapped with Tailwind classes. No shared auth component was abstracted as each page has a distinct layout.

---

## Notes

- Auth pages do not use `SchoolLayout` — they use `AuthLayout` which has no nav/sidebar
- Forms use `useForm` from `@inertiajs/react` for error handling and processing state
- All forms POST to Laravel — no client-side validation beyond HTML5 `required`
