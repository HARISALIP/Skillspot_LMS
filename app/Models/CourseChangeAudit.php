<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseChangeAudit extends Model
{
    public $timestamps = false;
    protected $fillable = ['course_id', 'actor_id', 'subject_type', 'subject_id', 'changed_fields'];
    protected $casts = ['changed_fields' => 'array', 'created_at' => 'datetime'];

    public function course() { return $this->belongsTo(Course::class); }
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
