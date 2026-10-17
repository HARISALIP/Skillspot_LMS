<?php
namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Models\BatchAttendance;
use App\Models\Certificate;
use App\Models\VendorPreallowedContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BatchController extends Controller
{
    use ResolvesVendor;

    public function index()
    {
        $vendor  = $this->vendor();
        $batches = Course::where('vendor_id', $vendor->id)
            ->withCount('enrollments')
            ->with('sections.lessons')
            ->latest()
            ->get()
            ->map(function ($c) {
                $c->live_count = $c->sections->flatMap->lessons->where('type','live')->count();
                return $c;
            });
        return view('vendor.batches.index', compact('vendor','batches'));
    }

    public function show(Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);

        $enrolled = $course->enrollments()->with('user')->get();
        $lessons  = Lesson::where('type','live')
            ->whereHas('section', fn($q) => $q->where('course_id', $course->id))
            ->with('section')
            ->orderBy('live_scheduled_at')
            ->get();

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

        $certs = Certificate::where('course_id', $course->id)->pluck('user_id')->toArray();
        $allowedUsers = $course->allowedUsers()->get();
        $canManage = !$this->isVendorOnly();

        return view('vendor.batches.show', compact(
            'vendor','course','allowedUsers','enrolled','lessons','attendanceSummary','certs','canManage'
        ));
    }

    public function attendance(Course $course, Lesson $lesson)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        abort_if($lesson->section?->course_id !== $course->id, 403);

        $enrolled = $course->enrollments()->with('user')->get();
        $existing = BatchAttendance::where('lesson_id', $lesson->id)->pluck('status','user_id')->toArray();
        $canManage = !$this->isVendorOnly();

        return view('vendor.batches.attendance', compact('vendor','course','lesson','enrolled','existing','canManage'));
    }

    public function saveAttendance(Request $request, Course $course, Lesson $lesson)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        abort_if($lesson->section?->course_id !== $course->id, 403);

        $data = $request->validate(['attendance' => 'sometimes|array', 'attendance.*' => 'in:present,absent,late']);
        $attendance = $data['attendance'] ?? [];
        $enrolledUserIds = $course->enrollments()->pluck('user_id');
        abort_if(collect(array_keys($attendance))->map(fn ($id) => (int) $id)->diff($enrolledUserIds)->isNotEmpty(), 422,
            'Attendance can only be saved for enrolled learners.');
        foreach ($attendance as $userId => $status) {
            BatchAttendance::updateOrCreate(
                ['lesson_id' => $lesson->id, 'user_id' => $userId],
                ['course_id' => $course->id, 'vendor_id' => $vendor->id, 'status' => $status, 'marked_by' => auth()->id()]
            );
        }
        return back()->with('success', 'Attendance saved ✅');
    }

    public function revokeStudent(Course $course, User $user)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $course->allowedUsers()->detach($user->id);
        Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->delete();

        return back()->with('success', $user->name . ' removed.');
    }

    public function preAllow(Request $request, Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $request->validate(['contacts' => 'required|string', 'contact_type' => 'required|in:email,phone']);

        $contacts = array_filter(array_map('trim', preg_split('/[\s,;\n]+/', $request->contacts)));
        foreach ($contacts as $contact) {
            VendorPreallowedContact::firstOrCreate(
                ['vendor_id' => $vendor->id, 'contact' => $contact],
                ['contact_type' => $request->contact_type, 'added_by' => auth()->id()]
            );
            $existing = $request->contact_type === 'email'
                ? User::where('email', $contact)->first()
                : User::where('phone', $contact)->first();
            if ($existing) {
                $course->allowedUsers()->syncWithoutDetaching([$existing->id => ['added_by' => auth()->id()]]);
                Enrollment::firstOrCreate(
                    ['user_id' => $existing->id, 'course_id' => $course->id],
                    ['vendor_id' => $vendor->id, 'amount_paid' => 0, 'payment_method' => 'vendor_access']
                );
                DB::table('student_vendor_access')->updateOrInsert(
                    ['user_id' => $existing->id, 'vendor_id' => $vendor->id],
                    ['granted_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        return back()->with('success', count($contacts) . ' contact(s) pre-allowed ✅');
    }

    public function report(Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);

        $enrolled = $course->enrollments()->with('user')->get();
        $lessons  = Lesson::where('type','live')
            ->whereHas('section', fn($q) => $q->where('course_id', $course->id))
            ->orderBy('live_scheduled_at')
            ->get();

        $report = $enrolled->map(function($enrollment) use ($lessons, $course) {
            $attended = BatchAttendance::where('course_id', $course->id)->where('user_id', $enrollment->user_id)->where('status','present')->count();
            $late     = BatchAttendance::where('course_id', $course->id)->where('user_id', $enrollment->user_id)->where('status','late')->count();
            $total    = $lessons->count();
            return [
                'user'     => $enrollment->user,
                'attended' => $attended,
                'late'     => $late,
                'total'    => $total,
                'pct'      => $total > 0 ? round((($attended + $late) / $total) * 100) : 0,
                'progress' => $enrollment->progress,
            ];
        });

        return view('vendor.batches.report', compact('vendor','course','report','lessons'));
    }

    public function issueCertificate(Request $request, Course $course, Enrollment $enrollment)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        abort_if($enrollment->course_id !== $course->id, 403);

        if (Certificate::where('course_id', $course->id)->where('user_id', $enrollment->user_id)->exists()) {
            return back()->with('error', 'Certificate already issued.');
        }

        Certificate::create([
            'user_id'            => $enrollment->user_id,
            'course_id'          => $course->id,
            'vendor_id'          => $vendor->id,
            'enrollment_id'      => $enrollment->id,
            'certificate_number' => strtoupper(Str::random(12)),
            'issued_at'          => now(),
        ]);

        $enrollment->update(['status' => 'completed', 'completed_at' => now(), 'progress' => 100]);
        return back()->with('success', "Certificate issued to {$enrollment->user->name} ✅");
    }

    public function bulkAllow(Request $request, Course $course)
    {
        $vendor = $this->vendor();
        abort_if($course->vendor_id !== $vendor->id, 403);
        $request->validate(['emails' => 'required|string']);

        $emails   = array_filter(array_map('trim', preg_split('/[\s,;\n]+/', $request->emails)));
        $added    = 0; $notFound = [];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if (!$user) { $notFound[] = $email; continue; }
            $course->allowedUsers()->syncWithoutDetaching([$user->id => ['added_by' => auth()->id()]]);
            Enrollment::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['vendor_id' => $vendor->id, 'amount_paid' => 0, 'payment_method' => 'vendor_access']
            );
            $added++;
        }

        $msg = "{$added} student(s) added ✅";
        if ($notFound) $msg .= " | Not found: " . implode(', ', $notFound);
        return back()->with('success', $msg);
    }
}
