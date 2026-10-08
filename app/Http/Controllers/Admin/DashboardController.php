<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Certificate;
use App\Models\License;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users'            => User::count(),
            'admins'           => User::role('admin')->count(),
            'courses'          => Course::count(),
            'published_courses'=> Course::where('is_published', 1)->count(),
            'enrollments'      => Enrollment::count(),
            'certs'            => Certificate::count(),
            'licenses'         => License::where('status', 'active')->count(),
            'reviews'          => DB::table('reviews')->count(),
            'revenue'          => Enrollment::sum('amount_paid'),
        ];

        $recentUsers   = User::with('roles')->latest()->take(8)->get();
        $recentCourses = Course::with('sections')->latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentCourses'));
    }
}
