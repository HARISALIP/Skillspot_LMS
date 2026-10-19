<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Quiz extends Model {
    protected $fillable = ['owner_id', 'lesson_id', 'title', 'pass_score', 'time_limit', 'max_attempts', 'show_answers', 'show_submission_history', 'shuffle_questions', 'limit_questions_to', 'enable_negative_marking', 'marks_to_cut', 'enable_proctoring', 'max_violations', 'enable_scheduling', 'schedule_start', 'schedule_end'];
    protected $casts = ['show_answers' => 'boolean', 'show_submission_history' => 'boolean', 'shuffle_questions' => 'boolean', 'enable_negative_marking' => 'boolean', 'enable_proctoring' => 'boolean', 'enable_scheduling' => 'boolean', 'schedule_start' => 'datetime', 'schedule_end' => 'datetime'];
    public function owner()     { return $this->belongsTo(User::class, 'owner_id'); }
    public function lesson()    { return $this->belongsTo(Lesson::class); }
    public function selectedByLessons() { return $this->hasMany(Lesson::class, 'quiz_id'); }
    public function questions() { return $this->hasMany(QuizQuestion::class); }
    public function attempts() { return $this->hasMany(QuizAttempt::class); }
}
