# Component Reuse Checklist

**How to use:** Before planning any frontend component for a new feature, check this list first. If a component you need already exists, use it — do not recreate it. Update this file when new reusable components are added.

---

## Rule
> ❌ Never create a component that already exists.
> ✅ Always check here before planning new components.
> ✅ Update this file when a new reusable component is added (SOP Step 9).

---

## shadcn/ui Base Components (`resources/js/Components/ui/`)

These are never modified directly. Customise via props or wrapper components.

> **Installed** = file exists in `resources/js/Components/ui/`. **Planned** = listed in shadcn registry but not yet installed.

### Installed

| Component | Import | Use for | Used by features |
|---|---|---|---|
| `avatar` | `@/Components/ui/avatar` | User/school avatars | _not yet used in pages_ |
| `badge` | `@/Components/ui/badge` | Status badges, labels | Tasks (template indicator), Admin (student/school status), RootAdmin (school status) |
| `button` | `@/Components/ui/button` | All buttons — `variant` prop for style | Auth, Onboarding, Admin, Teacher, Settings — nearly every page |
| `card` | `@/Components/ui/card` | Content cards | Auth (login/register), RootAdmin (dashboard stats) |
| `dialog` | `@/Components/ui/dialog` | Modal dialogs | _not yet used in pages_ |
| `dropdown-menu` | `@/Components/ui/dropdown-menu` | Contextual menus | _not yet used in pages_ |
| `input` | `@/Components/ui/input` | Text inputs | Auth, Onboarding, Admin Settings, Teacher forms |
| `label` | `@/Components/ui/label` | Form labels | Paired with every Input across all form pages |
| `select` | `@/Components/ui/select` | Dropdowns | Teacher Messages (class select), Teacher Homework (class select), Admin Messages |
| `switch` | `@/Components/ui/switch` | Toggle switch | Admin Settings General (feature toggles) |
| `textarea` | `@/Components/ui/textarea` | Multi-line text | Teacher Messages (compose body), Teacher Homework (description) |
| `toast` | `@/Components/ui/toast` | Notification toasts | Available via Toaster, flash message display |
| `toaster` | `@/Components/ui/toaster` | Toast display container | Mounted in layouts for flash messages |

### Planned (not yet installed)

| Component | Import | Use for |
|---|---|---|
| `checkbox` | `@/Components/ui/checkbox` | Checkboxes |
| `alert-dialog` | `@/Components/ui/alert-dialog` | Confirmation dialogs (destructive actions) |
| `table` | `@/Components/ui/table` | Data tables |
| `tabs` | `@/Components/ui/tabs` | Tabbed navigation |
| `sheet` | `@/Components/ui/sheet` | Slide-in panels |
| `separator` | `@/Components/ui/separator` | Visual dividers |
| `popover` | `@/Components/ui/popover` | Floating content |
| `command` | `@/Components/ui/command` | Command palette / search |
| `skeleton` | `@/Components/ui/skeleton` | Loading states |

---

## Atoms (`resources/js/Components/Atoms/`)

_No custom atoms yet — all atomic components delegate to shadcn/ui. Update this section when atoms are extracted._

| Component | Import | Use for | Added in feature |
|---|---|---|---|
| — | — | — | — |

---

## Molecules (`resources/js/Components/Molecules/`)

| Component | Import | Use for | Props | Added in feature |
|---|---|---|---|---|
| `WizardShell` | `@/Components/Molecules/WizardShell` | Multi-step wizard with progress indicator | `steps: {label, number}[]`, `currentStep: number`, `children`, `title: string` | Onboarding |

---

## Organisms (`resources/js/Components/Organisms/`)

| Component | Import | Use for | Props | Added in feature |
|---|---|---|---|---|
| `SchoolNavBar` | `@/Components/Organisms/SchoolNavBar` | Top navigation bar with school branding, dashboard link, user logout | Uses Inertia `auth.user` page props | SchoolLayout (all school pages) |
| `TodoList` | `@/Components/Organisms/TodoList` | Draggable, sortable todo/task items with checkbox toggle and deadline display | `taskId: string`, `items: TodoItem[]` | Tasks (Teacher/Tasks/Index) |

**TodoList external deps:** `@dnd-kit/core`, `@dnd-kit/sortable`, `@dnd-kit/utilities`

---

## Layouts (`resources/js/layouts/`)

