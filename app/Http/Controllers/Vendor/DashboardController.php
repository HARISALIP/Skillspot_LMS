<?php
namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\BatchAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ResolvesVendor;

    public function pick()
    {
        $user    = auth()->user();

        // Teacher: resolve from vendor_teachers
        if ($user->hasRole('teacher')) {
            $vendors = Vendor::whereHas('teachers', fn($q) => $q->where('user_id', $user->id))
                ->where('status','active')->withCount('courses','enrollments')->get();
        } else {
            $vendors = Vendor::whereHas('managers', fn($q) => $q->where('user_id', $user->id))
                ->where('status','active')->withCount('courses','enrollments')->get();
        }

        if ($vendors->count() === 1) {
            session(['active_vendor_id' => $vendors->first()->id]);
            return redirect()->route('vendor.dashboard');
        }
        return view('vendor.pick', compact('vendors'));
    }

    public function switchVendor(Request $request)
    {
        $request->validate(['vendor_id' => 'required|integer']);
        $user = auth()->user();

        if ($user->hasRole('teacher')) {
            $vendor = Vendor::whereHas('teachers', fn($q) => $q->where('user_id', $user->id))
                ->where('id', $request->vendor_id)->where('status','active')->firstOrFail();
        } else {
            $vendor = Vendor::whereHas('managers', fn($q) => $q->where('user_id', $user->id))
                ->where('id', $request->vendor_id)->where('status','active')->firstOrFail();
        }

        session(['active_vendor_id' => $vendor->id]);
        return redirect()->route('vendor.dashboard')
            ->with('success', 'Switched to ' . $vendor->brand_name . ' ✅');
    }

    public function index()
    {
        $isTeacher    = $this->isTeacher();
        $isVendorOnly = $this->isVendorOnly();

        // Teacher: dedicated dashboard showing all assigned vendors
        if ($isTeacher) {
            $myVendors = Vendor::whereHas('teachers', fn($q) => $q->where('user_id', auth()->id()))
                ->where('status','active')
                ->withCount('courses','enrollments')
                ->get();

            // All course IDs across all assigned vendors
            $vendorIds  = $myVendors->pluck('id');
            $allCourses = Course::whereIn('vendor_id', $vendorIds)->withCount('enrollments')->latest()->get();

            // ITForge courses assigned directly to this teacher
            $itforgeCourses = auth()->user()->assignedCourses()
                ->withCount('enrollments')->latest()->get();

            // Upcoming live classes across all vendors
            $upcomingLive = Lesson::where('type','live')
                ->whereHas('section.course', fn($q) => $q->whereIn('vendor_id', $vendorIds))
                ->where('live_scheduled_at','>', now())
                ->orderBy('live_scheduled_at')->take(8)
                ->with('section.course')->get();

            // Today's live classes
            $todayLive = Lesson::where('type','live')
                ->whereHas('section.course', fn($q) => $q->whereIn('vendor_id', $vendorIds))
                ->whereDate('live_scheduled_at', today())
                ->orderBy('live_scheduled_at')
                ->with('section.course')->get();

            // Attendance stats across all vendors
            $allLessonIds = Lesson::where('type','live')
                ->whereHas('section.course', fn($q) => $q->whereIn('vendor_id', $vendorIds))
                ->where('live_scheduled_at','<', now())->pluck('id');
            $totalPresent = BatchAttendance::whereIn('lesson_id', $allLessonIds)->where('status','present')->count();
            $totalAbsent  = BatchAttendance::whereIn('lesson_id', $allLessonIds)->where('status','absent')->count();
            $totalMarked  = $totalPresent + $totalAbsent;
            $attendanceStats = [
                'present' => $totalPresent, 'absent' => $totalAbsent,
                'rate'    => $totalMarked > 0 ? round(($totalPresent / $totalMarked) * 100) : 0,
            ];

            $totalStudents = Enrollment::whereIn('vendor_id', $vendorIds)->distinct('user_id')->count('user_id');

            return view('vendor.teacher-dashboard', compact(
                'myVendors','allCourses','itforgeCourses','upcomingLive','todayLive',
                'attendanceStats','totalStudents','isTeacher'
            ));
        }

        // Vendor/Admin: existing dashboard
        $vendor  = $this->vendor();
        $courses = Course::where('vendor_id', $vendor->id)->withCount('enrollments')->get();
        $totalStudents = Enrollment::where('vendor_id', $vendor->id)->distinct('user_id')->count('user_id');

        $upcomingLive = Lesson::where('type','live')
            ->whereHas('section.course', fn($q) => $q->where('vendor_id', $vendor->id))
            ->where('live_scheduled_at','>', now())
            ->orderBy('live_scheduled_at')->take(5)->get();

        $trendingBatches = Course::where('vendor_id', $vendor->id)
            ->where('course_type','batch')->where('is_published', true)
            ->withCount(['enrollments as recent_enrollments' => fn($q) => $q->where('created_at','>=', now()->subDays(30))])
            ->withCount('enrollments')
            ->orderByDesc('recent_enrollments')->take(5)->get();

        $allLessonIds = Lesson::where('type','live')
            ->whereHas('section.course', fn($q) => $q->where('vendor_id', $vendor->id))
            ->where('live_scheduled_at','<', now())->pluck('id');
        $totalPresent = BatchAttendance::whereIn('lesson_id', $allLessonIds)->where('status','present')->count();
        $totalAbsent  = BatchAttendance::whereIn('lesson_id', $allLessonIds)->where('status','absent')->count();
        $totalMarked  = $totalPresent + $totalAbsent;
        $attendanceStats = [
            'present' => $totalPresent, 'absent' => $totalAbsent,
            'rate'    => $totalMarked > 0 ? round(($totalPresent / $totalMarked) * 100) : 0,
        ];

        $myVendors = Vendor::whereHas('managers', fn($q) => $q->where('user_id', auth()->id()))->where('status','active')->get();

        return view('vendor.dashboard', compact(
            'vendor','courses','totalStudents','upcomingLive',
            'trendingBatches','attendanceStats','myVendors','isVendorOnly','isTeacher'
        ));
    }

    // ── Teachers management ────────────────────────────────────────────
    public function teachers()
    {
        $vendor          = $this->vendor();
        $teachers        = $vendor->teachers()->get();
        $allTeacherUsers = User::role('teacher')->orderBy('name')->get(['id','name','email']);
        return view('vendor.teachers', compact('vendor','teachers','allTeacherUsers'));
    }

    public function addTeacher(Request $request)
    {
        $vendor = $this->vendor();
        $request->validate(['user_id' => 'required|exists:users,id']);
        $user = User::findOrFail($request->user_id);
        if (!$user->hasRole('teacher')) $user->assignRole('teacher');
        DB::table('vendor_teachers')->updateOrInsert(
            ['vendor_id' => $vendor->id, 'user_id' => $user->id],
            ['added_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]
        );
        return back()->with('success', $user->name . ' added as teacher ✅');
    }

    public function removeTeacher(Request $request, User $user)
    {
        $vendor = $this->vendor();
        DB::table('vendor_teachers')
            ->where('vendor_id', $vendor->id)->where('user_id', $user->id)->delete();
        return back()->with('success', $user->name . ' removed from teachers.');
    }

    // ── Student Access Management ─────────────────────────────────────
    public function students()
    {
        $vendor   = $this->vendor();
        $students = $vendor->studentAccess()->get();
        $courses  = Course::where('vendor_id', $vendor->id)->where('is_published', true)->orderBy('title')->get(['id','title']);
        return view('vendor.students', compact('vendor','students','courses'));
    }

    public function grantStudentAccess(\Illuminate\Http\Request $request)
    {
        $vendor = $this->vendor();
        $request->validate([
            'email'     => 'required|email|exists:users,email',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        // Grant portal access
        \Illuminate\Support\Facades\DB::table('student_vendor_access')->updateOrInsert(
            ['user_id' => $user->id, 'vendor_id' => $vendor->id],
            ['granted_by' => auth()->id(), 'note' => $request->note ?? null, 'updated_at' => now(), 'created_at' => now()]
        );

        // Update portal_access flag
        if ($user->portal_access === 'skillspot_only') {
            $user->update(['portal_access' => 'both']);
        }

        // Enroll in course if selected
        if ($request->course_id) {
            $course = Course::where('id', $request->course_id)->where('vendor_id', $vendor->id)->first();
            if ($course) {
                \App\Models\Enrollment::firstOrCreate(
                    ['user_id' => $user->id, 'course_id' => $course->id],
                    ['vendor_id' => $vendor->id, 'amount_paid' => 0, 'payment_method' => 'vendor_access', 'granted_by' => auth()->id()]
                );
            }
        }

        return back()->with('success', $user->name . ' assigned & granted access ✅');
    }

    public function revokeStudentAccess(\App\Models\User $user)
    {
        $vendor = $this->vendor();
        \Illuminate\Support\Facades\DB::table('student_vendor_access')
            ->where('user_id', $user->id)->where('vendor_id', $vendor->id)->delete();
        return back()->with('success', $user->name . ' access revoked.');
    }

    public function preAllow(\Illuminate\Http\Request $request)
    {
        $vendor = $this->vendor();
        $request->validate([
            'contacts'      => 'required|string',
            'contact_type'  => 'required|in:email,phone',
        ]);

        $contacts = array_filter(array_map('trim', preg_split('/[\s,;\n]+/', $request->contacts)));
        $added = 0;

        foreach ($contacts as $contact) {
            \App\Models\VendorPreallowedContact::firstOrCreate(
                ['vendor_id' => $vendor->id, 'contact' => $contact],
                ['contact_type' => $request->contact_type, 'added_by' => auth()->id(), 'used' => false]
            );
            // If user already exists with this contact — grant access immediately
            $existing = $request->contact_type === 'email'
                ? \App\Models\User::where('email', $contact)->first()
                : \App\Models\User::where('phone', $contact)->first();
            if ($existing) {
                \Illuminate\Support\Facades\DB::table('student_vendor_access')->updateOrInsert(
                    ['user_id' => $existing->id, 'vendor_id' => $vendor->id],
                    ['granted_by' => auth()->id(), 'note' => 'auto from preallow', 'updated_at' => now(), 'created_at' => now()]
                );
                if ($existing->portal_access === 'skillspot_only') {
                    $existing->update(['portal_access' => 'both']);
                }
            }
            $added++;
        }

        return back()->with('success', $added . ' contact(s) pre-allowed ✅');
    }

    public function removePreAllow(\Illuminate\Http\Request $request, int $id)
    {
        $vendor = $this->vendor();
        \App\Models\VendorPreallowedContact::where('id', $id)->where('vendor_id', $vendor->id)->delete();
        return back()->with('success', 'Removed.');
    }
}
