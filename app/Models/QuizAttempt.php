<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = ['user_id', 'quiz_id', 'score', 'passed', 'answers', 'status', 'manual_results', 'feedback', 'graded_by', 'graded_at'];
    protected $casts = ['answers' => 'array', 'manual_results' => 'array', 'passed' => 'boolean', 'graded_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function quiz() { return $this->belongsTo(Quiz::class); }
}
