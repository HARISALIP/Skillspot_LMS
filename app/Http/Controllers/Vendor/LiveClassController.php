<?php
namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\LiveClass;
use App\Models\LiveClassSession;
use App\Models\LiveClassEnrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LiveClassController extends Controller
{
    use ResolvesVendor;

    /** Vendors current user can manage live classes for */
    protected function myVendors()
    {
        $user = auth()->user();
        if ($user->hasRole(['super-admin','admin'])) {
            return Vendor::where('status','active')->orderBy('brand_name')->get();
        }
        if ($user->hasRole('teacher')) {
            return Vendor::whereHas('teachers', fn($q) => $q->where('user_id', $user->id))
                ->where('status','active')->orderBy('brand_name')->get();
        }
        return Vendor::whereHas('managers', fn($q) => $q->where('user_id', $user->id))
            ->where('status','active')->orderBy('brand_name')->get();
    }

    // ── Index ──────────────────────────────────────────────────────────
    public function index()
    {
        $vendor = $this->vendor();

        $classes = LiveClass::where('vendor_id', $vendor->id)
            ->whereNotIn('status', ['completed','cancelled'])
            ->withCount('enrollments')
            ->with(['sessions' => fn($q) => $q->where('status','live')])
            ->orderByDesc('created_at')
            ->get();

        return view('vendor.live_classes.index', compact('vendor', 'classes'));
    }

    // ── Create form ────────────────────────────────────────────────────
    public function create()
    {
        $vendor    = $this->vendor();
        $myVendors = $this->myVendors();
        return view('vendor.live_classes.create', compact('vendor', 'myVendors'));
    }

    // ── Store ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $myVendors = $this->myVendors();

        $request->validate([
            'vendor_id'       => 'nullable|integer|in:' . ($myVendors->pluck('id')->implode(',') ?: '0'),
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'schedule_type'   => 'required|in:hours_based,date_based',
            'platform'        => 'required|in:zoom,google_meet,teams,other',
            'meeting_url'     => 'nullable|url|max:500',
            'meeting_id'      => 'nullable|string|max:100',
            'meeting_password'=> 'nullable|string|max:100',
            'max_students'    => 'nullable|integer|min:0',
            'scheduled_at'    => 'nullable|date|after:now',
            // hours_based
            'total_hours'     => 'required_if:schedule_type,hours_based|nullable|numeric|min:0.5',
            // date_based
            'batch_name'       => 'required_if:schedule_type,date_based|nullable|string|max:255',
            'batch_start_date' => 'required_if:schedule_type,date_based|nullable|date',
            'batch_end_date'   => 'required_if:schedule_type,date_based|nullable|date|after_or_equal:batch_start_date',
        ]);

        // Resolve target vendor
        $targetVendor = $request->filled('vendor_id')
            ? $myVendors->firstWhere('id', (int)$request->vendor_id)
            : ($myVendors->first() ?? $this->vendor());

        if (!$targetVendor) {
            return back()->withErrors(['vendor_id' => 'No vendor selected or accessible.'])->withInput();
        }

        // Generate unique slug
        $base = Str::slug($request->title);
        $slug = $base;
        $i    = 1;
        while (LiveClass::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        LiveClass::create([
            'vendor_id'        => $targetVendor->id,
            'created_by'       => auth()->id(),
            'title'            => $request->title,
            'slug'             => $slug,
            'description'      => $request->description,
            'schedule_type'    => $request->schedule_type,
            'platform'         => $request->platform,
            'meeting_url'      => $request->meeting_url,
            'meeting_id'       => $request->meeting_id,
            'meeting_password' => $request->meeting_password,
            'max_students'     => $request->max_students ?? 0,
            'scheduled_at'     => $request->scheduled_at,
            'total_hours'      => $request->schedule_type === 'hours_based' ? $request->total_hours : null,
            'batch_name'       => $request->schedule_type === 'date_based' ? $request->batch_name : null,
            'batch_start_date' => $request->schedule_type === 'date_based' ? $request->batch_start_date : null,
            'batch_end_date'   => $request->schedule_type === 'date_based' ? $request->batch_end_date : null,
            'status'           => 'active',
        ]);

        // Switch active vendor to the target if different
        session(['active_vendor_id' => $targetVendor->id]);

        return redirect()->route('vendor.live_classes.index')
            ->with('success', 'Live class "' . $request->title . '" created under ' . $targetVendor->brand_name . ' ✅');
    }

    // ── Show / Manage ──────────────────────────────────────────────────
    public function show(LiveClass $liveClass)
    {
        $vendor = $this->vendor();
        // Allow access if user manages this class's vendor
        $myVendors = $this->myVendors();
        abort_unless($myVendors->contains('id', $liveClass->vendor_id), 403);

        $liveClass->load(['sessions.teacher', 'enrollments.user']);
        $activeSession = $liveClass->activeSession();

        // Students of this vendor for enrollment
        $vendorStudents = User::whereHas('vendorAccess', fn($q) => $q->where('vendor_id', $liveClass->vendor_id))
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['admin','super-admin','vendor']))
            ->orderBy('name')
            ->get(['id','name','email','phone']);

        $enrolledIds = $liveClass->enrollments->pluck('user_id')->toArray();

        return view('vendor.live_classes.show', compact(
            'vendor', 'liveClass', 'activeSession', 'vendorStudents', 'enrolledIds'
        ));
    }

    // ── Start session ──────────────────────────────────────────────────
    public function start(Request $request, LiveClass $liveClass)
    {
        $myVendors = $this->myVendors();
        abort_unless($myVendors->contains('id', $liveClass->vendor_id), 403);

        if ($liveClass->isLive()) {
            return back()->with('error', 'Class is already live.');
        }
        if (in_array($liveClass->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'This class is ' . $liveClass->status . ' and cannot be started.');
        }

        $request->validate([
            'module_name'  => 'nullable|string|max:255',
            'session_name' => 'nullable|string|max:255',
        ]);

        LiveClassSession::create([
            'live_class_id' => $liveClass->id,
            'teacher_id'    => auth()->id(),
            'module_name'   => $request->module_name,
            'session_name'  => $request->session_name,
            'started_at'    => now(),
            'status'        => 'live',
        ]);
        $liveClass->update(['status' => 'live']);

        return back()->with('success', ($request->session_name ?: 'Session') . ' started! Students can join now.');
    }

    // ── Stop session ───────────────────────────────────────────────────
    public function stop(LiveClass $liveClass)
    {
        $myVendors = $this->myVendors();
        abort_unless($myVendors->contains('id', $liveClass->vendor_id), 403);

        $session = $liveClass->activeSession();
        if (!$session) {
            return back()->with('error', 'No active session found.');
        }

        $stoppedAt       = now();
        $durationMinutes = (int) $session->started_at->diffInMinutes($stoppedAt);
        $durationHours   = round($durationMinutes / 60, 2);

        $session->update([
            'stopped_at'       => $stoppedAt,
            'duration_minutes' => $durationMinutes,
            'duration_hours'   => $durationHours,
            'status'           => 'ended',
        ]);

        $liveClass->update(['status' => 'active']);
        $liveClass->recalculateHours();
        $liveClass->refresh();

        $msg = "⏹ Class stopped. Session: {$durationHours}h ({$durationMinutes} min). Total: {$liveClass->completed_hours}h";
        if ($liveClass->status === 'completed') {
            $msg .= ' 🎉 Total hours reached — class marked complete!';
        }
        return back()->with('success', $msg);
    }

    // ── Enroll student ─────────────────────────────────────────────────
    public function enroll(Request $request, LiveClass $liveClass)
    {
        $myVendors = $this->myVendors();
        abort_unless($myVendors->contains('id', $liveClass->vendor_id), 403);

        $request->validate(['user_id' => 'required|exists:users,id']);

        if ($liveClass->max_students > 0 && $liveClass->enrollments()->count() >= $liveClass->max_students) {
            return back()->with('error', 'Max students limit reached.');
        }

        LiveClassEnrollment::firstOrCreate(
            ['live_class_id' => $liveClass->id, 'user_id' => $request->user_id],
            ['enrolled_by' => auth()->id(), 'status' => 'active']
        );
        return back()->with('success', 'Student enrolled ✅');
    }

    // ── Unenroll student ───────────────────────────────────────────────
    public function unenroll(LiveClass $liveClass, User $user)
    {
        $myVendors = $this->myVendors();
        abort_unless($myVendors->contains('id', $liveClass->vendor_id), 403);

        LiveClassEnrollment::where('live_class_id', $liveClass->id)
            ->where('user_id', $user->id)
            ->delete();
        return back()->with('success', 'Student removed.');
    }

    // ── Delete ─────────────────────────────────────────────────────────
    public function destroy(LiveClass $liveClass)
    {
        $myVendors = $this->myVendors();
        abort_unless($myVendors->contains('id', $liveClass->vendor_id), 403);

        if ($liveClass->isLive()) {
            return back()->with('error', 'Cannot delete a live class in progress. Stop it first.');
        }
        $liveClass->delete();
        return redirect()->route('vendor.live_classes.index')->with('success', 'Live class deleted.');
    }

    // ── Upload recording / notes after session ─────────────────────────
    public function uploadFile(Request $request, LiveClass $liveClass, LiveClassSession $session)
    {
        $myVendors = $this->myVendors();
        abort_unless($myVendors->contains('id', $liveClass->vendor_id), 403);
        abort_if($session->live_class_id !== $liveClass->id, 403);

        $request->validate([
            'file_type' => 'required|in:recording,notes,resource',
            'title'     => 'nullable|string|max:255',
            'file'      => 'required|file|max:' . ((int) \App\Models\Setting::get('max_upload_mb', 2048) * 1024) . '|mimes:mp4,mkv,webm,avi,mov,pdf,ppt,pptx,doc,docx,xls,xlsx,txt,zip',
        ]);

        $file     = $request->file('file');
        $type     = $request->file_type;
        $folder   = "live-classes/{$liveClass->id}/sessions/{$session->id}/{$type}";
        $filename = now()->format('Ymd_His') . '_' . \Str::slug($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
        $path     = $folder . '/' . $filename;

        $stream = fopen($file->getRealPath(), 'rb');
        \Illuminate\Support\Facades\Storage::disk('r2')->writeStream($path, $stream);
        if (is_resource($stream)) fclose($stream);

        \App\Models\LiveClassSessionFile::create([
            'live_class_id'         => $liveClass->id,
            'live_class_session_id' => $session->id,
            'uploaded_by'           => auth()->id(),
            'file_type'             => $type,
            'title'                 => $request->title ?: $file->getClientOriginalName(),
            'disk'                  => 'r2',
            'path'                  => $path,
            'mime_type'             => $file->getMimeType(),
            'file_size'             => $file->getSize(),
            'is_visible'            => true,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => ucfirst($type) . ' uploaded successfully']);
        }
        return back()->with('success', ucfirst($type) . ' uploaded successfully ✅');
    }

    // ── Toggle file visibility ─────────────────────────────────────────
    public function toggleFile(LiveClass $liveClass, \App\Models\LiveClassSessionFile $file)
    {
        $myVendors = $this->myVendors();
        abort_unless($myVendors->contains('id', $liveClass->vendor_id), 403);
        $file->update(['is_visible' => !$file->is_visible]);
        return back()->with('success', $file->is_visible ? 'File visible to students ✅' : 'File hidden from students.');
    }

    // ── Delete file ────────────────────────────────────────────────────
    public function deleteFile(LiveClass $liveClass, \App\Models\LiveClassSessionFile $file)
    {
        $myVendors = $this->myVendors();
        abort_unless($myVendors->contains('id', $liveClass->vendor_id), 403);
        \Illuminate\Support\Facades\Storage::disk($file->disk)->delete($file->path);
        $file->delete();
        return back()->with('success', 'File deleted.');
    }

    // ── Secure view / stream ───────────────────────────────────────────
    public function viewFile(LiveClass $liveClass, \App\Models\LiveClassSessionFile $file)
    {
        $vendor = $this->vendor();
        abort_unless($this->myVendors()->contains('id', $liveClass->vendor_id), 403);

        $signedUrl = \Illuminate\Support\Facades\Storage::disk($file->disk)
            ->temporaryUrl($file->path, now()->addHours(2));

        return view('vendor.live_classes.file_viewer', compact('vendor', 'liveClass', 'file', 'signedUrl'));
    }

    // ── My Courses (completed) ─────────────────────────────────────────
    public function myCourses()
    {
        $vendor = $this->vendor();

        $completed = LiveClass::where('vendor_id', $vendor->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->withCount('enrollments')
            ->with(['sessions' => fn($q) => $q->where('status','ended')->orderBy('started_at'), 'sessions.files'])
            ->orderByDesc('updated_at')
            ->get();

        $active = LiveClass::where('vendor_id', $vendor->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->withCount('enrollments')
            ->with(['sessions' => fn($q) => $q->where('status','ended')->orderBy('started_at')])
            ->orderByDesc('created_at')
            ->get();

        return view('vendor.live_classes.my_courses', compact('vendor', 'completed', 'active'));
    }
}
