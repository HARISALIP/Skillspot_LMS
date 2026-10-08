<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentOrder::with(['user','course'])->latest();

        if ($s = $request->search) {
            $query->where(function($q) use ($s) {
                $q->where('razorpay_payment_id','like',"%{$s}%")
                  ->orWhere('razorpay_order_id','like',"%{$s}%")
                  ->orWhereHas('user', fn($q) => $q->where('name','like',"%{$s}%")->orWhere('email','like',"%{$s}%"));
            });
        }
        if ($request->status)  $query->where('status', $request->status);
        if ($request->mode)    $query->where('mode',   $request->mode);
        if ($request->from)    $query->whereDate('created_at','>=',$request->from);
        if ($request->to)      $query->whereDate('created_at','<=',$request->to);

        $payments = $query->paginate(20);

        $stats = [
            'total'    => PaymentOrder::count(),
            'paid'     => PaymentOrder::where('status','paid')->count(),
            'failed'   => PaymentOrder::where('status','failed')->count(),
            'refunded' => PaymentOrder::where('status','refunded')->count(),
            'revenue'  => PaymentOrder::where('status','paid')->sum('amount'),
        ];

        return view('admin.payments.index', compact('payments','stats'));
    }

    public function show(PaymentOrder $payment)
    {
        $payment->load(['user','course']);
        return view('admin.payments.show', compact('payment'));
    }

    public function refund(Request $request, PaymentOrder $payment)
    {
        if ($payment->status !== 'paid') {
            return back()->with('error', 'Only paid orders can be refunded.');
        }
        // Mark as refunded (actual Razorpay refund via API in future)
        $payment->update(['status' => 'refunded']);
        return back()->with('success', "Payment #{$payment->razorpay_payment_id} marked as refunded.");
    }
}
