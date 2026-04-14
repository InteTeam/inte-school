# Onboarding — Architecture

## Backend Layers

### Controllers

| Controller | Methods |
|---|---|
| `School/OnboardingController` | step1-4 (render), storeStep1-3 (validate + session), complete (create school) |
| `School/LegalDocumentController` | show, edit, update, publish, showAcceptance, recordAcceptance |

### Services

| Service | Methods |
|---|---|
| `OnboardingService` (final) | `createSchoolWithAdmin()`, `preFillLegalDocuments()`, `schoolCanGoLive()`, `isSlugAvailable()` |
| `LegalDocumentService` (final) | `getPublishedDocumentsForSchool()`, `publish()`, `recordAcceptance()`, `userNeedsToAccept()` |

### Models

| Model | Traits | Key Properties |
|---|---|---|
| `LegalDocumentTemplate` | HasUlids | type, name, content, is_active — platform-level, no school scope |
| `SchoolLegalDocument` | HasUlids, HasSchoolScope | type, content, version, is_published, published_at/by |
| `UserLegalAcceptance` | HasUlids, HasSchoolScope | document_id, document_version, accepted_at, ip_address, user_agent — append-only |

### Middleware: `EnsureLegalAcceptance` (alias: `legal`)

1. Calls `LegalDocumentService::userNeedsToAccept()`
2. If any document needs acceptance → redirect to `/legal/accept`
3. Applied to all school routes after `auth` and `school`

## Frontend Structure

### Pages

| Page | Layout | Purpose |
|---|---|---|
| `School/Onboarding/Step1.tsx` | WizardShell | Name + slug inputs |
| `School/Onboarding/Step2.tsx` | WizardShell | Logo upload + colour picker |
| `School/Onboarding/Step3.tsx` | WizardShell | Legal review (informational) |
| `School/Onboarding/Step4.tsx` | WizardShell | Confirmation + create button |
| `Legal/Accept.tsx` | AuthLayout | Acceptance form for all published documents |
| `Legal/Show.tsx` | SchoolLayout | Read-only document display |
| `Legal/Edit.tsx` | SchoolLayout | Rich text editor for document content |

### WizardShell Organism

Progress indicator component wrapping each step. Shows 4 steps with active/completed/pending states.
