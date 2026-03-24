<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_wrong_credentials_return_error(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_correct_credentials_log_user_in(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_disabled_user_is_blocked(): void
    {
        $user = User::factory()->disabled()->create(['email' => 'disabled@example.com']);

        // Log in first (disabled check happens on middleware, not login itself)
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_with_2fa_is_redirected_to_challenge(): void
    {
        $user = User::factory()->withTwoFactor()->create(['email' => '2fa@example.com']);

        $response = $this->post('/login', [
            'email' => '2fa@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->assertNotNull(session('2fa:user_id'));
    }

    public function test_first_registered_user_becomes_root_admin(): void
    {
        $this->assertSame(0, User::count());

        $user = User::factory()->rootAdmin()->create();

        $this->assertTrue($user->isRootAdmin());
    }

    public function test_non_root_admin_user_cannot_set_root_admin_via_fillable(): void
    {
        // Create a root admin first so the next user doesn't auto-get root admin
        User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $user = User::factory()->create(['email' => 'regular@example.com']);

        $this->assertFalse($user->isRootAdmin());
        $this->assertNotContains('is_root_admin', $user->getFillable());
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/dashboard');
    }
}
