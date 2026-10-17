<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PaymentOrder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

class PaymentService
{
    public function checkoutUnavailableReason(): ?string
    {
        if (Setting::get('razorpay_enabled', '0') !== '1') return 'Paid checkout is temporarily disabled.';
        if (Setting::get('razorpay_gateway_added') === '0') return 'Paid checkout is not configured. Ask an administrator to check Gateways.';
        $mode = Setting::get('razorpay_mode', 'live');
        if (!in_array($mode, ['test', 'live'], true)) return 'Payment mode is invalid. Ask an administrator to check Gateways.';
        $test = $mode === 'test';
        if (!filled(Setting::get($test ? 'razorpay_test_key_id' : 'razorpay_key_id'))
            || !filled(Setting::get($test ? 'razorpay_test_key_secret' : 'razorpay_key_secret'))
            || !filled(Setting::get('razorpay_webhook_secret'))) {
            return 'Paid checkout is not configured. Ask an administrator to check Gateways.';
        }
        return null;
    }

    private function credentials(string $mode): array
    {
        $test = $mode === 'test';
        $key = Setting::get($test ? 'razorpay_test_key_id' : 'razorpay_key_id');
        $secret = Setting::get($test ? 'razorpay_test_key_secret' : 'razorpay_key_secret');
        if (!filled($key) || !filled($secret)) throw new \DomainException('Razorpay is not configured.');
        return [$key, $secret];
    }

    protected function api(string $mode): Api
    {
        [$key, $secret] = $this->credentials($mode);
        return new Api($key, $secret);
    }

