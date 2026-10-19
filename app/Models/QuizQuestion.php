<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuizQuestion extends Model {
    protected static function booted(): void {
        static::saved(function (self $question) {
            $course = $question->quiz?->lesson?->section?->course;
            if ($course?->marketplace_status === 'live' && ($question->wasRecentlyCreated || $question->wasChanged())) {
                CourseChangeAudit::create(['course_id' => $course->id, 'actor_id' => auth()->id(),
                    'subject_type' => 'quiz_question', 'subject_id' => $question->id,
                    'changed_fields' => $question->wasRecentlyCreated ? ['created'] : array_keys($question->getChanges())]);
            }
        });
        static::deleted(function (self $question) {
            $course = $question->quiz?->lesson?->section?->course;
            if ($course?->marketplace_status === 'live') CourseChangeAudit::create(['course_id' => $course->id,
                'actor_id' => auth()->id(), 'subject_type' => 'quiz_question', 'subject_id' => $question->id, 'changed_fields' => ['deleted']]);
        });
    }
    protected $fillable = ['quiz_id','question','question_type','marks','options','correct_answer','correct_answers','accepted_answers','explanations','order'];
    protected $casts    = ['options'=>'array', 'correct_answers'=>'array', 'accepted_answers'=>'array', 'explanations'=>'array'];
    public function quiz() { return $this->belongsTo(Quiz::class); }
}
