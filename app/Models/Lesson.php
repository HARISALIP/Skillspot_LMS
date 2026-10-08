<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'section_id','title','description','type','content',
        'video_url','video_storage','video_r2_path',
        'live_platform','live_url','live_share_link','live_scheduled_at','live_duration_min','live_meeting_id','live_password',
        'recording_url','recording_shared',
        'notes','resources','duration','is_preview','order',
    ];

    protected $casts = [
        'is_preview'         => 'boolean',
        'recording_shared'   => 'boolean',
        'resources'          => 'array',
        'live_scheduled_at'  => 'datetime',
    ];

    public function section() { return $this->belongsTo(Section::class); }
    public function quiz()    { return $this->hasOne(Quiz::class); }

    public function isLiveNow(): bool
    {
        if ($this->type !== 'live' || !$this->live_scheduled_at) return false;
        $end = $this->live_scheduled_at->copy()->addMinutes($this->live_duration_min ?? 60);
        return now()->between($this->live_scheduled_at, $end);
    }
}
