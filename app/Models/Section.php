<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Section $section) {
            if ($section->course?->marketplace_status === 'live' && ($section->wasRecentlyCreated || $section->wasChanged())) {
                CourseChangeAudit::create(['course_id' => $section->course_id, 'actor_id' => auth()->id(),
                    'subject_type' => 'section', 'subject_id' => $section->id,
                    'changed_fields' => $section->wasRecentlyCreated ? ['created'] : array_keys($section->getChanges())]);
            }
        });
        static::deleted(function (Section $section) {
            if ($section->course?->marketplace_status === 'live') CourseChangeAudit::create(['course_id' => $section->course_id,
                'actor_id' => auth()->id(), 'subject_type' => 'section', 'subject_id' => $section->id, 'changed_fields' => ['deleted']]);
        });
    }
    protected $fillable = [
        'course_id','title','order','is_scorm','scorm_package_name','scorm_package_path',
        'scorm_manifest_path','scorm_launch_path','scorm_package_size',
    ];

    protected function casts(): array
    {
        return ['is_scorm' => 'boolean'];
    }

    public function course()  { return $this->belongsTo(Course::class); }
    public function lessons() { return $this->hasMany(Lesson::class)->orderBy('order'); }
}
