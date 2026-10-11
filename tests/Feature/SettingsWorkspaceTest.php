<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_grouped_settings_workspace(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('admin.settings'))
            ->assertOk()
            ->assertSeeText('System settings')
            ->assertSeeText('System Configuration')
            ->assertSeeText('Signup & Access')
            ->assertSeeText('Communication')
            ->assertSeeText('Email / SMTP')
            ->assertSeeText('Media & Storage')
            ->assertSeeText('Configuration')
            ->assertSeeText('License');
    }
}
