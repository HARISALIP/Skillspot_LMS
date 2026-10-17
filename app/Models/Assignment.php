<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected static function booted(): void
    {
        static::saved(function (self $assignment) {
            $course = $assignment->lesson?->section?->course;
            if ($course?->marketplace_status === 'live' && ($assignment->wasRecentlyCreated || $assignment->wasChanged())) {
                CourseChangeAudit::create(['course_id' => $course->id, 'actor_id' => auth()->id(),
                    'subject_type' => 'assignment', 'subject_id' => $assignment->id,
                    'changed_fields' => $assignment->wasRecentlyCreated ? ['created'] : array_keys($assignment->getChanges())]);
            }
        });
    }
    protected $fillable = ['lesson_id', 'instructions', 'pass_score', 'due_at'];
    protected $casts = ['due_at' => 'datetime'];

    public function lesson() { return $this->belongsTo(Lesson::class); }
    public function submissions() { return $this->hasMany(AssignmentSubmission::class); }
}
