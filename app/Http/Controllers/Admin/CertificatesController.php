<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CertificatesController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::with(['user','course'])->latest();

        if ($s = $request->search) {
            $query->where(function($q) use ($s) {
                $q->where('certificate_number','like',"%{$s}%")
                  ->orWhereHas('user',   fn($q) => $q->where('name','like',"%{$s}%")->orWhere('email','like',"%{$s}%"))
                  ->orWhereHas('course', fn($q) => $q->where('title','like',"%{$s}%"));
            });
        }
        if ($request->course_id) $query->where('course_id',$request->course_id);

        $certificates = $query->paginate(20);
        $courses      = Course::where('is_published',1)->orderBy('title')->get(['id','title']);
        $stats = [
            'total'       => Certificate::count(),
            'this_month'  => Certificate::whereMonth('issued_at', now()->month)->count(),
            'courses'     => Certificate::distinct('course_id')->count(),
        ];

        return view('admin.certificates.index', compact('certificates','courses','stats'));
    }

    public function issue(Request $request)
    {
        $request->validate([
            'user_id'   => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        $exists = Certificate::where('user_id',$request->user_id)
            ->where('course_id',$request->course_id)->exists();

        if ($exists) {
            return back()->with('error','Certificate already issued for this student and course.');
        }

        Certificate::create([
            'user_id'            => $request->user_id,
            'course_id'          => $request->course_id,
            'certificate_number' => 'ITF-'.strtoupper(Str::random(8)).'-'.now()->format('Y'),
            'issued_at'          => now(),
        ]);

        return back()->with('success','Certificate issued successfully ✅');
    }

    public function revoke(Certificate $certificate)
    {
        $name = $certificate->user->name ?? 'Student';
        $certificate->delete();
        return back()->with('success',"Certificate for \"{$name}\" revoked.");
    }
}
