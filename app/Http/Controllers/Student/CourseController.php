<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Upload;
use App\Models\Section;
use App\Models\Setting;
use App\Services\CourseService;
use App\Services\MediaUploader;
use App\Services\ScormPackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    // ── Course detail / landing page ──────────────────────────────────
    public function detail(string $slug)
    {
        $course = Course::where('slug',$slug)
            ->marketplaceLive()
            ->with(['sections.lessons'])
            ->withCount('enrollments')
            ->firstOrFail();

        $user       = Auth::user();
        $enrollment = $user
            ? Enrollment::where('user_id',$user->id)->where('course_id',$course->id)->first()
            : null;

        $hasAccess  = $user ? CourseService::hasAccess($user, $course) : false;

        // Block restricted courses from non-allowed visitors
        if ($course->visibility === 'restricted') {
            if (!$user) {
                return redirect()->route('login')->with('error','Please login to access this course.');
            }
            $isAdmin    = $user->hasRole('super-admin') || $user->hasRole('admin');
            $isAllowed  = $course->allowedUsers()->where('user_id',$user->id)->exists();
            if (!$isAdmin && !$isAllowed && !$hasAccess) {
                abort(403,'You do not have access to this course.');
            }
        }
        $isExpired  = $enrollment && $enrollment->access_type==='limited' && $enrollment->expires_at?->isPast();

        $totalLessons  = $course->sections->sum(fn($s) => $s->lessons->count());
        $totalDuration = $course->sections->flatMap->lessons->sum('duration');

        $requirements = is_array($course->requirements)
            ? $course->requirements
            : (json_decode($course->requirements ?? '[]', true) ?? []);

        $outcomes = is_array($course->outcomes)
            ? $course->outcomes
            : (json_decode($course->outcomes ?? '[]', true) ?? []);

        $checkoutIssue = app(\App\Services\PaymentService::class)->checkoutUnavailableReason();

        return view('student.course-detail', compact(
            'course','enrollment','hasAccess','isExpired',
            'totalLessons','totalDuration','requirements','outcomes','checkoutIssue'
        ));
    }

    // ── Free enroll ────────────────────────────────────────────────────
    public function enrollFree(Request $request, Course $course)
    {
        abort_unless($course->isMarketplaceLive(), 404, 'This course is not available for enrollment.');
        if (!$course->is_free && $course->price > 0) {
            return response()->json(['error'=>'This course requires payment.'], 422);
        }

        $enrollment = CourseService::enrollFree(Auth::user(), $course);

        if ($request->expectsJson()) {
            return response()->json(['success'=>true,'redirect'=>route('student.learn',$course->id)]);
        }
        return redirect()->route('student.learn',$course->id)
            ->with('success',"Enrolled in \"{$course->title}\"! 🎉");
    }

    // ── Create Razorpay order for course ──────────────────────────────
    public function createOrder(Request $request, Course $course)
    {
        $request->merge(['course_id' => $course->id]);
        return app(\App\Http\Controllers\Payment\PaymentController::class)->createOrder($request);
    }

    // ── Verify payment & enroll ────────────────────────────────────────
    public function verifyPayment(Request $request, Course $course)
    {
        $request->merge(['course_id' => $course->id]);
        return app(\App\Http\Controllers\Payment\PaymentController::class)->verify($request);
    }

    // ── Course learn page ──────────────────────────────────────────────
    public function learn(Course $course)
    {
        $user = Auth::user();

        if (!CourseService::hasAccess($user, $course)) {
            return redirect()->route('student.course-detail', $course->slug)
                ->with('error','Please enroll to access this course.');
        }

        $course->load('sections.lessons', 'announcements.author');
        $materialsByLesson = $this->materialsForLessons($course->sections->flatMap->lessons);
        $courseMaterials = $this->materialsForLessons(collect([$course]))[$course->id] ?? [];
        $enrollment = Enrollment::where('user_id',$user->id)->where('course_id',$course->id)->first();

        // Get lesson progress for this user
        $allLessonIds   = $course->sections->flatMap->lessons->pluck('id');
        $lessonProgress = DB::table('lesson_progress')
            ->where('user_id',$user->id)
            ->whereIn('lesson_id',$allLessonIds)
            ->pluck('progress','lesson_id');
        $completedIds   = DB::table('lesson_progress')
            ->where('user_id',$user->id)
            ->whereIn('lesson_id',$allLessonIds)
            ->where('completed',1)
            ->pluck('lesson_id');

        // First incomplete lesson
        $currentLesson = $course->sections->flatMap->lessons
            ->first(fn($l) => !$completedIds->contains($l->id))
            ?? $course->sections->first()?->lessons->first();

        $certificate = \App\Models\Certificate::where('user_id',$user->id)
            ->where('course_id',$course->id)->first();

        $razorpayKey = ''; // not needed on learn page

        return view('student.learn', compact(
            'course','enrollment','lessonProgress','completedIds',
            'currentLesson','certificate','materialsByLesson','courseMaterials'
        ));
    }

    public function preview(Course $course)
    {
        $this->authorizePreview($course);
        $course->load('sections.lessons', 'announcements.author');
        $materialsByLesson = $this->materialsForLessons($course->sections->flatMap->lessons, $course);
        $courseMaterials = $this->materialsForLessons(collect([$course]), $course)[$course->id] ?? [];
        $enrollment = null;
        $lessonProgress = collect();
        $completedIds = collect();
        $currentLesson = $course->sections->first()?->lessons->first();
        $certificate = null;
        $isCreatorPreview = true;
        $previewEditorUrl = match (request()->query('from')) {
            'vendor' => $course->vendor_id === session('active_vendor_id') ? route('vendor.courses.edit', $course) : route('admin.courses.edit', $course),
            'instructor' => route('instructor.courses.show', $course),
            default => Auth::user()->hasRole(['admin', 'super-admin']) ? route('admin.courses.edit', $course)
                : ($course->vendor_id && $course->vendor_id === session('active_vendor_id')
                    ? route('vendor.courses.edit', $course) : route('instructor.courses.show', $course)),
        };

        return view('student.learn', compact(
            'course', 'enrollment', 'lessonProgress', 'completedIds',
            'currentLesson', 'certificate', 'materialsByLesson', 'courseMaterials', 'isCreatorPreview', 'previewEditorUrl'
        ));
    }

    public function previewLesson(Course $course, Lesson $lesson)
    {
        $this->authorizePreview($course);
        abort_unless($lesson->section?->course_id === $course->id, 404);

        return $this->lessonResponse($course, $lesson, true);
    }

    private function authorizePreview(Course $course): void
    {
        $user = Auth::user();
        abort_unless($user && $course->canManageContent($user), 403);
    }

    // ── Get lesson for player (AJAX) ───────────────────────────────────
    public function getLesson(Course $course, Lesson $lesson)
    {
        $user = Auth::user();
        if ($lesson->section?->course_id !== $course->id || !CourseService::hasAccess($user, $course)) {
            return response()->json(['error'=>'Access denied.'], 403);
        }
        if ($course->enforce_lesson_completion) {
            $lessonIds = $course->sections()->with('lessons:id,section_id,order')->get()
                ->flatMap->lessons->pluck('id')->values();
            $position = $lessonIds->search($lesson->id);
            $requiredIds = $position === false ? collect() : $lessonIds->take($position);
            $completed = DB::table('lesson_progress')->where('user_id', $user->id)
                ->whereIn('lesson_id', $requiredIds)->where('completed', true)->count();
            if ($requiredIds->isNotEmpty() && $completed !== $requiredIds->count()) {
                return response()->json(['error' => 'Complete the previous lesson before opening this one.'], 423);
            }
        }

        return $this->lessonResponse($course, $lesson);
    }

    private function lessonResponse(Course $course, Lesson $lesson, bool $preview = false)
    {
        $user = Auth::user();
        // Signed video URL for R2 videos
        $videoUrl = null;
        if ($lesson->video_storage === 'r2' && $lesson->video_r2_path) {
            try {
                $videoUrl = Storage::disk('r2')->temporaryUrl($lesson->video_r2_path, now()->addMinutes(120));
            } catch (\Exception $e) {
                $videoUrl = null;
            }
        } else {
            $videoUrl = $lesson->video_url;
        }

        $progress = $preview ? null : DB::table('lesson_progress')
            ->where('user_id',$user->id)->where('lesson_id',$lesson->id)
            ->first();

        $quizData = null;
        if ($lesson->type === 'quiz' && $quiz = $lesson->quiz()->with('questions')->first()) {
            $latestAttempt = $preview ? null : $quiz->attempts()->where('user_id', $user->id)->latest('id')->first();
            $quizData = [
                'title' => $quiz->title,
                'pass_score' => $quiz->pass_score,
                'pending' => $latestAttempt?->status === 'pending',
                'feedback' => $latestAttempt?->feedback,
                'last_score' => $latestAttempt?->status === 'graded' ? $latestAttempt->score : null,
                'questions' => $quiz->questions->sortBy('order')->map(fn($q) => [
                    'id' => $q->id, 'question' => $q->question, 'options' => $q->options,
                    'type' => $q->question_type ?: 'single',
                ])->values(),
            ];
        }

        $assignmentData = null;
        if ($lesson->type === 'assignment' && $assignment = $lesson->assignment) {
            $submission = $preview ? null : $assignment->submissions()->where('user_id', $user->id)->first();
            $assignmentData = [
                'id' => $assignment->id, 'instructions' => $assignment->instructions,
                'pass_score' => $assignment->pass_score, 'due_at' => $assignment->due_at?->toIso8601String(),
                'submission' => $submission ? [
                    'status' => $submission->status, 'answer' => $submission->answer,
                    'score' => $submission->score, 'feedback' => $submission->feedback,
                    'file_name' => $submission->file_name,
                    'file_url' => $submission->file_path ? route('courses.teaching.submissions.file', [$course, $submission]) : null,
                ] : null,
            ];
        }

        return response()->json([
            'id'           => $lesson->id,
            'title'        => $lesson->title,
            'type'         => $lesson->type,
            'video_url'    => $videoUrl,
            'content'      => $lesson->content,
            // New editor documents are rendered client-side; legacy content remains unchanged.
            'block_document' => $lesson->block_document,
            'notes'        => $lesson->notes,
            'materials'    => $this->materialsForLessons(collect([$lesson]), $preview ? $course : null)[$lesson->id] ?? [],
            'duration'     => $lesson->duration ? (int) ceil($lesson->duration / 60) : 0,
            'is_preview'   => $lesson->is_preview,
            'progress'     => $progress?->progress ?? 0,
            'completed'    => (bool)($progress?->completed ?? false),
            'scorm_launch_url' => $lesson->type === 'scorm' ? $this->scormLaunchUrl($course, $lesson->section) : null,
            'scorm_data'   => $lesson->type === 'scorm' ? ($progress?->scorm_data ?? '') : null,
            'quiz'         => $quizData,
            'assignment'   => $assignmentData,
            'live_platform'=> $lesson->live_platform,
            'live_url'     => $lesson->live_url,
            'live_scheduled_at' => $lesson->live_scheduled_at?->toIso8601String(),
            'live_meeting_id'   => $lesson->live_meeting_id,
            'live_password'     => $lesson->live_password,
        ]);
    }

    private function materialsForLessons($lessons, ?Course $previewCourse = null): array
    {
        $ids = $lessons->flatMap(fn($lesson) => collect($lesson->resources ?? [])
            ->flatMap(fn($resource) => [$resource['upload_id'] ?? null, $resource['preview_upload_id'] ?? null]))
            ->filter()->unique();
        $query = Upload::whereIn('id', $ids);
        if (!$previewCourse) $query->where('is_public', true);
        $uploads = $query->get()->keyBy('id');
        $materials = [];
        foreach ($lessons as $lesson) {
            $materials[$lesson->id] = [];
            foreach ($lesson->resources ?? [] as $resource) {
                $file = $uploads->get($resource['upload_id'] ?? null);
                if (!$file) continue;
                $preview = $uploads->get($resource['preview_upload_id'] ?? null);
                $materials[$lesson->id][] = [
                    'title' => $resource['title'] ?? $file->original_name,
                    'kind' => $resource['kind'] ?? 'study_material',
                    'type' => $file->type,
                    'url' => $previewCourse ? route('media.course', [$previewCourse, $file->uuid]) : $file->public_url,
                    'preview_url' => $preview ? ($previewCourse ? route('media.course', [$previewCourse, $preview->uuid]) : $preview->public_url) : null,
                    'download_url' => $previewCourse ? route('media.course', [$previewCourse, $file->uuid]) : route('media.download', $file->uuid),
                ];
            }
        }
        return $materials;
    }

    // ── Save lesson progress ───────────────────────────────────────────
    public function saveProgress(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->section?->course_id === $course->id && CourseService::hasAccess(Auth::user(), $course), 403);
        abort_if(in_array($lesson->type, ['quiz', 'assignment'], true), 422, 'Complete the graded activity to finish this lesson.');
        $request->validate([
            'progress'        => 'required|numeric|min:0|max:100',
            'watched_seconds' => 'nullable|integer|min:0',
            'scorm_data'      => 'nullable|string|max:65535',
        ]);

        if ($lesson->type === 'scorm') {
            DB::table('lesson_progress')->updateOrInsert(
                ['user_id' => Auth::id(), 'lesson_id' => $lesson->id],
                ['scorm_data' => $request->input('scorm_data'), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $user   = Auth::user();
        $result = CourseService::updateLessonProgress(
            $user, $lesson,
            (int)$request->progress,
            (int)($request->watched_seconds ?? 0)
        );

        return response()->json([
            'ok'             => true,
            'lesson_done'    => $result['progress'] >= 90 || $request->progress >= 90,
            'course_progress'=> $result['progress'],
            'course_completed'=> $result['completed'],
            'certificate'    => $result['certificate'] ? [
                'number' => $result['certificate']->certificate_number,
                'issued' => $result['certificate']->issued_at?->format('d M Y'),
            ] : null,
        ]);
    }

    public function scormAsset(Course $course, Section $section, string $path, ScormPackageService $scormPackages)
    {
        abort_unless($section->course_id === $course->id && $section->is_scorm, 404);
        $user = Auth::user();
        $ownsCourse = $course->canManageContent($user);
        abort_unless($ownsCourse || CourseService::hasAccess($user, $course), 403);

        $asset = $scormPackages->assetPath($section, $path);
        return response()->file($asset, [
            'Content-Type' => File::mimeType($asset) ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function scormLaunchUrl(Course $course, Section $section): ?string
    {
        if (!$section->scorm_launch_path) return null;
        $parts = parse_url($section->scorm_launch_path);
        $url = route('student.scorm.asset', [$course, $section, $parts['path'] ?? $section->scorm_launch_path]);
        return $url.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    public function submitQuiz(Request $request, Course $course, Lesson $lesson)
    {
        $user = Auth::user();
        abort_unless($lesson->section?->course_id === $course->id && CourseService::hasAccess($user, $course), 403);
        abort_unless($lesson->type === 'quiz', 404);
        $data = $request->validate([
            'answers' => 'required|array',
        ]);
        $quiz = $lesson->quiz()->with('questions')->firstOrFail();
        abort_if($quiz->attempts()->where('user_id', $user->id)->latest('id')->value('status') === 'pending', 422, 'Your open answers are awaiting grading.');
        $questions = $quiz->questions;
        abort_unless($questions->isNotEmpty() && $questions->every(fn($q) => array_key_exists($q->id, $data['answers'])), 422, 'Answer every question.');
        try {
            $score = \App\Services\QuizGrader::score($questions, $data['answers']);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $pending = $questions->contains(fn ($question) => $question->question_type === 'open');
        $passed = !$pending && $score >= $quiz->pass_score;
        $quiz->attempts()->create(['user_id' => $user->id, 'score' => $score,
            'passed' => $passed, 'answers' => $data['answers'],
            'status' => $pending ? 'pending' : 'graded', 'graded_at' => $pending ? null : now()]);
        $result = $passed ? CourseService::updateLessonProgress($user, $lesson, 100) : null;

        return response()->json([
            'score' => $score,
            'passed' => $passed,
            'pending' => $pending,
            'pass_score' => $quiz->pass_score,
            'course_progress' => $result['progress'] ?? Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->value('progress') ?? 0,
            'certificate' => $result['certificate']?->certificate_number ?? null,
        ]);
    }
}
