<?php

namespace Tests\Feature;

use App\Models\PaymentGatewayAccount;
use App\Support\PaymentGatewayCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentGatewayAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_catalog_matches_the_reference_and_credentials_are_encrypted(): void
    {
        $this->assertSame(
            ['braintree', 'gocardless', 'mpesa', 'paymob', 'paypal', 'paytm', 'razorpay', 'stripe'],
            array_keys(PaymentGatewayCatalog::PROVIDERS)
        );

        $account = PaymentGatewayAccount::create([
            'provider' => 'stripe',
            'credentials' => ['secret_key' => 'sk_test_private', 'publishable_key' => 'pk_test_public'],
        ]);

        $this->assertFalse($account->fresh()->enabled);
        $this->assertSame('sk_test_private', $account->fresh()->credentials['secret_key']);
        $this->assertStringNotContainsString('sk_test_private', $account->getRawOriginal('credentials'));
        $this->assertArrayNotHasKey('credentials', $account->toArray());
    }

    public function test_legacy_provider_credentials_remain_encrypted_but_cannot_be_configured_for_checkout(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $account = PaymentGatewayAccount::create(['provider' => 'stripe',
            'credentials' => ['secret_key' => 'sk_test_private'], 'enabled' => false]);
        $this->assertFalse($account->enabled);
        $this->assertStringNotContainsString('sk_test_private', $account->getRawOriginal('credentials'));
        $this->actingAs($admin)->get(route('admin.settings', ['tab' => 'payment-gateways']))
            ->assertOk()->assertSeeText('stored gateway record(s) are inactive')
            ->assertDontSee('sk_test_private')->assertDontSee('Stripe');
        $this->actingAs($admin)->post(route('admin.settings.save', 'payment-gateways'), [
            'gateway_record' => 'new', 'provider' => 'stripe',
        ])->assertSessionHasErrors('provider');
        $this->assertSame('sk_test_private', $account->fresh()->credentials['secret_key']);
    }

    public function test_learner_cannot_open_or_save_gateway_settings(): void
    {
        $learner = User::factory()->create();
        $this->actingAs($learner)->get(route('admin.settings', ['tab' => 'payment-gateways']))->assertForbidden();
        $this->actingAs($learner)->post(route('admin.settings.save', 'payment-gateways'), [
            'gateway_record' => 'new', 'provider' => 'stripe',
        ])->assertForbidden();
        $this->assertDatabaseCount('payment_gateway_accounts', 0);
    }
}
