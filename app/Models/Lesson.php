<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Lesson $lesson) {
            $course = $lesson->section?->course;
            if ($course?->marketplace_status === 'live' && ($lesson->wasRecentlyCreated || $lesson->wasChanged())) {
                CourseChangeAudit::create(['course_id' => $course->id, 'actor_id' => auth()->id(),
                    'subject_type' => 'lesson', 'subject_id' => $lesson->id,
                    'changed_fields' => $lesson->wasRecentlyCreated ? ['created'] : array_keys($lesson->getChanges())]);
            }
        });
        static::deleted(function (Lesson $lesson) {
            $course = $lesson->section?->course;
            if ($course?->marketplace_status === 'live') CourseChangeAudit::create(['course_id' => $course->id, 'actor_id' => auth()->id(),
                'subject_type' => 'lesson', 'subject_id' => $lesson->id, 'changed_fields' => ['deleted']]);
        });
    }
    protected $fillable = [
        'section_id','quiz_id','title','description','type','content',
        'video_url','video_storage','video_r2_path',
        'live_platform','live_url','live_share_link','live_scheduled_at','live_duration_min','live_meeting_id','live_password',
        'recording_url','recording_shared',
        'notes','resources','duration','is_preview','order','block_document','instructor_notes_document',
    ];

    protected $casts = [
        'is_preview'         => 'boolean',
        'recording_shared'   => 'boolean',
        'resources'          => 'array',
        'block_document'     => 'array',
        'instructor_notes_document' => 'array',
        'live_scheduled_at'  => 'datetime',
    ];

    public function section() { return $this->belongsTo(Section::class); }
    public function quiz()    { return $this->hasOne(Quiz::class); }
    public function selectedQuiz() { return $this->belongsTo(Quiz::class, 'quiz_id'); }
    public function assignment() { return $this->hasOne(Assignment::class); }

    public function isLiveNow(): bool
    {
        if ($this->type !== 'live' || !$this->live_scheduled_at) return false;
        $end = $this->live_scheduled_at->copy()->addMinutes($this->live_duration_min ?? 60);
        return now()->between($this->live_scheduled_at, $end);
    }
}
