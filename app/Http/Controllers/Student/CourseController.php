<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Setting;
use App\Services\CourseService;
use App\Services\MediaUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    // ── Course detail / landing page ──────────────────────────────────
    public function detail(string $slug)
    {
        $course = Course::where('slug',$slug)
            ->where('is_published',1)
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

        $razorpayKey = Setting::get('razorpay_mode','live') === 'test'
            ? Setting::get('razorpay_test_key_id')
            : Setting::get('razorpay_key_id');

        return view('student.course-detail', compact(
            'course','enrollment','hasAccess','isExpired',
            'totalLessons','totalDuration','requirements','outcomes','razorpayKey'
        ));
    }

    // ── Free enroll ────────────────────────────────────────────────────
    public function enrollFree(Request $request, Course $course)
    {
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
        $user = Auth::user();

        if (CourseService::hasAccess($user, $course)) {
            return response()->json(['error'=>'Already enrolled.'], 422);
        }

        $amount = (int)(($course->sale_price ?? $course->price) * 100);
        $mode   = Setting::get('razorpay_mode','live');
        $keyId  = $mode === 'test' ? Setting::get('razorpay_test_key_id') : Setting::get('razorpay_key_id');
        $secret = $mode === 'test' ? Setting::get('razorpay_test_key_secret') : Setting::get('razorpay_key_secret');

        try {
            $api   = new \Razorpay\Api\Api($keyId, $secret);
            $order = $api->order->create([
                'amount'          => $amount,
                'currency'        => Setting::get('currency','INR'),
                'receipt'         => 'rcpt_'.$user->id.'_'.$course->id.'_'.time(),
                'payment_capture' => 1,
                'notes'           => [
                    'user_id'    => $user->id,
                    'course_id'  => $course->id,
                    'user_email' => $user->email,
                    'course'     => $course->title,
                ],
            ]);

            \App\Models\PaymentOrder::create([
                'user_id'           => $user->id,
                'course_id'         => $course->id,
                'razorpay_order_id' => $order->id,
                'amount'            => $course->sale_price ?? $course->price,
                'currency'          => Setting::get('currency','INR'),
                'mode'              => $mode,
                'status'            => 'created',
            ]);

            return response()->json([
                'order_id'   => $order->id,
                'amount'     => $amount,
                'currency'   => Setting::get('currency','INR'),
                'key_id'     => $keyId,
                'course'     => $course->title,
                'user_name'  => $user->name,
                'user_email' => $user->email,
                'user_phone' => $user->phone ?? '',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error'=>'Payment init failed: '.$e->getMessage()], 500);
        }
    }

    // ── Verify payment & enroll ────────────────────────────────────────
    public function verifyPayment(Request $request, Course $course)
    {
        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $mode   = Setting::get('razorpay_mode','live');
        $secret = $mode === 'test' ? Setting::get('razorpay_test_key_secret') : Setting::get('razorpay_key_secret');

        $generated = hash_hmac('sha256',
            $request->razorpay_order_id.'|'.$request->razorpay_payment_id, $secret);

        if (!hash_equals($generated, $request->razorpay_signature)) {
            return response()->json(['error'=>'Payment verification failed.'], 422);
        }

        $paymentOrder = \App\Models\PaymentOrder::where('razorpay_order_id',$request->razorpay_order_id)->first();
        if ($paymentOrder) {
            $paymentOrder->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
                'status'              => 'paid',
                'paid_at'             => now(),
            ]);
        }

        $enrollment = CourseService::enrollAfterPayment(
            Auth::user(), $course,
            (float)($course->sale_price ?? $course->price),
            $request->razorpay_payment_id
        );

        return response()->json([
            'success'  => true,
            'redirect' => route('student.learn', $course->id),
            'message'  => 'Payment successful! You are now enrolled. 🎉',
        ]);
    }

    // ── Course learn page ──────────────────────────────────────────────
    public function learn(Course $course)
    {
        $user = Auth::user();

        if (!CourseService::hasAccess($user, $course)) {
            return redirect()->route('student.course-detail', $course->slug)
                ->with('error','Please enroll to access this course.');
        }

        $course->load('sections.lessons');
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
            'currentLesson','certificate'
        ));
    }

    // ── Get lesson for player (AJAX) ───────────────────────────────────
    public function getLesson(Course $course, Lesson $lesson)
    {
        $user = Auth::user();
        if (!CourseService::hasAccess($user, $course)) {
            return response()->json(['error'=>'Access denied.'], 403);
        }

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

        $progress = DB::table('lesson_progress')
            ->where('user_id',$user->id)->where('lesson_id',$lesson->id)
            ->first();

        return response()->json([
            'id'           => $lesson->id,
            'title'        => $lesson->title,
            'type'         => $lesson->type,
            'video_url'    => $videoUrl,
            'content'      => $lesson->content,
            'notes'        => $lesson->notes,
            'duration'     => $lesson->duration,
            'is_preview'   => $lesson->is_preview,
            'progress'     => $progress?->progress ?? 0,
            'completed'    => (bool)($progress?->completed ?? false),
            'live_platform'=> $lesson->live_platform,
            'live_url'     => $lesson->live_url,
            'live_scheduled_at' => $lesson->live_scheduled_at?->toIso8601String(),
            'live_meeting_id'   => $lesson->live_meeting_id,
            'live_password'     => $lesson->live_password,
        ]);
    }

    // ── Save lesson progress ───────────────────────────────────────────
    public function saveProgress(Request $request, Course $course, Lesson $lesson)
    {
        $request->validate([
            'progress'        => 'required|numeric|min:0|max:100',
            'watched_seconds' => 'nullable|integer|min:0',
        ]);

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
}
