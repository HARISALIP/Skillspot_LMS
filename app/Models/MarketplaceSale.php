<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceSale extends Model
{
    protected $fillable = ['payment_order_id', 'buyer_id', 'course_id', 'seller_user_id', 'organization_id', 'gross_paid', 'discount_amount', 'tax_amount', 'processor_fee', 'split_basis', 'instructor_percent', 'platform_percent', 'seller_share', 'platform_share', 'status', 'hold_until', 'available_at', 'paid_at', 'refunded_at'];
    protected $casts = ['gross_paid'=>'decimal:2', 'discount_amount'=>'decimal:2', 'tax_amount'=>'decimal:2', 'processor_fee'=>'decimal:2', 'split_basis'=>'decimal:2', 'instructor_percent'=>'decimal:2', 'platform_percent'=>'decimal:2', 'seller_share'=>'decimal:2', 'platform_share'=>'decimal:2', 'hold_until'=>'datetime', 'available_at'=>'datetime', 'paid_at'=>'datetime', 'refunded_at'=>'datetime'];
    public function order() { return $this->belongsTo(PaymentOrder::class, 'payment_order_id'); }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function course() { return $this->belongsTo(Course::class); }
    public function seller() { return $this->belongsTo(User::class, 'seller_user_id'); }
    public function organization() { return $this->belongsTo(Vendor::class, 'organization_id'); }
    public function ledgerEntries() { return $this->hasMany(EarningLedgerEntry::class); }
}
