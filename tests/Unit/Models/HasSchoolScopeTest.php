<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Concerns\HasSchoolScope;
use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class HasSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a minimal table for our stub model
        Schema::create('stub_items', function ($table) {
            $table->string('id')->primary();
            $table->string('school_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('stub_items');
        parent::tearDown();
    }

    public function test_global_scope_is_registered(): void
    {
        $scopes = (new StubItem)->getGlobalScopes();

        $this->assertArrayHasKey(SchoolScope::class, $scopes);
    }

    public function test_auto_sets_school_id_from_session_on_create(): void
    {
        session(['current_school_id' => 'school-abc']);

        $item = StubItem::withoutGlobalScope(SchoolScope::class)->create(['id' => 'item-1', 'name' => 'Test']);

        $this->assertSame('school-abc', $item->school_id);
    }

    public function test_does_not_overwrite_explicitly_set_school_id(): void
    {
        session(['current_school_id' => 'school-abc']);

        $item = StubItem::withoutGlobalScope(SchoolScope::class)->create([
            'id' => 'item-2',
            'name' => 'Test',
            'school_id' => 'school-xyz',
        ]);

        $this->assertSame('school-xyz', $item->school_id);
    }

    public function test_scope_for_school_bypasses_global_scope(): void
    {
        // Seed two records for different schools
        StubItem::withoutGlobalScope(SchoolScope::class)->create(['id' => 'item-a', 'name' => 'A', 'school_id' => 'school-1']);
        StubItem::withoutGlobalScope(SchoolScope::class)->create(['id' => 'item-b', 'name' => 'B', 'school_id' => 'school-2']);

        $results = StubItem::forSchool('school-1')->get();

        $this->assertCount(1, $results);
        $this->assertSame('school-1', $results->first()->school_id);
    }

    public function test_global_scope_filters_by_session_school(): void
    {
        StubItem::withoutGlobalScope(SchoolScope::class)->create(['id' => 'item-c', 'name' => 'C', 'school_id' => 'school-1']);
        StubItem::withoutGlobalScope(SchoolScope::class)->create(['id' => 'item-d', 'name' => 'D', 'school_id' => 'school-2']);

        // Authenticate a user and set session school
        $user = new \App\Models\User(['id' => 'user-1', 'name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret']);
        $this->actingAs($user);
        session(['current_school_id' => 'school-1']);

        $results = StubItem::all();

        $this->assertCount(1, $results);
        $this->assertSame('school-1', $results->first()->school_id);
    }

    public function test_global_scope_returns_empty_when_no_session_school(): void
    {
        StubItem::withoutGlobalScope(SchoolScope::class)->create(['id' => 'item-e', 'name' => 'E', 'school_id' => 'school-1']);

        $user = new \App\Models\User(['id' => 'user-2', 'name' => 'Test', 'email' => 'test2@example.com', 'password' => 'secret']);
        $this->actingAs($user);
        session()->forget('current_school_id');

        $results = StubItem::all();

        $this->assertCount(0, $results);
    }
}

/**
 * Minimal stub model for testing the trait in isolation.
 */
class StubItem extends Model
{
    use HasSchoolScope;

    protected $table = 'stub_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'name', 'school_id'];
}
