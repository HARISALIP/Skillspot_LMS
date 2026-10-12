<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'user_id','course_id','vendor_id','enrollment_id',
        'certificate_number','file_path','issued_at','revoked_at','revoked_by','revocation_reason'
    ];

    protected $casts = ['issued_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function user()   { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function revoker() { return $this->belongsTo(User::class, 'revoked_by'); }
}
