<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = ['identifier','type','otp','purpose','used','expires_at'];
    protected $casts    = ['expires_at' => 'datetime', 'used' => 'boolean'];
}
