<?php
namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Enrollment;
use App\Services\CourseService;
use App\Services\LessonVideo;
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
        abort_unless($isAdmin || CourseService::hasAccess($user, $course)
            || ($lesson->is_preview && $course->is_published && $course->visibility === 'public' && $course->isMarketplaceLive()), 403);

        $url = LessonVideo::url($lesson);
        if (!$url) return response()->json(['error' => 'Video temporarily unavailable.'], 503);
        Log::info('Video accessed', ['user_id' => $user->id, 'lesson_id' => $lesson->id,
            'course_id' => $course->id, 'ip' => $request->ip()]);
        return response()->json(['url' => $url, 'expires' => now()->addMinutes(5)->timestamp,
            'type' => $lesson->video_storage === 'r2' ? 'private' : 'external']);
    }

    public function file(Lesson $lesson)
    {
        $course = $lesson->section->course;
        $user = Auth::user();
        abort_unless($user->hasRole(['admin', 'super-admin']) || CourseService::hasAccess($user, $course)
            || ($lesson->is_preview && $course->is_published && $course->visibility === 'public' && $course->isMarketplaceLive()), 403);
        $upload = LessonVideo::upload($lesson);
        abort_unless($upload && in_array($upload->disk, ['local', 'public'], true)
            && Storage::disk($upload->disk)->exists($upload->path), 404);

        return response()->file(Storage::disk($upload->disk)->path($upload->path), [
            'Content-Type' => $upload->mime_type, 'Cache-Control' => 'private, no-store',
        ]);
    }

    // ── Track progress ─────────────────────────────────────────────────
    public function progress(Request $request, Lesson $lesson)
    {
        $request->validate(['progress' => 'required|numeric|min:0|max:100']);

        $user    = Auth::user();
        $section = $lesson->section;
        $course  = $section->course;
        abort_unless(CourseService::hasAccess($user, $course), 403);
        abort_unless($lesson->type === 'video', 422);

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
