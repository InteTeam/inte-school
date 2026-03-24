# Multi-Tenancy Architecture

**Pattern:** Global scope isolation via `HasSchoolScope` trait
**Isolation unit:** `school_id` (ULID) on every tenant-scoped model
**Session context:** `EnsureSchoolContext` middleware reads `current_school_id` from session

---

## How It Works

### 1. School context from session

`EnsureSchoolContext` middleware (alias: `school`) resolves which school the authenticated user is currently acting as. It reads `current_school_id` from the session, verifies the user belongs to that school, and binds it to the request.

If no valid school context exists the user is redirected to an onboarding or school-selection page.

### 2. HasSchoolScope trait

All tenant-scoped models use this trait:

```php
use App\Models\Concerns\HasSchoolScope;

class MyModel extends Model
{
    use HasSchoolScope;
    use HasUlids;
}
```

What it does:
- Registers `SchoolScope` as a global scope — all queries automatically include `WHERE school_id = ?`
- Overrides `creating` model event to auto-set `school_id` from the session context
- `school_id` must **never** be in `$fillable` — it is always set by the trait

### 3. Creating records

```php
// CORRECT — school_id set automatically from session context
MyModel::create(['title' => 'Example']);

// Also correct when school_id must be explicit (e.g. in seeders, tests)
MyModel::forceCreate(['school_id' => $school->id, 'title' => 'Example']);
```

### 4. Querying

```php
// Normal query — automatically scoped to current school
MyModel::where('status', 'active')->get();

// Equivalent to:
MyModel::where('school_id', session('current_school_id'))
       ->where('status', 'active')->get();
```

---

## Root Admin Bypass

Root admin users (`is_root_admin = true`) have **no school session context**. Any query against a `HasSchoolScope` model will match zero rows unless the scope is explicitly removed.

### Service layer bypass

```php
// In FeatureRequestService::listAll()
return FeatureRequest::withoutGlobalScope(SchoolScope::class)
    ->with(['school', 'submitter'])
    ->orderBy('created_at', 'desc')
    ->get();
```

### Controller bypass (route model binding fails for scoped models)

Root admin controllers must **never** use typed route model binding for scoped models — the global scope will 404 every time. Use `string $id` + explicit bypass:

```php
// WRONG — HasSchoolScope filters to school_id = null → 404
public function update(Request $request, FeatureRequest $featureRequest): RedirectResponse

// CORRECT
public function update(Request $request, string $featureRequestId): RedirectResponse
{
    $featureRequest = FeatureRequest::withoutGlobalScope(SchoolScope::class)
        ->findOrFail($featureRequestId);
    // ...
}
```

---

## Testing Multi-Tenancy

Every feature test **must** verify cross-school isolation. Standard pattern:

```php
public function test_school_a_cannot_see_school_b_data(): void
{
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    $adminA = User::factory()->create();
    $schoolA->users()->attach($adminA->id, [
        'id' => Str::ulid(), 'role' => 'admin',
        'accepted_at' => now(), 'invited_at' => now(),
    ]);

    // Create a record belonging to school B
    MyModel::forceCreate(['school_id' => $schoolB->id, /* ... */]);

    $response = $this->actingAs($adminA)
        ->withSession(['current_school_id' => $schoolA->id])
        ->get(route('admin.my-resource.index'));

    // School A admin must see zero records from school B
    $response->assertInertia(fn ($page) => $page->has('items', 0));
}
```

---

## Mandatory Indexes

Every tenant table must have:

```php
// In migration up()
$table->index(['school_id', 'created_at'], 'idx_school_created');

// If a status column exists:
$table->index(['school_id', 'status'], 'idx_school_status');
```

These exist because `HasSchoolScope` always prepends `school_id` to every WHERE clause. Without the composite index, full table scans occur even on small datasets as the number of schools grows.

---

## School Settings (JSONB)

School-level configuration lives in `schools.settings` (JSONB column). Never hardcode values that could vary per school. Read via:

```php
$school->settings['sms_timeout_seconds'] ?? 900
```

Write via `$school->update(['settings' => array_merge($school->settings, ['key' => $value])])`.

Cache invalidation is handled by `SchoolSettingsObserver`.
