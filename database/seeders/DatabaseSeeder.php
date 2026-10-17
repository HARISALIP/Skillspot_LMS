<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create System Roles ──────────────────────────────────────────
        $roles = ['super-admin', 'admin', 'teacher', 'student', 'vendor'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // ── 2. Create System Permissions ───────────────────────────────────
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
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // ── 3. Assign All Permissions to Super Admin & Admin ───────────────
        $allPermissions = Permission::all();

        try {
            $superAdminRole = Role::where('name', 'super-admin')->first();
            if ($superAdminRole) { $superAdminRole->syncPermissions($allPermissions); }
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) { $adminRole->syncPermissions($allPermissions); }
        } catch (\Throwable $t) {}

        // Clear Spatie Permission cache
        try { app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions(); } catch (\Throwable $t) {}

        // Seed demo courses without modifying user credentials.
        try {
            $this->call(DemoCoursesSeeder::class);
        } catch (\Throwable $t) {}
    }
}
