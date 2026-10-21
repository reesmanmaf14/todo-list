<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Creates the "admin" and "user" roles and their permissions.
// Safe to run more than once: existing roles and permissions are reused, nothing is deleted.
// It never creates an admin account; promote an existing user with: php artisan users:make-admin <email>
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Spatie caches permissions; clear it so the changes below are seen immediately
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view all tasks',   // the task list shows every user's tasks
            'manage all tasks', // edit, complete and delete any task
            'assign tasks',     // choose who owns a task when creating or editing it
            'manage users',     // add user accounts (Add User page)
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])->syncPermissions($permissions);

        // Regular users need no extra permissions: owning a task is enough (see TaskPolicy)
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Accounts created before roles existed become regular users
        User::doesntHave('roles')->each(fn (User $user) => $user->assignRole('user'));
    }
}
