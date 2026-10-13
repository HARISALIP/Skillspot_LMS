<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutBatch extends Model
{
    protected $fillable = ['seller_user_id', 'organization_id', 'status', 'reference', 'note', 'created_by', 'paid_by', 'paid_at'];
    protected $casts = ['paid_at' => 'datetime'];
    public function items() { return $this->hasMany(PayoutItem::class); }
    public function organization() { return $this->belongsTo(Vendor::class, 'organization_id'); }
}
