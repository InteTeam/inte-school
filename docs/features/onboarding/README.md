# Onboarding

## Overview

4-step school creation wizard with session-based progress. Creates a new school with branding, pre-fills legal documents from platform templates, and enforces legal acceptance before app access.

## Wizard Steps

| Step | Purpose | Session Key |
|---|---|---|
| 1 | School name + slug (auto-slugified, uniqueness validated) | `onboarding.step1` |
| 2 | Logo upload + theme primary colour | `onboarding.step2` |
| 3 | Legal documents review (pre-filled from platform templates) | `onboarding.step3` |
| 4 | Confirmation → creates school | Clears all |

## Key Flows

### School Creation
1. Steps 1-3 store data in session
2. Step 4 `complete()` → `OnboardingService::createSchoolWithAdmin()`
3. Creates School record, uploads logo, sets theme
4. `preFillLegalDocuments()` copies active `LegalDocumentTemplate` rows into `SchoolLegalDocument`
5. Session cleared, redirect to dashboard

### Legal Acceptance Enforcement
- `EnsureLegalAcceptance` middleware (alias: `legal`) on all school routes
- Checks if user accepted latest published version of all documents
- Redirects to `/legal/accept` if any document is outdated
- `UserLegalAcceptance` records are append-only (IP + user agent for audit)

### Go-Live Check
`OnboardingService::schoolCanGoLive()` — both `privacy_policy` and `terms_conditions` must be published before school is accessible.

## Legal Document Lifecycle

1. **Template** (platform-level) → copied to school during onboarding
2. **Draft** — school admin can edit content
3. **Publish** — sets version, published_at, published_by; requires user re-acceptance
4. **Acceptance** — append-only audit trail with IP, user agent, document version snapshot

## Database Tables

- `legal_document_templates` — platform-level defaults (not school-scoped)
- `school_legal_documents` — per-school versioned documents
- `user_legal_acceptances` — append-only audit trail (no update/delete)

## Routes

### Onboarding (middleware: auth, not_disabled)
- `GET/POST /onboarding/step-1` through `step-4`
- `POST /onboarding/complete` — create school

### Legal (middleware: auth, not_disabled, school)
- `GET /legal/accept` — acceptance form
- `POST /legal/accept` — record acceptance
- `GET /legal/{type}` — view document
- `GET /legal/{document}/edit` — edit form (admin)
- `PUT|POST /legal/{document}` — save edits
- `POST /legal/{document}/publish` — publish version

## Security

- Append-only acceptance records — no update or delete operations
- IP address and user agent captured for UK Children's Code compliance
- Document version snapshotted at acceptance time
