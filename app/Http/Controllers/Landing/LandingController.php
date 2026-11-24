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

        // Load promotional course posters and seasonal camp flyers from public/uploads
        $uploadsDir = public_path('uploads');
        $posters = [];
        if (is_dir($uploadsDir)) {
            $files = glob($uploadsDir . DIRECTORY_SEPARATOR . '*.png');
            foreach ($files as $filePath) {
                $filename = basename($filePath);
                if (str_contains($filename, '(1)')) continue; // Skip duplicate copy

                $cleanTitle = preg_replace('/\.png$/i', '', $filename);
                $cleanTitle = preg_replace('/^(Skillspot|Winter|Colorful|Festive)\s+/i', '', $cleanTitle);
                $cleanTitle = preg_replace('/\s+(Poster|Ad|Advertisement)$/i', '', $cleanTitle);

                $category = 'Special Camp';
                if (preg_match('/(Python|Coding|Programming|Tech Camp)/i', $filename)) {
                    $category = 'Coding & CS';
                } elseif (preg_match('/(AI|Robotics)/i', $filename)) {
                    $category = 'AI & Robotics';
                } elseif (preg_match('/(Drawing|Painting|Poster Making)/i', $filename)) {
                    $category = 'Creative Arts';
                } elseif (preg_match('/(Maths|English|Yoga|Internship)/i', $filename)) {
                    $category = 'Skills & Career';
                }

                $posters[] = [
                    'filename' => $filename,
                    'title'    => trim($cleanTitle),
                    'category' => $category,
                    'url'      => asset('uploads/' . $filename),
                ];
            }
        }

        return view('landing.index', compact('featuredCourses', 'stats', 'posters'));
    }
}
