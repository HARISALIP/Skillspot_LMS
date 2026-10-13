<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use Illuminate\Http\Request;

class CertificatesController extends Controller
{
    public function index(Request $request)
    {
        $this->admin();
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
        $courses      = Course::orderBy('title')->get(['id','title']);
        $stats = [
            'total'       => Certificate::count(),
            'this_month'  => Certificate::whereMonth('issued_at', now()->month)->count(),
            'courses'     => Certificate::distinct('course_id')->count(),
        ];

        return view('admin.certificates.index', compact('certificates','courses','stats'));
    }

    public function verify(Certificate $certificate)
    {
        $this->admin();
        $certificate->load(['user', 'course', 'revoker']);
        return view('admin.certificates.verify', compact('certificate'));
    }

    public function revoke(Request $request, Certificate $certificate)
    {
        $this->admin();
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        abort_if($certificate->revoked_at, 422, 'Certificate is already revoked.');
        $certificate->update(['revoked_at' => now(), 'revoked_by' => $request->user()->id, 'revocation_reason' => $data['reason'] ?? null]);
        \DB::table('certificate_audits')->insert(['certificate_id' => $certificate->id, 'actor_id' => $request->user()->id, 'action' => 'revoked', 'note' => $data['reason'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('success','Certificate revoked and retained in the audit trail.');
    }

    private function admin(): void
    {
        abort_unless(auth()->user()->hasRole(['admin', 'super-admin']), 403);
    }
}
