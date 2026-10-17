<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayAccount extends Model
{
    protected $fillable = ['provider', 'credentials', 'enabled'];

    protected $hidden = ['credentials'];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'enabled' => 'boolean',
    ];
}
