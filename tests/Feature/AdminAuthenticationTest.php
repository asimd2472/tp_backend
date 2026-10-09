<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_available(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('TATA Pravesh')
            ->assertSee('Sign in securely');
    }

    public function test_admin_can_sign_in_and_open_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'a-secure-password',
        ]);

        $this->postJson(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'a-secure-password',
        ])
            ->assertOk()
            ->assertJsonPath('redirect', route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Account directory')
            ->assertSee($admin->email);
    }

    public function test_standard_members_cannot_sign_in_to_the_admin_panel(): void
    {
        $member = User::factory()->create([
            'password' => 'a-secure-password',
        ]);

        $this->postJson(route('admin.login.store'), [
            'email' => $member->email,
            'password' => 'a-secure-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'These credentials could not be verified for an administrator account.');

        $this->assertGuest();
    }

    public function test_admin_routes_require_an_administrator_account(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_sign_out(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}
