<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\PaymentOrder;
use App\Models\WebhookLog;
use App\Models\Enrollment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    private function razorpay(): Api
    {
        $mode   = Setting::get('razorpay_mode', 'live');
        $keyId  = $mode === 'test'
            ? Setting::get('razorpay_test_key_id')
            : Setting::get('razorpay_key_id');
        $secret = $mode === 'test'
            ? Setting::get('razorpay_test_key_secret')
            : Setting::get('razorpay_key_secret');

        return new Api($keyId, $secret);
    }

    // ── Create Razorpay order ──────────────────────────────────────────
    public function createOrder(Request $request)
    {
        $request->validate(['course_id' => 'required|exists:courses,id']);

        $course = Course::findOrFail($request->course_id);
        $user   = Auth::user();

        // Already enrolled?
        if (Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
            return response()->json(['error' => 'Already enrolled in this course.'], 422);
        }

        // Free course — skip payment
        if ($course->is_free || $course->price == 0) {
            return $this->enrollFree($user, $course);
        }

        $amount = (int) (($course->sale_price ?? $course->price) * 100); // paise

        try {
            $api   = $this->razorpay();
            $order = $api->order->create([
                'amount'          => $amount,
                'currency'        => Setting::get('currency', 'INR'),
                'receipt'         => 'rcpt_' . $user->id . '_' . $course->id . '_' . time(),
                'payment_capture' => 1,
                'notes'           => [
                    'user_id'    => $user->id,
                    'course_id'  => $course->id,
                    'user_email' => $user->email,
                    'course'     => $course->title,
                ],
            ]);

            // Save order record
            PaymentOrder::create([
                'user_id'           => $user->id,
                'course_id'         => $course->id,
                'razorpay_order_id' => $order->id,
                'amount'            => $course->sale_price ?? $course->price,
                'currency'          => Setting::get('currency', 'INR'),
                'mode'              => Setting::get('razorpay_mode', 'live'),
                'status'            => 'created',
            ]);

            return response()->json([
                'order_id'       => $order->id,
                'amount'         => $amount,
                'currency'       => Setting::get('currency', 'INR'),
                'key_id'         => Setting::get(
                    Setting::get('razorpay_mode') === 'test' ? 'razorpay_test_key_id' : 'razorpay_key_id'
                ),
                'course_name'    => $course->title,
                'user_name'      => $user->name,
                'user_email'     => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Payment initiation failed. Please try again.'], 500);
        }
    }

    // ── Verify payment after frontend callback ─────────────────────────
    public function verify(Request $request)
    {
        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $mode   = Setting::get('razorpay_mode', 'live');
        $secret = $mode === 'test'
            ? Setting::get('razorpay_test_key_secret')
            : Setting::get('razorpay_key_secret');

        $generated = hash_hmac(
            'sha256',
            $request->razorpay_order_id . '|' . $request->razorpay_payment_id,
            $secret
        );

        $order = PaymentOrder::where('razorpay_order_id', $request->razorpay_order_id)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        if (!hash_equals($generated, $request->razorpay_signature)) {
            $order->update(['status' => 'failed']);
            return response()->json(['error' => 'Payment verification failed. Invalid signature.'], 422);
        }

        DB::transaction(function () use ($order, $request) {
            $order->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
                'status'              => 'paid',
                'paid_at'             => now(),
            ]);

            Enrollment::firstOrCreate(
                ['user_id' => $order->user_id, 'course_id' => $order->course_id],
                [
                    'vendor_id'      => 1,
                    'amount_paid'    => $order->amount,
                    'payment_method' => 'razorpay',
                    'transaction_id' => $request->razorpay_payment_id,
                    'status'         => 'active',
                    'progress'       => 0,
                ]
            );
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Payment successful! You are now enrolled.',
            'course_id' => $order->course_id,
        ]);
    }

    // ── Razorpay Webhook ───────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');
        $secret    = Setting::get('razorpay_webhook_secret', '');

        // Log everything first
        $log = WebhookLog::create([
            'event'               => $request->input('event', 'unknown'),
            'razorpay_payment_id' => $request->input('payload.payment.entity.id'),
            'razorpay_order_id'   => $request->input('payload.payment.entity.order_id'),
            'status'              => 'received',
            'payload' => json_decode($payload, true) ?? [],
        ]);

        // Verify signature if webhook secret is configured
        if (!empty($secret)) {
            $generated = hash_hmac('sha256', $payload, $secret);
            if (!hash_equals($generated, $signature)) {
                $log->update(['status' => 'failed', 'error' => 'Invalid signature']);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        try {
            $data  = json_decode($payload, true);
            $event = $data['event'] ?? '';

            match ($event) {
                'payment.captured' => $this->handlePaymentCaptured($data),
                'payment.failed'   => $this->handlePaymentFailed($data),
                'refund.created'   => $this->handleRefund($data),
                default            => null,
            };

            $log->update(['status' => 'processed']);
            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::error('Webhook processing error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Processing error'], 500);
        }
    }

    // ── Handle payment.captured event ─────────────────────────────────
    private function handlePaymentCaptured(array $data): void
    {
        $payment = $data['payload']['payment']['entity'] ?? [];
        $orderId = $payment['order_id'] ?? null;
        if (!$orderId) return;

        $order = PaymentOrder::where('razorpay_order_id', $orderId)->first();
        if (!$order || $order->status === 'paid') return;

        DB::transaction(function () use ($order, $payment) {
            $order->update([
                'razorpay_payment_id' => $payment['id'],
                'status'              => 'paid',
                'paid_at'             => now(),
                'webhook_payload'     => json_encode($payment),
            ]);

            Enrollment::firstOrCreate(
                ['user_id' => $order->user_id, 'course_id' => $order->course_id],
                [
                    'vendor_id'      => 1,
                    'amount_paid'    => $order->amount,
                    'payment_method' => 'razorpay',
                    'transaction_id' => $payment['id'],
                    'status'         => 'active',
                    'progress'       => 0,
                ]
            );
        });
    }

    // ── Handle payment.failed event ────────────────────────────────────
    private function handlePaymentFailed(array $data): void
    {
        $payment = $data['payload']['payment']['entity'] ?? [];
        $orderId = $payment['order_id'] ?? null;
        if (!$orderId) return;

        PaymentOrder::where('razorpay_order_id', $orderId)
            ->where('status', 'created')
            ->update(['status' => 'failed']);
    }

    // ── Handle refund.created event ────────────────────────────────────
    private function handleRefund(array $data): void
    {
        $refund    = $data['payload']['refund']['entity'] ?? [];
        $paymentId = $refund['payment_id'] ?? null;
        if (!$paymentId) return;

        $order = PaymentOrder::where('razorpay_payment_id', $paymentId)->first();
        if (!$order) return;

        $order->update(['status' => 'refunded']);

        Enrollment::where('user_id', $order->user_id)
            ->where('course_id', $order->course_id)
            ->update(['status' => 'refunded']);
    }

    // ── Enroll free course directly ────────────────────────────────────
    private function enrollFree($user, $course)
    {
        Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['vendor_id' => 1, 'amount_paid' => 0, 'status' => 'active', 'progress' => 0]
        );
        return response()->json(['success' => true, 'free' => true, 'course_id' => $course->id]);
    }
}
