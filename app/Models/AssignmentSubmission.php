<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $fillable = ['assignment_id', 'user_id', 'answer', 'file_path', 'file_name', 'status', 'score', 'feedback', 'graded_by', 'submitted_at', 'graded_at'];
    protected $casts = ['submitted_at' => 'datetime', 'graded_at' => 'datetime'];

    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function grader() { return $this->belongsTo(User::class, 'graded_by'); }
}
