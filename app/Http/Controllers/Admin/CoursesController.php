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
use App\Services\LessonQuiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
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
        $query = Course::with('sections.lessons')
            ->withCount(['enrollments', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest();

        if ($s = $request->search) {
            $query->where(function($q) use ($s) {
                $q->where('title','like',"%{$s}%")->orWhere('category','like',"%{$s}%");
            });
        }
        if ($request->category) $query->where('category', $request->category);
        $tab = $request->input('tab', 'published');
        if ($tab === 'published') $query->where('is_published', 1);
        if ($tab === 'upcoming') $query->whereDate('batch_start_date', '>', today());
        if ($tab === 'unpublished') $query->where('is_published', 0);
        // shortcut: courses have no creator_id, scope Created once ownership is modeled.

        $courses    = $query->paginate(15);
        $totalCourses   = Course::count();
        $published  = Course::where('is_published',1)->count();
        $draft      = Course::where('is_published',0)->count();
        $totalEnroll= Enrollment::count();

        $categories = $this->getCategories();
        $categoryRecords = CourseCategory::where('is_active', 1)->orderBy('order_col')->get(['id', 'name']);
        $instructors = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['teacher', 'admin', 'super-admin']))
            ->orderBy('name')->get(['id', 'name', 'email']);
        if (!$instructors->contains('id', auth()->id())) $instructors->prepend(auth()->user());
        $canCreateInstructors = auth()->user()->hasRole(['admin', 'super-admin']);
        return view('admin.courses.index', compact(
            'courses','totalCourses','published','draft','totalEnroll','categories','categoryRecords','instructors','canCreateInstructors'
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
        ]);

        $course->teachers()->syncWithoutDetaching([auth()->id() => ['added_by' => auth()->id()]]);
        return redirect()->route('admin.courses.edit', $course)
            ->with('success', "Course \"{$course->title}\" imported as a draft. Add its sections and lessons before publishing.");
    }

    // ── Store ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        if (is_string($request->input('description'))) {
            $request->merge(['description' => $this->sanitizeRichText($request->input('description'))]);
        }
        if ($request->boolean('modal_create')) {
            $request->validate([
                'short_introduction' => 'required|string|max:1000',
                'description' => 'required|string',
                'instructor_ids' => 'required|array|min:1',
            ]);
        }
        if ($newCategory = trim((string) $request->input('new_category'))) {
            $category = CourseCategory::firstOrCreate(
                ['name' => $newCategory],
                ['is_active' => true, 'order_col' => CourseCategory::max('order_col') + 1]
            );
            $request->merge(['category' => $category->name]);
        }
        $data = $this->validateCourse($request);
        if ($request->hasFile('thumbnail_file')) {
            $data['thumbnail'] = Storage::url($request->file('thumbnail_file')->store('course-thumbnails', 'public'));
        }
        $course = DB::transaction(function () use ($data, $request) {
            $course = Course::create($data);
            $teacherIds = $request->input('instructor_ids', [auth()->id()]);
            $course->teachers()->syncWithPivotValues($teacherIds, ['added_by' => auth()->id()]);
            return $course;
        });
        return redirect()->route('admin.courses.edit', $course->id)
            ->with('success', "Course \"{$course->title}\" created ✅ Now add sections & lessons.");
    }

    public function storeInstructor(Request $request)
    {
        abort_unless($request->user()->hasRole(['admin', 'super-admin']), 403);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $name = trim($data['first_name'].' '.($data['last_name'] ?? ''));
        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'country' => $request->user()->country ?: 'IN',
            'country_name' => $request->user()->country_name ?: 'India',
            'registered_via' => 'admin',
            'portal_access' => 'skillspot_only',
            'password' => Hash::make($data['password']),
        ]);
        Role::findOrCreate('teacher', 'web');
        $user->assignRole('teacher');

        return response()->json(['instructor' => $user->only(['id', 'name', 'email'])], 201);
    }

    // ── Edit form ──────────────────────────────────────────────────────
    public function edit(Course $course)
    {
        $course->load('sections.lessons','allowedUsers');
        $materialUploads = Upload::whereIn('id', collect($course->resources ?? [])
            ->flatMap(fn($r) => [$r['upload_id'] ?? null, $r['preview_upload_id'] ?? null])->filter())
            ->pluck('original_name', 'id');
        $vendors = Vendor::where('status','active')->orderBy('brand_name')->get(['id','brand_name']);
        return view('admin.courses.form', [
            'course'     => $course,
            'vendors'    => $vendors,
            'categories' => $this->getCategories(),
            'levels'     => $this->getLevels(),
            'languages'  => $this->getLanguages(),
            'isEdit'     => true,
            'materialUploads' => $materialUploads,
        ]);
    }

    // ── Update ─────────────────────────────────────────────────────────
    public function update(Request $request, Course $course)
    {
        $request->merge(['visibility' => $request->input('visibility') ?: $course->visibility]);
        $data = $this->validateCourse($request, $course->id);
        $course->update($data);
        return back()->with('success', "Course updated ✅");
    }

    // ── Toggle publish ─────────────────────────────────────────────────
    public function togglePublish(Course $course)
    {
        $next = match($course->visibility) {
            'draft'      => 'public',
            'public'     => 'draft',
            'restricted' => 'draft',
            default      => 'public',
        };
        $course->update(['visibility'=>$next,'is_published'=>$next!=='draft'?1:0]);
        $msg = ['draft'=>'set to Draft','public'=>'Published (Public)','restricted'=>'set to Restricted'];
        return back()->with('success', "\"\" ".$msg[$next]." ✅");
    }

    // Grant restricted access to specific user
    public function grantAccess(Request $request, Course $course)
    {
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
        $course->allowedUsers()->detach($userId);
        return back()->with('success', 'Access revoked.');
    }

    // ── Delete ─────────────────────────────────────────────────────────
    public function destroy(Course $course)
    {
        $title = $course->title;
        $course->delete();
        return redirect()->route('admin.courses')
            ->with('success', "Course \"{$title}\" deleted.");
    }

    // ── Section: store ─────────────────────────────────────────────────
    public function storeSection(Request $request, Course $course)
    {
        $request->validate(['title' => 'required|string|max:255']);
        $order = $course->sections()->max('order') + 1;
        $course->sections()->create([
            'title' => $request->title,
            'order' => $order,
        ]);
        return back()->with('success', 'Section added ✅');
    }

    // ── Section: update ────────────────────────────────────────────────
    public function updateSection(Request $request, Course $course, Section $section)
    {
        $request->validate(['title' => 'required|string|max:255']);
        $section->update(['title' => $request->title]);
        return back()->with('success', 'Section updated ✅');
    }

    // ── Section: delete ────────────────────────────────────────────────
    public function destroySection(Course $course, Section $section)
    {
        $section->delete();
        return back()->with('success', 'Section deleted.');
    }

    // ── Section: reorder ───────────────────────────────────────────────
    public function reorderSections(Request $request, Course $course)
    {
        foreach ($request->order ?? [] as $pos => $sectionId) {
            Section::where('id', $sectionId)->where('course_id', $course->id)
                   ->update(['order' => $pos + 1]);
        }
        return response()->json(['ok' => true]);
    }

    // ── Lesson: create form ────────────────────────────────────────────
    public function createLesson(Course $course, Section $section)
    {
        return view('admin.courses.lesson', [
            'course'  => $course,
            'section' => $section,
            'lesson'  => new Lesson(),
            'isEdit'  => false,
            'materialUploads' => [],
        ]);
    }

    // ── Lesson: store ──────────────────────────────────────────────────
    public function storeLesson(Request $request, Course $course, Section $section)
    {
        abort_unless($section->course_id === $course->id, 404);
        $data = $this->validateLesson($request);
        $quiz = $data['type'] === 'quiz' ? LessonQuiz::validate($request) : null;
        $data['order'] = $section->lessons()->max('order') + 1;
        $lesson = DB::transaction(function () use ($section, $data, $quiz) {
            $lesson = $section->lessons()->create($data);
            if ($quiz) LessonQuiz::save($lesson, $quiz);
            return $lesson;
        });
        return redirect()->route('admin.courses.edit', $course->id)
            ->with('success', "Lesson \"{$lesson->title}\" added ✅");
    }

    // ── Lesson: edit form ──────────────────────────────────────────────
    public function editLesson(Course $course, Section $section, Lesson $lesson)
    {
        abort_unless($section->course_id === $course->id && $lesson->section_id === $section->id, 404);
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
        ]);
    }

    // ── Lesson: update ─────────────────────────────────────────────────
    public function updateLesson(Request $request, Course $course, Section $section, Lesson $lesson)
    {
        abort_unless($section->course_id === $course->id && $lesson->section_id === $section->id, 404);
        $data = $this->validateLesson($request);
        $quiz = $data['type'] === 'quiz' ? LessonQuiz::validate($request) : null;
        DB::transaction(function () use ($lesson, $data, $quiz) {
            $lesson->update($data);
            if ($quiz) LessonQuiz::save($lesson, $quiz);
        });
        return redirect()->route('admin.courses.edit', $course->id)
            ->with('success', "Lesson updated ✅");
    }

    // ── Lesson: delete ─────────────────────────────────────────────────
    public function destroyLesson(Course $course, Section $section, Lesson $lesson)
    {
        $lesson->delete();
        return back()->with('success', 'Lesson deleted.');
    }

    // ── Lesson: reorder ────────────────────────────────────────────────
    public function reorderLessons(Request $request, Course $course, Section $section)
    {
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
            'category'          => 'required|string|max:100',
            'level'             => 'required|string|max:60',
            'language'          => 'required|string|max:10',
            'price'             => 'required|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0',
            'is_free'           => 'boolean',
            'is_featured'       => 'boolean',
            'visibility'        => 'nullable|in:draft,public,restricted',
            'thumbnail'         => 'nullable|string|max:500',
            'thumbnail_file'    => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
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
        $data['registration_open']= $request->boolean('registration_open');
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

    private function sanitizeRichText(string $html): string
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<div id="course-description">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $allowedTags = ['p', 'br', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 's', 'ul', 'ol', 'li', 'a', 'img', 'blockquote', 'pre', 'hr', 'table', 'tbody', 'tr', 'td', 'div'];
        foreach (iterator_to_array($document->getElementsByTagName('*')) as $node) {
            if ($node->getAttribute('id') === 'course-description') continue;
            if (!in_array($node->tagName, $allowedTags, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $isSafeUrl = in_array($attribute->name, ['href', 'src'], true)
                    && (str_starts_with($attribute->value, '/') || filter_var($attribute->value, FILTER_VALIDATE_URL));
                if (!in_array($attribute->name, ['alt', 'target', 'rel'], true) && !$isSafeUrl) $node->removeAttribute($attribute->name);
            }
            if ($node->tagName === 'a' && $node->hasAttribute('target')) $node->setAttribute('rel', 'noopener');
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
            'duration'        => 'nullable|integer|min:0',
            'is_preview'      => 'boolean',
        ], self::MATERIAL_RULES));
        $data['is_preview']    = $request->boolean('is_preview');
        $data['video_storage'] = $request->input('video_storage', 'external');
        $data['resources'] = $this->validatedMaterials($data['resources'] ?? []);
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
}