    public function createOrder(User $user, Course $course): array
    {
        if (!$course->isMarketplaceLive() || $course->is_free || (float) $course->price <= 0) throw new \DomainException('This course is not available for paid checkout.');
        if (Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->where('status', 'active')->exists()) throw new \DomainException('Already enrolled in this course.');
        if ($reason = $this->checkoutUnavailableReason()) throw new \DomainException($reason);
        $mode = Setting::get('razorpay_mode', 'live');
        [$key] = $this->credentials($mode);
        $currency = Setting::get('currency', 'INR');
        $amount = (float) ($course->sale_price ?? $course->price);
        $providerOrder = $this->api($mode)->order->create([
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'receipt' => 'course_'.$course->id.'_'.bin2hex(random_bytes(8)),
            'notes' => ['user_id' => $user->id, 'course_id' => $course->id],
        ]);
        PaymentOrder::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'razorpay_order_id' => $providerOrder['id'], 'amount' => $amount,
            'currency' => $currency, 'mode' => $mode, 'status' => 'created',
        ]);
        return [
            'order_id' => $providerOrder['id'], 'amount' => (int) round($amount * 100),
            'currency' => $currency, 'key_id' => $key,
            'course_name' => $course->title, 'course' => $course->title,
            'user_name' => $user->name, 'user_email' => $user->email, 'user_phone' => $user->phone ?? '',
        ];
    }

    public function verifyCheckout(PaymentOrder $order, string $paymentId, string $signature): void
    {
        [, $secret] = $this->credentials($order->mode);
        $expected = hash_hmac('sha256', $order->razorpay_order_id.'|'.$paymentId, $secret);
        if (!hash_equals($expected, $signature)) throw new \DomainException('Invalid payment signature.');
        $payment = $this->api($order->mode)->payment->fetch($paymentId);
        $this->confirmCaptured($order, [
            'id' => $payment['id'], 'order_id' => $payment['order_id'],
            'status' => $payment['status'], 'amount' => $payment['amount'], 'currency' => $payment['currency'],
        ], $signature);
        if ($order->fresh()->status !== 'paid') throw new \DomainException('This payment has already been refunded.');
    }

    public function confirmCaptured(PaymentOrder $order, array $payment, ?string $signature = null): void
    {
        if (($payment['status'] ?? null) !== 'captured'
            || ($payment['order_id'] ?? null) !== $order->razorpay_order_id
            || (int) ($payment['amount'] ?? -1) !== (int) round((float) $order->amount * 100)
            || ($payment['currency'] ?? null) !== $order->currency
            || !filled($payment['id'] ?? null)) {
            throw new \DomainException('Captured payment does not match the order.');
        }
        DB::transaction(function () use ($order, $payment, $signature) {
            $locked = PaymentOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'refunded') return;
            if ($locked->status === 'paid' && $locked->razorpay_payment_id !== $payment['id']) throw new \DomainException('Order was paid by a different payment.');
            if ($locked->status !== 'paid') {
                $locked->update([
                    'razorpay_payment_id' => $payment['id'], 'razorpay_signature' => $signature,
                    'status' => 'paid', 'paid_at' => now(),
                ]);
            }
            $enrollment = Enrollment::firstOrCreate(
                ['user_id' => $locked->user_id, 'course_id' => $locked->course_id],
                ['vendor_id' => $locked->course->vendor_id, 'amount_paid' => $locked->amount,
                    'payment_method' => 'razorpay', 'transaction_id' => $payment['id'],
                    'status' => 'active', 'progress' => 0]
            );
            if ($enrollment->status === 'refunded') $enrollment->update(['status' => 'active', 'amount_paid' => $locked->amount, 'transaction_id' => $payment['id']]);
            MarketplaceService::recordPaidSale($locked);
        });
    }

    public function requestFullRefund(PaymentOrder $order): string
    {
        $this->credentials($order->mode);
        DB::transaction(function () use ($order) {
            $locked = PaymentOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'paid' || !$locked->razorpay_payment_id || $locked->refund_requested_at) throw new \DomainException('Only captured, paid orders without an outstanding refund can be refunded.');
            $locked->update(['refund_requested_at' => now()]);
        });
        try {
            $refund = $this->api($order->mode)->payment->fetch($order->razorpay_payment_id)->refund([
                'amount' => (int) round((float) $order->amount * 100),
                'speed' => 'normal',
            ]);
            $order->update(['razorpay_refund_id' => $refund['id']]);
            if (($refund['status'] ?? null) === 'processed') $this->confirmRefund($order, $refund['id']);
            return $refund['id'];
        } catch (\Throwable $e) {
            // A provider timeout can still have created a refund: reconcile before retrying.
            throw $e;
        }
    }

    public function reconcileRefund(PaymentOrder $order): string
    {
        if ($order->status !== 'paid' || !$order->refund_requested_at || !$order->razorpay_payment_id) {
            throw new \DomainException('This order has no outstanding refund to reconcile.');
        }
        $refunds = $this->api($order->mode)->refund->all([
            'payment_id' => $order->razorpay_payment_id, 'count' => 100,
        ]);
        if (($refunds['count'] ?? 0) > count($refunds['items'] ?? [])) {
            throw new \DomainException('Razorpay returned an incomplete refund list. Check this payment manually.');
        }
        $matching = collect($refunds['items'] ?? [])->filter(fn ($refund) =>
            ($refund['payment_id'] ?? null) === $order->razorpay_payment_id
            && (int) ($refund['amount'] ?? -1) === (int) round((float) $order->amount * 100)
        );
        if ($matching->contains(fn ($refund) => ($refund['status'] ?? null) === 'processed')) {
            $this->confirmRefund($order, $matching->first(fn ($refund) => $refund['status'] === 'processed')['id']);
            return 'Razorpay confirmed the full refund. Enrollment and earnings were reversed.';
        }
        if ($matching->contains(fn ($refund) => !in_array($refund['status'] ?? null, ['failed', 'rejected'], true))) {
            return 'Razorpay still has an outstanding refund. Try reconciliation again later.';
        }
        if (($refunds['count'] ?? 0) !== $matching->count()) {
            throw new \DomainException('Other refunds exist for this payment. Check Razorpay manually before retrying.');
        }
        if ($order->refund_requested_at->gt(now()->subDay())) {
            return 'No refund is visible yet. Wait 24 hours after the request before unlocking a retry.';
        }
        PaymentOrder::whereKey($order->id)->where('status', 'paid')->update([
            'refund_requested_at' => null, 'razorpay_refund_id' => null,
        ]);
        return 'Razorpay has no outstanding refund. The order is ready for a new refund request.';
    }

    public function confirmRefund(PaymentOrder $order, string $refundId): void
    {
        DB::transaction(function () use ($order, $refundId) {
            $locked = PaymentOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'refunded') return;
            if ($locked->status !== 'paid') throw new \DomainException('Refund does not match a paid order.');
            $locked->update(['status' => 'refunded', 'razorpay_refund_id' => $refundId]);
            MarketplaceService::reverseSale($locked);
            Enrollment::where('user_id', $locked->user_id)->where('course_id', $locked->course_id)->update(['status' => 'refunded']);
        });
    }
}
