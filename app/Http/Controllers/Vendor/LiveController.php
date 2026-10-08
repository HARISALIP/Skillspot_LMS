<?php
namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LiveController extends Controller
{
    use ResolvesVendor;

    public function index()
    {
        $vendor  = $this->vendor();
        $now     = now();
        $allLive = Lesson::where('type','live')
            ->whereNotNull('live_scheduled_at')
            ->whereHas('section.course', fn($q) => $q->where('vendor_id', $vendor->id))
            ->with('section.course')
            ->orderBy('live_scheduled_at','desc')
            ->get();

        $upcoming = $allLive->filter(fn($l) =>
            $l->live_scheduled_at && $l->live_scheduled_at->isFuture()
        )->sortBy('live_scheduled_at');

        $liveNow = $allLive->filter(fn($l) =>
            $l->live_scheduled_at &&
            $now->between($l->live_scheduled_at, $l->live_scheduled_at->copy()->addMinutes($l->live_duration_min ?? 60))
        );

        $past = $allLive->filter(fn($l) =>
            $l->live_scheduled_at &&
            $now->gt($l->live_scheduled_at->copy()->addMinutes($l->live_duration_min ?? 60))
        );

        $courses  = Course::where('vendor_id', $vendor->id)->where('is_published',1)->orderBy('title')->get(['id','title']);
        $sections = Section::whereIn('course_id', $courses->pluck('id'))
            ->orderBy('order')
            ->get(['id','course_id','title'])
            ->groupBy('course_id')
            ->map(fn($s) => $s->map(fn($sec) => ['id'=>$sec->id,'title'=>$sec->title])->values())
            ->toArray();

        return view('vendor.live.index', compact('vendor','upcoming','liveNow','past','courses','sections'));
    }

    public function store(Request $request)
    {
        $vendor = $this->vendor();
        $isRecurring = $request->session_type === 'recurring';

        $rules = [
            'title'             => 'required|string|max:255',
            'session_type'      => 'required|in:one_time,recurring',
            'live_platform'     => 'required|in:google_meet,zoom,teams,other',
            'live_url'          => 'required|string|max:500',
            'live_share_link'   => 'nullable|string|max:500',
            'live_duration_min' => 'required|integer|min:15',
            'live_meeting_id'   => 'nullable|string|max:100',
            'live_password'     => 'nullable|string|max:100',
            'enroll_limit'      => 'nullable|integer|min:0',
        ];

        if ($isRecurring) {
            $rules['recurring_days']  = 'required|array|min:1';
            $rules['recurring_days.*']= 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun';
            $rules['recurring_time']  = 'required|date_format:H:i';
            $rules['recurring_start'] = 'required|date|after_or_equal:today';
            $rules['recurring_end']   = 'required|date|after:recurring_start';
        } else {
            $rules['live_scheduled_at'] = 'required|date|after:now';
        }

        $request->validate($rules);

        // Auto-find or create "Live Sessions" course + section
        $course = Course::firstOrCreate(
            ['vendor_id' => $vendor->id, 'title' => 'Live Sessions'],
            [
                'slug'         => Str::slug($vendor->brand_name . '-live-sessions') . '-' . $vendor->id,
                'is_published' => false,
                'is_free'      => true,
                'course_type'  => 'open',
                'visibility'   => 'private',
            ]
        );
        $section = Section::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'Live Sessions'],
            ['order' => 1]
        );

        $base = [
            'section_id'        => $section->id,
            'type'              => 'live',
            'content'           => $request->session_type,
            'notes'             => (string)($request->enroll_limit ?? 0),
            'live_platform'     => $request->live_platform,
            'live_url'          => $request->live_url,
            'live_share_link'   => $request->live_share_link,
            'live_duration_min' => $request->live_duration_min,
            'live_meeting_id'   => $request->live_meeting_id,
            'live_password'     => $request->live_password,
        ];

        $created = 0;

        if ($isRecurring) {
            $dayMap = ['Mon'=>1,'Tue'=>2,'Wed'=>3,'Thu'=>4,'Fri'=>5,'Sat'=>6,'Sun'=>0];
            $start  = \Carbon\Carbon::parse($request->recurring_start);
            $end    = \Carbon\Carbon::parse($request->recurring_end);
            $time   = $request->recurring_time;
            $days   = $request->recurring_days;

            $current = $start->copy();
            while ($current->lte($end)) {
                $dayName = $current->format('D'); // Mon, Tue...
                if (in_array($dayName, $days)) {
                    $scheduled = \Carbon\Carbon::parse($current->format('Y-m-d') . ' ' . $time);
                    $maxOrder  = $section->lessons()->max('order') + 1;
                    Lesson::create(array_merge($base, [
                        'title'             => $request->title . ' (' . $current->format('d M') . ')',
                        'live_scheduled_at' => $scheduled,
                        'order'             => $maxOrder,
                    ]));
                    $created++;
                }
                $current->addDay();
            }
            return back()->with('success', "{$created} recurring sessions scheduled for \"{$request->title}\" ✅");
        } else {
            $maxOrder = $section->lessons()->max('order') + 1;
            Lesson::create(array_merge($base, [
                'title'             => $request->title,
                'live_scheduled_at' => $request->live_scheduled_at,
                'order'             => $maxOrder,
            ]));
            return back()->with('success', "Live class \"{$request->title}\" scheduled ✅");
        }
    }

    public function updateRecording(Request $request, Lesson $lesson)
    {
        $vendor = $this->vendor();
        abort_if($lesson->section->course->vendor_id !== $vendor->id, 403);
        $request->validate([
            'recording_url'    => 'required|url|max:500',
            'recording_shared' => 'boolean',
        ]);
        $lesson->update([
            'recording_url'    => $request->recording_url,
            'recording_shared' => $request->boolean('recording_shared'),
        ]);
        return back()->with('success', 'Recording updated ✅');
    }

    public function toggleShare(Lesson $lesson)
    {
        $vendor = $this->vendor();
        abort_if($lesson->section->course->vendor_id !== $vendor->id, 403);
        $lesson->recording_shared = !$lesson->recording_shared;
        $lesson->save();
        return back()->with('success', $lesson->recording_shared ? 'Recording shared with students ✅' : 'Recording hidden from students.');
    }

    public function destroy(Lesson $lesson)
    {
        $vendor = $this->vendor();
        abort_if($lesson->section->course->vendor_id !== $vendor->id, 403);
        $lesson->delete();
        return back()->with('success', 'Live session removed.');
    }
}
