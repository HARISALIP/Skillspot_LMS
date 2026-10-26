<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\MarketplaceService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('marketplace:release-earnings', function () {
    $released = MarketplaceService::releaseAvailableEarnings();
    $this->info("Released {$released} marketplace earnings.");
})->purpose('Move marketplace earnings past the seven-day refund hold to available.');

Artisan::command('courses:delete-all {--force : Force the operation to run without confirmation}', function () {
    if (!$this->option('force') && !$this->confirm('Are you sure you want to delete ALL courses and their lessons/quizzes? This cannot be undone!')) {
        $this->warn('Operation cancelled.');
        return;
    }

    $count = \App\Models\Course::count();
    $driver = \Illuminate\Support\Facades\DB::getDriverName();
    if ($driver === 'mysql') {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    } elseif ($driver === 'sqlite') {
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF;');
    }

    $tables = [
        'batch_attendance',
        'batches',
        'certificates',
        'certificate_audits',
        'course_allowed_users',
        'course_announcements',
        'course_change_audits',
        'course_reviews',
        'course_teachers',
        'enrollments',
        'lesson_progress',
        'quiz_attempts',
        'quiz_questions',
        'quizzes',
        'assignment_submissions',
        'assignments',
        'lessons',
        'sections',
        'payment_orders',
        'marketplace_sales',
        'reviews',
        'courses',
    ];

    foreach ($tables as $tbl) {
        if (\Illuminate\Support\Facades\Schema::hasTable($tbl)) {
            \Illuminate\Support\Facades\DB::table($tbl)->truncate();
        }
    }

    if ($driver === 'mysql') {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    } elseif ($driver === 'sqlite') {
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON;');
    }

    \Illuminate\Support\Facades\Artisan::call('optimize:clear');

    $this->info("Successfully deleted {$count} courses and all associated content.");
})->purpose('Permanently delete all courses, lessons, quizzes, and course enrollments');

Schedule::command('marketplace:release-earnings')->everyMinute()->withoutOverlapping();

