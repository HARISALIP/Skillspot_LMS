<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Upload;
use App\Services\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseWorkspaceController extends Controller
{
    public function index()
    {
        $categoryRecords = CourseCategory::where('is_active', true)->orderBy('order_col')->get(['id', 'name']);
        $instructors = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['teacher', 'evaluator']))
            ->orWhereHas('instructorProfile', fn ($query) => $query->whereIn('status', ['pending', 'approved', 'rejected']))
            ->orderBy('name')->get(['id', 'name', 'email']);
        if (!$instructors->contains('id', auth()->id())) $instructors->prepend(auth()->user());
        $canCreateInstructors = false;
        $inviteMailReady = false;
        return view('instructor.workspace', compact('categoryRecords', 'instructors', 'canCreateInstructors', 'inviteMailReady') + ['course' => null]);
    }

    public function show(Course $course)
    {
        $this->authorizeCourse($course);
        return app(\App\Http\Controllers\Admin\CoursesController::class)
            ->edit($course)
            ->with('creatorWorkspace', true);
    }

    public function courses(Request $request)
    {
        $query = Course::query()->withCount('enrollments')->latest();
        $this->scopeToAuthor($query, $request->user());

        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('category', 'like', "%{$search}%"));
        }

        return $query->get()->map(fn (Course $course) => $this->courseData($course));
    }

    public function course(Course $course)
    {
        $this->authorizeCourse($course);
        $course->load(['sections.lessons', 'teachers:id,name,email'])->loadCount('enrollments');

        return $this->courseData($course, true);
    }

    public function quizLibrary(Course $course)
    {
        $this->authorizeCourse($course);
        return \App\Models\Quiz::query()->whereNull('lesson_id')
            ->when(!request()->user()->hasRole(['admin', 'super-admin']), fn ($query) => $query->where('owner_id', request()->user()->id))
            ->withCount('questions')->orderBy('title')->get()->map(fn ($quiz) => [
                'id' => $quiz->id, 'title' => $quiz->title, 'questions_count' => $quiz->questions_count,
            ]);
    }

    public function storeCourse(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'short_introduction' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:100000',
            'price' => 'nullable|numeric|min:0|max:9999999',
            'is_free' => 'nullable|boolean',
        ]);

        $course = DB::transaction(function () use ($data, $request) {
            $course = Course::create([
                ...$data,
                'category' => $data['category'] ?? 'General',
                'level' => 'beginner',
                'language' => 'en',
                'price' => $data['price'] ?? 0,
                'is_free' => $data['is_free'] ?? true,
                'visibility' => 'draft',
                'is_published' => false,
                'primary_instructor_id' => $request->user()->id,
            ]);
            $course->teachers()->syncWithoutDetaching([$request->user()->id => ['added_by' => $request->user()->id]]);
            return $course;
        });

        return response()->json($this->courseData($course), 201);
    }

    public function updateCourse(Request $request, Course $course)
    {
        $this->authorizeCourse($course);
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|nullable|string|max:100',
            'short_introduction' => 'sometimes|nullable|string|max:1000',
            'description' => 'sometimes|nullable|string|max:100000',
            'thumbnail' => 'sometimes|nullable|string|max:500',
            'price' => 'sometimes|required|numeric|min:0|max:9999999',
            'is_free' => 'sometimes|required|boolean',
            'is_preview' => 'prohibited',
        ]);
        $course->update($data);
        return $this->courseData($course->fresh());
    }

    public function storeSection(Request $request, Course $course)
    {
        $this->authorizeCourse($course);
        $data = $request->validate(['title' => 'required|string|max:255']);
        $section = $course->sections()->create($data + ['order' => ((int) $course->sections()->max('order')) + 1]);
        return response()->json($this->sectionData($section, true), 201);
    }

    public function updateSection(Request $request, Course $course, Section $section)
    {
        $this->belongsToCourse($course, $section);
        $this->authorizeCourse($course);
        $section->update($request->validate(['title' => 'required|string|max:255']));
        return $this->sectionData($section->fresh());
    }

    public function storeLesson(Request $request, Course $course, Section $section)
    {
        $this->belongsToCourse($course, $section);
        $this->authorizeCourse($course);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'is_preview' => 'nullable|boolean',
        ]);
        $lesson = $section->lessons()->create([
            'title' => $data['title'],
            'type' => 'text',
            'is_preview' => $data['is_preview'] ?? false,
            'order' => ((int) $section->lessons()->max('order')) + 1,
            'block_document' => ['time' => now()->valueOf(), 'blocks' => [], 'version' => '2.29.0'],
        ]);
        CourseService::refreshEnrollmentProgress($course);
        return response()->json($this->lessonData($lesson), 201);
    }

    public function updateLesson(Request $request, Course $course, Section $section, Lesson $lesson)
    {
        $this->belongsToCourse($course, $section, $lesson);
        $this->authorizeCourse($course);
        abort_if(!in_array($lesson->type, ['text', 'video', 'pdf'], true), 422, 'Edit graded and live lessons in Teaching tools or the existing course editor.');
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|in:text,video,pdf',
            'video_url' => ['sometimes', 'nullable', 'string', 'max:500', function ($attribute, $value, $fail) use ($course) {
                if ($value && !str_starts_with($value, url('/course-media/'.$course->id.'/'))
                    && (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true))) $fail('Enter an HTTP or HTTPS video URL.');
            }],
            'duration' => 'sometimes|integer|min:0|max:1440',
            'resources' => 'sometimes|array|max:30',
            'resources.*.title' => 'required|string|max:255',
            'resources.*.kind' => 'required|in:study_material,image,slides,question_paper',
            'resources.*.upload_id' => 'required|integer|exists:uploads,id',
            'is_preview' => 'sometimes|boolean',
            'block_document' => 'sometimes|array',
            'block_document.blocks' => 'required_with:block_document|array|max:500',
            'block_document.time' => 'nullable|integer',
            'block_document.version' => 'nullable|string|max:40',
            'instructor_notes_document' => 'sometimes|nullable|array',
            'instructor_notes_document.blocks' => 'required_with:instructor_notes_document|array|max:500',
            'instructor_notes_document.time' => 'nullable|integer',
            'instructor_notes_document.version' => 'nullable|string|max:40',
        ]);
        if (array_key_exists('block_document', $data)) {
            $quizBlock = collect($data['block_document']['blocks'] ?? [])->first(fn ($block) => ($block['type'] ?? null) === 'quiz');
            $quizId = data_get($quizBlock, 'data.quiz_id');
            if ($quizId) {
                $quiz = \App\Models\Quiz::whereNull('lesson_id')->findOrFail($quizId);
                abort_unless(request()->user()->hasRole(['admin', 'super-admin']) || $quiz->owner_id === request()->user()->id, 403);
            }
            $data['quiz_id'] = $quizId;
        }
        foreach ($data['resources'] ?? [] as $resource) {
            abort_unless((int) data_get(Upload::find($resource['upload_id'])?->used_in, 'course_id') === $course->id, 422, 'Files must belong to this course.');
        }
        if (isset($data['duration'])) $data['duration'] *= 60;
        $lesson->update($data);
        return $this->lessonData($lesson->fresh());
    }

    public function reorder(Request $request, Course $course)
    {
        $this->authorizeCourse($course);
        $data = $request->validate([
            'sections' => 'required|array|min:1',
            'sections.*.id' => 'required|integer',
            'sections.*.lessons' => 'present|array',
            'sections.*.lessons.*' => 'integer',
        ]);

        DB::transaction(function () use ($course, $data) {
            $sections = $course->sections()->with('lessons:id,section_id')->get()->keyBy('id');
            abort_unless($sections->count() === count($data['sections']), 422, 'Outline is out of date.');
            $knownLessons = $course->sections()->with('lessons:id,section_id')->get()->flatMap->lessons->pluck('id')->sort()->values()->all();
            $submittedLessons = collect($data['sections'])->flatMap(fn ($row) => $row['lessons'])->sort()->values()->all();
            abort_unless($knownLessons === $submittedLessons, 422, 'Outline is out of date.');

            foreach ($data['sections'] as $position => $row) {
                $section = $sections->get($row['id']);
                abort_unless($section, 422, 'Unknown chapter.');
                $section->update(['order' => $position + 1]);
                foreach ($row['lessons'] as $lessonPosition => $lessonId) {
                    Lesson::whereKey($lessonId)->update(['section_id' => $section->id, 'order' => $lessonPosition + 1]);
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    private function scopeToAuthor($query, $user): void
    {
        if ($user->hasRole(['admin', 'super-admin'])) return;
        $query->where(fn ($q) => $q->where('primary_instructor_id', $user->id)
            ->orWhere(fn ($vendorCourses) => $vendorCourses->whereNotNull('vendor_id')
                ->whereHas('teachers', fn ($teachers) => $teachers->whereKey($user->id))));
    }

    private function authorizeCourse(Course $course): void
    {
        $query = Course::whereKey($course->id);
        $this->scopeToAuthor($query, request()->user());
        abort_unless($query->exists(), 403);
    }

    private function belongsToCourse(Course $course, Section $section, ?Lesson $lesson = null): void
    {
        abort_unless($section->course_id === $course->id && (!$lesson || $lesson->section_id === $section->id), 404);
    }

    private function courseData(Course $course, bool $full = false): array
    {
        $data = [
            'id' => $course->id, 'title' => $course->title, 'slug' => $course->slug,
            'category' => $course->category, 'thumbnail' => $course->thumbnail,
            'short_introduction' => $course->short_introduction, 'description' => $course->description,
            'price' => $course->price, 'is_free' => (bool) $course->is_free,
            'marketplace_status' => $course->marketplace_status, 'enrollments_count' => $course->enrollments_count ?? 0,
            'updated_at' => optional($course->updated_at)->toIso8601String(),
        ];
        if ($full) {
            $data['sections'] = $course->sections->map(fn (Section $section) => $this->sectionData($section, true))->values();
            $data['teachers'] = $course->teachers->map->only(['id', 'name', 'email'])->values();
        }
        return $data;
    }

    private function sectionData(Section $section, bool $withLessons = false): array
    {
        $data = ['id' => $section->id, 'title' => $section->title, 'order' => $section->order];
        if ($withLessons) $data['lessons'] = $section->lessons->map(fn (Lesson $lesson) => $this->lessonData($lesson))->values();
        return $data;
    }

    private function lessonData(Lesson $lesson): array
    {
        return [
            'id' => $lesson->id, 'title' => $lesson->title, 'type' => $lesson->type,
            'is_preview' => (bool) $lesson->is_preview, 'order' => $lesson->order,
            'blocks' => $lesson->block_document,
            'video_url' => $lesson->video_url, 'duration' => $lesson->duration ? (int) ceil($lesson->duration / 60) : 0,
            'resources' => $lesson->resources ?? [],
            'quiz_id' => $lesson->quiz_id,
            'instructor_notes' => $lesson->instructor_notes_document,
            'legacy_content' => $lesson->block_document ? null : $lesson->content,
        ];
    }
}
