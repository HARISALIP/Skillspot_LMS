<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseReview extends Model
{
    protected $fillable = ['course_id', 'submitted_by', 'reviewed_by', 'status', 'feedback', 'submitted_at', 'reviewed_at'];
    protected $casts = ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    public function course() { return $this->belongsTo(Course::class); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
