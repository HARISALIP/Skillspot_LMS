<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveClassEnrollment extends Model
{
    protected $fillable = [
        'live_class_id', 'user_id', 'enrolled_by', 'status',
    ];

    public function liveClass() { return $this->belongsTo(LiveClass::class); }
    public function user()      { return $this->belongsTo(User::class); }
}
