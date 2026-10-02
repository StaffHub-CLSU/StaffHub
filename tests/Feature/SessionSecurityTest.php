<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_an_inactive_user_is_logged_out_before_accessing_a_protected_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_an_authenticated_page_is_not_stored_by_the_browser(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache');
    }
}
