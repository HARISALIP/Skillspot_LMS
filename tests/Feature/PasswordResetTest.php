<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_invited_user_can_set_a_password_from_a_reset_token(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        $user = User::factory()->create(['email_verified_at' => null]);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('a-secure-password', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_a_failed_login_does_not_replace_a_legacy_password_hash(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Setting::set('human_verify_login', '0', 'security', 'boolean');
        $user = User::factory()->create(['email' => 'legacy@example.test']);
        $legacyHash = preg_replace('/^\\$2y\\$/', '$2b$', Hash::make('correct-password'));
        DB::table('users')->where('id', $user->id)->update(['password' => $legacyHash]);

        $this->post('/login', ['login' => $user->email, 'password' => 'incorrect-password'])
            ->assertSessionHasErrors('login');

        $this->assertSame($legacyHash, $user->fresh()->password);
    }

}
