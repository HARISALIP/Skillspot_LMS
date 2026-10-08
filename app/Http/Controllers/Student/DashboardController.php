<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $enrollments = Enrollment::with(['course.sections.lessons'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['active','completed'])
            ->latest()->get()
            ->filter(fn($e) => $e->course !== null)
            ->filter(fn($e) =>
                $e->access_type === 'lifetime' ||
                ($e->expires_at && $e->expires_at->isFuture())
            );

        $completedCount = $enrollments->where('status','completed')->count();
        $inProgress     = $enrollments->where('status','active')->where('progress','>',0)->count();

        $certificates = Certificate::with('course')
            ->where('user_id', $user->id)
            ->latest()->take(5)->get();
        $certCount = $certificates->count();

        $totalMinutes = 0;
        foreach ($enrollments as $en) {
            if ($en->course) {
                $totalMinutes += $en->course->sections->flatMap->lessons->sum('duration') ?? 0;
            }
        }
        $totalHours = round($totalMinutes / 60, 1);

        $enrolledCourseIds = $enrollments->pluck('course_id');

        $upcomingLive = Lesson::where('type','live')
            ->where('live_scheduled_at','>', now())
            ->whereHas('section', fn($q) => $q->whereIn('course_id', $enrolledCourseIds))
            ->with('section.course')
            ->orderBy('live_scheduled_at')
            ->take(3)->get();

        // Only recommend PUBLIC courses
        $recommended = Course::where('is_published',1)
            ->where('visibility','public')
            ->whereNotIn('id', $enrolledCourseIds)
            ->withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(6)->get();

        $streak = DB::table('lesson_progress')
            ->where('user_id', $user->id)
            ->where('updated_at','>=', now()->subDays(30))
            ->selectRaw('DATE(updated_at) as day')
            ->distinct()->count();

        return view('student.dashboard', compact(
            'enrollments','completedCount','inProgress',
            'certificates','certCount','totalHours',
            'upcomingLive','recommended','streak'
        ));
    }

    public function courses()
    {
        $user        = Auth::user();
        $enrollments = Enrollment::with(['course.sections.lessons'])
            ->where('user_id', $user->id)
            ->latest()->paginate(12);
        return view('student.courses', compact('enrollments'));
    }

    public function browse(Request $request)
    {
        $user  = Auth::user();
        $query = Course::where('is_published',1)->withCount('enrollments');

        // Only show public courses on browse + restricted courses this student is allowed in
        $query->where(function($q) use ($user) {
            $q->where('visibility','public');
            if ($user) {
                $q->orWhere(function($q2) use ($user) {
                    $q2->where('visibility','restricted')
                       ->whereHas('allowedUsers', fn($q3) => $q3->where('user_id',$user->id));
                });
            }
        });

        if ($s = $request->search)   $query->where('title','like',"%{$s}%");
        if ($c = $request->category) $query->where('category',$c);
        if ($l = $request->level)    $query->where('level',$l);
        if ($request->free)          $query->where('is_free',1);

        $courses    = $query->latest()->paginate(12);
        $categories = \App\Models\CourseCategory::where('is_active',1)->orderBy('order_col')->get();
        $levels     = \App\Models\CourseLevel::where('is_active',1)->orderBy('order_col')->get();
        return view('student.browse', compact('courses','categories','levels'));
    }

    public function certificates()
    {
        $user         = Auth::user();
        $certificates = Certificate::with('course')
            ->where('user_id', $user->id)->latest()->get();
        return view('student.certs', compact('certificates'));
    }

    public function profile()
    {
        $user = Auth::user();
        return view('student.profile', compact('user'));
    }

    public function settings()
    {
        $user = Auth::user();
        return view('student.settings', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone,'.$user->id,
        ]);
        $user->update(['name'=>$request->name,'phone'=>$request->phone]);
        return back()->with('success','Profile updated ✅');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required','confirmed',\Illuminate\Validation\Rules\Password::min(8)],
        ]);
        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password'=>'Current password is incorrect.']);
        }
        $user->update(['password'=>\Illuminate\Support\Facades\Hash::make($request->password)]);
        return back()->with('success','Password changed ✅');
    }

    // ── Vendor Portal Picker (student with multiple vendor accesses) ───
    public function vendorPick()
    {
        $user    = auth()->user();
        $vendors = $user->vendorAccess()->where('vendors.status','active')->get();

        if ($vendors->count() === 0) {
            return redirect()->route('student.dashboard');
        }
        if ($vendors->count() === 1) {
            return redirect()->route('vendor.portal.dashboard', $vendors->first()->slug);
        }

        return view('student.vendor-pick', compact('vendors'));
    }

    public function vendorPickSubmit(\Illuminate\Http\Request $request)
    {
        $request->validate(['vendor_id' => 'required|integer']);
        $user   = auth()->user();
        $vendor = \App\Models\Vendor::whereHas('studentAccess', fn($q) => $q->where('user_id', $user->id))
            ->where('id', $request->vendor_id)
            ->where('status','active')
            ->firstOrFail();

        return redirect()->route('vendor.portal.dashboard', $vendor->slug);
    }
}
