<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_admin_menu_route_requires_its_matching_permission(): void
    {
        $menus = [
            'view_dashboard' => 'admin.dashboard',
            'manage_roles' => 'admin.roles.index',
            'manage_products' => 'admin.products.index',
            'manage_french_door' => 'admin.products.french-door.index',
            'manage_windows' => 'admin.windows.index',
            'manage_blog' => 'admin.blog.index',
            'manage_dealers' => 'admin.dealers.index',
        ];

        foreach ($menus as $permission => $route) {
            $user = User::factory()->admin()->create();
            $user->syncPermissions([$permission]);

            $this->actingAs($user)
                ->get(route($route))
                ->assertOk();
        }
    }

    public function test_menu_links_and_routes_are_hidden_without_permission(): void
    {
        $user = User::factory()->admin()->create();
        $user->syncPermissions(['manage_dealers']);

        $this->actingAs($user)
            ->get(route('admin.dealers.index'))
            ->assertOk()
            ->assertSee('href="'.route('admin.dealers.index').'"', false)
            ->assertDontSee('href="'.route('admin.dashboard').'"', false)
            ->assertDontSee('href="'.route('admin.roles.index').'"', false)
            ->assertDontSee('href="'.route('admin.products.index').'"', false)
            ->assertDontSee('href="'.route('admin.blog.index').'"', false);

        $this->get(route('admin.blog.index'))->assertForbidden();
    }

    public function test_user_role_updates_require_manage_users_permission(): void
    {
        $user = User::factory()->admin()->create();
        $user->syncPermissions(['manage_roles']);
        $target = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.users.roles.update', $target), ['roles' => []])
            ->assertForbidden();
    }

    public function test_sign_in_redirects_to_the_first_permitted_menu(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'a-secure-password',
        ]);
        $user->syncPermissions(['manage_dealers']);

        $this->postJson(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'a-secure-password',
        ])
            ->assertOk()
            ->assertJsonPath('redirect', route('admin.dealers.index'));
    }

    public function test_sign_in_is_denied_when_no_menu_permission_is_assigned(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'a-secure-password',
        ]);
        $user->syncPermissions([]);

        $this->postJson(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'a-secure-password',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'No admin menu permissions have been assigned to this account.');

        $this->assertGuest();
    }
}
