<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseAnnouncement extends Model
{
    protected static function booted(): void
    {
        static::created(function (self $announcement) {
            if ($announcement->course?->marketplace_status === 'live') CourseChangeAudit::create([
                'course_id' => $announcement->course_id, 'actor_id' => $announcement->author_id,
                'subject_type' => 'announcement', 'subject_id' => $announcement->id,
                'changed_fields' => ['created']]);
        });
    }
    protected $fillable = ['course_id', 'author_id', 'title', 'body'];
    public function course() { return $this->belongsTo(Course::class); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
