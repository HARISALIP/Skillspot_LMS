<?php
namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\CourseCategory;
use App\Models\CourseLevel;
use App\Models\CourseLanguage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CoursesController extends Controller
{
    use ResolvesVendor;

    // ── Helpers ─────────────────────────────────────────────────────────
    private function getCategories(): array {
        return CourseCategory::where('is_active',1)->orderBy('order_col')->pluck('name')->toArray();
    }
    private function getLevels(): array {
        return CourseLevel::where('is_active',1)->orderBy('order_col')->pluck('slug')->toArray();
    }
    private function getLanguages(): array {
        return CourseLanguage::where('is_active',1)->orderBy('order_col')->pluck('name','code')->toArray();
    }

    // ── Course list ──────────────────────────────────────────────────────
    public function index()
    {
        $vendor  = $this->vendor();
        $courses = Course::where('vendor_id', $vendor->id)->withCount('enrollments')->latest()->paginate(15);
        $isVendorOnly = $this->isVendorOnly();
        $isTeacher    = $this->isTeacher();
        return view('vendor.courses.index', compact('vendor','courses','isVendorOnly','isTeacher'));
    }

    // ── Create form ──────────────────────────────────────────────────────
    public function create()
    {
        $vendor = $this->vendor();
        $course = new Course();
        return view('vendor.courses.form', [
            'vendor'     => $vendor,
            'course'     => $course,
            'categories' => $this->getCategories(),
            'levels'     => $this->getLevels(),
            'languages'  => $this->getLanguages(),
            'isEdit'     => false,
        ]);
    }

    // ── Store ────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $vendor = $this->vendor();
        $data   = $this->validateCourse($request);
        $data['vendor_id'] = $vendor->id;
        $course = Course::create($data);
        return redirect()->route('vendor.courses.edit', $course->id)
            ->with('success', "Course \"{$course->title}\" created ✅ Now add sections & lessons.");
    }

    // ── Edit form ────────────────────────────────────────────────────────
    public function edit(Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403, 'Course not in your vendor.');
        $course->load('sections.lessons','allowedUsers');
        return view('vendor.courses.form', [
            'vendor'     => $vendor,
            'course'     => $course,
            'categories' => $this->getCategories(),
            'levels'     => $this->getLevels(),
            'languages'  => $this->getLanguages(),
            'isEdit'     => true,
        ]);
    }

    // ── Update ───────────────────────────────────────────────────────────
    public function update(Request $request, Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $data = $this->validateCourse($request, $course->id);
        $data['vendor_id'] = $vendor->id; // keep locked to vendor
        $course->update($data);
        return back()->with('success', "Course updated ✅");
    }

    // ── Toggle Publish ───────────────────────────────────────────────────
    public function togglePublish(Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $next = match($course->visibility) {
            'draft'      => 'public',
            'public'     => 'draft',
            'restricted' => 'draft',
            default      => 'public',
        };
        $course->update(['visibility' => $next, 'is_published' => $next !== 'draft' ? 1 : 0]);
        $msg = ['draft' => 'set to Draft', 'public' => 'Published ✅', 'restricted' => 'set to Restricted'];
        return back()->with('success', "\"{$course->title}\" " . $msg[$next]);
    }

    // ── Delete ───────────────────────────────────────────────────────────
    public function destroy(Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $title = $course->title;
        $course->delete();
        return redirect()->route('vendor.courses')
            ->with('success', "Course \"{$title}\" deleted.");
    }

    // ── Students ─────────────────────────────────────────────────────────
    public function students(Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $allowed  = $course->allowedUsers()->get();
        $enrolled = $course->enrollments()->with('user')->get();
        $isVendorOnly = $this->isVendorOnly();
        $isTeacher    = $this->isTeacher();
        return view('vendor.courses.students', compact('vendor','course','allowed','enrolled','isVendorOnly','isTeacher'));
    }

    public function allowStudent(Request $request, Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)->first();

        if ($course->max_students > 0 && $course->allowedUsers()->count() >= $course->max_students) {
            return back()->with('error', "Max student limit ({$course->max_students}) reached.");
        }

        $course->allowedUsers()->syncWithoutDetaching([
            $user->id => ['added_by' => auth()->id(), 'note' => $request->note ?? null]
        ]);
        return back()->with('success', "{$user->name} allowed ✅");
    }

    public function revokeStudent(Course $course, User $user)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $course->allowedUsers()->detach($user->id);
        return back()->with('success', "Access revoked.");
    }

    // ── Section: store ───────────────────────────────────────────────────
    public function storeSection(Request $request, Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $request->validate(['title' => 'required|string|max:255']);
        $order = $course->sections()->max('order') + 1;
        $course->sections()->create(['title' => $request->title, 'order' => $order]);
        return back()->with('success', 'Section added ✅');
    }

    // ── Section: update ──────────────────────────────────────────────────
    public function updateSection(Request $request, Course $course, Section $section)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $request->validate(['title' => 'required|string|max:255']);
        $section->update(['title' => $request->title]);
        return back()->with('success', 'Section updated ✅');
    }

    // ── Section: delete ──────────────────────────────────────────────────
    public function destroySection(Course $course, Section $section)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $section->delete();
        return back()->with('success', 'Section deleted.');
    }

    // ── Section: reorder ─────────────────────────────────────────────────
    public function reorderSections(Request $request, Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        foreach ($request->order ?? [] as $pos => $sectionId) {
            Section::where('id', $sectionId)->where('course_id', $course->id)
                   ->update(['order' => $pos + 1]);
        }
        return response()->json(['ok' => true]);
    }

    // ── Lesson: create form ──────────────────────────────────────────────
    public function createLesson(Course $course, Section $section)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        return view('vendor.courses.lesson', [
            'vendor'  => $vendor,
            'course'  => $course,
            'section' => $section,
            'lesson'  => new Lesson(),
            'isEdit'  => false,
        ]);
    }

    // ── Lesson: store ────────────────────────────────────────────────────
    public function storeLesson(Request $request, Course $course, Section $section)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $data = $this->validateLesson($request);
        $data['order'] = $section->lessons()->max('order') + 1;
        $lesson = $section->lessons()->create($data);
        return redirect()->route('vendor.courses.edit', $course->id)
            ->with('success', "Lesson \"{$lesson->title}\" added ✅");
    }

    // ── Lesson: edit form ────────────────────────────────────────────────
    public function editLesson(Course $course, Section $section, Lesson $lesson)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        return view('vendor.courses.lesson', [
            'vendor'  => $vendor,
            'course'  => $course,
            'section' => $section,
            'lesson'  => $lesson,
            'isEdit'  => true,
        ]);
    }

    // ── Lesson: update ───────────────────────────────────────────────────
    public function updateLesson(Request $request, Course $course, Section $section, Lesson $lesson)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $lesson->update($this->validateLesson($request));
        return redirect()->route('vendor.courses.edit', $course->id)
            ->with('success', "Lesson updated ✅");
    }

    // ── Lesson: delete ───────────────────────────────────────────────────
    public function destroyLesson(Course $course, Section $section, Lesson $lesson)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $lesson->delete();
        return back()->with('success', 'Lesson deleted.');
    }

    // ── Validate course ──────────────────────────────────────────────────
    private function validateCourse(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
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

        if (isset($data['requirements'])) {
            $data['requirements'] = json_encode(array_filter(array_map('trim', explode("\n", $data['requirements']))));
        }
        if (isset($data['outcomes'])) {
            $data['outcomes'] = json_encode(array_filter(array_map('trim', explode("\n", $data['outcomes']))));
        }

        $data['is_free']           = $request->boolean('is_free');
        $data['is_featured']       = $request->boolean('is_featured');
        $data['registration_open'] = $request->boolean('registration_open');
        $data['max_students']      = (int)($request->input('max_students', 0));
        $data['course_type']       = $request->input('course_type', 'open');
        $data['visibility']        = $request->input('visibility', 'draft');
        $data['is_published']      = in_array($data['visibility'], ['public', 'restricted']) ? 1 : 0;

        if (!$ignoreId) {
            $data['slug'] = Course::uniqueSlug($data['title']);
        }

        return $data;
    }

    // ── Validate lesson ──────────────────────────────────────────────────
    private function validateLesson(Request $request): array
    {
        $data = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'type'               => 'required|in:video,text,quiz,pdf,live',
            'content'            => 'nullable|string',
            'video_url'          => 'nullable|string|max:500',
            'video_storage'      => 'nullable|in:r2,external',
            'video_r2_path'      => 'nullable|string|max:500',
            'live_platform'      => 'nullable|string|max:20',
            'live_url'           => 'nullable|string|max:500',
            'live_scheduled_at'  => 'nullable|date',
            'live_duration_min'  => 'nullable|integer|min:15',
            'live_meeting_id'    => 'nullable|string|max:100',
            'live_password'      => 'nullable|string|max:100',
            'notes'              => 'nullable|string',
            'duration'           => 'nullable|integer|min:0',
            'is_preview'         => 'boolean',
        ]);
        $data['is_preview']    = $request->boolean('is_preview');
        $data['video_storage'] = $request->input('video_storage', 'external');
        return $data;
    }
}
