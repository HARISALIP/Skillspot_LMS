<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'event','razorpay_payment_id','razorpay_order_id',
        'status','payload','error',
    ];

    protected $casts = ['payload' => 'array'];
}
