<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveClassSession extends Model
{
    protected $fillable = [
        'live_class_id', 'teacher_id',
        'module_name', 'session_name',
        'started_at', 'stopped_at',
        'duration_minutes', 'duration_hours',
        'status', 'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function liveClass() { return $this->belongsTo(LiveClass::class); }
    public function files()     { return $this->hasMany(LiveClassSessionFile::class); }
    public function teacher()   { return $this->belongsTo(User::class, 'teacher_id'); }
}
// already exists — just need attendance relation
