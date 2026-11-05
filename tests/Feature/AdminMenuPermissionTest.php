<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
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
            ->assertJsonPath('redirect', route('admin.two-factor.challenge'));

        $this->postJson(route('admin.two-factor.challenge.verify'), [
            'code' => (new Google2FA)->getCurrentOtp($user->two_factor_secret),
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

    public function test_first_login_enrolls_authenticator_before_authentication(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'a-secure-password',
        ]);
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_last_used_step' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
        $this->assertGuest();

        $this->postJson(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'a-secure-password',
        ])->assertOk()
            ->assertJsonPath('redirect', route('admin.two-factor.setup'));

        $this->assertGuest();
        $this->get(route('admin.two-factor.setup'))
            ->assertOk()
            ->assertSee('Authenticator setup QR code')
            ->assertSee('setup key manually');

        $secret = Crypt::decryptString(session('admin_two_factor.secret'));
        $recoveryCodes = $this->postJson(route('admin.two-factor.setup.confirm'), [
            'code' => (new Google2FA)->oathTotp($secret, (new Google2FA)->getTimestamp() - 2),
        ])->assertOk()
            ->assertJsonPath('redirect', route('admin.dashboard'))
            ->assertJsonCount(8, 'recovery_codes')
            ->json('recovery_codes');

        $user->refresh();
        $this->assertNotSame($secret, DB::table('users')->where('id', $user->id)->value('two_factor_secret'));
        $this->assertSame(8, count($user->two_factor_recovery_codes));
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertAuthenticatedAs($user);
        $this->assertCount(8, $recoveryCodes);
    }

    public function test_totp_codes_cannot_be_reused(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'a-secure-password',
        ]);
        $code = (new Google2FA)->getCurrentOtp($user->two_factor_secret);

        $this->postJson(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'a-secure-password',
        ])->assertJsonPath('redirect', route('admin.two-factor.challenge'));

        $this->postJson(route('admin.two-factor.challenge.verify'), ['code' => $code])
            ->assertOk();

        $this->post(route('admin.logout'));
        $this->postJson(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'a-secure-password',
        ])->assertJsonPath('redirect', route('admin.two-factor.challenge'));

        $this->postJson(route('admin.two-factor.challenge.verify'), ['code' => $code])
            ->assertUnprocessable();
    }

    public function test_recovery_code_can_only_be_used_once(): void
    {
        $user = User::factory()->admin()->create([
            'password' => 'a-secure-password',
        ]);
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_last_used_step' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->postJson(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'a-secure-password',
        ])->assertJsonPath('redirect', route('admin.two-factor.setup'));

        $setupSecret = Crypt::decryptString(session('admin_two_factor.secret'));
        $recoveryCode = $this->postJson(route('admin.two-factor.setup.confirm'), [
            'code' => (new Google2FA)->oathTotp($setupSecret, (new Google2FA)->getTimestamp() - 2),
        ])->assertOk()
            ->json('recovery_codes.0');

        $this->post(route('admin.logout'));
        $this->postJson(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'a-secure-password',
        ])->assertJsonPath('redirect', route('admin.two-factor.challenge'));

        $this->postJson(route('admin.two-factor.challenge.verify'), ['code' => $recoveryCode])
            ->assertOk();

        $user->refresh();
        $this->assertCount(7, $user->two_factor_recovery_codes);

        $this->post(route('admin.logout'));
        $this->postJson(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'a-secure-password',
        ])->assertJsonPath('redirect', route('admin.two-factor.challenge'));

        $this->postJson(route('admin.two-factor.challenge.verify'), ['code' => $recoveryCode])
            ->assertUnprocessable();
    }
}
