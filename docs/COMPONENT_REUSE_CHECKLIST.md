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

| Component | Import | Use for |
|---|---|---|
| `button` | `@/Components/ui/button` | All buttons — use `variant` prop for style |
| `input` | `@/Components/ui/input` | Text inputs |
| `textarea` | `@/Components/ui/textarea` | Multi-line text |
| `label` | `@/Components/ui/label` | Form labels |
| `checkbox` | `@/Components/ui/checkbox` | Checkboxes |
| `select` | `@/Components/ui/select` | Dropdowns |
| `dialog` | `@/Components/ui/dialog` | Modal dialogs |
| `alert-dialog` | `@/Components/ui/alert-dialog` | Confirmation dialogs (destructive actions) |
| `dropdown-menu` | `@/Components/ui/dropdown-menu` | Contextual menus |
| `badge` | `@/Components/ui/badge` | Status badges, labels |
| `card` | `@/Components/ui/card` | Content cards |
| `table` | `@/Components/ui/table` | Data tables |
| `tabs` | `@/Components/ui/tabs` | Tabbed navigation |
| `sheet` | `@/Components/ui/sheet` | Slide-in panels |
| `separator` | `@/Components/ui/separator` | Visual dividers |
| `popover` | `@/Components/ui/popover` | Floating content |
| `command` | `@/Components/ui/command` | Command palette / search |
| `avatar` | `@/Components/ui/avatar` | User/school avatars |
| `skeleton` | `@/Components/ui/skeleton` | Loading states |
| `toast` | via `use-toast` hook | Notification toasts |

---

## Atoms (`resources/js/Components/Atoms/`)

_Populated as atoms are built. Update this section in SOP Step 9._

| Component | Import | Use for | Added in feature |
|---|---|---|---|
| — | — | — | — |

---

## Molecules (`resources/js/Components/Molecules/`)

_Populated as molecules are built. Update this section in SOP Step 9._

| Component | Import | Use for | Added in feature |
|---|---|---|---|
| — | — | — | — |

---

## Organisms (`resources/js/Components/Organisms/`)

_Populated as organisms are built. Update this section in SOP Step 9._

| Component | Import | Use for | Added in feature |
|---|---|---|---|
| — | — | — | — |

---

## Layouts (`resources/js/layouts/`)

| Layout | Use for |
|---|---|
| `AuthLayout` | Login, register, 2FA, password reset, device registration |
| `SchoolLayout` | All authenticated school app pages (admin, teacher, support, student) |
| `ParentLayout` | Parent PWA views — minimal, mobile-first |

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

### Auth
| Component | Path | Notes |
|---|---|---|
| — | — | Populated after Phase 1 |

### Messaging
| Component | Path | Notes |
|---|---|---|
| — | — | Populated after Phase 2 |

### Attendance
| Component | Path | Notes |
|---|---|---|
| — | — | Populated after Phase 2 |

### Calendar & Scheduler
| Component | Path | Notes |
|---|---|---|
| — | — | Populated after Phase 3 |

### Tasks
| Component | Path | Notes |
|---|---|---|
| — | — | Populated after Phase 3 |

### Documents & RAG
| Component | Path | Notes |
|---|---|---|
| — | — | Populated after Phase 4 |

---

## How to Add a New Component to This List

When SOP Step 9 asks you to extract reusable components:

1. Confirm the component is used (or clearly reusable) across more than one feature
2. Add it to the correct section above with: component name, import path, what it's for, which feature introduced it
3. Commit the updated `COMPONENT_REUSE_CHECKLIST.md` alongside the feature

**Do not add components that are single-use or feature-specific** — those belong in `docs/features/{name}/components.md` only.
