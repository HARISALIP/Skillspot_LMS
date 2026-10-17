<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUsersPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_grant_or_manage_super_admin_access(): void
    {
        foreach (['admin', 'super-admin', 'student'] as $role) Role::findOrCreate($role, 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $learner = User::factory()->create();
        $learner->assignRole('student');

        $this->actingAs($admin)->get(route('admin.users'))
            ->assertOk()
            ->assertDontSee('aria-label="Delete '.$superAdmin->name.'"', false)
            ->assertDontSee('aria-label="Edit '.$superAdmin->name.'"', false);
        $this->get(route('admin.users.create'))->assertOk()->assertDontSee('value="super-admin"', false);
        $this->post(route('admin.users.store'), [
            'name' => 'Unauthorized Super Admin', 'email' => 'unauthorized@example.test',
            'country' => 'IN', 'password' => 'ExamplePassword2026', 'role' => 'super-admin',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.test']);

        $this->put(route('admin.users.update', $learner), [
            'name' => $learner->name, 'email' => $learner->email,
            'country' => 'IN', 'role' => 'super-admin',
        ])->assertSessionHasErrors('role');
        $this->assertFalse($learner->fresh()->hasRole('super-admin'));
        $this->get(route('admin.users.edit', $superAdmin))->assertForbidden();
        $this->put(route('admin.users.update', $superAdmin), [
            'name' => 'Changed', 'email' => $superAdmin->email,
            'country' => 'IN', 'role' => 'student',
        ])->assertForbidden();
        $this->post(route('admin.users.access.portal', $superAdmin), [
            'portal_access' => 'vendor_only',
        ])->assertForbidden();
        $this->delete(route('admin.users.destroy', $superAdmin))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_admin_cannot_edit_system_role_permissions_or_seed_them(): void
    {
        $adminRole = Role::findOrCreate('admin', 'web');
        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.roles.edit', $superAdminRole))->assertForbidden();
        $this->put(route('admin.roles.update', $superAdminRole), [
            'name' => 'super-admin', 'permissions' => [],
        ])->assertForbidden();
        $this->get(route('admin.roles.edit', $adminRole))->assertForbidden();
        $this->post(route('admin.roles.seed'))->assertForbidden();
    }
}
