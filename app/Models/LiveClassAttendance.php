<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveClassAttendance extends Model
{
    protected $table = 'live_class_attendance';

    protected $fillable = [
        'live_class_id', 'live_class_session_id', 'user_id',
        'joined_at', 'left_at', 'duration_minutes',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
    ];

    public function liveClass() { return $this->belongsTo(LiveClass::class); }
    public function session()   { return $this->belongsTo(LiveClassSession::class, 'live_class_session_id'); }
    public function user()      { return $this->belongsTo(User::class); }
}
