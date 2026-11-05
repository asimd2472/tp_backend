<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_manage_users_permission_can_create_a_user_and_assign_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::create(['name' => 'content-editor', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'user_name' => 'New Member',
                'user_email' => 'new-member@example.com',
                'user_phone' => '555-0199',
                'user_password' => 'new-secure-password',
                'user_password_confirmation' => 'new-secure-password',
                'user_roles' => [$role->name],
            ])
            ->assertRedirect(route('admin.roles.index').'#user-access')
            ->assertSessionHas('success', 'User created successfully.');

        $user = User::where('email', 'new-member@example.com')->firstOrFail();
        $this->assertSame('New Member', $user->name);
        $this->assertSame('555-0199', $user->phone);
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertTrue($user->hasRole($role));
        $this->assertFalse($user->is_admin);
    }

    public function test_user_creation_requires_a_unique_email_and_confirmed_password(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($admin)
            ->from(route('admin.roles.index'))
            ->post(route('admin.users.store'), [
                'user_name' => 'New Member',
                'user_email' => 'existing@example.com',
                'user_password' => 'new-secure-password',
                'user_password_confirmation' => 'incorrect-password',
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHasErrors(['user_email', 'user_password']);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_user_with_manage_users_permission_can_edit_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'Updated Member',
                'email' => 'updated@example.com',
                'phone' => '555-0123',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('admin.roles.index').'#user-access')
            ->assertSessionHas('success', 'User updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Updated Member',
            'email' => 'updated@example.com',
            'phone' => '555-0123',
        ]);
        $this->assertTrue(Hash::check('new-secure-password', $target->fresh()->password));
    }

    public function test_user_edit_can_leave_the_password_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create([
            'password' => 'current-password',
        ]);
        $currentPassword = $target->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'phone' => '',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.roles.index').'#user-access');

        $this->assertSame($currentPassword, $target->fresh()->password);
    }

    public function test_user_with_manage_users_permission_can_delete_a_user_and_access_assignments(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();
        $role = Role::create(['name' => 'member-role', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'member-permission', 'guard_name' => 'web']);
        $target->assignRole($role);
        $target->givePermissionTo($permission);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.roles.index').'#user-access')
            ->assertSessionHas('success', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $target->id]);
        $this->assertDatabaseMissing('model_has_permissions', ['model_id' => $target->id]);
    }

    public function test_user_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.roles.index').'#user-access')
            ->assertSessionHas('error', 'You cannot delete your own account.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_edit_and_delete_require_manage_users_permission(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->syncPermissions(['manage_roles']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'Unauthorized',
                'email' => $target->email,
            ])
            ->assertForbidden();

        $this->post(route('admin.users.store'), [
            'user_name' => 'Unauthorized',
            'user_email' => 'unauthorized@example.com',
            'user_password' => 'new-secure-password',
            'user_password_confirmation' => 'new-secure-password',
        ])->assertForbidden();

        $this->delete(route('admin.users.destroy', $target))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_role_management_page_shows_user_edit_and_delete_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Create user')
            ->assertSee('Edit user')
            ->assertSee(route('admin.users.store'), false)
            ->assertSee(route('admin.users.update', $target), false)
            ->assertSee(route('admin.users.destroy', $target), false);
    }
}
