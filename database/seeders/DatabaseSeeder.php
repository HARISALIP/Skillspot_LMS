<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        $superAdminRole = Role::findByName('super-admin');
        $superAdminRole->syncPermissions($allPermissions);

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions($allPermissions);

        // Clear Spatie Permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 4. Create / Update Super Admin Account ──────────────────────────
        $superAdminPassword = env('SUPER_ADMIN_PASSWORD') ?: 'SkillSpot#2026@Secure';
        $superAdmin = User::updateOrCreate(
            ['email' => 'skillspot.in@gmail.com'],
            [
                'name'              => 'Skillspot Super Admin',
                'password'          => Hash::make($superAdminPassword),
                'portal_access'     => 'both',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles(['super-admin', 'admin']);

        // ── 5. Create / Update Teacher Account ──────────────────────────────
        $teacherPassword = env('TEACHER_PASSWORD') ?: 'Teacher#2026@Skillspot';
        $teacher = User::updateOrCreate(
            ['email' => 'teacher@skillspot.in'],
            [
                'name'              => 'Skillspot Instructor',
                'password'          => Hash::make($teacherPassword),
                'portal_access'     => 'both',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $teacher->syncRoles(['teacher', 'admin']);

        // ── 6. Create / Update Student Account ──────────────────────────────
        $studentPassword = env('STUDENT_PASSWORD') ?: 'Student#2026@Skillspot';
        $student = User::updateOrCreate(
            ['email' => 'student@skillspot.in'],
            [
                'name'              => 'Skillspot Student',
                'password'          => Hash::make($studentPassword),
                'portal_access'     => 'Skillspot_only',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $student->syncRoles(['student']);

        // ── 7. Re-hash any legacy / plain text passwords in users table ──────
        User::all()->each(function ($u) {
            if ($u->password && !str_starts_with($u->password, '$2y$') && !str_starts_with($u->password, '$2b$') && !str_starts_with($u->password, '$2a$')) {
                $u->password = Hash::make($u->password);
                $u->save();
            }
        });
    }
}
