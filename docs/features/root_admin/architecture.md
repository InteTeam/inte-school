# Root Admin — Architecture

## Backend Layers

### Controllers

| Controller | Methods | Purpose |
|---|---|---|
| `RootAdmin/DashboardController` | `index()` | Platform stats: school_count, active_school_count, user_count |
| `RootAdmin/SchoolController` | `index()` | All schools including soft-deleted, ordered by created_at desc |
| `RootAdmin/FeatureRequestController` | `index()`, `updateStatus()` | Cross-school feature request feed + status lifecycle |

### No Dedicated Models

Root admin uses existing models with scope bypass:
- `School` — `withTrashed()` for soft-deleted visibility
- `FeatureRequest` — `withoutGlobalScopes()` for cross-school access
- `User` — aggregate counts

### Service: `FeatureRequestService` (final)

- `submit()` — create request (school admin), enforces 2000 char limit
- `listForSchool()` — single school requests
- `listAll()` — cross-school feed (root admin, bypasses SchoolScope)
- `updateStatus()` — lifecycle transitions

## Frontend Structure

### Pages

| Page | Layout | Purpose |
|---|---|---|
| `RootAdmin/Dashboard.tsx` | RootAdminLayout | Three stat cards |
| `RootAdmin/Schools/Index.tsx` | RootAdminLayout | School table with status badges |
| `RootAdmin/FeatureRequests/Index.tsx` | RootAdminLayout | Cross-school feed with inline status dropdown |

### Layout: `RootAdminLayout`

Separate from SchoolLayout — no school context, root-admin-specific navigation.
