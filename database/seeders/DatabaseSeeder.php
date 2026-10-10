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

        try {
            $superAdminRole = Role::where('name', 'super-admin')->first();
            if ($superAdminRole) { $superAdminRole->syncPermissions($allPermissions); }
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) { $adminRole->syncPermissions($allPermissions); }
        } catch (\Throwable $t) {}

        // Clear Spatie Permission cache
        try { app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions(); } catch (\Throwable $t) {}

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
        try { $superAdmin->syncRoles(['super-admin', 'admin']); } catch (\Throwable $t) {}

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
        try { $teacher->syncRoles(['teacher', 'admin']); } catch (\Throwable $t) {}

        // ── 6. Create / Update Student Account ──────────────────────────────
        $studentPassword = env('STUDENT_PASSWORD') ?: 'Student#2026@Skillspot';
        $student = User::updateOrCreate(
            ['email' => 'student@skillspot.in'],
            [
                'name'              => 'Skillspot Student',
                'password'          => Hash::make($studentPassword),
                'portal_access'     => 'skillspot_only',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        try { $student->syncRoles(['student']); } catch (\Throwable $t) {}

        // ── 7. Re-hash any legacy / non-$2y$ passwords ($2b$, plain text) in users table ──────
        User::all()->each(function ($u) {
            if ($u->password && !str_starts_with($u->password, '$2y$')) {
                // If it's a known account, use default password
                if ($u->email === 'skillspot.in@gmail.com') {
                    $u->password = Hash::make('SkillSpot#2026@Secure');
                } elseif ($u->email === 'teacher@skillspot.in') {
                    $u->password = Hash::make('Teacher#2026@Skillspot');
                } elseif ($u->email === 'student@skillspot.in') {
                    $u->password = Hash::make('Student#2026@Skillspot');
                } else {
                    $u->password = Hash::make('Skillspot#2026');
                }
                $u->save();
            }
        });
    }
}
