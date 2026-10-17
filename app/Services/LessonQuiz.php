<?php
namespace App\Services;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LessonQuiz
{
    public static function validate(Request $request): array
    {
        $data = $request->validate([
            'quiz_title' => 'required|string|max:255',
            'quiz_pass_score' => 'required|integer|between:1,100',
            'quiz_questions' => 'required|array|min:1',
            'quiz_questions.*.question' => 'required|string|max:1000',
            'quiz_questions.*.question_type' => 'sometimes|in:single,multiple,open',
            'quiz_questions.*.options' => 'nullable|array|max:6',
            'quiz_questions.*.options.*' => 'required|string|max:255',
            'quiz_questions.*.correct_answer' => 'nullable|integer|between:0,5',
            'quiz_questions.*.correct_answers' => 'nullable|array',
            'quiz_questions.*.correct_answers.*' => 'integer|between:0,5',
        ]);
        foreach ($data['quiz_questions'] as $index => $question) {
            $type = $question['question_type'] ?? 'single';
            $count = count($question['options'] ?? []);
            if ($type !== 'open' && ($count < 2 || $count > 6)) {
                throw ValidationException::withMessages(["quiz_questions.$index.options" => 'Choose two to six answer options.']);
            }
            if ($type === 'single' && (!isset($question['correct_answer']) || $question['correct_answer'] >= $count)) {
                throw ValidationException::withMessages(["quiz_questions.$index.correct_answer" => 'Choose one valid correct answer.']);
            }
            if ($type === 'multiple' && (empty($question['correct_answers']) || collect($question['correct_answers'])->contains(fn ($answer) => $answer >= $count))) {
                throw ValidationException::withMessages(["quiz_questions.$index.correct_answers" => 'Choose valid correct answers.']);
            }
        }
        return $data;
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
                'options' => array_values($question['options'] ?? []),
                'question_type' => $question['question_type'] ?? 'single',
                'correct_answer' => (string) ($question['correct_answer'] ?? ''),
                'correct_answers' => $question['correct_answers'] ?? null,
                'order' => $order,
            ]);
        }
    }
}
