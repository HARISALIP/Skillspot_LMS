<?php
namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;

class LandingController extends Controller
{
    public function index()
    {
        // Featured courses for homepage
        $featuredCourses = Course::where('is_published', 1)
            ->where('visibility','public')
            ->where('is_featured', 1)
            ->withCount('enrollments')
            ->latest()
            ->take(6)
            ->get();

        // If less than 6 featured, fill with other published
        if ($featuredCourses->count() < 6) {
            $more = Course::where('is_published', 1)
                ->where('visibility','public')
                ->where('is_featured', 0)
                ->withCount('enrollments')
                ->latest()
                ->take(6 - $featuredCourses->count())
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
