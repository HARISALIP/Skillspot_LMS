<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PaymentOrder;
use App\Models\Vendor;
use App\Services\OrganizationAccess;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrganizationEnrollmentController extends Controller
{
    private function course(string $slug, Course $course): Vendor
    {
        $vendor = Vendor::where('slug', $slug)->where('status', 'active')->firstOrFail();
        abort_unless($course->vendor_id === $vendor->id && $course->organization_published, 404);
        abort_unless(OrganizationAccess::eligible(auth()->user(), $course), 404);
        return $vendor;
    }

    public function show(string $slug, Course $course, PaymentService $payments)
    {
        $vendor = $this->course($slug, $course);
        $hasAccess = OrganizationAccess::hasChannel(auth()->user(), $course, 'organization');
        $checkoutIssue = $payments->checkoutUnavailableReason();
        return view('vendor.portal.course', compact('vendor', 'course', 'hasAccess', 'checkoutIssue'));
    }

    public function enrollFree(string $slug, Course $course)
    {
        $vendor = $this->course($slug, $course);
        abort_unless($course->is_free || (float) $course->price <= 0, 422);
        abort_unless($course->registration_open && !$course->isFull(), 422);
        DB::transaction(function () use ($course) {
            $enrollment = Enrollment::firstOrCreate(
                ['user_id' => auth()->id(), 'course_id' => $course->id],
                ['vendor_id' => $course->vendor_id, 'amount_paid' => 0, 'payment_method' => 'organization_free', 'status' => 'active']
            );
            if (!in_array($enrollment->status, ['active', 'completed'], true)) $enrollment->update(['status' => 'active']);
            OrganizationAccess::grant(auth()->user(), $course, 'organization', 'free');
        });
        return redirect()->route('vendor.portal.batch', [$vendor->slug, $course->id]);
    }

    public function order(string $slug, Course $course, PaymentService $payments)
    {
        $this->course($slug, $course);
        abort_unless($course->registration_open && !$course->isFull(), 422);
        try {
            return response()->json($payments->createOrder(auth()->user(), $course, 'organization'));
        } catch (\DomainException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            Log::error('Organization checkout failed', ['course_id' => $course->id, 'error' => $exception->getMessage()]);
            return response()->json(['error' => 'Could not start checkout. Try again.'], 503);
        }
    }

    public function verify(Request $request, string $slug, Course $course, PaymentService $payments)
    {
        $vendor = Vendor::where('slug', $slug)->where('status', 'active')->firstOrFail();
        abort_unless($course->vendor_id === $vendor->id, 404);
        $data = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);
        $order = PaymentOrder::where('razorpay_order_id', $data['razorpay_order_id'])
            ->where('user_id', $request->user()->id)->where('course_id', $course->id)
            ->where('sale_channel', 'organization')->firstOrFail();
        try {
            $payments->verifyCheckout($order, $data['razorpay_payment_id'], $data['razorpay_signature']);
        } catch (\DomainException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            Log::error('Organization payment verification failed', ['order_id' => $order->id, 'error' => $exception->getMessage()]);
            return response()->json(['error' => 'Payment confirmation is pending. Contact support if charged.'], 503);
        }
        return response()->json(['success' => true, 'redirect' => route('vendor.portal.batch', [$vendor->slug, $course->id])]);
    }
}
