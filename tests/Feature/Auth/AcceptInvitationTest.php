<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\School;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AcceptInvitationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake HIBP API to avoid external dependency in tests
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $this->school = School::factory()->create();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
    }

    private function inviteTeacher(string $email = 'teacher@example.com'): User
    {
        $service = app(UserManagementService::class);

        return $service->inviteStaff($this->school, [
            'name' => 'New Teacher',
            'email' => $email,
            'role' => 'teacher',
        ], $this->admin);
    }

    private function getToken(User $user): string
    {
        return DB::table('school_user')
            ->where('user_id', $user->id)
            ->where('school_id', $this->school->id)
            ->value('invitation_token');
    }

    // --- Guest access ---

    public function test_invitation_page_requires_guest(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/invitation/accept?token=any');

        $response->assertRedirect('/dashboard');
    }

    public function test_invitation_page_redirects_to_login_without_token(): void
    {
        $response = $this->get('/invitation/accept');

        $response->assertRedirect(route('login'));
    }

    public function test_invitation_page_renders_for_valid_token(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        $response = $this->get("/invitation/accept?token={$token}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/AcceptInvitation')
            ->where('token', $token)
        );
    }

    // --- Token validation ---

    public function test_invalid_token_redirects_with_error(): void
    {
        $response = $this->get('/invitation/accept?token=invalid-token-here');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('type', 'error');
    }

    public function test_expired_token_redirects_with_error(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        DB::table('school_user')
            ->where('user_id', $teacher->id)
            ->where('school_id', $this->school->id)
            ->update(['invitation_expires_at' => now()->subDay()]);

        $response = $this->get("/invitation/accept?token={$token}");

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('type', 'error');
    }

    // --- Acceptance flow ---

    public function test_invitation_can_be_accepted_via_http(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        $response = $this->post('/invitation/accept', [
            'token' => $token,
            'name' => 'Jane Teacher',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'name' => 'Jane Teacher',
        ]);

        $pivot = DB::table('school_user')
            ->where('user_id', $teacher->id)
            ->where('school_id', $this->school->id)
            ->first();

        $this->assertNotNull($pivot->accepted_at);
        $this->assertNull($pivot->invitation_token);
    }

    public function test_role_is_preserved_after_acceptance(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        $this->post('/invitation/accept', [
            'token' => $token,
            'name' => 'Jane Teacher',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $pivot = DB::table('school_user')
            ->where('user_id', $teacher->id)
            ->where('school_id', $this->school->id)
            ->first();

        $this->assertSame('teacher', $pivot->role);
    }

    // --- Single use ---

    public function test_token_cannot_be_used_twice(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        // First acceptance
        $this->post('/invitation/accept', [
            'token' => $token,
            'name' => 'Jane Teacher',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        // Second attempt with same token
        $response = $this->post('/invitation/accept', [
            'token' => $token,
            'name' => 'Different Name',
            'password' => 'AnotherPass123!',
            'password_confirmation' => 'AnotherPass123!',
        ]);

        $response->assertSessionHasErrors('token');

        // Name should remain from first acceptance
        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'name' => 'Jane Teacher',
        ]);
    }

    public function test_viewing_accepted_token_redirects_with_error(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        // Accept the invitation
        $this->post('/invitation/accept', [
            'token' => $token,
            'name' => 'Jane Teacher',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        // Try to view the acceptance page again — token is now null so can't find it
        $response = $this->get("/invitation/accept?token={$token}");

        $response->assertRedirect(route('login'));
    }

    // --- Password validation ---

    public function test_password_must_be_at_least_12_characters(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        $response = $this->post('/invitation/accept', [
            'token' => $token,
            'name' => 'Jane Teacher',
            'password' => 'Short1!',
            'password_confirmation' => 'Short1!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_must_have_mixed_case(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        $response = $this->post('/invitation/accept', [
            'token' => $token,
            'name' => 'Jane Teacher',
            'password' => 'alllowercase123!',
            'password_confirmation' => 'alllowercase123!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_must_contain_number(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        $response = $this->post('/invitation/accept', [
            'token' => $token,
            'name' => 'Jane Teacher',
            'password' => 'NoNumbersHere!',
            'password_confirmation' => 'NoNumbersHere!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_confirmation_must_match(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        $response = $this->post('/invitation/accept', [
            'token' => $token,
            'name' => 'Jane Teacher',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPass123!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_name_is_required(): void
    {
        $teacher = $this->inviteTeacher();
        $token = $this->getToken($teacher);

        $response = $this->post('/invitation/accept', [
            'token' => $token,
            'name' => '',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
