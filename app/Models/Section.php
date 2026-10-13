<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
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
