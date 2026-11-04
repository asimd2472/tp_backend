<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'manage_users',
        'manage_roles',
        'manage_settings',
        'view_dashboard',
        'manage_products',
        'manage_french_door',
        'manage_windows',
        'manage_blog',
        'manage_dealers',
    ];

    private array $newPermissions = [
        'manage_products',
        'manage_french_door',
        'manage_windows',
        'manage_blog',
        'manage_dealers',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $permissions = Permission::query()
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->get();

        Role::query()
            ->whereIn('name', ['super-admin', 'admin'])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', $this->newPermissions)
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Permission $permission) => $permission->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
