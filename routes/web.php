<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Landing\LandingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController  as AdminDash;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\CoursesController;
use App\Http\Controllers\Admin\CourseOptionsController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\CertificatesController;
use App\Http\Controllers\Admin\LiveController;
use App\Http\Controllers\Admin\BatchController as AdminBatch;
use App\Http\Controllers\Admin\EnrollmentsController;
use App\Http\Controllers\Student\DashboardController as StudentDash;
use App\Http\Controllers\Student\CourseController as StudentCourse;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\SecureVideoController;

// ── Landing ────────────────────────────────────────────────────────────────
Route::get('/', [LandingController::class, 'index'])->name('home');

// ── Secure video streaming (auth required)
Route::middleware(['auth','portal.access'])->group(function () {
    Route::get('/watch/lesson/{lesson}/stream',   [SecureVideoController::class, 'stream'])->name('video.stream');
    Route::post('/watch/lesson/{lesson}/progress',[SecureVideoController::class, 'progress'])->name('video.progress');
});


// ── Student Course routes ──────────────────────────────────────────────────
Route::middleware(['auth','portal.access'])->group(function () {
    Route::get('/course/{slug}',                             [StudentCourse::class,'detail'])->name('student.course-detail');
    Route::post('/course/{course}/enroll-free',             [StudentCourse::class,'enrollFree'])->name('student.enroll-free');
    Route::post('/course/{course}/order',                   [StudentCourse::class,'createOrder'])->name('student.create-order');
    Route::post('/course/{course}/verify-payment',          [StudentCourse::class,'verifyPayment'])->name('student.verify-payment');
    Route::get('/learn/{course}',                           [StudentCourse::class,'learn'])->name('student.learn');
    Route::get('/learn/{course}/lesson/{lesson}',           [StudentCourse::class,'getLesson'])->name('student.get-lesson');
    Route::post('/learn/{course}/lesson/{lesson}/progress', [StudentCourse::class,'saveProgress'])->name('student.save-progress');
});
// ── Razorpay Webhook (NO csrf — verified via signature) ────────────────────
Route::post('/webhook/razorpay', [PaymentController::class, 'webhook'])
     ->name('webhook.razorpay')
     ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// ── Auth (guest only) ──────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [AuthController::class, 'login']);
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// ── OTP verification (guest)
Route::middleware('guest')->group(function () {
    Route::post('/register/verify-otp', [\App\Http\Controllers\Auth\AuthController::class, 'verifyOtp'])->name('register.verify-otp');
    Route::get('/register/resend-otp',  [\App\Http\Controllers\Auth\AuthController::class, 'resendOtp'])->name('register.resend-otp');
});

// ── Logout ─────────────────────────────────────────────────────────────────
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Payment (authenticated) ────────────────────────────────────────────────
Route::middleware('auth')->prefix('payment')->name('payment.')->group(function () {
    Route::post('/order',  [PaymentController::class, 'createOrder'])->name('order');
    Route::post('/verify', [PaymentController::class, 'verify'])->name('verify');
});

