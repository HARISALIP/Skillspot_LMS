<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
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
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
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

        // 4. Create or Update Super Admin User
        $email = env('SUPER_ADMIN_EMAIL', 'skillspot.in@gmail.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'SkillSpot#2026@Secure');

        $superAdmin = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => 'Skillspot Super Admin',
                'password'          => Hash::make($password),
                'portal_access'     => 'both',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        try {
            $superAdmin->syncRoles(['super-admin', 'admin']);
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
