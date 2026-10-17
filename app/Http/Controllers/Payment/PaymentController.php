<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\PaymentOrder;
use App\Models\WebhookLog;
use App\Models\Enrollment;
use App\Models\Course;
use App\Services\MarketplaceService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

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
        abort_unless($course->isMarketplaceLive(), 404, 'This course is not available for purchase.');
        if ($course->is_free || $course->price == 0) return $this->enrollFree($request->user(), $course);
        try {
            return response()->json($this->payments->createOrder($request->user(), $course));
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
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

        $order = PaymentOrder::where('razorpay_order_id', $request->razorpay_order_id)->first();
        abort_unless($order && $order->user_id === $request->user()->id && (!$request->filled('course_id') || $order->course_id === (int) $request->course_id), 404);
        try {
            $this->payments->verifyCheckout($order, $request->razorpay_payment_id, $request->razorpay_signature);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Razorpay payment verification failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Could not confirm the captured payment. Please try again.'], 503);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Payment successful! You are now enrolled.',
            'course_id' => $order->course_id,
            'redirect' => filled(Setting::get('razorpay_redirect_to'))
                ? url(Setting::get('razorpay_redirect_to'))
                : route('student.learn', $order->course_id),
        ]);
    }

    // ── Razorpay Webhook ───────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');
        $secret    = Setting::get('razorpay_webhook_secret', '');

        if (!filled($secret)) return response()->json(['error' => 'Webhook is not configured'], 503);
        $generated = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($generated, $signature)) return response()->json(['error' => 'Invalid signature'], 400);

        $log = WebhookLog::create([
            'event'               => $request->input('event', 'unknown'),
            'razorpay_payment_id' => $request->input('payload.payment.entity.id'),
            'razorpay_order_id'   => $request->input('payload.payment.entity.order_id'),
            'status'              => 'received',
            'payload' => json_decode($payload, true) ?? [],
        ]);

        try {
            $data  = json_decode($payload, true);
            $event = $data['event'] ?? '';

            match ($event) {
                'payment.captured' => $this->handlePaymentCaptured($data),
                'payment.failed'   => $this->handlePaymentFailed($data),
                'refund.processed' => $this->handleRefund($data),
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
        if (!$order) return;

        $this->payments->confirmCaptured($order, $payment);
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

        abort_unless(($refund['status'] ?? null) === 'processed'
            && (int) ($refund['amount'] ?? -1) === (int) round((float) $order->amount * 100), 422);
        $this->payments->confirmRefund($order, $refund['id']);
    }

    // ── Enroll free course directly ────────────────────────────────────
    private function enrollFree($user, $course)
    {
        Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['vendor_id' => $course->vendor_id, 'amount_paid' => 0, 'status' => 'active', 'progress' => 0]
        );
        return response()->json(['success' => true, 'free' => true, 'course_id' => $course->id]);
    }
}
