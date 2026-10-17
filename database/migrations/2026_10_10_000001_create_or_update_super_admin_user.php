<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure system roles exist
        $roles = ['super-admin', 'admin', 'teacher', 'student', 'vendor'];
        foreach ($roles as $r) {
            try {
                Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
            } catch (\Throwable $e) {}
        }

        // 2. Ensure system permissions exist
        $permissions = [
            'view dashboard',
            'view users', 'create users', 'edit users', 'delete users', 'assign roles',
            'view courses', 'create courses', 'edit courses', 'delete courses', 'publish courses',
            'view enrollments', 'manage enrollments',
            'view payments', 'issue refunds',
            'view certificates', 'issue certificates', 'revoke certificates',
            'view settings', 'edit settings',
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'view reports',
        ];

        foreach ($permissions as $p) {
            try {
                Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            } catch (\Throwable $e) {}
        }

        // 3. Assign all permissions to super-admin & admin
        try {
            $allPermissions = Permission::all();
            $superAdminRole = Role::where('name', 'super-admin')->first();
            if ($superAdminRole) {
                $superAdminRole->syncPermissions($allPermissions);
            }
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->syncPermissions($allPermissions);
            }
        } catch (\Throwable $e) {}

        // Clear Spatie Permission cache
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {}

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep user or soft action
    }
};
