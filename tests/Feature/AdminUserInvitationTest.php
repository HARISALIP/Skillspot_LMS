<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserInvitationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('student', 'web');
        Role::findOrCreate('teacher', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    private function verifiedMail(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test', 'mail.from.address' => 'no-reply@example.test']);
        Setting::set('mail_invites_verified_at', now()->toIso8601String());
    }

    public function test_unverified_mail_blocks_form_and_direct_creation_even_with_legacy_fields(): void
    {
        $admin = $this->admin();
        config(['mail.default' => 'log']);
        Notification::fake();

        $this->actingAs($admin)->get(route('admin.users.create'))
            ->assertOk()->assertSee('Email Accounts')->assertSee('disabled', false);
        $this->post(route('admin.users.store'), [
            'name' => 'Blocked Person', 'email' => 'blocked@example.test', 'country' => 'IN',
            'role' => 'student', 'password' => 'SharedPassword2026', 'send_welcome' => '1',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.test']);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        Notification::assertNothingSent();
    }

    public function test_verified_mail_invites_each_allowed_role_without_using_submitted_password(): void
    {
        $admin = $this->admin();
        $this->verifiedMail();
        Notification::fake();

        $this->actingAs($admin)->get(route('admin.users.create'))
            ->assertOk()->assertSee('one-time link')->assertDontSee('name="password"', false)
            ->assertDontSee('name="send_welcome"', false)
            ->assertSeeText('Create User & Send Invite')
            ->assertDontSeeText('Password must be at least 8 characters')
            ->assertDontSeeText('User will receive a welcome email if enabled');

        foreach (['student', 'teacher', 'admin'] as $role) {
            $email = $role.'-invited@example.test';
            $this->actingAs($admin)->post(route('admin.users.store'), [
                'name' => 'Invited '.$role, 'email' => $email, 'country' => 'IN',
                'role' => $role, 'password' => 'SharedPassword2026', 'send_welcome' => '1',
            ])->assertRedirect(route('admin.users'))->assertSessionHas('success');

            $invited = User::where('email', $email)->firstOrFail();
            $this->assertTrue($invited->hasRole($role));
            $this->assertFalse(Hash::check('SharedPassword2026', $invited->password));
            Notification::assertSentToTimes($invited, ResetPassword::class, 1);
        }

        Notification::assertCount(3);
    }

    public function test_failed_invitation_removes_account_role_and_token(): void
    {
        $admin = $this->admin();
        $this->verifiedMail();
        $existingRoleAssignments = DB::table('model_has_roles')->count();
        Password::shouldReceive('sendResetLink')->once()->andReturnUsing(function (array $credentials) {
            DB::table('password_reset_tokens')->insert([
                'email' => $credentials['email'], 'token' => Hash::make('unused'), 'created_at' => now(),
            ]);
            return Password::INVALID_USER;
        });

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Failed Invite', 'email' => 'failed@example.test', 'country' => 'IN', 'role' => 'student',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'failed@example.test']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'failed@example.test']);
        $this->assertSame($existingRoleAssignments, DB::table('model_has_roles')->count());
    }

    public function test_duplicate_email_does_not_send_another_invitation(): void
    {
        $admin = $this->admin();
        $this->verifiedMail();
        Notification::fake();
        User::factory()->create(['email' => 'existing@example.test']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Duplicate', 'email' => 'existing@example.test', 'country' => 'IN', 'role' => 'student',
        ])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_mail_exception_removes_a_partially_created_invitation(): void
    {
        $admin = $this->admin();
        $this->verifiedMail();
        $existingRoleAssignments = DB::table('model_has_roles')->count();
        Password::shouldReceive('sendResetLink')->once()->andReturnUsing(function (array $credentials) {
            DB::table('password_reset_tokens')->insert([
                'email' => $credentials['email'], 'token' => Hash::make('unused'), 'created_at' => now(),
            ]);
            throw new \RuntimeException('Simulated transport failure');
        });

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Transport Failed', 'email' => 'transport@example.test', 'country' => 'IN', 'role' => 'student',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'transport@example.test']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'transport@example.test']);
        $this->assertSame($existingRoleAssignments, DB::table('model_has_roles')->count());
    }

    public function test_invitation_link_sets_password_once_and_expires_after_an_hour(): void
    {
        $admin = $this->admin();
        $this->verifiedMail();
        Notification::fake();

        $invite = function (string $email) use ($admin): array {
            $this->actingAs($admin)->post(route('admin.users.store'), [
                'name' => 'Invited Learner', 'email' => $email, 'country' => 'IN', 'role' => 'student',
            ])->assertRedirect(route('admin.users'));
            $user = User::where('email', $email)->firstOrFail();
            $token = null;
            Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
                $token = $notification->token;
                return true;
            });
            return [$user, $token];
        };

        [$user, $token] = $invite('first-invite@example.test');
        $this->assertNotNull($token);
        $this->post(route('logout'));
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email,
            'password' => 'ChosenPassword2026', 'password_confirmation' => 'ChosenPassword2026',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('ChosenPassword2026', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email,
            'password' => 'ReusedPassword2026', 'password_confirmation' => 'ReusedPassword2026',
        ])->assertSessionHasErrors('email');
        Setting::set('human_verify_login', '0');
        $this->post(route('login'), [
            'login' => $user->email, 'password' => 'ChosenPassword2026',
        ])->assertRedirect(route('student.dashboard'));
        $this->post(route('logout'));

        [$expiredUser, $expiredToken] = $invite('expired-invite@example.test');
        $this->post(route('logout'));
        $this->travel(61)->minutes();
        $this->post(route('password.update'), [
            'token' => $expiredToken, 'email' => $expiredUser->email,
            'password' => 'ExpiredPassword2026', 'password_confirmation' => 'ExpiredPassword2026',
        ])->assertSessionHasErrors('email');
    }

    public function test_public_learner_registration_remains_independent_of_invitation_mail(): void
    {
        Role::findOrCreate('student', 'web');
        config(['mail.default' => 'log']);
        Setting::set('allow_registration', '1');
        Setting::set('human_verify_register', '0');
        Setting::set('otp_email_enabled', '0');
        Setting::set('otp_phone_enabled', '0');

        $this->get(route('register'))->assertOk();
        $this->post(route('register'), [
            'name' => 'Self Registered', 'email' => 'self@example.test', 'country' => 'IN',
            'password' => 'MyOwnPassword2026', 'password_confirmation' => 'MyOwnPassword2026',
            'terms' => '1',
        ])->assertRedirect(route('student.dashboard'));

        $this->assertTrue(User::where('email', 'self@example.test')->firstOrFail()->hasRole('student'));
    }
}
