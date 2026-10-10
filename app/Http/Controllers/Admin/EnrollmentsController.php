<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class EnrollmentsController extends Controller
{
    // ── List all enrollments ───────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Enrollment::with(['user','course'])
            ->latest();

        $hasAccessType = Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'access_type');
        $hasExpiresAt = Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'expires_at');

        if ($s = $request->search) {
            $query->whereHas('user', fn($q) =>
                $q->where('name','like',"%{$s}%")
                  ->orWhere('email','like',"%{$s}%")
            )->orWhereHas('course', fn($q) =>
                $q->where('title','like',"%{$s}%")
            );
        }
        if ($request->course_id)    $query->where('course_id',   $request->course_id);
        if ($request->status)       $query->where('status',      $request->status);
        if ($request->access_type && $hasAccessType)  $query->where('access_type', $request->access_type);
        if ($request->expiry === 'expired' && $hasExpiresAt)  $query->where('expires_at','<', now());
        if ($request->expiry === 'expiring' && $hasExpiresAt) $query->whereBetween('expires_at',[now(), now()->addDays(7)]);

        $enrollments = $query->paginate(20);
        $courses     = Course::where('is_published',1)->orderBy('title')->get(['id','title']);
        $stats = [
            'total'    => Enrollment::count(),
            'active'   => Enrollment::where('status','active')->count(),
            'expired'  => $hasExpiresAt ? Enrollment::where('expires_at','<',now())->whereNotNull('expires_at')->count() : 0,
            'expiring' => $hasExpiresAt ? Enrollment::whereBetween('expires_at',[now(),now()->addDays(7)])->count() : 0,
        ];

        return view('admin.enrollments.index', compact('enrollments','courses','stats'));
    }

    // ── Enroll student form ────────────────────────────────────────────
    public function create(Request $request)
    {
        $courses  = Course::where('is_published',1)->orderBy('title')->get(['id','title','category','level']);
        $preUser  = $request->user_id  ? User::find($request->user_id)  : null;
        $preCourse= $request->course_id? Course::find($request->course_id): null;
        return view('admin.enrollments.create', compact('courses','preUser','preCourse'));
    }

    // ── Grant enrollment (manual) ──────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'user_ids'    => ['required','array','min:1'],
            'user_ids.*'  => ['exists:users,id'],
            'course_id'   => ['required','exists:courses,id'],
            'access_type' => ['required','in:lifetime,limited'],
            'expires_at'  => ['required_if:access_type,limited','nullable','date','after:today'],
            'notes'       => ['nullable','string','max:500'],
        ]);

        $count  = 0;
        $skip   = 0;
        $course = Course::findOrFail($request->course_id);

        foreach ($request->user_ids as $userId) {
            $exists = Enrollment::where('user_id',$userId)
                ->where('course_id',$request->course_id)->exists();
            if ($exists) { $skip++; continue; }

            Enrollment::create([
                'user_id'     => $userId,
                'course_id'   => $request->course_id,
                'vendor_id'   => null,
                'amount_paid' => 0,
                'payment_method'=> 'manual',
                'status'      => 'active',
                'access_type' => $request->access_type,
                'expires_at'  => $request->access_type === 'limited'
                    ? Carbon::parse($request->expires_at)->endOfDay()
                    : null,
                'granted_by'  => Auth::id(),
                'notes'       => $request->notes,
                'progress'    => 0,
            ]);
            $count++;
        }

        $msg = "{$count} student(s) enrolled in \"{$course->title}\"";
        if ($skip) $msg .= " ({$skip} already enrolled, skipped)";

        return redirect()->route('admin.enrollments')
            ->with('success', $msg.' ✅');
    }

    // ── Edit enrollment ────────────────────────────────────────────────
    public function edit(Enrollment $enrollment)
    {
        $enrollment->load(['user','course']);
        return view('admin.enrollments.edit', compact('enrollment'));
    }

    // ── Update enrollment ──────────────────────────────────────────────
    public function update(Request $request, Enrollment $enrollment)
    {
        $request->validate([
            'access_type' => ['required','in:lifetime,limited'],
            'expires_at'  => ['required_if:access_type,limited','nullable','date'],
            'status'      => ['required','in:active,completed,refunded'],
            'notes'       => ['nullable','string','max:500'],
        ]);

        $enrollment->update([
            'access_type' => $request->access_type,
            'expires_at'  => $request->access_type === 'limited'
                ? Carbon::parse($request->expires_at)->endOfDay()
                : null,
            'status'      => $request->status,
            'notes'       => $request->notes,
        ]);

        return back()->with('success','Enrollment updated ✅');
    }

    // ── Revoke enrollment ──────────────────────────────────────────────
    public function destroy(Enrollment $enrollment)
    {
        $name   = $enrollment->user->name;
        $course = $enrollment->course->title;
        $enrollment->delete();
        return back()->with('success',"\"{$name}\" removed from \"{$course}\".");
    }

    // ── Extend expiry ──────────────────────────────────────────────────
    public function extend(Request $request, Enrollment $enrollment)
    {
        $request->validate(['days'=>'required|integer|min:1|max:1825']);
        $current = $enrollment->expires_at ?? now();
        $enrollment->update([
            'expires_at'  => $current->addDays($request->days),
            'access_type' => 'limited',
            'status'      => 'active',
        ]);
        return back()->with('success',"Access extended by {$request->days} days ✅");
    }

    // ── Bulk enroll from CSV / user search ────────────────────────────
    public function searchUsers(Request $request)
    {
        $q = $request->q;
        $users = User::where(function($query) use ($q) {
            $query->where('name','like',"%{$q}%")
                  ->orWhere('email','like',"%{$q}%")
                  ->orWhere('phone','like',"%{$q}%");
        })->take(10)->get(['id','name','email','phone','country']);

        return response()->json($users->map(fn($u) => [
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
        ]));
    }

    // ── Check expired (run via scheduler) ────────────────────────────
    public function checkExpired()
    {
        $expired = Enrollment::where('access_type','limited')
            ->where('expires_at','<',now())
            ->where('status','active')
            ->update(['status'=>'refunded']); // use 'refunded' to block access
        return response()->json(['expired_count'=>$expired]);
    }
}
