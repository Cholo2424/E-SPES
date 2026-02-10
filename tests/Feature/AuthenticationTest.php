<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LoginLog;
use App\Models\LogoutLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login page is accessible.
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('login');
    }

    /**
     * Test successful login with coordinator role.
     */
    public function test_coordinator_can_login_successfully(): void
    {
        // Create a coordinator user
        $user = User::create([
            'name' => 'Test Coordinator',
            'email' => 'coordinator@test.com',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
        ]);

        // Attempt login
        $response = $this->post('/login', [
            'email' => 'coordinator@test.com',
            'password' => 'password123',
        ]);

        // Assert redirected to dashboard
        $response->assertRedirect(route('coordinator.dashboard'));
        $this->assertAuthenticatedAs($user);

        // Assert login was logged
        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->user_id,
            'email_entered' => 'coordinator@test.com',
            'status' => 'SUCCESS',
        ]);

        // Assert user's last login was updated
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
    }

    /**
     * Test login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();

        // Assert failed login was logged
        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->user_id,
            'email_entered' => 'test@test.com',
            'status' => 'FAILED',
        ]);
    }

    /**
     * Test non-coordinator cannot login.
     */
    public function test_non_coordinator_cannot_login(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();

        // Assert failed login was logged with reason
        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->user_id,
            'email_entered' => 'admin@test.com',
            'status' => 'FAILED',
            'failure_reason' => 'User is not a coordinator',
        ]);
    }

    /**
     * Test inactive user cannot login.
     */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@test.com',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();

        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->user_id,
            'status' => 'FAILED',
            'failure_reason' => 'Account is inactive',
        ]);
    }

    /**
     * Test account lockout after multiple failed attempts.
     */
    public function test_account_locks_after_multiple_failed_attempts(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'locktest@test.com',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
        ]);

        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'locktest@test.com',
                'password' => 'wrongpassword',
            ]);
        }

        $user->refresh();

        // Assert account is locked
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->isLocked());

        // Try to login with correct password - should still fail
        $response = $this->post('/login', [
            'email' => 'locktest@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /**
     * Test successful logout logs the session.
     */
    public function test_logout_logs_session(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'logout@test.com',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
        ]);

        // Login
        $this->actingAs($user);

        // Logout
        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        // Assert logout was logged
        $this->assertDatabaseHas('logout_logs', [
            'user_id' => $user->user_id,
        ]);
    }

    /**
     * Test coordinator dashboard is accessible to authenticated coordinators.
     */
    public function test_coordinator_dashboard_is_accessible(): void
    {
        $user = User::create([
            'name' => 'Test Coordinator',
            'email' => 'coordinator@test.com',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/coordinator/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('coordinator.dashboard');
        $response->assertViewHas('user');
        $response->assertViewHas('recentLogins');
        $response->assertViewHas('recentLogouts');
        $response->assertViewHas('stats');
    }

    /**
     * Test unauthenticated user cannot access dashboard.
     */
    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get('/coordinator/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * Test login history page is accessible.
     */
    public function test_login_history_page_is_accessible(): void
    {
        $user = User::create([
            'name' => 'Test Coordinator',
            'email' => 'coordinator@test.com',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/coordinator/login-history');

        $response->assertStatus(200);
        $response->assertViewIs('coordinator.login-history');
    }

    /**
     * Test logout history page is accessible.
     */
    public function test_logout_history_page_is_accessible(): void
    {
        $user = User::create([
            'name' => 'Test Coordinator',
            'email' => 'coordinator@test.com',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/coordinator/logout-history');

        $response->assertStatus(200);
        $response->assertViewIs('coordinator.logout-history');
    }
}
