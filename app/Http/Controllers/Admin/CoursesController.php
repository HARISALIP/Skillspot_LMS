<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLevel;
use App\Models\CourseLanguage;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\Upload;
use App\Models\Enrollment;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Setting;
use App\Services\LessonQuiz;
use App\Services\CourseService;
use App\Services\ScormPackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CoursesController extends Controller
{
    private const MATERIAL_RULES = [
        'resources' => 'nullable|array|max:100',
        'resources.*.title' => 'required|string|max:120',
        'resources.*.kind' => 'required|in:slides,audio,image,notes,quiz_paper,question_paper,study_material',
        'resources.*.upload_id' => 'required|integer|exists:uploads,id',
        'resources.*.preview_upload_id' => 'nullable|integer|exists:uploads,id',
    ];

    private function getCategories(): array {
        if (!\Illuminate\Support\Facades\Schema::hasTable('course_categories')) {
            return ['Web Development', 'Mobile App Development', 'Data Science & AI', 'UI/UX Design'];
        }
        $cats = CourseCategory::where('is_active',1)->orderBy('order_col')->pluck('name')->toArray();
        return !empty($cats) ? $cats : ['Web Development', 'Mobile App Development', 'Data Science & AI', 'UI/UX Design'];
    }
    private function getLevels(): array {
        if (!\Illuminate\Support\Facades\Schema::hasTable('course_levels')) {
            return ['all-levels', 'beginner', 'intermediate', 'advanced'];
        }
        $lvls = CourseLevel::where('is_active',1)->orderBy('order_col')->pluck('slug')->toArray();
        return !empty($lvls) ? $lvls : ['all-levels', 'beginner', 'intermediate', 'advanced'];
    }
    private function getLanguages(): array {
        if (!\Illuminate\Support\Facades\Schema::hasTable('course_languages')) {
            return ['en' => 'English', 'hi' => 'Hindi', 'es' => 'Spanish'];
        }
        $langs = CourseLanguage::where('is_active',1)->orderBy('order_col')->pluck('name','code')->toArray();
        return !empty($langs) ? $langs : ['en' => 'English', 'hi' => 'Hindi', 'es' => 'Spanish'];
    }

    // ── List courses ───────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Course::with(['sections.lessons', 'primaryInstructor', 'vendor'])
            ->withCount(['enrollments', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest();

        if ($s = $request->search) {
            $query->where(function($q) use ($s) {
                $q->where('title','like',"%{$s}%")->orWhere('category','like',"%{$s}%");
            });
        }
        if ($request->category) $query->where('category', $request->category);
        if ($request->boolean('certification')) $query->where('enable_certification', true);
        $tab = $request->input('tab', 'published');
        if ($tab === 'published') $query->where('is_published', 1);
        if ($tab === 'upcoming') $query->where(fn ($q) => $q->where('upcoming', 1)->orWhereDate('batch_start_date', '>', today()));
        if ($tab === 'unpublished') $query->where('is_published', 0);
        if ($tab === 'created') $query->where(function ($q) {
            $q->where('primary_instructor_id', auth()->id())
                ->orWhereHas('teachers', fn ($teacher) => $teacher->whereKey(auth()->id()));
        });

        $pageLength = in_array((int) $request->input('limit', 24), [24, 60, 120], true) ? (int) $request->input('limit', 24) : 24;
        $courses    = $query->paginate($pageLength);
        $totalCourses   = Course::count();
        $published  = Course::where('is_published',1)->count();
        $draft      = Course::where('is_published',0)->count();
        $totalEnroll= Enrollment::count();

        $categories = $this->getCategories();
        $categoryRecords = CourseCategory::where('is_active', 1)->orderBy('order_col')->get(['id', 'name']);
        $instructors = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['teacher', 'evaluator']))
            ->orderBy('name')->get(['id', 'name', 'email']);
        if (auth()->user()->hasRole(['teacher', 'evaluator']) && !$instructors->contains('id', auth()->id())) $instructors->prepend(auth()->user());
        $canCreateInstructors = auth()->user()->hasRole(['admin', 'super-admin']);
        $inviteMailReady = $this->inviteMailReady();
        return view('admin.courses.index', compact(
            'courses','pageLength','totalCourses','published','draft','totalEnroll','categories','categoryRecords','instructors','canCreateInstructors','inviteMailReady'
        ));
    }

    // ── Create form ────────────────────────────────────────────────────
    public function create()
    {
        return redirect()->route('admin.courses', ['new' => 1]);
    }

    public function importForm()
    {
        return view('admin.courses.import');
    }

    public function importZip(Request $request)
    {
        $request->validate(['archive' => 'required|file|mimes:zip|max:51200']);
        $zip = new \ZipArchive();

        if ($zip->open($request->file('archive')->getRealPath()) !== true || $zip->numFiles > 500) {
            return back()->withErrors(['archive' => 'Choose a valid course ZIP with no more than 500 files.']);
        }

        $archiveSize = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);
            $archiveSize += $entry['size'] ?? 0;
            if ($archiveSize > 52428800 || (($entry['size'] ?? 0) > 1048576 && ($entry['comp_size'] ?? 0) * 100 < $entry['size'])) {
                $zip->close();
                return back()->withErrors(['archive' => 'The ZIP expands to more than 50 MB or is compressed too heavily.']);
            }
        }

        $courseFile = $zip->statName('course.json');
        $courseContents = $courseFile && $courseFile['size'] <= 1048576 ? $zip->getFromName('course.json') : false;
        $zip->close();
        $courseData = is_string($courseContents) ? json_decode($courseContents, true) : null;
        $title = is_array($courseData) && is_string($courseData['title'] ?? null) ? trim($courseData['title']) : '';

        if ($title === '' || strlen($title) > 255) {
            return back()->withErrors(['archive' => 'The ZIP must contain a course.json file with a course title.']);
        }

        $course = Course::create([
            'title' => $title,
            'description' => is_string($courseData['description'] ?? null) ? $courseData['description'] : null,
            'category' => is_string($courseData['category'] ?? null) ? $courseData['category'] : 'General',
            'level' => is_string($courseData['level'] ?? null) ? $courseData['level'] : 'beginner',
            'language' => is_string($courseData['language'] ?? null) ? $courseData['language'] : 'en',
            'price' => 0,
            'is_free' => true,
            'visibility' => 'draft',
            'is_published' => false,
            'primary_instructor_id' => auth()->id(),
        ]);

        $course->teachers()->syncWithoutDetaching([auth()->id() => ['added_by' => auth()->id()]]);
        return redirect()->route('admin.courses.edit', $course)
            ->with('success', "Course \"{$course->title}\" imported as a draft. Add its sections and lessons before publishing.");
    }

    // ── Store ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        if ($request->routeIs('instructor.courses.create.store')) $request->merge(['vendor_id' => null]);
        if (is_string($request->input('description'))) {
            $request->merge(['description' => $this->sanitizeRichText($request->input('description'))]);
        }
        if ($request->boolean('modal_create')) {
            $request->validate([
                'short_introduction' => 'required|string|max:1000',
                'description' => 'required|string',
                'instructor_ids' => 'required|array|min:1',
                'new_category' => 'nullable|string|max:100',
            ]);
            $description = (string) $request->input('description');
            if (trim(html_entity_decode(strip_tags($description))) === '' && !preg_match('/<(?:img|video|iframe)\b[^>]*\bsrc="[^"]+"/i', $description)) {
                throw ValidationException::withMessages(['description' => 'Enter a course description.']);
            }
        }
        $data = $this->validateCourse($request);
        $teacherIds = collect($request->input('instructor_ids', [auth()->id()]))->map(fn ($id) => (int) $id)->unique()->values();
        $allowed = User::whereKey($teacherIds)->where(function ($query) {
            $query->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['teacher', 'evaluator', 'admin', 'super-admin']))
                ->orWhereHas('instructorProfile', fn ($profile) => $profile->whereIn('status', ['pending', 'approved', 'rejected']));
        })->pluck('id');
        if ($teacherIds->diff($allowed)->isNotEmpty()) {
            throw ValidationException::withMessages(['instructor_ids' => 'Choose valid instructors.']);
        }
        if ($request->routeIs('vendor.courses.modal.store')) {
            $vendorTeachers = Vendor::findOrFail($data['vendor_id'])->teachers()->pluck('users.id');
            if ($teacherIds->diff($vendorTeachers)->isNotEmpty()) {
                throw ValidationException::withMessages(['instructor_ids' => 'Choose instructors assigned to this organization.']);
            }
        }
        if ($request->hasFile('thumbnail_file')) {
            $data['thumbnail'] = $this->saveUploadedFile($request->file('thumbnail_file'), 'course-thumbnails');
        }
        if ($request->hasFile('intro_video_file')) {
            $data['intro_video'] = $this->saveUploadedFile($request->file('intro_video_file'), 'course-previews');
        }
        $course = DB::transaction(function () use ($data, $request, $teacherIds) {
            if ($newCategory = trim((string) $request->input('new_category'))) {
                $data['category'] = CourseCategory::firstOrCreate(
                    ['name' => $newCategory],
                    ['is_active' => true, 'order_col' => ((int) CourseCategory::max('order_col')) + 1]
                )->name;
            }
            $data['primary_instructor_id'] = auth()->user()->hasRole(['admin', 'super-admin'])
                ? ($teacherIds->first() ?? auth()->id())
                : auth()->id();
            $data['marketplace_status'] = 'draft';
            $data['marketplace_visible'] = false;
            $data['visibility'] = 'draft';
            $data['is_published'] = false;
            $course = Course::create($data);
            $course->teachers()->syncWithPivotValues($teacherIds->all(), ['added_by' => auth()->id()]);
            $this->markMaterialUploadsUsedByCourse($course, $data['resources'] ?? []);
            return $course;
        });
        $destination = $request->routeIs('vendor.courses.modal.store') ? route('vendor.courses.edit', $course)
            : ($request->routeIs('instructor.courses.create.store') ? route('instructor.courses.show', $course) : route('admin.courses.edit', $course));
        return redirect($destination)
            ->with('success', "Course \"{$course->title}\" created ✅ Now add sections & lessons.");
    }

    public function storeInstructor(Request $request)
    {
        abort_unless($request->user()->hasRole(['admin', 'super-admin']), 403);
        if (!$this->inviteMailReady()) {
            throw ValidationException::withMessages([
                'email' => 'SMTP must be configured and a test email sent before member invitations can be created.',
            ]);
        }

        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['in:teacher,admin'],
        ]);

        $name = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) ?: Str::before($data['email'], '@');
        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'country' => $request->user()->country ?: 'IN',
            'country_name' => $request->user()->country_name ?: 'India',
            'registered_via' => 'admin',
            'portal_access' => 'skillspot_only',
            'password' => Hash::make(Str::random(64)),
        ]);
        $roles = array_unique(array_merge(['student'], $data['roles']));
        foreach ($roles as $role) Role::findOrCreate($role, 'web');
        $user->syncRoles($roles);

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
            if ($status !== Password::RESET_LINK_SENT) throw new \RuntimeException(__($status));
        } catch (\Throwable $exception) {
            $user->delete();
            throw ValidationException::withMessages(['email' => 'The invitation could not be sent. No member was created.']);
        }

        return response()->json(['instructor' => $user->only(['id', 'name', 'email']), 'roles' => $roles], 201);
    }

    private function inviteMailReady(): bool
    {
        return config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.from.address'))
            && filled(Setting::get('mail_invites_verified_at'));
    }

    // ── Edit form ──────────────────────────────────────────────────────
    public function edit(Course $course)
    {
        $this->authorizeCourseOwner($course);
        $course->load(['sections.lessons','allowedUsers','teachers','primaryInstructor','vendor','enrollments.user','reviews']);
        $materialUploads = Upload::whereIn('id', collect($course->resources ?? [])
            ->flatMap(fn($r) => [$r['upload_id'] ?? null, $r['preview_upload_id'] ?? null])->filter())
            ->pluck('original_name', 'id');
        $vendors = Vendor::where('status','active')->orderBy('brand_name')->get(['id','brand_name']);
        $instructors = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['teacher', 'evaluator']))
            ->orderBy('name')->get(['id', 'name', 'email']);
        if (auth()->user()->hasRole(['teacher', 'evaluator']) && !$instructors->contains('id', auth()->id())) $instructors->prepend(auth()->user());
        $relatedCourses = Course::whereKeyNot($course->id)
            ->where(function ($query) use ($course) {
                $query->where(function ($published) { $published->where('is_published', true)->where('visibility', 'public'); })
                    ->orWhereIn('id', $course->related_course_ids ?? []);
            })->orderBy('title')->get(['id', 'title', 'slug', 'is_published']);
        return view('admin.courses.form', [
            'course'     => $course,
            'vendors'    => $vendors,
            'categories' => $this->getCategories(),
            'levels'     => $this->getLevels(),
            'languages'  => $this->getLanguages(),
            'isEdit'     => true,
            'materialUploads' => $materialUploads,
            'instructors' => $instructors,
            'relatedCourses' => $relatedCourses,
        ]);
    }

    // ── Update ─────────────────────────────────────────────────────────
    public function update(Request $request, Course $course)
    {
        $this->authorizeCourseOwner($course);
        $creatorWorkspaceRequest = $request->routeIs('instructor.courses.update');
        if ($creatorWorkspaceRequest) {
            $request->merge([
                'vendor_id' => $course->vendor_id,
                'instructor_ids' => $course->teachers()->pluck('users.id')->whenEmpty(fn () => collect([$course->primary_instructor_id]))->all(),
            ]);
        }
        if (is_string($request->input('description'))) {
            $request->merge(['description' => $this->sanitizeRichText($request->input('description'))]);
        }
        $request->merge(['visibility' => $request->input('visibility') ?: $course->visibility]);
        $data = $this->validateCourse($request, $course->id);
        if ($creatorWorkspaceRequest) {
            $data['visibility'] = $course->visibility;
            $data['is_published'] = $course->is_published;
        }
        if ($request->boolean('related_course_ids_present')) {
            $data['related_course_ids'] = $request->input('related_course_ids', []);
        }
        if ($request->hasFile('thumbnail_file')) {
            $data['thumbnail'] = $this->saveUploadedFile($request->file('thumbnail_file'), 'course-thumbnails');
        } elseif ($request->boolean('remove_thumbnail')) {
            $data['thumbnail'] = null;
        }
        if ($request->hasFile('intro_video_file')) {
            $data['intro_video'] = $this->saveUploadedFile($request->file('intro_video_file'), 'course-previews');
        }
        if ($course->marketplace_status !== 'live' || !$course->marketplace_visible) {
            $data['visibility'] = 'draft';
            $data['is_published'] = false;
        }
        DB::transaction(function () use ($course, $data, $request) {
            $course->update($data);
            $this->markMaterialUploadsUsedByCourse($course, $data['resources'] ?? []);
            if ($request->has('instructor_ids')) {
                $teacherIds = collect($request->input('instructor_ids', []))->map(fn ($id) => (int) $id)->unique()->values();
                $course->teachers()->syncWithPivotValues($teacherIds, ['added_by' => auth()->id()]);
                if ($teacherIds->isNotEmpty()) $course->update(['primary_instructor_id' => $teacherIds->first()]);
            }
        });
        return redirect($this->workspaceEditUrl($course).'#settings')->with('success', "Course updated ✅");
    }

    // ── Toggle publish ─────────────────────────────────────────────────
    public function togglePublish(Course $course)
    {
        $this->authorizeCourseOwner($course);
        $publish = !$course->marketplace_visible;
        $course->update(['marketplace_status' => $publish ? 'live' : $course->marketplace_status, 'marketplace_visible' => $publish, 'visibility' => $publish ? 'public' : 'draft', 'is_published' => $publish]);
        return back()->with('success', $publish ? 'Course is live in the Skillspot marketplace.' : 'Course has been unpublished.');
    }

    public function export(Course $course)
    {
        $this->authorizeCourseOwner($course);
        $course->load('sections.lessons');

        $export = [
            'course' => $course->only(['title', 'slug', 'description', 'short_introduction', 'meta_description', 'meta_keywords', 'thumbnail', 'thumbnail_color', 'intro_video', 'language', 'level', 'category', 'tags', 'price', 'sale_price', 'is_free', 'is_featured', 'upcoming', 'enforce_lesson_completion', 'enable_certification', 'requirements', 'outcomes', 'resources', 'max_students', 'course_type', 'batch_name', 'batch_start_date', 'batch_end_date', 'registration_open']),
            'sections' => $course->sections->map(fn (Section $section) => [
                'title' => $section->title,
                'order' => $section->order,
                'lessons' => $section->lessons->map(fn (Lesson $lesson) => $lesson->only(['title', 'description', 'type', 'content', 'video_url', 'notes', 'resources', 'duration', 'is_preview', 'order', 'block_document', 'instructor_notes_document']))->values(),
            ])->values(),
        ];

        return response()->streamDownload(fn () => print(json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), Str::slug($course->title).'-course.json', ['Content-Type' => 'application/json']);
    }

    // Grant restricted access to specific user
    public function grantAccess(Request $request, Course $course)
    {
        $this->authorizeCourseOwner($course);
        $request->validate(['user_id'=>'required|exists:users,id']);
        $course->allowedUsers()->syncWithoutDetaching([
            $request->user_id => ['added_by'=>auth()->id(),'note'=>$request->input('note'),'created_at'=>now()]
        ]);
        $user = \App\Models\User::find($request->user_id);
        return back()->with('success', "Access granted to {$user->name} ✅");
    }

    // Revoke restricted access
    public function revokeAccess(Request $request, Course $course, $userId)
    {
        $this->authorizeCourseOwner($course);
        $course->allowedUsers()->detach($userId);
        return back()->with('success', 'Access revoked.');
    }

    // ── Delete ─────────────────────────────────────────────────────────
    public function destroy(Course $course, ScormPackageService $scormPackages)
    {
        $this->authorizeCourseOwner($course);
        $title = $course->title;
        $course->sections()->where('is_scorm', true)->get()->each(fn (Section $section) => $scormPackages->delete($section));
        $course->delete();
        return redirect()->route(request()->routeIs('instructor.courses.*') ? 'instructor.courses.index' : 'admin.courses')
            ->with('success', "Course \"{$title}\" deleted.");
    }

    // ── Section: store ─────────────────────────────────────────────────
    public function storeSection(Request $request, Course $course, ScormPackageService $scormPackages)
    {
        $this->authorizeCourseOwner($course);
        $data = $request->validateWithBag('addChapter', [
            'title' => 'required|string|max:255',
            'is_scorm' => 'sometimes|boolean',
            'scorm_package' => 'required_if:is_scorm,1|nullable|file|max:204800',
        ]);
        if ($request->boolean('is_scorm') && strtolower($request->file('scorm_package')?->getClientOriginalExtension() ?? '') !== 'zip') {
            $validation = ValidationException::withMessages(['scorm_package' => 'Please upload a ZIP package.']);
            $validation->errorBag = 'addChapter';
            throw $validation;
        }
        $order = $course->sections()->max('order') + 1;
        $section = null;
        try {
            DB::transaction(function () use ($request, $course, $data, $order, $scormPackages, &$section) {
                $section = $course->sections()->create([
                    'title' => $data['title'], 'order' => $order,
                    'is_scorm' => $request->boolean('is_scorm'),
                ]);
                if ($section->is_scorm) {
                    $section->update($scormPackages->install($request->file('scorm_package'), $course, $section));
                    $section->lessons()->create(['title' => $section->title, 'type' => 'scorm', 'order' => 1]);
                }
            });
        } catch (\RuntimeException $exception) {
            if ($section) $scormPackages->delete($section);
            $validation = ValidationException::withMessages(['scorm_package' => $exception->getMessage()]);
            $validation->errorBag = 'addChapter';
            throw $validation;
        }
        if ($section?->is_scorm) CourseService::refreshEnrollmentProgress($course);
        return redirect($this->workspaceEditUrl($course).'#editor')->with('success', 'Chapter added.');
    }

    // ── Section: update ────────────────────────────────────────────────
    public function updateSection(Request $request, Course $course, Section $section)
    {
        $this->authorizeCourseOwner($course);
        abort_unless($section->course_id === $course->id, 404);
        $request->validate(['title' => 'required|string|max:255']);
        $section->update(['title' => $request->title]);
        return back()->with('success', 'Section updated ✅');
    }

    // ── Section: delete ────────────────────────────────────────────────
    public function destroySection(Course $course, Section $section, ScormPackageService $scormPackages)
    {
        $this->authorizeCourseOwner($course);
        abort_unless($section->course_id === $course->id, 404);
        $hadLessons = $section->lessons()->exists();
        $scormPackages->delete($section);
        $section->delete();
        if ($hadLessons) CourseService::refreshEnrollmentProgress($course);
        if (request()->expectsJson()) return response()->noContent();
        return redirect($this->workspaceEditUrl($course).'#editor')->with('success', 'Chapter deleted.');
    }

    // ── Section: reorder ───────────────────────────────────────────────
    public function reorderSections(Request $request, Course $course)
    {
        $this->authorizeCourseOwner($course);
        foreach ($request->order ?? [] as $pos => $sectionId) {
            Section::where('id', $sectionId)->where('course_id', $course->id)
                   ->update(['order' => $pos + 1]);
        }
        return response()->json(['ok' => true]);
    }

    // ── Lesson: create form ────────────────────────────────────────────
    public function createLesson(Course $course, Section $section)
    {
        $this->authorizeCourseOwner($course);
        abort_unless($section->course_id === $course->id, 404);
        if (request()->boolean('embed')) {
            return view('admin.courses.lesson-frappe', [
                'course' => $course,
                'section' => $section,
                'lesson' => new Lesson(),
                'isEdit' => false,
            ]);
        }
        return view('admin.courses.lesson', [
            'course'  => $course,
            'section' => $section,
            'lesson'  => new Lesson(),
            'isEdit'  => false,
            'materialUploads' => [],
            'availableQuizzes' => $this->availableQuizzes(),
        ]);
    }

    // ── Lesson: store ──────────────────────────────────────────────────
    public function storeLesson(Request $request, Course $course, Section $section)
    {
        $this->authorizeCourseOwner($course);
        abort_unless($section->course_id === $course->id, 404);
        $data = $this->validateLesson($request);
        $quiz = $data['type'] === 'quiz' && empty($data['quiz_id']) ? LessonQuiz::validate($request) : null;
        $data['order'] = $section->lessons()->max('order') + 1;
        $lesson = DB::transaction(function () use ($section, $data, $quiz) {
            $lesson = $section->lessons()->create($data);
            $this->markMaterialUploadsUsedByCourse($section->course, $data['resources'] ?? []);
            if ($quiz) LessonQuiz::save($lesson, $quiz);
            return $lesson;
        });
        CourseService::refreshEnrollmentProgress($course);
        if ($request->boolean('embed')) return response('<script>window.parent.location.assign('.json_encode($this->workspaceEditUrl($course).'#editor').')</script>');
        return redirect($this->workspaceEditUrl($course))
            ->with('success', "Lesson \"{$lesson->title}\" added ✅");
    }

    // ── Lesson: edit form ──────────────────────────────────────────────
    public function editLesson(Course $course, Section $section, Lesson $lesson)
    {
        $this->authorizeCourseOwner($course);
        abort_unless($section->course_id === $course->id && $lesson->section_id === $section->id, 404);
        if (request()->boolean('embed')) {
            return view('admin.courses.lesson-frappe', compact('course', 'section', 'lesson') + ['isEdit' => true]);
        }
        $lesson->load('quiz.questions');
        $materialUploads = Upload::whereIn('id', collect($lesson->resources ?? [])
            ->flatMap(fn($r) => [$r['upload_id'] ?? null, $r['preview_upload_id'] ?? null])
            ->filter()->unique())->pluck('original_name', 'id');
        return view('admin.courses.lesson', [
            'course'  => $course,
            'section' => $section,
            'lesson'  => $lesson,
            'isEdit'  => true,
            'materialUploads' => $materialUploads,
            'availableQuizzes' => $this->availableQuizzes(),
        ]);
    }

    // ── Lesson: update ─────────────────────────────────────────────────
    public function updateLesson(Request $request, Course $course, Section $section, Lesson $lesson)
    {
        $this->authorizeCourseOwner($course);
        abort_unless($section->course_id === $course->id && $lesson->section_id === $section->id, 404);
        $data = $this->validateLesson($request);
        $quiz = $data['type'] === 'quiz' && empty($data['quiz_id']) ? LessonQuiz::validate($request) : null;
        DB::transaction(function () use ($course, $lesson, $data, $quiz) {
            $lesson->update($data);
            $this->markMaterialUploadsUsedByCourse($course, $data['resources'] ?? []);
            if ($quiz) LessonQuiz::save($lesson, $quiz);
        });
        if ($request->boolean('embed')) return response('<script>window.parent.location.assign('.json_encode($this->workspaceEditUrl($course).'#editor').')</script>');
        return redirect($this->workspaceEditUrl($course))
            ->with('success', "Lesson updated ✅");
    }

    // ── Lesson: delete ─────────────────────────────────────────────────
    public function destroyLesson(Course $course, Section $section, Lesson $lesson)
    {
        $this->authorizeCourseOwner($course);
        abort_unless($section->course_id === $course->id && $lesson->section_id === $section->id, 404);
        $lesson->delete();
        CourseService::refreshEnrollmentProgress($course);
        return back()->with('success', 'Lesson deleted.');
    }

    // ── Lesson: reorder ────────────────────────────────────────────────
    public function reorderLessons(Request $request, Course $course, Section $section)
    {
        $this->authorizeCourseOwner($course);
        abort_unless($section->course_id === $course->id, 404);
        foreach ($request->order ?? [] as $pos => $lessonId) {
            Lesson::where('id', $lessonId)->where('section_id', $section->id)
                  ->update(['order' => $pos + 1]);
        }
        return response()->json(['ok' => true]);
    }

    // ── Validate course fields ─────────────────────────────────────────
    private function validateCourse(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate(array_merge([
            'vendor_id'         => 'nullable|exists:vendors,id',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'short_introduction'=> 'nullable|string|max:1000',
            'meta_description'  => 'nullable|string|max:500',
            'meta_keywords'     => 'nullable|string|max:1000',
            'category'          => 'nullable|string|max:100',
            'tags'              => 'nullable|string|max:1000',
            'level'             => 'required|string|max:60',
            'language'          => 'required|string|max:10',
            'price'             => 'required|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0',
            'is_free'           => 'boolean',
            'is_featured'       => 'boolean',
            'upcoming'          => 'boolean',
            'enforce_lesson_completion' => 'boolean',
            'enable_certification' => 'boolean',
            'visibility'        => 'nullable|in:draft,public,restricted',
            'thumbnail'         => 'nullable|string|max:500',
            'thumbnail_color'   => ['nullable','regex:/^#[0-9a-fA-F]{6}$/'],
            'thumbnail_file'    => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'remove_thumbnail'  => 'nullable|boolean',
            'intro_video_file'  => 'nullable|file|mimes:mp4,webm,ogg,ogv|max:51200',
            'instructor_ids'    => 'nullable|array',
            'instructor_ids.*'  => 'integer|exists:users,id',
            'intro_video'       => 'nullable|string|max:500',
            'requirements'      => 'nullable|string',
            'outcomes'          => 'nullable|string',
            'max_students'      => 'nullable|integer|min:0',
            'course_type'       => 'nullable|in:open,one_time,batch',
            'batch_name'        => 'nullable|string|max:120',
            'batch_start_date'  => 'nullable|date',
            'batch_end_date'    => 'nullable|date',
            'registration_open' => 'boolean',
            'related_course_ids'=> 'nullable|array|max:12',
            'related_course_ids.*' => ['integer','exists:courses,id', function ($attribute, $value, $fail) use ($ignoreId) { if ($ignoreId && (int) $value === $ignoreId) $fail('A course cannot be related to itself.'); }],
        ], self::MATERIAL_RULES));
        $data['resources'] = $this->validatedMaterials($data['resources'] ?? []);

        // Convert textarea lines to array
        if (isset($data['requirements'])) {
            $data['requirements'] = json_encode(array_filter(array_map('trim', explode("\n", $data['requirements']))));
        }
        if (isset($data['outcomes'])) {
            $data['outcomes'] = json_encode(array_filter(array_map('trim', explode("\n", $data['outcomes']))));
        }

        $data['is_free']          = $request->boolean('is_free');
        $data['is_featured']      = $request->boolean('is_featured');
        $data['upcoming']         = $request->boolean('upcoming');
        $data['enforce_lesson_completion'] = $request->boolean('enforce_lesson_completion');
        $data['enable_certification'] = $ignoreId ? $request->boolean('enable_certification') : true;
        $data['registration_open']= $ignoreId ? $request->boolean('registration_open') : $request->boolean('registration_open', true);
        $data['max_students']     = (int)($request->input('max_students', 0));
        $data['course_type']      = $request->input('course_type','open');
        if (empty($data['vendor_id'])) $data['vendor_id'] = null;
        $data['visibility']  = $request->input('visibility','draft');
        $data['is_published']= in_array($data['visibility'],['public','restricted']) ? 1 : 0;

        // Auto-slug on create
        if (!$ignoreId) {
            $data['slug'] = Course::uniqueSlug($data['title']);
        }

        return $data;
    }

    private function authorizeCourseOwner(Course $course): void
    {
        $user = auth()->user();
        if ($user->hasRole(['admin', 'super-admin'])) return;
        abort_unless($course->primary_instructor_id === $user->id || $course->teachers()->where('users.id', $user->id)->exists(), 403);
    }

    private function workspaceEditUrl(Course $course): string
    {
        return request()->routeIs('instructor.courses.*')
            ? route('instructor.courses.show', $course)
            : route('admin.courses.edit', $course);
    }

    private function sanitizeRichText(string $html): string
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<div id="course-description">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $allowedTags = ['p', 'br', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 's', 'strike', 'font', 'span', 'mark', 'code', 'ul', 'ol', 'li', 'a', 'img', 'video', 'iframe', 'blockquote', 'pre', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'div'];
        foreach (iterator_to_array($document->getElementsByTagName('*')) as $node) {
            if ($node->getAttribute('id') === 'course-description') continue;
            if (!in_array($node->tagName, $allowedTags, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $isSafeUrl = in_array($attribute->name, ['href', 'src'], true)
                    && (str_starts_with($attribute->value, '/') || filter_var($attribute->value, FILTER_VALIDATE_URL))
                    && !preg_match('/^(?:javascript|data|vbscript):/i', $attribute->value);
                $isSafeColor = $node->tagName === 'font' && $attribute->name === 'color'
                    && preg_match('/^#[0-9a-f]{6}$/i', $attribute->value);
                $styleRules = array_values(array_filter(array_map('trim', explode(';', $attribute->value))));
                $isSafeStyle = $attribute->name === 'style' && $styleRules !== [] && collect($styleRules)->every(fn ($rule) =>
                    preg_match('/^text-align:\s*(?:left|center|right)$/i', $rule)
                    || preg_match('/^color:\s*inherit$/i', $rule)
                    || preg_match('/^(?:color|background-color):\s*(?:#[0-9a-f]{6}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\))$/i', $rule)
                );
                if (!in_array($attribute->name, ['alt', 'rel', 'title', 'loading', 'allowfullscreen', 'controls', 'preload'], true) && !$isSafeUrl && !$isSafeColor && !$isSafeStyle) $node->removeAttribute($attribute->name);
            }
            if ($node->tagName === 'iframe') {
                $host = strtolower(parse_url($node->getAttribute('src'), PHP_URL_HOST) ?: '');
                $allowedEmbedHosts = ['youtube.com', 'youtu.be', 'youtube-nocookie.com', 'vimeo.com', 'player.vimeo.com', 'codepen.io', 'codesandbox.io', 'figma.com', 'embed.figma.com', 'docs.google.com', 'drive.google.com', 'notion.so'];
                $allowedHost = collect($allowedEmbedHosts)->contains(fn ($domain) => $host === $domain || str_ends_with($host, '.'.$domain));
                if (!$node->hasAttribute('src') || !$allowedHost) {
                    $node->parentNode?->removeChild($node);
                    continue;
                }
                $node->setAttribute('loading', 'lazy');
                $node->setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups allow-forms allow-presentation');
            }
        }

        $root = $document->getElementById('course-description');
        return collect(iterator_to_array($root->childNodes))->map(fn ($child) => $document->saveHTML($child))->implode('');
    }

    // ── Validate lesson fields ─────────────────────────────────────────
    private function validateLesson(Request $request): array
    {
        $data = $request->validate(array_merge([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'required|in:video,text,quiz,pdf,live',
            'content'         => 'nullable|string',
            'video_url'       => 'nullable|string|max:500',
            'video_storage'      => 'nullable|in:r2,external',
            'video_r2_path'      => 'nullable|string|max:500',
            'live_platform'      => 'nullable|string|max:20',
            'live_url'           => 'nullable|string|max:500',
            'live_scheduled_at'  => 'nullable|date',
            'live_duration_min'  => 'nullable|integer|min:15',
            'live_meeting_id'    => 'nullable|string|max:100',
            'live_password'      => 'nullable|string|max:100',
            'notes'           => 'nullable|string',
            'duration'        => 'nullable|integer|min:0|max:1440',
            'is_preview'      => 'boolean',
            'quiz_id'         => 'nullable|integer|exists:quizzes,id',
        ], self::MATERIAL_RULES));
        $data['is_preview']    = $request->boolean('is_preview');
        if (!empty($data['quiz_id'])) {
            $quiz = \App\Models\Quiz::whereNull('lesson_id')->findOrFail($data['quiz_id']);
            abort_unless(auth()->user()->hasRole(['admin', 'super-admin']) || $quiz->owner_id === auth()->id(), 403);
        }
        $data['video_storage'] = $request->input('video_storage', 'external');
        $data['resources'] = $this->validatedMaterials($data['resources'] ?? []);
        if (isset($data['duration'])) $data['duration'] *= 60;
        return $data;
    }

    private function validatedMaterials(array $resources): array
    {
        $resources = array_values($resources);
        $allowedTypes = [
            'slides' => ['presentation'], 'audio' => ['audio'], 'image' => ['image'],
            'notes' => ['pdf','document'], 'quiz_paper' => ['pdf'],
            'question_paper' => ['pdf'], 'study_material' => ['pdf'],
        ];
        $uploads = Upload::whereIn('id', collect($resources)
            ->flatMap(fn($r) => [$r['upload_id'], $r['preview_upload_id'] ?? null])->filter()->unique())
            ->get()->keyBy('id');
        foreach ($resources as $index => $resource) {
            $file = $uploads->get($resource['upload_id']);
            $preview = $uploads->get($resource['preview_upload_id'] ?? null);
            if (!$file?->is_public || !in_array($file->type, $allowedTypes[$resource['kind']], true)
                || ($preview && (!$preview->is_public || $preview->type !== 'pdf'))
                || ($resource['kind'] === 'slides' && !$preview)
                || (!empty($resource['preview_upload_id']) && (!$preview || $resource['kind'] !== 'slides'))) {
                throw ValidationException::withMessages(["resources.$index.upload_id" => 'Choose a public file of the correct type for this material.']);
            }
        }
        return $resources;
    }

    private function markMaterialUploadsUsedByCourse(Course $course, array $resources): void
    {
        $ids = collect($resources)
            ->flatMap(fn ($resource) => [$resource['upload_id'] ?? null, $resource['preview_upload_id'] ?? null])
            ->filter()->unique();
        Upload::whereIn('id', $ids)->update(['used_in' => json_encode(['course_id' => $course->id])]);
    }

    private function availableQuizzes()
    {
        return \App\Models\Quiz::query()->whereNull('lesson_id')
            ->when(!auth()->user()->hasRole(['admin', 'super-admin']), fn ($query) => $query->where('owner_id', auth()->id()))
            ->withCount('questions')->orderBy('title')->get();
    }

    private function saveUploadedFile($file, string $directory): string
    {
        $filename = $file->hashName();
        $cleanDir = trim($directory, '/');
        $relativeSubpath = $cleanDir . '/' . $filename;

        // Destination directories
        $publicStorageDir = public_path('storage/' . $cleanDir);
        $publicImagesDir  = public_path('images/' . $cleanDir);
        $storageAppDir    = storage_path('app/public/' . $cleanDir);

        foreach ([$publicStorageDir, $publicImagesDir, $storageAppDir] as $dir) {
            if (!file_exists($dir)) {
                @mkdir($dir, 0755, true);
            }
        }

        // Native PHP move directly into public/storage for immediate web serving
        try {
            $file->move($publicStorageDir, $filename);
            $savedFile = $publicStorageDir . '/' . $filename;

            // Create redundant backup copies in storage/app/public and public/images
            if (file_exists($savedFile)) {
                @copy($savedFile, $storageAppDir . '/' . $filename);
                @copy($savedFile, $publicImagesDir . '/' . $filename);
            }
        } catch (\Throwable $e) {
            // Fallback to Laravel storeAs if direct move fails
            try {
                $file->storeAs($cleanDir, $filename, 'public');
                $src = storage_path('app/public/' . $relativeSubpath);
                if (file_exists($src)) {
                    @copy($src, $publicStorageDir . '/' . $filename);
                }
            } catch (\Throwable $sEx) {}
        }

        return '/storage/' . $relativeSubpath;
    }
}
