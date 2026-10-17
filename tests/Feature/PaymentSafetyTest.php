<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MarketplaceSale;
use App\Models\PaymentOrder;
use App\Models\PayoutBatch;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MarketplaceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_checkout_starts_disabled_until_explicitly_enabled(): void
    {
        $this->assertSame('Paid checkout is temporarily disabled.', app(PaymentService::class)->checkoutUnavailableReason());
    }

    public function test_only_admin_can_reconcile_a_refund_with_the_payment_service(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $learner = User::factory()->create();
        $course = Course::create(['title' => 'Refund check']);
        $order = PaymentOrder::create(['user_id' => $learner->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_check', 'amount' => 100, 'status' => 'paid',
            'refund_requested_at' => now()]);
        $payments = \Mockery::mock(PaymentService::class);
        $payments->shouldReceive('reconcileRefund')->once()->with(\Mockery::on(fn ($value) => $value->is($order)))
            ->andReturn('Razorpay still has an outstanding refund.');
        $this->app->instance(PaymentService::class, $payments);

        $this->actingAs($learner)->post(route('admin.payments.refund.reconcile', $order))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.payments.refund.reconcile', $order))
            ->assertRedirect()->assertSessionHas('success');
    }

    public function test_refund_reconciliation_uses_provider_state_before_unlocking_or_reversing_access(): void
    {
        $buyer = User::factory()->create();
        $course = Course::create(['title' => 'Provider refund']);
        $order = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_provider', 'razorpay_payment_id' => 'pay_provider',
            'amount' => 100, 'currency' => 'INR', 'mode' => 'test', 'status' => 'paid',
            'refund_requested_at' => now()]);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'status' => 'active']);
        Setting::set('razorpay_test_key_id', 'test-key', 'payment');
        Setting::set('razorpay_test_key_secret', 'test-secret', 'payment');

        $refunds = \Mockery::mock(\Razorpay\Api\Refund::class);
        $empty = new \Razorpay\Api\Collection();
        $empty->fill(['entity' => 'collection', 'count' => 0, 'items' => []]);
        $processed = new \Razorpay\Api\Collection();
        $processed->fill(['entity' => 'collection', 'count' => 1, 'items' => [[
            'entity' => 'refund', 'id' => 'rfnd_provider', 'payment_id' => 'pay_provider',
            'amount' => 10000, 'status' => 'processed',
        ]]]);
        $refunds->shouldReceive('all')->times(3)->andReturn($empty, $empty, $processed);
        $api = new class($refunds) extends \Razorpay\Api\Api {
            public function __construct(private \Razorpay\Api\Refund $fakeRefund) { parent::__construct('test-key', 'test-secret'); }
            public function __get($name) { return $this->fakeRefund; }
        };
        $payments = new class($api) extends PaymentService {
            public function __construct(private \Razorpay\Api\Api $fakeApi) {}
            protected function api(string $mode): \Razorpay\Api\Api { return $this->fakeApi; }
        };
        $payments->reconcileRefund($order);
        $this->assertNotNull($order->fresh()->refund_requested_at);
        $order->update(['refund_requested_at' => now()->subDays(2)]);
        $payments->reconcileRefund($order->fresh());
        $this->assertNull($order->fresh()->refund_requested_at);
        $order->update(['refund_requested_at' => now()]);
        $payments->reconcileRefund($order->fresh());
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('refunded', Enrollment::first()->status);
    }

    public function test_unsigned_or_unconfigured_webhook_cannot_write_payment_data(): void
    {
        $payload = ['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_fake']]]];
        $this->postJson(route('webhook.razorpay'), $payload)->assertStatus(503);
        Setting::set('razorpay_webhook_secret', 'test-secret', 'payment');
        $this->postJson(route('webhook.razorpay'), $payload)->assertStatus(400);
        $this->assertDatabaseCount('webhook_logs', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_gateway_completion_redirect_is_internal_and_defaults_to_the_purchased_course(): void
    {
        $buyer = User::factory()->create();
        $course = Course::create(['title' => 'Redirect course']);
        $order = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_redirect', 'amount' => 100, 'currency' => 'INR', 'mode' => 'test', 'status' => 'paid']);
        $payments = \Mockery::mock(PaymentService::class);
        $payments->shouldReceive('verifyCheckout')->twice();
        $this->app->instance(PaymentService::class, $payments);
        $payload = ['razorpay_order_id' => $order->razorpay_order_id, 'razorpay_payment_id' => 'pay_redirect', 'razorpay_signature' => 'signed'];

        $this->actingAs($buyer)->postJson(route('payment.verify'), $payload)
            ->assertOk()->assertJsonPath('redirect', route('student.learn', $course));
        Setting::set('razorpay_redirect_to', '/dashboard', 'payment-gateways');
        $this->actingAs($buyer)->postJson(route('payment.verify'), $payload)
            ->assertOk()->assertJsonPath('redirect', url('/dashboard'));
    }

    public function test_disabled_gateway_blocks_both_paid_order_routes_but_not_existing_payment_settlement(): void
    {
        $buyer = User::factory()->create();
        $course = Course::create(['title' => 'Gateway course', 'is_free' => false, 'price' => 100,
            'marketplace_status' => 'live', 'marketplace_visible' => true,
            'is_published' => true, 'visibility' => 'public']);
        Setting::set('razorpay_enabled', '0', 'payment-gateways');

        $this->actingAs($buyer)->get(route('student.course-detail', $course->slug))
            ->assertOk()->assertSeeText('Paid enrollment unavailable')->assertDontSee('id="payBtn"', false);

        $this->actingAs($buyer)->postJson(route('student.create-order', $course), [])->assertStatus(422)
            ->assertJsonPath('error', 'Paid checkout is temporarily disabled.');
        $this->actingAs($buyer)->postJson(route('payment.order'), ['course_id' => $course->id])->assertStatus(422);
        $this->assertDatabaseCount('payment_orders', 0);
        $this->assertDatabaseCount('enrollments', 0);

        $order = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_before_shutdown', 'amount' => 100, 'currency' => 'INR', 'mode' => 'test', 'status' => 'created']);
        app(PaymentService::class)->confirmCaptured($order, ['id' => 'pay_before_shutdown', 'order_id' => 'order_before_shutdown',
            'status' => 'captured', 'amount' => 10000, 'currency' => 'INR']);
        $this->assertDatabaseHas('enrollments', ['user_id' => $buyer->id, 'course_id' => $course->id]);
    }

    public function test_vendor_course_registration_cannot_grant_a_paid_course_without_checkout(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Paid Academy', 'status' => 'active']);
        $course = Course::create(['title' => 'Paid portal course', 'vendor_id' => $vendor->id,
            'is_free' => false, 'price' => 100, 'is_published' => true,
            'marketplace_status' => 'live', 'marketplace_visible' => true]);

        $this->get(route('vendor.portal.register', [$vendor->slug, $course->id]))
            ->assertRedirect(route('student.course-detail', $course->slug));
        $this->post(route('vendor.portal.register.submit', [$vendor->slug, $course->id]), [
            'name' => 'No Payment', 'email' => 'unpaid@example.test', 'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'unpaid@example.test']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_only_matching_captured_payment_enrolls_and_repeated_confirmation_is_idempotent(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $course = Course::create(['title' => 'Paid course', 'primary_instructor_id' => $seller->id]);
        $order = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_safe', 'amount' => 100, 'currency' => 'INR', 'mode' => 'test', 'status' => 'created']);
        $payments = app(PaymentService::class);
        $valid = ['id' => 'pay_safe', 'order_id' => 'order_safe', 'status' => 'captured', 'amount' => 10000, 'currency' => 'INR'];

        foreach ([['status' => 'authorized'], ['amount' => 1], ['order_id' => 'other']] as $change) {
            try {
                $payments->confirmCaptured($order, array_replace($valid, $change));
                $this->fail('Mismatched or uncaptured payment was accepted.');
            } catch (\DomainException) {
                $this->assertDatabaseCount('enrollments', 0);
            }
        }
        $payments->confirmCaptured($order, $valid);
        $payments->confirmCaptured($order, $valid);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseCount('marketplace_sales', 1);
    }

    public function test_full_refund_after_payout_records_negative_earning_once(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $course = Course::create(['title' => 'Refunded course', 'primary_instructor_id' => $seller->id]);
        $order = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_refund', 'razorpay_payment_id' => 'pay_refund',
            'amount' => 100, 'currency' => 'INR', 'mode' => 'test', 'status' => 'paid']);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'status' => 'active']);
        $sale = MarketplaceService::recordPaidSale($order);
        $sale->update(['status' => 'paid', 'paid_at' => now()]);

        $payments = app(PaymentService::class);
        $payments->confirmRefund($order, 'rfnd_safe');
        $payments->confirmRefund($order, 'rfnd_safe');
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('reversed', $sale->fresh()->status);
        $this->assertSame('refunded', Enrollment::first()->status);
        $this->assertDatabaseCount('earning_ledger_entries', 3);
        $this->assertDatabaseHas('earning_ledger_entries', ['marketplace_sale_id' => $sale->id, 'entry_type' => 'refund_reversal', 'amount' => -70]);
    }

    public function test_refunded_webhook_replay_does_not_restore_access_but_new_purchase_does(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $course = Course::create(['title' => 'Buy again', 'primary_instructor_id' => $seller->id]);
        $old = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_old', 'amount' => 100, 'currency' => 'INR', 'mode' => 'test', 'status' => 'created']);
        $payments = app(PaymentService::class);
        $captured = ['id' => 'pay_old', 'order_id' => 'order_old', 'status' => 'captured', 'amount' => 10000, 'currency' => 'INR'];
        $payments->confirmCaptured($old, $captured);
        $payments->confirmRefund($old, 'rfnd_old');
        $payments->confirmCaptured($old, $captured);
        $this->assertSame('refunded', Enrollment::first()->status);

        $new = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_new', 'amount' => 100, 'currency' => 'INR', 'mode' => 'test', 'status' => 'created']);
        $payments->confirmCaptured($new, ['id' => 'pay_new', 'order_id' => 'order_new', 'status' => 'captured', 'amount' => 10000, 'currency' => 'INR']);
        $this->assertSame('active', Enrollment::first()->status);
        $this->assertSame('pay_new', Enrollment::first()->transaction_id);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_admin_cannot_mark_a_payout_paid_without_confirming_the_bank_transfer(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $batch = PayoutBatch::create(['created_by' => $admin->id]);

        $this->actingAs($admin)->patch(route('admin.marketplace.payouts.paid', $batch), ['reference' => 'BANK123'])
            ->assertSessionHasErrors('transfer_confirmed');
        $this->assertSame('draft', $batch->fresh()->status);
    }
}
