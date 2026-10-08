<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\BatchAttendance;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    public function index()
    {
        $now = now();

        $allLive = Lesson::where('type','live')
            ->whereNotNull('live_scheduled_at')
            ->with('section.course')
            ->orderBy('live_scheduled_at','desc')
            ->get();

        $upcoming = $allLive->filter(fn($l) =>
            $l->live_scheduled_at && $l->live_scheduled_at->isFuture() ||
            $now->between($l->live_scheduled_at, $l->live_scheduled_at->copy()->addMinutes($l->live_duration_min ?? 60))
        )->sortBy('live_scheduled_at');

        $past = $allLive->filter(fn($l) =>
            $l->live_scheduled_at &&
            $now->gt($l->live_scheduled_at->copy()->addMinutes($l->live_duration_min ?? 60))
        );

        $stats = [
            'upcoming' => $upcoming->filter(fn($l) => $l->live_scheduled_at->isFuture())->count(),
            'live_now' => $upcoming->filter(fn($l) =>
                $now->between($l->live_scheduled_at, $l->live_scheduled_at->copy()->addMinutes($l->live_duration_min ?? 60))
            )->count(),
            'past'     => $past->count(),
        ];

        $courses  = Course::where('is_published',1)->orderBy('title')->get(['id','title']);

        $sections = Section::whereIn('course_id', $courses->pluck('id'))
            ->orderBy('order')
            ->get(['id','course_id','title'])
            ->groupBy('course_id')
            ->map(fn($s) => $s->map(fn($sec) => ['id'=>$sec->id,'title'=>$sec->title])->values())
            ->toArray();

        return view('admin.live.index', compact('upcoming','past','stats','courses','sections'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id'          => 'required|exists:courses,id',
            'section_id'         => 'required|exists:sections,id',
            'title'              => 'required|string|max:255',
            'live_platform'      => 'required|in:google_meet,zoom,teams,other',
            'live_url'           => 'required|string|max:500',
            'live_scheduled_at'  => 'required|date|after:now',
            'live_duration_min'  => 'required|integer|min:15',
            'live_meeting_id'    => 'nullable|string|max:100',
            'live_password'      => 'nullable|string|max:100',
            'description'        => 'nullable|string',
            'is_preview'         => 'boolean',
        ]);

        $section  = Section::findOrFail($request->section_id);
        $maxOrder = $section->lessons()->max('order') + 1;

        Lesson::create([
            'section_id'        => $section->id,
            'title'             => $request->title,
            'description'       => $request->description,
            'type'              => 'live',
            'live_platform'     => $request->live_platform,
            'live_url'          => $request->live_url,
            'live_scheduled_at' => $request->live_scheduled_at,
            'live_duration_min' => $request->live_duration_min,
            'live_meeting_id'   => $request->live_meeting_id,
            'live_password'     => $request->live_password,
            'is_preview'        => $request->boolean('is_preview'),
            'order'             => $maxOrder,
        ]);

        return redirect()->route('admin.live')
            ->with('success', "Live class \"{$request->title}\" scheduled ✅");
    }

    public function destroy(Lesson $lesson)
    {
        if ($lesson->type !== 'live') {
            return back()->with('error','Not a live lesson.');
        }
        $title = $lesson->title;
        $lesson->delete();
        return back()->with('success', "Session \"{$title}\" cancelled.");
    }

    /** Admin: view attendance for a live lesson */
    public function attendance(Lesson $lesson)
    {
        abort_if($lesson->type !== 'live', 404);
        $lesson->load('section.course');

        $enrolled  = Enrollment::where('course_id', $lesson->section->course_id)
            ->with('user')
            ->get();

        $existing  = BatchAttendance::where('lesson_id', $lesson->id)
            ->with('markedBy')
            ->get()
            ->keyBy('user_id');

        $summary = [
            'present' => $existing->where('status','present')->count(),
            'late'    => $existing->where('status','late')->count(),
            'absent'  => $existing->where('status','absent')->count(),
            'total'   => $enrolled->count(),
        ];

        return view('admin.live.attendance', compact('lesson','enrolled','existing','summary'));
    }

    /** Admin: save/update attendance */
    public function saveAttendance(Request $request, Lesson $lesson)
    {
        abort_if($lesson->type !== 'live', 404);

        foreach ($request->input('attendance', []) as $userId => $status) {
            if (!in_array($status, ['present','absent','late'])) continue;
            BatchAttendance::updateOrCreate(
                ['lesson_id' => $lesson->id, 'user_id' => $userId],
                [
                    'course_id' => $lesson->section->course_id,
                    'vendor_id' => $lesson->section->course->vendor_id,
                    'status'    => $status,
                    'marked_by' => auth()->id(),
                    'note'      => 'admin override',
                ]
            );
        }

        return back()->with('success', 'Attendance updated ✅');
    }
}
