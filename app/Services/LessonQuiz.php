<?php
namespace App\Services;

use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonQuiz
{
    public static function validate(Request $request): array
    {
        return $request->validate([
            'quiz_title' => 'required|string|max:255',
            'quiz_pass_score' => 'required|integer|between:1,100',
            'quiz_questions' => 'required|array|min:3',
            'quiz_questions.*.question' => 'required|string|max:1000',
            'quiz_questions.*.options' => 'required|array|size:4',
            'quiz_questions.*.options.*' => 'required|string|max:255',
            'quiz_questions.*.correct_answer' => 'required|integer|between:0,3',
        ]);
    }

    public static function save(Lesson $lesson, array $data): void
    {
        $quiz = $lesson->quiz()->updateOrCreate(
            ['lesson_id' => $lesson->id],
            ['title' => $data['quiz_title'], 'pass_score' => $data['quiz_pass_score']]
        );
        $quiz->questions()->delete();
        foreach (array_values($data['quiz_questions']) as $order => $question) {
            $quiz->questions()->create([
                'question' => $question['question'],
                'options' => array_values($question['options']),
                'correct_answer' => (string) $question['correct_answer'],
                'order' => $order,
            ]);
        }
    }
}
