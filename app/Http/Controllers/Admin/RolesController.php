<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesController extends Controller
{
    // ── Predefined system roles (cannot be deleted) ────────────────────
    const SYSTEM_ROLES = ['super-admin', 'admin', 'student'];

    // ── All available permissions grouped ─────────────────────────────
    const PERMISSION_GROUPS = [
        'Dashboard' => [
            'view dashboard',
        ],
        'Users' => [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
        ],
        'Courses' => [
            'view courses',
            'create courses',
            'edit courses',
            'delete courses',
            'publish courses',
        ],
        'Enrollments' => [
            'view enrollments',
            'manage enrollments',
        ],
        'Payments' => [
            'view payments',
            'issue refunds',
        ],
        'Certificates' => [
            'view certificates',
            'issue certificates',
            'revoke certificates',
        ],
        'Settings' => [
            'view settings',
            'edit settings',
        ],
        'Roles' => [
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
        ],
        'Reports' => [
            'view reports',
        ],
    ];

    // ── List roles ─────────────────────────────────────────────────────
    public function index()
    {
        $roles       = Role::withCount('users')->orderBy('id')->get();
        $permissions = Permission::orderBy('name')->get()->groupBy(function($p) {
            return explode(' ', $p->name, 2)[1] ?? 'other';
        });
        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    // ── Create form ────────────────────────────────────────────────────
    public function create()
    {
        $permissionGroups = self::PERMISSION_GROUPS;
        $allPerms         = Permission::orderBy('name')->get();
        $role             = null;
        return view('admin.roles.form', compact('permissionGroups', 'allPerms', 'role'));
    }

    // ── Store new role ─────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:60', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
        ]);

        $slug = strtolower(str_replace(' ', '-', trim($request->name)));
        $role = Role::create(['name' => $slug, 'guard_name' => 'web']);

        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.roles')
            ->with('success', "Role \"{$slug}\" created ✅");
    }

    // ── Edit form ──────────────────────────────────────────────────────
    public function edit(Role $role)
    {
        $permissionGroups = self::PERMISSION_GROUPS;
        $allPerms         = Permission::orderBy('name')->get();
        $rolePerms        = $role->permissions->pluck('name')->toArray();
        return view('admin.roles.form', compact('role', 'permissionGroups', 'allPerms', 'rolePerms'));
    }

    // ── Update role ────────────────────────────────────────────────────
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:60', 'unique:roles,name,'.$role->id],
            'permissions' => ['nullable', 'array'],
        ]);

        // Only allow rename if not a system role
        if (!in_array($role->name, self::SYSTEM_ROLES)) {
            $role->update(['name' => strtolower(str_replace(' ', '-', trim($request->name)))]);
        }

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.roles')
            ->with('success', "Role \"{$role->name}\" updated ✅");
    }

    // ── Delete role ────────────────────────────────────────────────────
    public function destroy(Role $role)
    {
        if (in_array($role->name, self::SYSTEM_ROLES)) {
            return back()->with('error', "System role \"{$role->name}\" cannot be deleted.");
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', "Cannot delete role with {$role->users()->count()} assigned users. Reassign them first.");
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('admin.roles')
            ->with('success', "Role \"{$name}\" deleted.");
    }

    // ── Seed all permissions ───────────────────────────────────────────
    public function seedPermissions()
    {
        $count = 0;
        foreach (self::PERMISSION_GROUPS as $group => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
                $count++;
            }
        }

        // Super-admin gets all permissions
        $superAdmin = Role::findByName('super-admin');
        $superAdmin->syncPermissions(Permission::all());

        // Admin gets most permissions (no delete users / roles)
        $admin = Role::findByName('admin');
        $admin->syncPermissions(
            Permission::whereNotIn('name', ['delete users','delete roles','edit settings','assign roles'])->get()
        );

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('admin.roles')
            ->with('success', "All {$count} permissions seeded. Super-Admin & Admin roles updated ✅");
    }
}
