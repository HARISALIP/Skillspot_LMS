<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLevel;
use App\Models\CourseLanguage;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CoursesController extends Controller
{
    private function getCategories(): array {
        return CourseCategory::where('is_active',1)->orderBy('order_col')->pluck('name')->toArray();
    }
    private function getLevels(): array {
        return CourseLevel::where('is_active',1)->orderBy('order_col')->pluck('slug')->toArray();
    }
    private function getLanguages(): array {
        return CourseLanguage::where('is_active',1)->orderBy('order_col')->pluck('name','code')->toArray();
    }

    // ── List courses ───────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Course::withCount('enrollments')->latest();

        if ($s = $request->search) {
            $query->where(function($q) use ($s) {
                $q->where('title','like',"%{$s}%")->orWhere('category','like',"%{$s}%");
            });
        }
        if ($request->category) $query->where('category', $request->category);
        if ($request->level)    $query->where('level',    $request->level);
        if ($request->status === 'published') $query->where('is_published', 1);
        if ($request->status === 'draft')     $query->where('is_published', 0);

        $courses    = $query->paginate(15);
        $totalCourses   = Course::count();
        $published  = Course::where('is_published',1)->count();
        $draft      = Course::where('is_published',0)->count();
        $totalEnroll= Enrollment::count();

        $categories = $this->getCategories();
        return view('admin.courses.index', compact(
            'courses','totalCourses','published','draft','totalEnroll','categories'
        ));
    }

    // ── Create form ────────────────────────────────────────────────────
    public function create()
    {
        $course  = new Course();
        $vendors = Vendor::where('status','active')->orderBy('brand_name')->get(['id','brand_name']);
        return view('admin.courses.form', [
            'course'     => $course,
            'vendors'    => $vendors,
            'categories' => $this->getCategories(),
            'levels'     => $this->getLevels(),
            'languages'  => $this->getLanguages(),
            'isEdit'     => false,
        ]);
    }

    // ── Store ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $this->validateCourse($request);
        $course = Course::create($data);
        return redirect()->route('admin.courses.edit', $course->id)
            ->with('success', "Course \"{$course->title}\" created ✅ Now add sections & lessons.");
    }

    // ── Edit form ──────────────────────────────────────────────────────
    public function edit(Course $course)
    {
        $course->load('sections.lessons','allowedUsers');
        return view('admin.courses.form', [
            'course'     => $course,
            'vendors'    => $vendors,
            'categories' => $this->getCategories(),
            'levels'     => $this->getLevels(),
            'languages'  => $this->getLanguages(),
            'isEdit'     => true,
        ]);
    }

    // ── Update ─────────────────────────────────────────────────────────
    public function update(Request $request, Course $course)
    {
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
        ]);
    }

    // ── Lesson: store ──────────────────────────────────────────────────
    public function storeLesson(Request $request, Course $course, Section $section)
    {
        $data = $this->validateLesson($request);
        $data['order'] = $section->lessons()->max('order') + 1;
        $lesson = $section->lessons()->create($data);
        return redirect()->route('admin.courses.edit', $course->id)
            ->with('success', "Lesson \"{$lesson->title}\" added ✅");
    }

    // ── Lesson: edit form ──────────────────────────────────────────────
    public function editLesson(Course $course, Section $section, Lesson $lesson)
    {
        return view('admin.courses.lesson', [
            'course'  => $course,
            'section' => $section,
            'lesson'  => $lesson,
            'isEdit'  => true,
        ]);
    }

    // ── Lesson: update ─────────────────────────────────────────────────
    public function updateLesson(Request $request, Course $course, Section $section, Lesson $lesson)
    {
        $lesson->update($this->validateLesson($request));
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
        $data = $request->validate([
            'vendor_id'         => 'nullable|exists:vendors,id',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'category'          => 'required|string|max:100',
            'level'             => 'required|string|max:60',
            'language'          => 'required|string|max:10',
            'price'             => 'required|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0',
            'is_free'           => 'boolean',
            'is_featured'       => 'boolean',
            'visibility'        => 'nullable|in:draft,public,restricted',
            'thumbnail'         => 'nullable|string|max:500',
            'intro_video'       => 'nullable|string|max:500',
            'requirements'      => 'nullable|string',
            'outcomes'          => 'nullable|string',
            'max_students'      => 'nullable|integer|min:0',
            'course_type'       => 'nullable|in:open,one_time,batch',
            'batch_name'        => 'nullable|string|max:120',
            'batch_start_date'  => 'nullable|date',
            'batch_end_date'    => 'nullable|date',
            'registration_open' => 'boolean',
        ]);

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

    // ── Validate lesson fields ─────────────────────────────────────────
    private function validateLesson(Request $request): array
    {
        $data = $request->validate([
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
        ]);
        $data['is_preview']    = $request->boolean('is_preview');
        $data['video_storage'] = $request->input('video_storage', 'external');
        return $data;
    }
}
