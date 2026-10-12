<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarningLedgerEntry extends Model
{
    protected $fillable = ['marketplace_sale_id', 'payee_type', 'payee_user_id', 'organization_id', 'entry_type', 'amount', 'effective_at'];
    protected $casts = ['amount' => 'decimal:2', 'effective_at' => 'datetime'];
    public function sale() { return $this->belongsTo(MarketplaceSale::class, 'marketplace_sale_id'); }
}
