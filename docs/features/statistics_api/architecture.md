# Statistics & API — Architecture

## Backend Layers

### Model: `SchoolApiKey`

- Traits: HasUlids, HasSchoolScope
- Key methods: `findByKey()` (hash + match), `hasPermission()`, `isExpired()`
- Casts: permissions (array), last_used_at (datetime), expires_at (datetime)
- Relations: creator (User), school (School)

### Service: `StatisticsService` (final)

- `getDashboard(school, period)` — aggregates all metric types for admin dashboard
- `getForApi(school, period, permissions)` — filtered by API key permissions
- `flushCache(schoolId)` — invalidates all stats keys for school
- Private aggregators: `getAttendanceStats()`, `getMessageStats()`, `getHomeworkStats()`, `getUserStats()`

### Middleware: `AuthenticateApiKey` (alias: `api_key`)

1. Reads `Authorization: Bearer <raw_key>` header
2. Hashes with SHA-256, queries `school_api_keys.key_hash`
3. Validates expiration
4. Updates `last_used_at` (non-blocking)
5. Attaches `SchoolApiKey` to `$request->attributes`
6. Returns 401 JSON on failure

### Controllers

| Controller | Methods | Purpose |
|---|---|---|
| `Admin/StatisticsController` | `index()` | Dashboard with period selector |
| `Admin/ApiKeyController` | `index()`, `store()`, `destroy()` | Key CRUD, one-time raw key display |
| `Api/StatsApiController` | `index()` | External API with permission filtering |

## Frontend Structure

### Pages

| Page | Layout | Role |
|---|---|---|
| `Admin/Statistics/Dashboard.tsx` | SchoolLayout | Stat cards with period selector (week/month/term) |
| `Admin/Settings/ApiKeys.tsx` | SchoolLayout | Key management, permission checkboxes, one-time key display |
