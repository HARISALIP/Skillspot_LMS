<?php
namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Certificate;
use App\Models\User;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseService
{
    // ── Enroll student in a course (free) ─────────────────────────────
    public static function enrollFree(User $user, Course $course): Enrollment
    {
        return DB::transaction(function() use ($user, $course) {
            $existing = Enrollment::where('user_id',$user->id)
                ->where('course_id',$course->id)->first();
            if ($existing) return $existing;

            return Enrollment::create([
                'user_id'       => $user->id,
                'course_id'     => $course->id,
                'vendor_id'     => $course->vendor_id,
                'amount_paid'   => 0,
                'payment_method'=> 'free',
                'status'        => 'active',
                'access_type'   => 'lifetime',
                'progress'      => 0,
            ]);
        });
    }

    // ── Enroll after successful payment ───────────────────────────────
    public static function enrollAfterPayment(
        User $user, Course $course,
        float $amount, string $paymentId,
        string $accessType = 'lifetime',
        ?\Carbon\Carbon $expiresAt = null
    ): Enrollment {
        return DB::transaction(function() use ($user,$course,$amount,$paymentId,$accessType,$expiresAt) {
            $existing = Enrollment::where('user_id',$user->id)
                ->where('course_id',$course->id)->first();
            if ($existing) {
                $existing->update(['status'=>'active','transaction_id'=>$paymentId]);
                return $existing;
            }

            return Enrollment::create([
                'user_id'        => $user->id,
                'course_id'      => $course->id,
                'vendor_id'      => $course->vendor_id,
                'amount_paid'    => $amount,
                'payment_method' => 'razorpay',
                'transaction_id' => $paymentId,
                'status'         => 'active',
                'access_type'    => $accessType,
                'expires_at'     => $expiresAt,
                'progress'       => 0,
            ]);
        });
    }

    // ── Mark lesson progress ───────────────────────────────────────────
    public static function updateLessonProgress(
        User $user, Lesson $lesson, int $percent, int $watchedSeconds = 0
    ): array {
        $completed = $percent >= 90;

        DB::table('lesson_progress')->updateOrInsert(
            ['user_id'=>$user->id, 'lesson_id'=>$lesson->id],
            [
                'progress'       => $percent,
                'completed'      => $completed ? 1 : 0,
                'watched_seconds'=> $watchedSeconds,
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
        );

        // Recalculate course progress
        $section  = $lesson->section;
        $course   = $section->course;
        $result   = self::recalculateCourseProgress($user, $course);

        return $result;
    }

    // ── Recalculate overall course progress ───────────────────────────
    public static function recalculateCourseProgress(User $user, Course $course): array
    {
        $course->loadMissing('sections.lessons');
        $allLessonIds = $course->sections->flatMap->lessons->pluck('id');
        $total        = $allLessonIds->count();

        if ($total === 0) return ['progress'=>0,'completed'=>false,'certificate'=>null];

        $completedCount = DB::table('lesson_progress')
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $allLessonIds)
            ->where('completed', 1)
            ->count();

        $progress = (int) round(($completedCount / $total) * 100);
        $courseCompleted = $completedCount >= $total;

        $enrollment = Enrollment::where('user_id',$user->id)
            ->where('course_id',$course->id)->first();

        if (!$enrollment) return ['progress'=>$progress,'completed'=>$courseCompleted,'certificate'=>null];

        $enrollment->update([
            'progress'     => $progress,
            'status'       => $courseCompleted ? 'completed' : 'active',
            'completed_at' => $courseCompleted ? now() : null,
        ]);

        // Auto-issue certificate if just completed
        $certificate = null;
        if ($courseCompleted) {
            $certificate = self::issueCertificate($user, $course, $enrollment);
        }

        return [
            'progress'    => $progress,
            'completed'   => $courseCompleted,
            'certificate' => $certificate,
        ];
    }

    // ── Auto-issue certificate ─────────────────────────────────────────
    public static function issueCertificate(User $user, Course $course, Enrollment $enrollment): Certificate
    {
        $existing = Certificate::where('user_id',$user->id)
            ->where('course_id',$course->id)->first();
        if ($existing) return $existing;

        return Certificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'vendor_id'          => $course->vendor_id,
            'enrollment_id'      => $enrollment->id,
            'certificate_number' => 'ITF-' . strtoupper(Str::random(6)) . '-' . now()->format('Y'),
            'issued_at'          => now(),
        ]);
    }

    // ── Check if student has valid access ─────────────────────────────
    public static function hasAccess(User $user, Course $course): bool
    {
        $enrollment = Enrollment::where('user_id',$user->id)
            ->where('course_id',$course->id)
            ->whereIn('status',['active','completed'])
            ->first();

        if (!$enrollment) return false;
        if ($enrollment->access_type === 'limited' && $enrollment->expires_at?->isPast()) return false;

        return true;
    }
}
