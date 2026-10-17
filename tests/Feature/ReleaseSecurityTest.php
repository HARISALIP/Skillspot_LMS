<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReleaseSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_does_not_create_default_accounts_or_reset_existing_passwords(): void
    {
        $user = User::factory()->create(['email' => 'skillspot.in@gmail.com', 'password' => Hash::make('private-test-password')]);
        $password = $user->password;

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($password, $user->fresh()->password);
        $this->assertDatabaseMissing('users', ['email' => 'teacher@skillspot.in']);
        $this->assertDatabaseMissing('users', ['email' => 'student@skillspot.in']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_public_diagnostics_are_absent_and_r2_has_no_credential_fallbacks(): void
    {
        $this->assertFileDoesNotExist(public_path('db_test.php'));
        $this->assertFileDoesNotExist(public_path('run_migrations.php'));
        $config = file_get_contents(config_path('filesystems.php'));
        $this->assertStringContainsString("env('R2_ACCESS_KEY_ID')", $config);
        $this->assertStringContainsString("env('R2_SECRET_ACCESS_KEY')", $config);
        $this->assertStringContainsString("env('R2_ENDPOINT')", $config);
    }
}
