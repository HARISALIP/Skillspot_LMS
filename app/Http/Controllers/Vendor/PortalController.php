<?php
namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LiveClass;
use App\Models\LiveClassEnrollment;
use App\Models\LiveClassAttendance;
use App\Models\User;
use App\Models\Vendor;
use App\Services\HumanVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PortalController extends Controller
{
    // ── Resolve vendor from slug ───────────────────────────────────────
    private function resolveVendor(string $slug): Vendor
    {
        return Vendor::where('slug', $slug)->where('status', 'active')->firstOrFail();
    }

    // ── Home / Landing ─────────────────────────────────────────────────
    public function home(string $slug)
    {
        $vendor = $this->resolveVendor($slug);

        $courses = Course::where('vendor_id', $vendor->id)
            ->where('is_published', 1)
            ->withCount('enrollments')
            ->orderByDesc('created_at')
            ->get();

        return view('vendor.portal.home', compact('vendor', 'courses'));
    }

    // ── Login ──────────────────────────────────────────────────────────
    public function showLogin(string $slug)
    {
        $vendor = $this->resolveVendor($slug);
        return view('vendor.portal.login', compact('vendor'));
    }

    public function doLogin(Request $request, string $slug)
    {
        $vendor = $this->resolveVendor($slug);

        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Human verification
        $hv = HumanVerifier::verify($request, 'portal_login_' . $slug);
        if (!$hv['pass']) {
            return back()->withErrors(['_hv' => $hv['reason']])->withInput($request->only('login'));
        }

        $login = trim($request->input('login'));
        $isPhone = preg_match('/^[\+\d][\d\s\-\(\)]{6,14}$/', $login);

        $field = 'email';
        $value = $login;

        if ($isPhone) {
            $field = 'phone';
            $value = preg_replace('/[\s\-\(\)]/', '', $login);
            $value = ltrim($value, '+');
            if (strlen($value) === 12 && str_starts_with($value, '91')) {
                $value = substr($value, 2);
            }
        }

        if (Auth::attempt([$field => $value, 'password' => $request->password], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Single device: kill all other sessions for students
            if ($user->hasRole('student')) {
                $currentId = $request->session()->getId();
                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $currentId)
                    ->delete();
            }

            // Must have access to this vendor
            $hasAccess = DB::table('student_vendor_access')
                ->where('user_id', $user->id)
                ->where('vendor_id', $vendor->id)
                ->exists();

            if (!$hasAccess && !$user->hasRole(['admin', 'super-admin', 'teacher', 'vendor'])) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors([
                    'login' => 'You do not have access to this portal. Contact your administrator.',
                ])->withInput($request->only('login'));
            }

            $intended = session()->pull('url.intended');
            return $intended
                ? redirect()->to($intended)->with('success', 'Welcome back, ' . $user->name . '! 👋')
                : redirect()->route('vendor.portal.dashboard', $slug)->with('success', 'Welcome back, ' . $user->name . '! 👋');
        }

        $label = $isPhone ? 'phone number' : 'email';
        return back()
            ->withErrors(['login' => "Incorrect {$label} or password. Please try again."])
            ->withInput($request->only('login'));
    }

    // ── Logout ─────────────────────────────────────────────────────────
    public function logout(Request $request, string $slug)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.portal.login', $slug)
            ->with('success', 'You have been logged out.');
    }

    // ── Register (general — no course) ────────────────────────────────
    public function showRegister(string $slug)
    {
        $vendor  = $this->resolveVendor($slug);
        $courses = Course::where('vendor_id', $vendor->id)
            ->where('is_published', 1)
            ->orderBy('title')
            ->get();

        return view('vendor.portal.register-general', compact('vendor', 'courses'));
    }

    public function doRegisterGeneral(Request $request, string $slug)
    {
        $vendor = $this->resolveVendor($slug);

        $hv = HumanVerifier::verify($request, 'portal_register_' . $slug);
        if (!$hv['pass']) {
            return back()->withErrors(['_hv' => $hv['reason']])->withInput();
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone ?: null,
            'country'        => 'IN',
            'country_name'   => 'India',
            'password'       => Hash::make($request->password),
            'registered_via'     => 'vendor_portal',
            'portal_access'      => 'vendor_only',
            'registered_vendor_id' => $vendor->id,
        ]);

        $user->assignRole('student');

        // Grant access to this vendor
        DB::table('student_vendor_access')->insertOrIgnore([
            'user_id'    => $user->id,
            'vendor_id'  => $vendor->id,
            'granted_by' => null,
            'note'       => 'auto on registration',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('vendor.portal.dashboard', $slug)
            ->with('success', 'Welcome to ' . $vendor->brand_name . '! 🎉');
    }

    // ── Register (course-specific) ────────────────────────────────────
    public function register(string $slug, int $courseId)
    {
        $vendor = $this->resolveVendor($slug);
        $course = Course::where('id', $courseId)->where('vendor_id', $vendor->id)
            ->where('is_published', 1)->firstOrFail();

        $enrolledCount = Enrollment::where('course_id', $course->id)->count();
        $course->enrolled_count = $enrolledCount;

        return view('vendor.portal.register', compact('vendor', 'course'));
    }

    public function doRegister(Request $request, string $slug, int $courseId)
    {
        $vendor = $this->resolveVendor($slug);
        $course = Course::where('id', $courseId)->where('vendor_id', $vendor->id)
            ->where('is_published', 1)->firstOrFail();

        $hv = HumanVerifier::verify($request, 'portal_register_course_' . $courseId);
        if (!$hv['pass']) {
            return back()->withErrors(['_hv' => $hv['reason']])->withInput();
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Check seat limit
        if ($course->max_students > 0) {
            $enrolled = Enrollment::where('course_id', $course->id)->count();
            if ($enrolled >= $course->max_students) {
                return back()->withErrors(['email' => 'Sorry, this course is full.'])->withInput();
            }
        }

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone ?: null,
            'country'        => 'IN',
            'country_name'   => 'India',
            'password'       => Hash::make($request->password),
            'registered_via'     => 'vendor_portal',
            'portal_access'      => 'vendor_only',
            'registered_vendor_id' => $vendor->id,
        ]);

        $user->assignRole('student');

        // Grant vendor access
        DB::table('student_vendor_access')->insertOrIgnore([
            'user_id'    => $user->id,
            'vendor_id'  => $vendor->id,
            'granted_by' => null,
            'note'       => 'auto on registration',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Enroll in course
        Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'vendor_id'      => $vendor->id,
                'amount_paid'    => 0,
                'payment_method' => 'portal_registration',
                'status'         => 'active',
                'access_type'    => 'lifetime',
                'granted_by'     => null,
            ]
        );

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('vendor.portal.dashboard', $slug)
            ->with('success', 'Welcome to ' . $vendor->brand_name . '! You are enrolled in "' . $course->title . '". 🎉');
    }

    // ── Dashboard ──────────────────────────────────────────────────────
    public function dashboard(string $slug)
    {
        $vendor = $this->resolveVendor($slug);
        $user   = Auth::user();

        // Course enrollments
        $enrollments = Enrollment::with(['course.sections.lessons'])
            ->where('user_id', $user->id)
            ->whereHas('course', fn($q) => $q->where('vendor_id', $vendor->id))
            ->where('status', 'active')
            ->latest()
            ->get();

        // Live class enrollments (hours/batch system)
        $liveClassEnrollments = LiveClassEnrollment::with(['liveClass.sessions'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('liveClass', fn($q) => $q->where('vendor_id', $vendor->id)
                ->whereNotIn('status', ['cancelled']))
            ->latest()
            ->get();

        // Upcoming old-style live lessons from courses
        $courseIds    = $enrollments->pluck('course_id');
        $upcomingLive = Lesson::with('section.course')
            ->whereHas('section', fn($q) => $q->whereIn('course_id', $courseIds))
            ->where('type', 'live')
            ->where('live_scheduled_at', '>', now())
            ->orderBy('live_scheduled_at')
            ->take(5)
            ->get();

        return view('vendor.portal.dashboard', compact(
            'vendor', 'enrollments', 'liveClassEnrollments', 'upcomingLive'
        ));
    }

    // ── Live class page ────────────────────────────────────────────────
    public function liveClass(string $slug, Lesson $lesson)
    {
        $vendor = $this->resolveVendor($slug);
        $user   = Auth::user();

        // Verify enrolled
        $courseId = $lesson->section->course_id ?? null;
        $enrolled = $courseId && Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();

        if (!$enrolled) {
            abort(403, 'You are not enrolled in this course.');
        }

        return view('vendor.portal.live', compact('vendor', 'lesson'));
    }

    // ── Recording page ─────────────────────────────────────────────────
    public function recording(string $slug, Lesson $lesson)
    {
        $vendor = $this->resolveVendor($slug);
        $user   = Auth::user();

        if (!$lesson->recording_shared || !$lesson->recording_url) {
            abort(404, 'Recording not available.');
        }

        // Verify enrolled
        $courseId = $lesson->section->course_id ?? null;
        $enrolled = $courseId && Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();

        if (!$enrolled) {
            abort(403, 'You are not enrolled in this course.');
        }

        return view('vendor.portal.recording', compact('vendor', 'lesson'));
    }

    // ── Batch page ─────────────────────────────────────────────────────
    public function batch(string $slug, int $courseId)
    {
        $vendor = $this->resolveVendor($slug);
        $user   = Auth::user();

        $course = Course::where('id', $courseId)
            ->where('vendor_id', $vendor->id)
            ->with('sections.lessons')
            ->firstOrFail();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->firstOrFail();

        $liveLessons = $course->sections->flatMap->lessons
            ->where('type', 'live')
            ->sortBy('live_scheduled_at')
            ->values();

        $recordings = $course->sections->flatMap->lessons
            ->where('type', 'live')
            ->where('recording_shared', 1)
            ->filter(fn($l) => !empty($l->recording_url))
            ->sortByDesc('live_scheduled_at')
            ->values();

        $documents = $course->sections->flatMap->lessons
            ->whereIn('type', ['document', 'pdf', 'resource'])
            ->sortBy('order')
            ->values();

        // Attendance percentage
        $attended = DB::table('lesson_progress')
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $liveLessons->pluck('id'))
            ->count();

        $total     = $liveLessons->count();
        $attendPct = $total > 0 ? round(($attended / $total) * 100) : 0;

        return view('vendor.portal.batch', compact(
            'vendor', 'course', 'enrollment',
            'liveLessons', 'recordings', 'documents', 'attendPct'
        ));
    }

    // ── Live Class Detail (student view) ──────────────────────────────
    public function liveClassDetail(string $slug, string $liveClassSlug)
    {
        $vendor    = $this->resolveVendor($slug);
        $user      = Auth::user();
        $liveClass = LiveClass::where('slug', $liveClassSlug)->where('vendor_id', $vendor->id)->firstOrFail();

        // Must be enrolled
        $enrollment = LiveClassEnrollment::where('live_class_id', $liveClass->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $liveClass->load(['sessions' => fn($q) => $q->orderByDesc('started_at'), 'sessions.files']);

        $activeSession = $liveClass->activeSession();

        // Mark join attendance if class is live now
        if ($activeSession) {
            LiveClassAttendance::firstOrCreate(
                ['live_class_session_id' => $activeSession->id, 'user_id' => $user->id],
                ['live_class_id' => $liveClass->id, 'joined_at' => now()]
            );
        }

        // Student attendance records
        $attendance = LiveClassAttendance::where('live_class_id', $liveClass->id)
            ->where('user_id', $user->id)
            ->with('session')
            ->orderByDesc('joined_at')
            ->get();

        $totalSessions        = $liveClass->sessions->where('status', 'ended')->count();
        $attendedSessions     = $attendance->count();
        $attendPct            = $totalSessions > 0 ? min(100, round(($attendedSessions / $totalSessions) * 100)) : 0;
        $totalAttendedMinutes = $attendance->sum('duration_minutes');

        return view('vendor.portal.live_class', compact(
            'vendor', 'liveClass', 'enrollment',
            'activeSession', 'attendance',
            'attendPct', 'attendedSessions', 'totalSessions', 'totalAttendedMinutes'
        ));
    }

    // ── Batch login (for batch-gated courses) ─────────────────────────
    public function batchLogin(Request $request, string $slug, int $courseId)
    {
        $vendor = $this->resolveVendor($slug);
        $course = Course::where('id', $courseId)->where('vendor_id', $vendor->id)->firstOrFail();

        $request->validate(['password' => 'required|string']);

        if ($request->password !== $course->batch_password) {
            return back()->withErrors(['password' => 'Incorrect batch password.']);
        }

        session(['batch_access_' . $courseId => true]);

        return redirect()->route('vendor.portal.batch', [$slug, $courseId]);
    }

    // ── Student: secure file viewer ────────────────────────────────────
    public function viewSessionFile(string $slug, string $liveClassSlug, \App\Models\LiveClassSessionFile $file)
    {
        $vendor    = $this->resolveVendor($slug);
        $user      = Auth::user();
        $liveClass = LiveClass::where('slug', $liveClassSlug)->where('vendor_id', $vendor->id)->firstOrFail();

        // Must be enrolled in this live class
        LiveClassEnrollment::where('live_class_id', $liveClass->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        // File must be visible
        abort_if(!$file->is_visible, 403, 'This file is not available.');
        abort_if($file->live_class_id !== $liveClass->id, 403);

        // Generate signed URL — 2 hours, no download headers
        $signedUrl = \Illuminate\Support\Facades\Storage::disk($file->disk)
            ->temporaryUrl($file->path, now()->addHours(2));

        return view('vendor.portal.file_viewer', compact('vendor', 'liveClass', 'file', 'signedUrl'));
    }

}