| Layout | Use for | Injects |
|---|---|---|
| `AuthLayout` | Login, register, 2FA, password reset, device registration, invitation acceptance | Centred card with logo + app branding |
| `SchoolLayout` | All authenticated school pages (admin, teacher, support, student) | `SchoolNavBar` organism at top |
| `ParentLayout` | Parent PWA views — minimal, mobile-first (max-width 32rem) | Compact header |
| `RootAdminLayout` | Root admin panel (dashboard, schools, feature requests, legal templates) | Nav links to dashboard + schools |

---

## Hooks (`resources/js/hooks/`)

| Hook | Import | Use for |
|---|---|---|
| `useSessionHeartbeat` | `@/hooks/useSessionHeartbeat` | Prevent 419 session expiry on long-lived pages |
| `useIsMobile` | `@/hooks/useIsMobile` | Responsive layout decisions |
| `use-toast` | `@/hooks/use-toast` | Show toast notifications |
| `useVapidPush` | `@/hooks/useVapidPush` | Register device for VAPID push notifications |

---

## Utilities (`resources/js/lib/`)

| Utility | Import | Use for |
|---|---|---|
| `cn()` | `@/lib/utils` | Merge Tailwind class names conditionally |

---

## Feature-Specific Component Registry

_Updated in SOP Step 9 after each feature is completed._

### Auth (7 pages)
| Component | Path | Notes |
|---|---|---|
| Button, Input, Label | `ui/` | Login, ForgotPassword, ResetPassword forms |
| Card, CardHeader, CardTitle, CardDescription, CardContent | `ui/card` | Auth page containers (via AuthLayout) |
| — | `Auth/TwoFactor.tsx` | 2FA challenge page |
| — | `Auth/AcceptInvitation.tsx` | Token-based invitation acceptance |
| — | `Auth/DeviceRegistration.tsx` | VAPID push device registration |

### Onboarding (4 pages)
| Component | Path | Notes |
|---|---|---|
| WizardShell | `Molecules/WizardShell` | Step progress indicator on all 4 pages |
| Button, Input, Label | `ui/` | Form controls on each step |

### Messaging (4 pages: Admin + Teacher compose, Parent inbox + thread)
| Component | Path | Notes |
|---|---|---|
| Select, SelectContent, SelectItem, SelectTrigger, SelectValue | `ui/select` | Class/recipient selection |
| Textarea | `ui/textarea` | Message body compose |
| Button, Label | `ui/` | Form actions |

### Attendance (1 page: Teacher register)
| Component | Path | Notes |
|---|---|---|
| Button | `ui/button` | Mark attendance actions |
| Badge | `ui/badge` | Status indicators (present/absent/late) |

### Calendar (2 pages: Admin + Teacher calendar index)
| Component | Path | Notes |
|---|---|---|
| Button | `ui/button` | Event actions |
| Card | `ui/card` | Calendar event cards |

### Tasks (2 pages: Teacher index + homework create)
| Component | Path | Notes |
|---|---|---|
| TodoList | `Organisms/TodoList` | Draggable task items on Teacher/Tasks/Index |
| Badge | `ui/badge` | Template indicator on todo items |
| Select, SelectContent, SelectItem, SelectTrigger, SelectValue | `ui/select` | Class selection on homework create |
| Button, Input, Label, Textarea | `ui/` | Form controls |

### Documents & RAG (3 pages: Admin index + upload, RAG query)
| Component | Path | Notes |
|---|---|---|
| Button | `ui/button` | Upload + delete actions |
| Badge | `ui/badge` | Processing status indicators |

### Settings (6 pages)
| Component | Path | Notes |
|---|---|---|
| Button, Input, Label | `ui/` | All settings forms |
| Switch | `ui/switch` | Feature toggles in General settings |
| Card | `ui/card` | Settings section containers |

### Root Admin (4 pages)
| Component | Path | Notes |
|---|---|---|
| Badge | `ui/badge` | School active/inactive status |
| Card | `ui/card` | Dashboard stats cards |
| Button | `ui/button` | Actions |

### Parent Views (6 pages — ParentLayout)
| Component | Path | Notes |
|---|---|---|
| — | — | Minimal, layout-driven — no custom components yet |

### Student Views (4 pages)
| Component | Path | Notes |
|---|---|---|
| — | — | Minimal, layout-driven — no custom components yet |

---

## How to Add a New Component to This List

When SOP Step 9 asks you to extract reusable components:

1. Confirm the component is used (or clearly reusable) across more than one feature
2. Add it to the correct section above with: component name, import path, what it's for, which feature introduced it
3. Commit the updated `COMPONENT_REUSE_CHECKLIST.md` alongside the feature

**Do not add components that are single-use or feature-specific** — those belong in `docs/features/{name}/components.md` only.
