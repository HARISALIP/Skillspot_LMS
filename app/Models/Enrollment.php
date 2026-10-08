<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id','course_id','vendor_id','amount_paid',
        'payment_method','transaction_id','status','progress','completed_at',
        'access_type','expires_at','granted_by','notes','is_restricted'
    ];

    protected $casts = [
        'amount_paid'  => 'decimal:2',
        'completed_at' => 'datetime',
        'expires_at'   => 'datetime',
        'is_restricted'=> 'boolean',
    ];

    public function user()   { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function vendor() { return $this->belongsTo(User::class, 'vendor_id'); }
}
