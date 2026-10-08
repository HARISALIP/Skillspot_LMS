<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Models\BatchAttendance;
use App\Models\Certificate;
use App\Models\Vendor;
use App\Models\VendorPreallowedContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with('vendor')->withCount('enrollments');

        if ($request->vendor_id) $query->where('vendor_id', $request->vendor_id);
        if ($request->type)      $query->where('course_type', $request->type);
        if ($request->search)    $query->where('title','like','%'.$request->search.'%');

        $batches = $query->latest()->paginate(20);
        $vendors = Vendor::orderBy('brand_name')->get(['id','brand_name']);
        return view('admin.batches.index', compact('batches','vendors'));
    }

    public function show(Course $course)
    {
        $course->load('vendor','sections.lessons');
        $enrolled = $course->enrollments()->with('user')->get();
        $lessons  = Lesson::where('type','live')
            ->whereHas('section', fn($q) => $q->where('course_id', $course->id))
            ->orderBy('live_scheduled_at')->get();

        $attendanceSummary = [];
        foreach ($lessons as $lesson) {
            $records = BatchAttendance::where('lesson_id', $lesson->id)->get();
            $attendanceSummary[$lesson->id] = [
                'present' => $records->where('status','present')->count(),
                'absent'  => $records->where('status','absent')->count(),
                'late'    => $records->where('status','late')->count(),
                'total'   => $enrolled->count(),
            ];
        }
        $certs       = Certificate::where('course_id', $course->id)->pluck('user_id')->toArray();
        $allowedUsers = $course->allowedUsers()->get();
        $preallowed  = VendorPreallowedContact::where('vendor_id', $course->vendor_id)->get();

        return view('admin.batches.show', compact(
            'course','enrolled','lessons','attendanceSummary','certs','allowedUsers','preallowed'
        ));
    }

    public function bulkAllow(Request $request, Course $course)
    {
        $request->validate(['emails' => 'required|string']);
        $emails = array_filter(array_map('trim', preg_split('/[\s,;\n]+/', $request->emails)));
        $added = 0; $notFound = [];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if (!$user) { $notFound[] = $email; continue; }
            $course->allowedUsers()->syncWithoutDetaching([$user->id => ['added_by' => auth()->id()]]);
            Enrollment::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['vendor_id' => $course->vendor_id, 'amount_paid' => 0, 'payment_method' => 'admin_grant']
            );
            // grant vendor access
            if ($course->vendor_id) {
                DB::table('student_vendor_access')->updateOrInsert(
                    ['user_id' => $user->id, 'vendor_id' => $course->vendor_id],
                    ['granted_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]
                );
            }
            $added++;
        }
        $msg = "{$added} student(s) added ✅";
        if ($notFound) $msg .= " | Not found: " . implode(', ', $notFound);
        return back()->with('success', $msg);
    }

    public function revokeStudent(Course $course, User $user)
    {
        $course->allowedUsers()->detach($user->id);
        Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->delete();
        return back()->with('success', $user->name . ' removed.');
    }

    public function attendance(Course $course, Lesson $lesson)
    {
        $lesson->load('section.course');
        $enrolled = $course->enrollments()->with('user')->get();
        $existing = BatchAttendance::where('lesson_id', $lesson->id)->with('markedBy')->get()->keyBy('user_id');
        $summary  = [
            'present' => $existing->where('status','present')->count(),
            'late'    => $existing->where('status','late')->count(),
            'absent'  => $existing->where('status','absent')->count(),
            'total'   => $enrolled->count(),
        ];
        return view('admin.batches.attendance', compact('course','lesson','enrolled','existing','summary'));
    }

    public function saveAttendance(Request $request, Course $course, Lesson $lesson)
    {
        foreach ($request->input('attendance', []) as $userId => $status) {
            if (!in_array($status, ['present','absent','late'])) continue;
            BatchAttendance::updateOrCreate(
                ['lesson_id' => $lesson->id, 'user_id' => $userId],
                ['course_id' => $course->id, 'vendor_id' => $course->vendor_id, 'status' => $status, 'marked_by' => auth()->id()]
            );
        }
        return back()->with('success', 'Attendance saved ✅');
    }

    public function report(Course $course)
    {
        $enrolled = $course->enrollments()->with('user')->get();
        $lessons  = Lesson::where('type','live')
            ->whereHas('section', fn($q) => $q->where('course_id', $course->id))
            ->orderBy('live_scheduled_at')->get();

        $report = $enrolled->map(function($e) use ($lessons, $course) {
            $attended = BatchAttendance::where('course_id',$course->id)->where('user_id',$e->user_id)->where('status','present')->count();
            $late     = BatchAttendance::where('course_id',$course->id)->where('user_id',$e->user_id)->where('status','late')->count();
            $total    = $lessons->count();
            return [
                'user'     => $e->user,
                'attended' => $attended, 'late' => $late, 'total' => $total,
                'pct'      => $total > 0 ? round((($attended+$late)/$total)*100) : 0,
                'progress' => $e->progress,
            ];
        });
        return view('admin.batches.report', compact('course','report','lessons'));
    }

    public function issueCertificate(Request $request, Course $course, Enrollment $enrollment)
    {
        abort_if($enrollment->course_id !== $course->id, 403);
        if (Certificate::where('course_id',$course->id)->where('user_id',$enrollment->user_id)->exists()) {
            return back()->with('error', 'Certificate already issued.');
        }
        Certificate::create([
            'user_id'            => $enrollment->user_id,
            'course_id'          => $course->id,
            'vendor_id'          => $course->vendor_id,
            'enrollment_id'      => $enrollment->id,
            'certificate_number' => strtoupper(Str::random(12)),
            'issued_at'          => now(),
        ]);
        $enrollment->update(['status' => 'completed', 'completed_at' => now(), 'progress' => 100]);
        return back()->with('success', 'Certificate issued to ' . $enrollment->user->name . ' ✅');
    }

    public function preAllow(Request $request, Course $course)
    {
        $request->validate(['contacts' => 'required|string', 'contact_type' => 'required|in:email,phone']);
        $contacts = array_filter(array_map('trim', preg_split('/[\s,;\n]+/', $request->contacts)));
        $added = 0;
        foreach ($contacts as $contact) {
            if ($course->vendor_id) {
                VendorPreallowedContact::firstOrCreate(
                    ['vendor_id' => $course->vendor_id, 'contact' => $contact],
                    ['contact_type' => $request->contact_type, 'added_by' => auth()->id()]
                );
            }
            $existing = $request->contact_type === 'email'
                ? User::where('email', $contact)->first()
                : User::where('phone', $contact)->first();
            if ($existing) {
                $course->allowedUsers()->syncWithoutDetaching([$existing->id => ['added_by' => auth()->id()]]);
                Enrollment::firstOrCreate(
                    ['user_id' => $existing->id, 'course_id' => $course->id],
                    ['vendor_id' => $course->vendor_id, 'amount_paid' => 0, 'payment_method' => 'admin_grant']
                );
            }
            $added++;
        }
        return back()->with('success', $added . ' contact(s) pre-allowed ✅');
    }
}
