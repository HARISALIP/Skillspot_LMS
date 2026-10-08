<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'vendor_id','plan','status','max_courses','max_students',
        'storage_gb','expires_at','activated_at'
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function vendor() { return $this->belongsTo(User::class, 'vendor_id'); }
}
