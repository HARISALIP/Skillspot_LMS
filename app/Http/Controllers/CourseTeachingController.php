<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\Upload;
use App\Services\CourseService;
use App\Services\LessonQuiz;
use App\Services\QuizGrader;
use App\Services\MediaUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourseTeachingController extends Controller
{
    private function author(Request $request, Course $course): void
    {
        abort_unless($course->canManageContent($request->user()), 403);
        if (!$request->user()->hasRole(['admin', 'super-admin']) && !$course->vendor_id) {
            abort_unless($request->user()->instructorProfile?->status !== 'suspended', 403);
        }
    }

    public function show(Request $request, Course $course)
    {
        $this->author($request, $course);
        $course->load(['sections.lessons.assignment.submissions.user', 'sections.lessons.quiz.questions',
            'sections.lessons.quiz.attempts.user', 'announcements.author']);
        return view('studio.teaching', compact('course'));
    }

    public function storeAssignment(Request $request, Course $course, Section $section)
    {
        $this->author($request, $course);
        abort_unless($section->course_id === $course->id, 404);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string', 'max:30000'],
            'pass_score' => ['required', 'integer', 'between:1,100'],
            'due_at' => ['nullable', 'date', 'after:now'],
        ]);
        DB::transaction(function () use ($section, $data) {
            $lesson = $section->lessons()->create(['title' => $data['title'], 'type' => 'assignment',
                'order' => ((int) $section->lessons()->max('order')) + 1]);
            $lesson->assignment()->create(['instructions' => $data['instructions'],
                'pass_score' => $data['pass_score'], 'due_at' => $data['due_at'] ?? null]);
        });
        CourseService::refreshEnrollmentProgress($course);
        return back()->with('success', 'Assignment added.');
    }

    public function updateAssignment(Request $request, Course $course, Assignment $assignment)
    {
        $this->author($request, $course);
        abort_unless($assignment->lesson?->section?->course_id === $course->id, 404);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string', 'max:30000'],
            'pass_score' => ['required', 'integer', 'between:1,100'],
            'due_at' => ['nullable', 'date'],
        ]);
        DB::transaction(function () use ($assignment, $data) {
            $assignment->lesson->update(['title' => $data['title']]);
            $assignment->update(['instructions' => $data['instructions'],
                'pass_score' => $data['pass_score'], 'due_at' => $data['due_at'] ?? null]);
        });
        return back()->with('success', 'Assignment updated.');
    }

    private function quizInput(Request $request): array
    {
        $request->merge(['quiz_questions' => [[
            'question' => $request->input('question'),
            'question_type' => $request->input('question_type', 'single'),
            'options' => array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $request->input('options'))), fn ($line) => $line !== '')),
            'correct_answer' => $request->filled('correct_answer') ? (int) $request->input('correct_answer') - 1 : null,
            'correct_answers' => array_values(array_map(fn ($value) => (int) $value - 1,
                array_filter(array_map('trim', explode(',', (string) $request->input('correct_answers'))), fn ($value) => $value !== ''))),
        ]]]);
        return LessonQuiz::validate($request);
    }

    public function storeQuiz(Request $request, Course $course, Section $section)
    {
        $this->author($request, $course);
        abort_unless($section->course_id === $course->id, 404);
        $data = $this->quizInput($request);
        DB::transaction(function () use ($section, $data) {
            $lesson = $section->lessons()->create(['title' => $data['quiz_title'], 'type' => 'quiz',
                'order' => ((int) $section->lessons()->max('order')) + 1]);
            LessonQuiz::save($lesson, $data);
        });
        CourseService::refreshEnrollmentProgress($course);
        return back()->with('success', 'Quiz added.');
    }

    public function addQuizQuestion(Request $request, Course $course, Quiz $quiz)
    {
        $this->author($request, $course);
        abort_unless($quiz->lesson?->section?->course_id === $course->id, 404);
        $request->merge(['quiz_title' => $quiz->title, 'quiz_pass_score' => $quiz->pass_score]);
        $question = $this->quizInput($request)['quiz_questions'][0];
        $quiz->questions()->create([
            'question' => $question['question'], 'question_type' => $question['question_type'],
            'options' => $question['options'] ?? [], 'correct_answer' => (string) ($question['correct_answer'] ?? ''),
            'correct_answers' => $question['correct_answers'] ?? null,
            'order' => ((int) $quiz->questions()->max('order')) + 1,
        ]);
        return back()->with('success', 'Question added.');
    }

    public function gradeQuiz(Request $request, Course $course, QuizAttempt $attempt)
    {
        $this->author($request, $course);
        $quiz = $attempt->quiz()->with('questions')->firstOrFail();
        abort_unless($quiz->lesson?->section?->course_id === $course->id, 404);
        abort_unless($attempt->status === 'pending' && $quiz->attempts()->where('user_id', $attempt->user_id)->latest('id')->value('id') === $attempt->id, 422);
        $data = $request->validate(['open_results' => ['required', 'array'],
            'open_results.*' => ['required', 'boolean'], 'feedback' => ['nullable', 'string', 'max:5000']]);
        $openIds = $quiz->questions->where('question_type', 'open')->pluck('id');
        abort_unless($openIds->isNotEmpty() && $openIds->every(fn ($id) => array_key_exists($id, $data['open_results'])), 422, 'Grade every open answer.');
        $manual = collect($data['open_results'])->only($openIds->map(fn ($id) => (string) $id)->all())->map(fn ($value) => (bool) $value)->all();
        $score = QuizGrader::score($quiz->questions, $attempt->answers, $manual, $quiz);
        $passed = $score >= $quiz->pass_score;
        DB::transaction(function () use ($attempt, $manual, $score, $passed, $request, $data, $quiz) {
            $attempt->update(['manual_results' => $manual, 'score' => $score, 'passed' => $passed,
                'status' => 'graded', 'feedback' => $data['feedback'] ?? null,
                'graded_by' => $request->user()->id, 'graded_at' => now()]);
            if ($passed) CourseService::updateLessonProgress($attempt->user, $quiz->lesson, 100);
        });
        return back()->with('success', 'Open answers graded.');
    }

    public function submitAssignment(Request $request, Course $course, Assignment $assignment)
    {
        abort_unless($assignment->lesson?->section?->course_id === $course->id, 404);
        abort_unless(CourseService::hasAccess($request->user(), $course), 403);
        abort_if($assignment->due_at?->isPast(), 422, 'This assignment is closed.');
        $data = $request->validate([
            'answer' => ['nullable', 'string', 'max:30000', 'required_without:file'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg'],
        ]);
        $existing = $assignment->submissions()->where('user_id', $request->user()->id)->first();
        abort_if($existing?->status === 'graded' && $existing->score >= $assignment->pass_score, 422, 'This assignment is already passed.');
        $file = $request->file('file');
        $assignment->submissions()->updateOrCreate(['user_id' => $request->user()->id], [
            'answer' => $data['answer'] ?? null,
            'file_path' => $file ? $file->store('assignment-submissions', 'local') : $existing?->file_path,
            'file_name' => $file?->getClientOriginalName() ?? $existing?->file_name,
            'status' => 'submitted', 'score' => null, 'feedback' => null,
            'graded_by' => null, 'graded_at' => null, 'submitted_at' => now(),
        ]);
        if ($request->expectsJson()) return response()->json(['success' => true, 'message' => 'Assignment submitted privately for grading.']);
        return back()->with('success', 'Assignment submitted privately for grading.');
    }

    public function grade(Request $request, Course $course, AssignmentSubmission $submission)
    {
        $this->author($request, $course);
        abort_unless($submission->assignment?->lesson?->section?->course_id === $course->id, 404);
        abort_if($submission->status === 'graded' && $submission->score >= $submission->assignment->pass_score, 422, 'A passed submission cannot be regraded.');
        $data = $request->validate(['score' => ['required', 'integer', 'between:0,100'],
            'feedback' => ['nullable', 'string', 'max:5000']]);
        DB::transaction(function () use ($submission, $data, $request) {
            $submission->update(['status' => 'graded', 'score' => $data['score'],
                'feedback' => $data['feedback'] ?? null, 'graded_by' => $request->user()->id,
                'graded_at' => now()]);
            if ($data['score'] >= $submission->assignment->pass_score) {
                CourseService::updateLessonProgress($submission->user, $submission->assignment->lesson, 100);
            }
        });
        return back()->with('success', 'Grade saved and learner progress updated when passed.');
    }

    public function download(Request $request, Course $course, AssignmentSubmission $submission)
    {
        abort_unless($submission->assignment?->lesson?->section?->course_id === $course->id, 404);
        abort_unless($submission->user_id === $request->user()->id || $course->canManageContent($request->user()), 403);
        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);
        return Storage::disk('local')->download($submission->file_path, $submission->file_name);
    }

    public function announce(Request $request, Course $course)
    {
        $this->author($request, $course);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000']]);
        $course->announcements()->create($data + ['author_id' => $request->user()->id]);
        return back()->with('success', 'Announcement posted to enrolled learners.');
    }

    public function uploadMedia(Request $request, Course $course)
    {
        $this->author($request, $course);
        $data = $request->validate(['file' => ['required', 'file', 'max:512000']]);
        $upload = MediaUploader::upload($data['file'], 'course-materials', false);
        $upload->update(['used_in' => ['course_id' => $course->id]]);
        return response()->json(['success' => true, 'id' => $upload->id, 'uuid' => $upload->uuid,
            'name' => $upload->original_name, 'type' => $upload->type, 'size' => $upload->human_size,
            'path' => $upload->path, 'url' => route('media.course', [$course, $upload->uuid])], 201);
    }

    public function media(Request $request, Course $course)
    {
        $this->author($request, $course);
        $files = Upload::where('used_in->course_id', $course->id)
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->when($request->search, fn ($query, $search) => $query->where('original_name', 'like', "%{$search}%"))
            ->latest()->paginate(20);

        return response()->json(['data' => $files->map(fn ($file) => [
            'id' => $file->id, 'uuid' => $file->uuid, 'name' => $file->original_name,
            'url' => route('media.course', [$course, $file->uuid]), 'path' => $file->path,
            'type' => $file->type, 'size' => $file->human_size, 'extension' => $file->extension,
        ]), 'next_page_url' => $files->nextPageUrl(), 'total' => $files->total()]);
    }
}
