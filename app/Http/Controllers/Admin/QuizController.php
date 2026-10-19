<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Support\RichTextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $quizzes = Quiz::query()->whereNull('lesson_id')->withCount('attempts')->withSum('questions', 'marks')
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->latest()->paginate(24)->withQueryString();

        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        return view('admin.quizzes.form', ['quiz' => new Quiz(['pass_score' => 100]), 'questionBank' => collect()]);
    }

    public function questions(Request $request)
    {
        $questions = QuizQuestion::query()->whereHas('quiz', fn ($query) => $query->whereNull('lesson_id'))
            ->with('quiz:id,title')
            ->when($request->filled('search'), fn ($query) => $query->where('question', 'like', '%'.$request->string('search').'%'))
            ->latest()->paginate(24)->withQueryString();

        return view('admin.quizzes.questions', compact('questions'));
    }

    public function submissions(Request $request)
    {
        $attempts = QuizAttempt::query()->whereHas('quiz', fn ($query) => $query->whereNull('lesson_id'))
            ->with(['quiz:id,title', 'user:id,name'])
            ->latest()->paginate(24)->withQueryString();

        return view('admin.quizzes.submissions', compact('attempts'));
    }

    public function store(Request $request)
    {
        $data = $this->payload($request);
        $quiz = DB::transaction(function () use ($data, $request) {
            $quiz = Quiz::create($data['quiz'] + ['owner_id' => $request->user()->id]);
            $this->replaceQuestions($quiz, $data['questions']);
            return $quiz;
        });
        if ($request->expectsJson()) return response()->json(['edit_url' => route('admin.quizzes.edit', $quiz)], 201);
        return redirect()->route('admin.quizzes.edit', $quiz)->with('success', 'Quiz created.');
    }

    public function edit(Quiz $quiz)
    {
        abort_if($quiz->lesson_id, 404);
        $quiz->load('questions');
        $questionBank = \App\Models\QuizQuestion::query()->where('quiz_id', '!=', $quiz->id)
            ->whereHas('quiz', fn ($query) => $query->whereNull('lesson_id'))
            ->with('quiz:id,title')->latest()->limit(100)->get();
        return view('admin.quizzes.form', compact('quiz', 'questionBank'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        abort_if($quiz->lesson_id, 404);
        abort_if($quiz->attempts()->exists(), 422, 'A quiz with submissions cannot be restructured.');
        $data = $this->payload($request);
        DB::transaction(function () use ($quiz, $data) {
            $quiz->update($data['quiz']);
            $this->replaceQuestions($quiz, $data['questions']);
        });
        if ($request->expectsJson()) return response()->json(['saved_at' => $quiz->updated_at?->toIso8601String()]);
        return back()->with('success', 'Quiz updated.');
    }

    public function destroy(Quiz $quiz)
    {
        abort_if($quiz->lesson_id || $quiz->selectedByLessons()->exists(), 422, 'Remove this quiz from courses before deleting it.');
        $quiz->delete();
        return redirect()->route('admin.quizzes')->with('success', 'Quiz deleted.');
    }

    private function payload(Request $request): array
    {
        $questions = $request->input('questions', []);
        foreach ($questions as $index => $question) {
            if (($question['type'] ?? null) !== 'user_input') unset($questions[$index]['accepted_answers']);
            if (!in_array($question['type'] ?? null, ['single', 'multiple'], true)) {
                unset($questions[$index]['options'], $questions[$index]['explanations'], $questions[$index]['correct_answer'], $questions[$index]['correct_answers']);
            }
        }
        $request->merge(['questions' => $questions]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'pass_score' => ['nullable', 'integer', 'between:1,100'],
            'time_limit' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:100'],
            'show_answers' => ['nullable', 'boolean'],
            'show_submission_history' => ['nullable', 'boolean'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'limit_questions_to' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'enable_negative_marking' => ['nullable', 'boolean'],
            'marks_to_cut' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'enable_proctoring' => ['nullable', 'boolean'],
            'max_violations' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'enable_scheduling' => ['nullable', 'boolean'],
            'schedule_start' => ['nullable', 'date'],
            'schedule_end' => ['nullable', 'date', 'after:schedule_start'],
            'questions' => ['nullable', 'array', 'max:200'],
            'questions.*.question' => ['required', 'string', 'max:10000'],
            'questions.*.type' => ['required', 'in:single,multiple,user_input,open'],
            'questions.*.marks' => ['required', 'integer', 'min:1', 'max:1000'],
            'questions.*.options' => ['nullable', 'array', 'max:10'],
            'questions.*.options.*' => ['nullable', 'string', 'max:2000'],
            'questions.*.explanations' => ['nullable', 'array', 'max:10'],
            'questions.*.explanations.*' => ['nullable', 'string', 'max:2000'],
            'questions.*.correct_answer' => ['nullable', 'integer', 'min:0', 'max:9'],
            'questions.*.correct_answers' => ['nullable', 'array', 'max:10'],
            'questions.*.correct_answers.*' => ['integer', 'min:0', 'max:9'],
            'questions.*.accepted_answers' => ['nullable', 'array', 'max:10'],
            'questions.*.accepted_answers.*' => ['string', 'max:2000'],
        ]);

        $questions = collect($data['questions'] ?? [])->values()->map(function (array $question, int $order) {
            $questionHtml = RichTextSanitizer::sanitize($question['question']);
            if (trim(html_entity_decode(strip_tags($questionHtml))) === '' && !preg_match('/<(?:img|video|iframe)\b[^>]*\bsrc="[^"]+"/i', $questionHtml)) {
                throw ValidationException::withMessages(["questions.$order.question" => 'Enter a question.']);
            }
            $options = array_values(array_filter($question['options'] ?? [], fn ($option) => trim((string) $option) !== ''));
            $explanations = array_values($question['explanations'] ?? []);
            $type = $question['type'];
            if (in_array($type, ['single', 'multiple'], true)) {
                if (count($options) < 2) throw ValidationException::withMessages(["questions.$order.options" => 'Choice questions need at least two options.']);
                $answers = $type === 'multiple' ? array_values(array_unique($question['correct_answers'] ?? [])) : [$question['correct_answer'] ?? -1];
                if (!$answers || collect($answers)->contains(fn ($answer) => $answer < 0 || $answer >= count($options))) throw ValidationException::withMessages(["questions.$order.correct_answer" => 'Choose valid correct answers.']);
                if ($type === 'multiple' && count($answers) < 2) throw ValidationException::withMessages(["questions.$order.correct_answers" => 'Multiple choice needs at least two correct answers.']);
            }
            $accepted = collect($question['accepted_answers'] ?? [])->flatMap(fn ($answer) => preg_split('/\R/u', (string) $answer))->map(fn ($answer) => trim($answer))->filter()->values()->all();
            if ($type === 'user_input' && empty($accepted)) {
                throw ValidationException::withMessages(["questions.$order.accepted_answers" => 'User input needs at least one accepted answer.']);
            }
            return [
                'question' => $questionHtml, 'question_type' => $type, 'marks' => $question['marks'],
                'options' => $options, 'explanations' => $type === 'single' || $type === 'multiple' ? $explanations : null, 'correct_answer' => $type === 'single' ? (string) $answers[0] : '',
                'correct_answers' => $type === 'multiple' ? $answers : null,
                'accepted_answers' => $type === 'user_input' ? $accepted : null,
                'order' => $order,
            ];
        })->all();

        $quiz = collect($data)->only(['title', 'pass_score', 'time_limit', 'max_attempts', 'limit_questions_to', 'marks_to_cut', 'max_violations', 'schedule_start', 'schedule_end'])->all();
        $quiz['pass_score'] = $quiz['pass_score'] ?? 100;
        foreach (['show_answers', 'show_submission_history', 'shuffle_questions', 'enable_negative_marking', 'enable_proctoring', 'enable_scheduling'] as $field) $quiz[$field] = $request->boolean($field);
        if (!$quiz['shuffle_questions']) $quiz['limit_questions_to'] = null;
        if (!$quiz['enable_negative_marking']) $quiz['marks_to_cut'] = null;
        if (!$quiz['enable_proctoring']) $quiz['max_violations'] = null;
        if (!$quiz['enable_scheduling']) $quiz['schedule_start'] = $quiz['schedule_end'] = null;
        return compact('quiz', 'questions');
    }

    private function replaceQuestions(Quiz $quiz, array $questions): void
    {
        $quiz->questions()->delete();
        $quiz->questions()->createMany($questions);
    }
}
