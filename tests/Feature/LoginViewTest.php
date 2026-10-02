<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginViewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_page_renders_the_inertia_login_screen_for_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_a_user_can_authenticate_with_a_username_and_password(): void
    {
        $user = User::factory()->create(['username' => 'testadmin', 'password' => 'Password123!']);

        $this->post('/login', [
            'username' => 'testadmin',
            'password' => 'Password123!',
            'remember' => true,
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_local_seeder_rehashes_the_documented_administrator_password(): void
    {
        $admin = User::query()->firstOrCreate(
            ['username' => 'admin'],
            User::factory()->make([
                'username' => 'admin',
                'password' => 'legacy-password-value',
            ])->getAttributes(),
        );
        $admin->forceFill(['password' => 'legacy-password-value'])->save();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'Password123!',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_do_not_authenticate_a_user(): void
    {
        User::factory()->create(['username' => 'testadmin', 'password' => 'Password123!']);

        $this->from('/login')->post('/login', [
            'username' => 'testadmin',
            'password' => 'incorrect-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_an_inactive_user_cannot_authenticate(): void
    {
        User::factory()->create([
            'username' => 'archived.employee',
            'password' => 'Password123!',
            'is_active' => false,
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'archived.employee',
            'password' => 'Password123!',
        ])->assertRedirect('/login')->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'username' => 'throttled-user',
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('username');
        }

        $this->post('/login', [
            'username' => 'throttled-user',
            'password' => 'incorrect-password',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_registration_persists_a_username_that_can_be_used_to_login(): void
    {
        $this->post('/register', [
            'first_name' => 'Anne',
            'last_name' => 'Peralta',
            'username' => 'anne.peralta',
            'email' => 'anne.peralta@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('/dashboard');

        $user = User::query()->where('username', 'anne.peralta')->firstOrFail();

        $this->assertSame('Anne Peralta', $user->name);
        $this->assertAuthenticatedAs($user);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/PendingAccount'));

        $this->get('/employee')->assertForbidden();

        $this->post('/logout');

        $this->post('/login', [
            'username' => 'anne.peralta',
            'password' => 'Password123!',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_recovery_continues_to_identify_users_by_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'recover@example.test']);

        $this->post('/forgot-password', ['email' => 'recover@example.test'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