// ── Admin area ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth','teacher.only'])->group(function () {
    Route::get('/',                      [AdminDash::class,       'index'])->name('dashboard');
    Route::get('/settings',              [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/mail/test',   [SettingsController::class, 'testMail'])->name('settings.mail.test');
    Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
    Route::post('/settings/toggle/{key}',  [SettingsController::class, 'toggle'])->name('settings.toggle');
    Route::post('/settings/{group}',     [SettingsController::class, 'save'])->name('settings.save');
    // Courses
    Route::get('/courses',                                            [CoursesController::class,'index'])->name('courses');
    Route::get('/courses/create',                                     [CoursesController::class,'create'])->name('courses.create');
    Route::post('/courses',                                           [CoursesController::class,'store'])->name('courses.store');
    Route::get('/courses/{course}/edit',                              [CoursesController::class,'edit'])->name('courses.edit');
    Route::put('/courses/{course}',                                   [CoursesController::class,'update'])->name('courses.update');
    Route::post('/courses/{course}/grant-access',        [CoursesController::class,'grantAccess'])->name('courses.grant-access');
    Route::delete('/courses/{course}/revoke-access/{user}', [CoursesController::class,'revokeAccess'])->name('courses.revoke-access');
    Route::patch('/courses/{course}/toggle-publish',                  [CoursesController::class,'togglePublish'])->name('courses.toggle-publish');
    Route::delete('/courses/{course}',                                [CoursesController::class,'destroy'])->name('courses.destroy');
    // Sections
    Route::post('/courses/{course}/sections',                         [CoursesController::class,'storeSection'])->name('courses.sections.store');
    Route::put('/courses/{course}/sections/{section}',                [CoursesController::class,'updateSection'])->name('courses.sections.update');
    Route::delete('/courses/{course}/sections/{section}',             [CoursesController::class,'destroySection'])->name('courses.sections.destroy');
    Route::post('/courses/{course}/sections/reorder',                 [CoursesController::class,'reorderSections'])->name('courses.sections.reorder');
    // Lessons
    Route::get('/courses/{course}/sections/{section}/lessons/create', [CoursesController::class,'createLesson'])->name('courses.lessons.create');
    Route::post('/courses/{course}/sections/{section}/lessons',       [CoursesController::class,'storeLesson'])->name('courses.lessons.store');
    Route::get('/courses/{course}/sections/{section}/lessons/{lesson}/edit',[CoursesController::class,'editLesson'])->name('courses.lessons.edit');
    Route::put('/courses/{course}/sections/{section}/lessons/{lesson}',[CoursesController::class,'updateLesson'])->name('courses.lessons.update');
    Route::delete('/courses/{course}/sections/{section}/lessons/{lesson}',[CoursesController::class,'destroyLesson'])->name('courses.lessons.destroy');
    // Live Classes
    Route::get('/live',                [LiveController::class,'index'])->name('live');
    Route::post('/live',               [LiveController::class,'store'])->name('live.store');
    Route::delete('/live/{lesson}',    [LiveController::class,'destroy'])->name('live.destroy');
    Route::get('/live/{lesson}/attendance',  [LiveController::class,'attendance'])->name('live.attendance');
    Route::post('/live/{lesson}/attendance', [LiveController::class,'saveAttendance'])->name('live.attendance.save');
    // Batch management
    Route::get('/batches',                                                       [AdminBatch::class,'index'])->name('batches');
    Route::get('/batches/{course}',                                              [AdminBatch::class,'show'])->name('batches.show');
    Route::post('/batches/{course}/bulk-allow',                                  [AdminBatch::class,'bulkAllow'])->name('batches.bulk-allow');
    Route::delete('/batches/{course}/revoke/{user}',                             [AdminBatch::class,'revokeStudent'])->name('batches.revoke');
    Route::get('/batches/{course}/attendance/{lesson}',                          [AdminBatch::class,'attendance'])->name('batches.attendance');
    Route::post('/batches/{course}/attendance/{lesson}',                         [AdminBatch::class,'saveAttendance'])->name('batches.attendance.save');
    Route::get('/batches/{course}/report',                                       [AdminBatch::class,'report'])->name('batches.report');
    Route::post('/batches/{course}/certificate/{enrollment}',                    [AdminBatch::class,'issueCertificate'])->name('batches.certificate');
    Route::post('/batches/{course}/preallow',                                    [AdminBatch::class,'preAllow'])->name('batches.preallow');
    // Payments
    Route::get('/payments',                    [PaymentsController::class,'index'])->name('payments');
    Route::patch('/payments/{payment}/refund', [PaymentsController::class,'refund'])->name('payments.refund');
    // Certificates
    Route::get('/certificates',                [CertificatesController::class,'index'])->name('certs');
    Route::post('/certificates',               [CertificatesController::class,'issue'])->name('certs.issue');
    Route::delete('/certificates/{certificate}',[CertificatesController::class,'revoke'])->name('certs.revoke');
    // Enrollments
    Route::get('/enrollments',                     [EnrollmentsController::class,'index'])->name('enrollments');
    Route::get('/enrollments/create',              [EnrollmentsController::class,'create'])->name('enrollments.create');
    Route::post('/enrollments',                    [EnrollmentsController::class,'store'])->name('enrollments.store');
    Route::get('/enrollments/{enrollment}/edit',   [EnrollmentsController::class,'edit'])->name('enrollments.edit');
    Route::put('/enrollments/{enrollment}',        [EnrollmentsController::class,'update'])->name('enrollments.update');
    Route::delete('/enrollments/{enrollment}',     [EnrollmentsController::class,'destroy'])->name('enrollments.destroy');
    Route::patch('/enrollments/{enrollment}/extend',[EnrollmentsController::class,'extend'])->name('enrollments.extend');
    Route::get('/enrollments/search-users',        [EnrollmentsController::class,'searchUsers'])->name('enrollments.search-users');
    // Media Manager
    Route::get('/media',              [MediaController::class,'index'])->name('media');
    Route::post('/media/upload',      [MediaController::class,'upload'])->name('media.upload');
    Route::get('/media/list',         [MediaController::class,'list'])->name('media.list');
    Route::get('/media/{upload}/url', [MediaController::class,'url'])->name('media.url');
    Route::delete('/media/{upload}',  [MediaController::class,'destroy'])->name('media.destroy');
    // Course Options (Categories, Levels, Languages)
    Route::get('/courses/categories',                      [CourseOptionsController::class,'categories'])->name('courses.categories');
    Route::post('/courses/categories',                     [CourseOptionsController::class,'storeCategory'])->name('courses.categories.store');
    Route::put('/courses/categories/{category}',           [CourseOptionsController::class,'updateCategory'])->name('courses.categories.update');
    Route::delete('/courses/categories/{category}',        [CourseOptionsController::class,'destroyCategory'])->name('courses.categories.destroy');
    Route::patch('/courses/categories/{category}/toggle',  [CourseOptionsController::class,'toggleCategory'])->name('courses.categories.toggle');
    Route::get('/courses/levels',                          [CourseOptionsController::class,'levels'])->name('courses.levels');
    Route::post('/courses/levels',                         [CourseOptionsController::class,'storeLevel'])->name('courses.levels.store');
    Route::put('/courses/levels/{level}',                  [CourseOptionsController::class,'updateLevel'])->name('courses.levels.update');
    Route::delete('/courses/levels/{level}',               [CourseOptionsController::class,'destroyLevel'])->name('courses.levels.destroy');
    Route::patch('/courses/levels/{level}/toggle',          [CourseOptionsController::class,'toggleLevel'])->name('courses.levels.toggle');
    Route::get('/courses/languages',                       [CourseOptionsController::class,'languages'])->name('courses.languages');
    Route::post('/courses/languages',                      [CourseOptionsController::class,'storeLanguage'])->name('courses.languages.store');
    Route::put('/courses/languages/{language}',            [CourseOptionsController::class,'updateLanguage'])->name('courses.languages.update');
    Route::delete('/courses/languages/{language}',         [CourseOptionsController::class,'destroyLanguage'])->name('courses.languages.destroy');
    Route::patch('/courses/languages/{language}/toggle',   [CourseOptionsController::class,'toggleLanguage'])->name('courses.languages.toggle');
    // Roles & Permissions
    Route::get('/roles',               [RolesController::class, 'index'])->name('roles');
    Route::get('/roles/create',        [RolesController::class, 'create'])->name('roles.create');
    Route::post('/roles',              [RolesController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit',   [RolesController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}',        [RolesController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}',     [RolesController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/seed',         [RolesController::class, 'seedPermissions'])->name('roles.seed');
    // Users
    Route::get('/users',             [UsersController::class, 'index'])->name('users');
    Route::get('/users/create',      [UsersController::class, 'create'])->name('users.create');
    Route::post('/users',            [UsersController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}',      [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}',   [UsersController::class, 'destroy'])->name('users.destroy');
    // Impersonate (super-admin only)
    Route::post('/users/{user}/impersonate',  [UsersController::class, 'impersonate'])->name('users.impersonate');
    Route::get('/users/impersonate/stop',     [UsersController::class, 'stopImpersonate'])->name('users.impersonate.stop');
    Route::get('/users/{user}/access',                    [UsersController::class,'access'])->name('users.access');
    Route::post('/users/{user}/access/add',               [UsersController::class,'addAccess'])->name('users.access.add');
    Route::delete('/users/{user}/access/remove/{vendor}', [UsersController::class,'removeAccess'])->name('users.access.remove');
    Route::post('/users/{user}/access/portal',            [UsersController::class,'setPortal'])->name('users.access.portal');
    // Teacher assignments
    Route::post('/users/{user}/teacher/vendor',                   [UsersController::class,'assignTeacherVendor'])->name('users.teacher.vendor.add');
    Route::delete('/users/{user}/teacher/vendor/{vendor}',        [UsersController::class,'removeTeacherVendor'])->name('users.teacher.vendor.remove');
    Route::post('/users/{user}/teacher/course',                   [UsersController::class,'assignTeacherCourse'])->name('users.teacher.course.add');
    Route::delete('/users/{user}/teacher/course/{courseId}',      [UsersController::class,'removeTeacherCourse'])->name('users.teacher.course.remove');
    // Profile
    Route::get('/profile',             [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile',             [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password',    [ProfileController::class, 'changePassword'])->name('profile.password');
    Route::post('/profile/logout-all', [ProfileController::class, 'logoutAll'])->name('profile.logout-all');
});

// ── Student / Learning area ────────────────────────────────────────────────
// Student vendor portal picker (outside prefix so URL is /portal-select)
Route::middleware('auth')->group(function () {
    Route::get('/portal-select',        [StudentDash::class,'vendorPick'])->name('student.vendor.pick');
    Route::post('/portal-select',       [StudentDash::class,'vendorPickSubmit'])->name('student.vendor.pick.submit');
});

Route::prefix('dashboard')->name('student.')->middleware(['auth','portal.access'])->group(function () {
    Route::get('/',                 [StudentDash::class,'index'])->name('dashboard');
    Route::get('/courses',          [StudentDash::class,'courses'])->name('courses');
    Route::get('/browse',           [StudentDash::class,'browse'])->name('browse');
    Route::get('/certs',            [StudentDash::class,'certificates'])->name('certs');
    Route::get('/profile',          [StudentDash::class,'profile'])->name('profile');
    Route::put('/profile',          [StudentDash::class,'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [StudentDash::class,'updatePassword'])->name('profile.password');
    Route::get('/settings',         [StudentDash::class,'settings'])->name('settings');
    Route::get('/progress',         fn() => view('student.progress'))->name('progress');
    Route::get('/live',             fn() => view('student.live'))->name('live');
    Route::get('/saved',            fn() => view('student.saved'))->name('saved');
});
// ── Public pages ───────────────────────────────────────────────────────────
Route::get('/about',    fn() => view('landing.about'))->name('about');
Route::get('/team',     fn() => view('landing.team'))->name('team');
Route::get('/pricing',  fn() => view('landing.pricing'))->name('pricing');
Route::get('/privacy',  fn() => view('landing.privacy'))->name('privacy');
Route::get('/terms',    fn() => view('landing.terms'))->name('terms');
Route::get('/contact',  fn() => view('landing.contact'))->name('contact');
Route::post('/contact', function(\Illuminate\Http\Request $req) {
    $req->validate(['name'=>'required','email'=>'required|email','message'=>'required|min:10']);
    \Illuminate\Support\Facades\Mail::send([],[],function($msg) use($req) {
        $msg->to(\App\Models\Setting::get('site_email','info@Skillspot.in'))
            ->replyTo($req->email, $req->name)
            ->subject('[Contact] ' . ucfirst($req->subject ?? 'General') . ' — ' . $req->name)
            ->html('<h3>From: '.$req->name.' &lt;'.$req->email.'&gt;</h3><p><strong>Subject:</strong> '.ucfirst($req->subject).'</p><p>'.nl2br(e($req->message)).'</p>');
    });
    return back()->with('contact_success', true)->with('contact_email', $req->email);
})->name('contact.send');

// ── Admin → Vendor Management ──────────────────────────────────────────────
use App\Http\Controllers\Admin\VendorsController as AdminVendors;

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/vendors',                          [AdminVendors::class,'index'])->name('vendors');
    Route::get('/vendors/create',                   [AdminVendors::class,'create'])->name('vendors.create');
    Route::post('/vendors',                         [AdminVendors::class,'store'])->name('vendors.store');
    Route::get('/vendors/{vendor}/edit',            [AdminVendors::class,'edit'])->name('vendors.edit');
    Route::put('/vendors/{vendor}',                 [AdminVendors::class,'update'])->name('vendors.update');
    Route::delete('/vendors/{vendor}',              [AdminVendors::class,'destroy'])->name('vendors.destroy');
    Route::patch('/vendors/{vendor}/toggle-status', [AdminVendors::class,'toggleStatus'])->name('vendors.toggle-status');
    Route::post('/vendors/{vendor}/add-manager',    [AdminVendors::class,'addManager'])->name('vendors.add-manager');
    Route::delete('/vendors/{vendor}/remove-manager/{user}', [AdminVendors::class,'removeManager'])->name('vendors.remove-manager');
});

// ── Vendor Panel (admin panel for vendor users) ────────────────────────────
use App\Http\Controllers\Vendor\DashboardController as VendorDash;
use App\Http\Controllers\Vendor\CoursesController as VendorCourses;
use App\Http\Controllers\Vendor\LiveController as VendorLive;
use App\Http\Controllers\Vendor\LiveClassController as VendorLiveClass;
use App\Http\Controllers\Vendor\ProfileController as VendorProfile;
use App\Http\Controllers\Vendor\BatchController as VendorBatch;

// Vendor picker (outside vendor.only middleware)
Route::middleware(['auth'])->group(function () {
    Route::get('/vendor/pick',  [VendorDash::class,'pick'])->name('vendor.pick');
    Route::post('/vendor/pick', [VendorDash::class,'switchVendor'])->name('vendor.switch');
});

Route::prefix('vendor')->name('vendor.')->middleware(['auth','vendor.only'])->group(function () {
    Route::get('/',                                          [VendorDash::class,'index'])->name('dashboard');
    // Profile / branding
    Route::get('/profile',                                   [VendorProfile::class,'index'])->name('profile');
    Route::put('/profile',                                   [VendorProfile::class,'update'])->name('profile.update');
    Route::get('/my-profile',                                [VendorProfile::class,'myProfile'])->name('my-profile');
    Route::put('/my-profile',                                [VendorProfile::class,'updateMyProfile'])->name('my-profile.update');
    Route::put('/my-profile/password',                       [VendorProfile::class,'changePassword'])->name('my-profile.password');
    // Courses (view — all roles)
    Route::get('/courses',                                   [VendorCourses::class,'index'])->name('courses');
    Route::get('/courses/{course}/students',                 [VendorCourses::class,'students'])->name('courses.students');
    // Live
    Route::get('/live', fn() => redirect()->route('vendor.live_classes.index'))->name('live');
    // Batches
    Route::get('/batches',                                                          [VendorBatch::class,'index'])->name('batches');
    Route::get('/batches/{course}',                                                 [VendorBatch::class,'show'])->name('batches.show');
    Route::get('/batches/{course}/attendance/{lesson}',                             [VendorBatch::class,'attendance'])->name('batches.attendance');
    Route::get('/batches/{course}/report',                                          [VendorBatch::class,'report'])->name('batches.report');
    // Teachers management
    Route::get('/teachers',                                  [VendorDash::class,'teachers'])->name('teachers');
    // Student access management
    Route::get('/students',                                  [VendorDash::class,'students'])->name('students');

    // ── Teacher & Admin write routes ───────────────────────────────────────
    Route::middleware('teacher.only')->group(function () {
        // Course full CRUD
        Route::get('/courses/create',                                                     [VendorCourses::class,'create'])->name('courses.create');
        Route::post('/courses',                                                           [VendorCourses::class,'store'])->name('courses.store');
        Route::get('/courses/{course}/edit',                                              [VendorCourses::class,'edit'])->name('courses.edit');
        Route::put('/courses/{course}',                                                   [VendorCourses::class,'update'])->name('courses.update');
        Route::patch('/courses/{course}/toggle-publish',                                  [VendorCourses::class,'togglePublish'])->name('courses.toggle-publish');
        Route::delete('/courses/{course}',                                                [VendorCourses::class,'destroy'])->name('courses.destroy');
        Route::post('/courses/{course}/grant-access',                                     [VendorCourses::class,'allowStudent'])->name('courses.grant-access');
        Route::delete('/courses/{course}/revoke-access/{user}',                           [VendorCourses::class,'revokeStudent'])->name('courses.revoke-access');
        // Sections
        Route::post('/courses/{course}/sections',                                         [VendorCourses::class,'storeSection'])->name('courses.sections.store');
        Route::put('/courses/{course}/sections/{section}',                                [VendorCourses::class,'updateSection'])->name('courses.sections.update');
        Route::delete('/courses/{course}/sections/{section}',                             [VendorCourses::class,'destroySection'])->name('courses.sections.destroy');
        Route::post('/courses/{course}/sections/reorder',                                 [VendorCourses::class,'reorderSections'])->name('courses.sections.reorder');
        // Lessons
        Route::get('/courses/{course}/sections/{section}/lessons/create',                 [VendorCourses::class,'createLesson'])->name('courses.lessons.create');
        Route::post('/courses/{course}/sections/{section}/lessons',                       [VendorCourses::class,'storeLesson'])->name('courses.lessons.store');
        Route::get('/courses/{course}/sections/{section}/lessons/{lesson}/edit',          [VendorCourses::class,'editLesson'])->name('courses.lessons.edit');
        Route::put('/courses/{course}/sections/{section}/lessons/{lesson}',               [VendorCourses::class,'updateLesson'])->name('courses.lessons.update');
        Route::delete('/courses/{course}/sections/{section}/lessons/{lesson}',            [VendorCourses::class,'destroyLesson'])->name('courses.lessons.destroy');
        // Live Classes
        Route::post('/live',                                                              [VendorLive::class,'store'])->name('live.store');
        Route::delete('/live/{lesson}',                                                   [VendorLive::class,'destroy'])->name('live.destroy');
        Route::get('/live/{lesson}/attendance',                                           [VendorLive::class,'attendance'])->name('live.attendance');
        Route::post('/live/{lesson}/attendance',                                          [VendorLive::class,'saveAttendance'])->name('live.attendance.save');
        // Live Classes (hours-based & date-based)
        Route::get('/live-classes',                                [VendorLiveClass::class,'index'])->name('live_classes.index');
        Route::get('/live-classes/create',                         [VendorLiveClass::class,'create'])->name('live_classes.create');
        Route::post('/live-classes',                               [VendorLiveClass::class,'store'])->name('live_classes.store');
        Route::get('/live-classes/{liveClass}',                    [VendorLiveClass::class,'show'])->name('live_classes.show');
        Route::post('/live-classes/{liveClass}/start',             [VendorLiveClass::class,'start'])->name('live_classes.start');
        Route::post('/live-classes/{liveClass}/stop',              [VendorLiveClass::class,'stop'])->name('live_classes.stop');
        Route::post('/live-classes/{liveClass}/enroll',            [VendorLiveClass::class,'enroll'])->name('live_classes.enroll');
        Route::delete('/live-classes/{liveClass}/unenroll/{user}', [VendorLiveClass::class,'unenroll'])->name('live_classes.unenroll');
        Route::delete('/live-classes/{liveClass}',                 [VendorLiveClass::class,'destroy'])->name('live_classes.destroy');
        Route::get('/my-courses',                                 [VendorLiveClass::class,'myCourses'])->name('live_classes.my_courses');
        // Session files
        Route::post('/live-classes/{liveClass}/sessions/{session}/upload', [VendorLiveClass::class,'uploadFile'])->name('live_classes.file.upload');
        Route::patch('/live-classes/{liveClass}/files/{file}/toggle',      [VendorLiveClass::class,'toggleFile'])->name('live_classes.file.toggle');
        Route::delete('/live-classes/{liveClass}/files/{file}',            [VendorLiveClass::class,'deleteFile'])->name('live_classes.file.delete');
        Route::get('/live-classes/{liveClass}/files/{file}/view',          [VendorLiveClass::class,'viewFile'])->name('live_classes.file.view');
        // Batch writes
        Route::post('/batches/{course}/bulk-allow',                                       [VendorBatch::class,'bulkAllow'])->name('batches.bulk-allow');
        Route::delete('/batches/{course}/revoke/{user}',                                  [VendorBatch::class,'revokeStudent'])->name('batches.revoke');
        Route::post('/batches/{course}/attendance/{lesson}',                              [VendorBatch::class,'saveAttendance'])->name('batches.attendance.save');
        Route::post('/batches/{course}/certificate/{enrollment}',                         [VendorBatch::class,'issueCertificate'])->name('batches.certificate');
        Route::post('/batches/{course}/preallow',                                         [VendorBatch::class,'preAllow'])->name('batches.preallow');
        // Teacher management writes
        Route::post('/teachers',                                                          [VendorDash::class,'addTeacher'])->name('teachers.add');
        Route::delete('/teachers/{user}',                                                 [VendorDash::class,'removeTeacher'])->name('teachers.remove');
        // Student access writes
        Route::post('/students/grant',                                                    [VendorDash::class,'grantStudentAccess'])->name('students.grant');
        Route::delete('/students/{user}/revoke',                                          [VendorDash::class,'revokeStudentAccess'])->name('students.revoke');
        Route::post('/students/preallow',                                                 [VendorDash::class,'preAllow'])->name('students.preallow');
        Route::delete('/students/preallow/{id}',                                          [VendorDash::class,'removePreAllow'])->name('students.preallow.remove');
    });
});

// ── Vendor Portal (branded student-facing) ─────────────────────────────────
use App\Http\Controllers\Vendor\PortalController as VendorPortal;

Route::prefix('v/{slug}')->name('vendor.portal.')->group(function () {
    // Public pages
    Route::get('/',                                          [VendorPortal::class,'home'])->name('home');
    // Branded login
    Route::get('/login',                                     [VendorPortal::class,'showLogin'])->name('login');
    Route::post('/login',                                    [VendorPortal::class,'doLogin'])->name('login.submit');
    // Branded register (general — no course required)
    Route::get('/register',                                  [VendorPortal::class,'showRegister'])->name('register.general');
    Route::post('/register',                                 [VendorPortal::class,'doRegisterGeneral'])->name('register.general.submit');
    // Course-specific register
    Route::get('/register/{course}',                         [VendorPortal::class,'register'])->name('register');
    Route::post('/register/{course}',                        [VendorPortal::class,'doRegister'])->name('register.submit');
    // Logout from vendor portal
    Route::post('/logout',                                   [VendorPortal::class,'logout'])->name('logout');
    // Auth-protected
    Route::middleware(['vendor.portal.auth','single.device'])->group(function () {
        Route::get('/dashboard',                             [VendorPortal::class,'dashboard'])->name('dashboard');
        Route::get('/live/{lesson}',                         [VendorPortal::class,'liveClass'])->name('live');
        Route::get('/recording/{lesson}',                    [VendorPortal::class,'recording'])->name('recording');
        Route::get('/batch/{course}',                        [VendorPortal::class,'batch'])->name('batch');
        Route::get('/live-class/{liveClassSlug}',            [VendorPortal::class,'liveClassDetail'])->name('live_class.detail');
        Route::get('/live-class/{liveClassSlug}/file/{file}', [VendorPortal::class,'viewSessionFile'])->name('live_class.file.view');
        Route::post('/batch/{course}/login',                 [VendorPortal::class,'batchLogin'])->name('batch.login');
    });
});

// ── System Accounts Seeder Helper Route ────────────────────────────────────
Route::get('/seed-system-accounts', function () {
    try {
        // Run Database Seeder
        (new \Database\Seeders\DatabaseSeeder())->run();

        // Clear Spatie Permission & Laravel Cache
        try { app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions(); } catch (\Throwable $t) {}
        try { \Illuminate\Support\Facades\Artisan::call('cache:clear'); } catch (\Throwable $t) {}

        return response('<!DOCTYPE html><html><head><title>System Accounts Seeded — Skillspot.in</title></head><body style="font-family:sans-serif;background:#0f172a;color:#f8fafc;padding:40px;line-height:1.6;"><div style="max-width:600px;margin:0 auto;background:#1e293b;padding:30px;border-radius:16px;box-shadow:0 10px 25px rgba(0,0,0,0.3);"><h2 style="color:#22c55e;margin-top:0;">✅ System Accounts & Permissions Seeded!</h2><p>All roles, permissions, and default accounts have been synchronized and passwords converted to valid Bcrypt hashes.</p><div style="background:#0f172a;padding:20px;border-radius:12px;margin:20px 0;"><h3 style="color:#38bdf8;margin-top:0;">Available Credentials:</h3><ul style="margin:0;padding-left:20px;color:#cbd5e1;"><li><strong>Super Admin:</strong> skillspot.in@gmail.com &nbsp;|&nbsp; <code>SkillSpot#2026@Secure</code></li><li><strong>Teacher:</strong> teacher@skillspot.in &nbsp;|&nbsp; <code>Teacher#2026@Skillspot</code></li><li><strong>Student:</strong> student@skillspot.in &nbsp;|&nbsp; <code>Student#2026@Skillspot</code></li></ul></div><p><a href="/login" style="display:inline-block;background:#2563eb;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Go to Login Page →</a></p></div></body></html>');
    } catch (\Throwable $e) {
        return response('<!DOCTYPE html><html><body style="font-family:sans-serif;background:#0f172a;color:#f8fafc;padding:30px;"><h2 style="color:#ef4444;">Seeder Exception Diagnostic Info</h2><p><strong>Error:</strong> '.e($e->getMessage()).'</p><p><strong>File:</strong> '.e($e->getFile()).' (Line '.e($e->getLine()).')</p><pre style="background:#1e293b;padding:15px;border-radius:8px;overflow-x:auto;">'.e($e->getTraceAsString()).'</pre></body></html>');
    }
});
