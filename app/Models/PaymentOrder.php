<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOrder extends Model
{
    protected $fillable = [
        'user_id','course_id','razorpay_order_id','razorpay_payment_id',
        'razorpay_signature','amount','currency','mode','status',
        'webhook_payload','paid_at',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'paid_at'         => 'datetime',
        'webhook_payload' => 'array',
    ];

    public function user()   { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
}
