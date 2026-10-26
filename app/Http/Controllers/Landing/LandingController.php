<?php
namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Setting;

class LandingController extends Controller
{
    public function index()
    {
        if (!auth()->check() && Setting::get('allow_guest_access', '1') !== '1') {
            return redirect()->route('login')->with('error', 'Sign in is required while guest access is disabled.');
        }

        // Featured courses for homepage
        $featuredCourses = Course::where('is_published', 1)
            ->where('visibility','public')
            ->where('is_featured', 1)
            ->with(['sections.lessons', 'primaryInstructor', 'vendor'])
            ->withCount('enrollments')
            ->latest()
            ->take(8)
            ->get();

        // If less than 8 featured, fill with other published
        if ($featuredCourses->count() < 8) {
            $more = Course::where('is_published', 1)
                ->where('visibility','public')
                ->where('is_featured', 0)
                ->with(['sections.lessons', 'primaryInstructor', 'vendor'])
                ->withCount('enrollments')
                ->latest()
                ->take(8 - $featuredCourses->count())
                ->get();
            $featuredCourses = $featuredCourses->merge($more);
        }

        $stats = [
            'courses'  => Course::where('is_published', 1)->where('visibility','public')->count(),
            'students' => \DB::table('enrollments')->distinct('user_id')->count(),
        ];

        return view('landing.index', compact('featuredCourses', 'stats'));
    }
}
