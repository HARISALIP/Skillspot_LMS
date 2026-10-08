<?php
namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SecureVideoController extends Controller
{
    // ── Generate signed URL for lesson video ───────────────────────────
    // Only accessible to enrolled students or admins
    public function stream(Request $request, Lesson $lesson)
    {
        $user   = Auth::user();
        $section= $lesson->section;
        $course = $section->course;

        // Admins always have access
        $isAdmin = $user->hasRole('super-admin') || $user->hasRole('admin');

        // Check enrollment for students
        if (!$isAdmin) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->first();

            // Allow free preview lessons even without enrollment
            if (!$enrollment && !$lesson->is_preview) {
                return response()->json(['error' => 'Access denied. Please enroll to watch this lesson.'], 403);
            }

            // Block if limited access expired
            if ($enrollment && $enrollment->access_type === 'limited' && $enrollment->expires_at?->isPast()) {
                return response()->json(['error' => 'Your access has expired. Contact support to renew.'], 403);
            }
        }

        // R2 stored video — generate short-lived signed URL
        if ($lesson->video_storage === 'r2' && $lesson->video_r2_path) {
            try {
                $signedUrl = Storage::disk('r2')->temporaryUrl(
                    $lesson->video_r2_path,
                    now()->addMinutes(120) // 2 hour expiry
                );

                // Log access for analytics
                Log::info('Video accessed', [
                    'user_id'   => $user->id,
                    'lesson_id' => $lesson->id,
                    'course_id' => $course->id,
                    'ip'        => $request->ip(),
                ]);

                return response()->json([
                    'url'     => $signedUrl,
                    'expires' => now()->addMinutes(120)->timestamp,
                    'type'    => 'r2',
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Video temporarily unavailable.'], 500);
            }
        }

        // External URL (YouTube/Vimeo) — return as-is
        if ($lesson->video_url) {
            return response()->json([
                'url'  => $lesson->video_url,
                'type' => 'external',
            ]);
        }

        return response()->json(['error' => 'No video available for this lesson.'], 404);
    }

    // ── Track progress ─────────────────────────────────────────────────
    public function progress(Request $request, Lesson $lesson)
    {
        $request->validate(['progress' => 'required|numeric|min:0|max:100']);

        $user    = Auth::user();
        $section = $lesson->section;
        $course  = $section->course;

        // Update lesson progress in DB
        \DB::table('lesson_progress')->updateOrInsert(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'progress'     => $request->progress,
                'completed'    => $request->progress >= 90 ? 1 : 0,
                'completed_at' => $request->progress >= 90 ? now() : null,
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );

        // Update overall course enrollment progress
        $totalLessons    = $course->sections->sum(fn($s) => $s->lessons->count());
        $completedLessons= \DB::table('lesson_progress')
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $course->sections->flatMap->lessons->pluck('id'))
            ->where('completed', 1)
            ->count();

        $courseProgress = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100)
            : 0;

        Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->update([
                'progress'     => $courseProgress,
                'completed_at' => $courseProgress >= 100 ? now() : null,
                'status'       => $courseProgress >= 100 ? 'completed' : 'active',
            ]);

        return response()->json([
            'ok'              => true,
            'lesson_progress' => $request->progress,
            'course_progress' => $courseProgress,
            'completed'       => $request->progress >= 90,
        ]);
    }
}
