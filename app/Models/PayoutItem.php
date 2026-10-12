<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutItem extends Model
{
    protected $fillable = ['payout_batch_id', 'marketplace_sale_id', 'amount', 'reference'];
    protected $casts = ['amount' => 'decimal:2'];
    public function batch() { return $this->belongsTo(PayoutBatch::class, 'payout_batch_id'); }
    public function sale() { return $this->belongsTo(MarketplaceSale::class, 'marketplace_sale_id'); }
}
