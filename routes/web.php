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
use App\Http\Controllers\Instructor\CourseWorkspaceController;
use App\Http\Controllers\CourseCreationController;
use App\Http\Controllers\Admin\CourseOptionsController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\CertificatesController;
use App\Http\Controllers\Admin\LiveController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\BatchController as AdminBatch;
use App\Http\Controllers\Admin\EnrollmentsController;
use App\Http\Controllers\Student\DashboardController as StudentDash;
use App\Http\Controllers\Student\CourseController as StudentCourse;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\SecureVideoController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\CourseTeachingController;

// ── Landing ────────────────────────────────────────────────────────────────
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/marketplace', [MarketplaceController::class, 'browse'])->name('marketplace.browse');
Route::get('/media/file/{upload:uuid}', [MediaController::class, 'publicFile'])->name('media.public');
Route::get('/media/file/{upload:uuid}/download', [MediaController::class, 'downloadPublicFile'])->name('media.download');
Route::get('/course-media/{course}/{upload:uuid}', [MediaController::class, 'courseFile'])->middleware('auth')->withoutScopedBindings()->name('media.course');
Route::get('/course/{slug}', [StudentCourse::class,'detail'])->name('student.course-detail');

// ── Secure video streaming (auth required)
Route::middleware(['auth','portal.access'])->group(function () {
    Route::get('/watch/lesson/{lesson}/stream',   [SecureVideoController::class, 'stream'])->name('video.stream');
    Route::post('/watch/lesson/{lesson}/progress',[SecureVideoController::class, 'progress'])->name('video.progress');
});


// ── Student Course routes ──────────────────────────────────────────────────
Route::middleware(['auth','portal.access'])->group(function () {
    Route::post('/course/{course}/enroll-free',             [StudentCourse::class,'enrollFree'])->name('student.enroll-free');
    Route::post('/course/{course}/order',                   [StudentCourse::class,'createOrder'])->name('student.create-order');
    Route::post('/course/{course}/verify-payment',          [StudentCourse::class,'verifyPayment'])->name('student.verify-payment');
    Route::get('/learn/{course}',                           [StudentCourse::class,'learn'])->name('student.learn');
    Route::get('/learn/{course}/lesson/{lesson}',           [StudentCourse::class,'getLesson'])->name('student.get-lesson');
    Route::get('/learn/{course}/scorm/{section}/{path}',     [StudentCourse::class,'scormAsset'])->where('path', '.*')->name('student.scorm.asset');
    Route::post('/learn/{course}/lesson/{lesson}/progress', [StudentCourse::class,'saveProgress'])->name('student.save-progress');
    Route::post('/learn/{course}/lesson/{lesson}/quiz', [StudentCourse::class,'submitQuiz'])->name('student.submit-quiz');
    Route::post('/learn/{course}/assignment/{assignment}', [CourseTeachingController::class, 'submitAssignment'])->name('student.assignment.submit');
});

Route::middleware('auth')->prefix('courses/{course}/teaching')->name('courses.teaching.')->group(function () {
    Route::get('/', [CourseTeachingController::class, 'show'])->name('show');
    Route::post('/sections/{section}/assignments', [CourseTeachingController::class, 'storeAssignment'])->name('assignments.store');
    Route::post('/sections/{section}/quizzes', [CourseTeachingController::class, 'storeQuiz'])->name('quizzes.store');
    Route::post('/quizzes/{quiz}/questions', [CourseTeachingController::class, 'addQuizQuestion'])->name('quizzes.questions.store');
    Route::patch('/quiz-attempts/{attempt}/grade', [CourseTeachingController::class, 'gradeQuiz'])->name('quizzes.attempts.grade');
    Route::put('/assignments/{assignment}', [CourseTeachingController::class, 'updateAssignment'])->name('assignments.update');
    Route::patch('/submissions/{submission}/grade', [CourseTeachingController::class, 'grade'])->name('submissions.grade');
    Route::get('/submissions/{submission}/file', [CourseTeachingController::class, 'download'])->name('submissions.file');
    Route::post('/announcements', [CourseTeachingController::class, 'announce'])->name('announcements.store');
    Route::get('/media', [CourseTeachingController::class, 'media'])->name('media.index');
    Route::post('/media', [CourseTeachingController::class, 'uploadMedia'])->name('media.store');
});

// ── Marketplace instructor onboarding ────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/teach', [MarketplaceController::class, 'applyForm'])->name('marketplace.apply');
    Route::post('/teach', [MarketplaceController::class, 'apply'])->name('marketplace.apply.store');
    Route::get('/instructor', [MarketplaceController::class, 'dashboard'])->name('marketplace.dashboard');
    Route::get('/studio', [MarketplaceController::class, 'dashboard'])->name('studio.dashboard');
    Route::put('/studio/payout', [MarketplaceController::class, 'updatePayout'])->name('studio.payout.update');
    Route::post('/studio/courses/{course}/submit-review', [MarketplaceController::class, 'submitCourse'])->name('studio.courses.submit-review');
});

Route::prefix('instructor')->name('instructor.')->middleware('auth')->group(function () {
    Route::get('/courses/{course}/preview', [StudentCourse::class, 'preview'])->name('courses.preview');
    Route::get('/courses/{course}/preview/lesson/{lesson}', [StudentCourse::class, 'previewLesson'])->name('courses.preview.lesson');
});

Route::prefix('instructor')->name('instructor.')->middleware(['auth', 'creator.access'])->group(function () {
    Route::get('/courses', [CourseWorkspaceController::class, 'index'])->name('courses.index');
    Route::post('/courses/create', [CoursesController::class, 'store'])->name('courses.create.store');
    Route::get('/courses/{course}', [CourseWorkspaceController::class, 'show'])->name('courses.show');
    Route::put('/courses/{course}', [CoursesController::class, 'update'])->name('courses.update');
    Route::get('/courses/{course}/export', [CoursesController::class, 'export'])->name('courses.export');
    Route::patch('/courses/{course}/toggle-publish', [CoursesController::class, 'togglePublish'])->name('courses.toggle-publish');
    Route::delete('/courses/{course}', [CoursesController::class, 'destroy'])->name('courses.destroy');
    Route::post('/courses/{course}/sections', [CoursesController::class, 'storeSection'])->name('courses.sections.store');
    Route::put('/courses/{course}/sections/{section}', [CoursesController::class, 'updateSection'])->name('courses.sections.update');
    Route::delete('/courses/{course}/sections/{section}', [CoursesController::class, 'destroySection'])->name('courses.sections.destroy');
    Route::post('/courses/{course}/sections/reorder', [CoursesController::class, 'reorderSections'])->name('courses.sections.reorder');
    Route::get('/courses/{course}/sections/{section}/lessons/create', [CoursesController::class, 'createLesson'])->name('courses.lessons.create');
    Route::post('/courses/{course}/sections/{section}/lessons', [CoursesController::class, 'storeLesson'])->name('courses.lessons.store');
    Route::get('/courses/{course}/sections/{section}/lessons/{lesson}/edit', [CoursesController::class, 'editLesson'])->name('courses.lessons.edit');
    Route::put('/courses/{course}/sections/{section}/lessons/{lesson}', [CoursesController::class, 'updateLesson'])->name('courses.lessons.update');
    Route::delete('/courses/{course}/sections/{section}/lessons/{lesson}', [CoursesController::class, 'destroyLesson'])->name('courses.lessons.destroy');
    Route::prefix('/workspace/courses')->name('api.courses.')->group(function () {
        Route::get('/', [CourseWorkspaceController::class, 'courses'])->name('index');
        Route::post('/', [CourseWorkspaceController::class, 'storeCourse'])->name('store');
        Route::get('/{course}/quizzes', [CourseWorkspaceController::class, 'quizLibrary'])->name('quizzes.index');
        Route::get('/{course}', [CourseWorkspaceController::class, 'course'])->name('show');
        Route::patch('/{course}', [CourseWorkspaceController::class, 'updateCourse'])->name('update');
        Route::post('/{course}/sections', [CourseWorkspaceController::class, 'storeSection'])->name('sections.store');
        Route::patch('/{course}/sections/{section}', [CourseWorkspaceController::class, 'updateSection'])->name('sections.update');
        Route::post('/{course}/sections/{section}/lessons', [CourseWorkspaceController::class, 'storeLesson'])->name('lessons.store');
        Route::patch('/{course}/sections/{section}/lessons/{lesson}', [CourseWorkspaceController::class, 'updateLesson'])->name('lessons.update');
        Route::post('/{course}/outline', [CourseWorkspaceController::class, 'reorder'])->name('outline');
    });
});
Route::middleware('auth')->prefix('courses/create')->name('courses.create.')->group(function () {
    Route::post('/media', [CourseCreationController::class, 'uploadMedia'])->name('media');
    Route::post('/categories', [CourseCreationController::class, 'storeCategory'])->name('categories');
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
    Route::get('/register/pending', [AuthController::class, 'showRegisterPending'])->name('register.pending');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
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
Route::prefix('admin')->name('admin.')->middleware(['auth','admin.only'])->group(function () {
    Route::get('/',                      [AdminDash::class,       'index'])->name('dashboard');
    Route::get('/settings',              [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/mail/test',   [SettingsController::class, 'testMail'])->name('settings.mail.test');
    Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
    Route::post('/settings/migrate',     [SettingsController::class, 'runMigrate'])->name('settings.migrate');
    Route::post('/settings/storage-link', [SettingsController::class, 'runStorageLink'])->name('settings.storage-link');
    Route::post('/settings/toggle/{key}',  [SettingsController::class, 'toggle'])->name('settings.toggle');
    Route::post('/settings/categories', [SettingsController::class, 'storeCategory'])->name('settings.categories.store');
    Route::put('/settings/categories/{category}', [SettingsController::class, 'updateCategory'])->name('settings.categories.update');
    Route::delete('/settings/categories/{category}', [SettingsController::class, 'destroyCategory'])->name('settings.categories.destroy');
    Route::delete('/settings/payment-gateways/razorpay', [SettingsController::class, 'deleteGateway'])->name('settings.gateways.delete');
    Route::post('/settings/{group}',     [SettingsController::class, 'save'])->name('settings.save');
    // Courses
    Route::get('/courses',                                            [CoursesController::class,'index'])->name('courses');
    Route::get('/courses/create',                                     [CoursesController::class,'create'])->name('courses.create');
    Route::get('/courses/import',                                     [CoursesController::class,'importForm'])->name('courses.import');
    Route::post('/courses/import',                                    [CoursesController::class,'importZip'])->name('courses.import.store');
    Route::post('/courses',                                           [CoursesController::class,'store'])->name('courses.store');
    Route::post('/courses/instructors',                               [CoursesController::class,'storeInstructor'])->name('courses.instructors.store');
    Route::get('/courses/{course}/edit',                              [CoursesController::class,'edit'])->name('courses.edit');
    Route::put('/courses/{course}',                                   [CoursesController::class,'update'])->name('courses.update');
    Route::get('/courses/{course}/export',                            [CoursesController::class,'export'])->name('courses.export');
    Route::post('/courses/{course}/grant-access',        [CoursesController::class,'grantAccess'])->name('courses.grant-access');
    Route::delete('/courses/{course}/revoke-access/{user}', [CoursesController::class,'revokeAccess'])->name('courses.revoke-access');
    Route::patch('/courses/{course}/toggle-publish',                  [CoursesController::class,'togglePublish'])->name('courses.toggle-publish');
    Route::delete('/courses/{course}',                                [CoursesController::class,'destroy'])->name('courses.destroy');
    Route::post('/courses/{course}/submit-review', [MarketplaceController::class, 'submitCourse'])->name('courses.submit-review');
    // Quiz library
    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes');
    Route::get('/quizzes/questions', [QuizController::class, 'questions'])->name('quizzes.questions');
    Route::get('/quizzes/submissions', [QuizController::class, 'submissions'])->name('quizzes.submissions');
    Route::get('/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
    Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
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
    Route::post('/payments/{payment}/reconcile-refund', [PaymentsController::class,'reconcileRefund'])->name('payments.refund.reconcile');
    // Certificates
    Route::get('/certificates',                [CertificatesController::class,'index'])->name('certs');
    Route::get('/certificates/{certificate}/verify',[CertificatesController::class,'verify'])->name('certs.verify');
    Route::patch('/certificates/{certificate}/revoke',[CertificatesController::class,'revoke'])->name('certs.revoke');
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
    Route::post('/users/{user}/approve', [UsersController::class, 'approve'])->name('users.approve');
    Route::post('/users/{user}/reject',  [UsersController::class, 'reject'])->name('users.reject');
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

    // Marketplace operations are admin-only in the controller, even though course authoring is shared with teachers.
    Route::get('/marketplace/instructors', [MarketplaceController::class, 'applications'])->name('marketplace.instructors');
    Route::patch('/marketplace/instructors/{profile}', [MarketplaceController::class, 'reviewApplication'])->name('marketplace.instructors.review');
    Route::get('/marketplace/reviews', [MarketplaceController::class, 'reviewQueue'])->name('marketplace.reviews');
    Route::patch('/marketplace/reviews/{course}', [MarketplaceController::class, 'reviewCourse'])->name('marketplace.reviews.update');
    Route::post('/marketplace/reviews/{course}/override', [MarketplaceController::class, 'publishAdminCourse'])->name('marketplace.reviews.override');
    Route::get('/marketplace/commissions', [MarketplaceController::class, 'commissions'])->name('marketplace.commissions');
    Route::post('/marketplace/commissions', [MarketplaceController::class, 'saveCommission'])->name('marketplace.commissions.save');
    Route::get('/marketplace/payouts', [MarketplaceController::class, 'payouts'])->name('marketplace.payouts');
    Route::post('/marketplace/payouts', [MarketplaceController::class, 'createPayout'])->name('marketplace.payouts.create');
    Route::patch('/marketplace/payouts/{batch}/paid', [MarketplaceController::class, 'markPayoutPaid'])->name('marketplace.payouts.paid');
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
    Route::get('/certs/{certificate}', [StudentDash::class,'showCertificate'])->name('certs.show');
    Route::get('/profile',          [StudentDash::class,'profile'])->name('profile');
    Route::put('/profile',          [StudentDash::class,'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [StudentDash::class,'updatePassword'])->name('profile.password');
    Route::get('/settings',         [StudentDash::class,'settings'])->name('settings');
    Route::get('/progress',         [StudentDash::class,'progress'])->name('progress');
    Route::get('/live',             fn() => view('student.live'))->name('live');
    Route::redirect('/saved', '/dashboard/browse')->name('saved');
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

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin.only'])->group(function () {
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
    Route::get('/earnings',                                  [MarketplaceController::class,'organizationDashboard'])->name('earnings');
    // Profile / branding
    Route::get('/profile',                                   [VendorProfile::class,'index'])->name('profile');
    Route::put('/profile',                                   [VendorProfile::class,'update'])->name('profile.update');
    Route::get('/my-profile',                                [VendorProfile::class,'myProfile'])->name('my-profile');
    Route::put('/my-profile',                                [VendorProfile::class,'updateMyProfile'])->name('my-profile.update');
    Route::put('/my-profile/password',                       [VendorProfile::class,'changePassword'])->name('my-profile.password');
    // Courses (view — all roles)
    Route::get('/courses',                                   [VendorCourses::class,'index'])->name('courses');
    Route::get('/courses/{course}/students',                 [VendorCourses::class,'students'])->name('courses.students');
    Route::get('/live-classes',                                [VendorLiveClass::class,'index'])->name('live_classes.index');
    Route::post('/courses/{course}/submit-review',           [MarketplaceController::class,'submitCourse'])->name('courses.submit-review');
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
        Route::post('/courses/create-modal',                                               [VendorCourses::class,'storeModal'])->name('courses.modal.store');
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
        Route::post('/live/{lesson}/recording',                                           [VendorLive::class,'updateRecording'])->name('live.recording');
        Route::delete('/live/{lesson}',                                                   [VendorLive::class,'destroy'])->name('live.destroy');
        Route::get('/live/{lesson}/attendance',                                           [VendorLive::class,'attendance'])->name('live.attendance');
        Route::post('/live/{lesson}/attendance',                                          [VendorLive::class,'saveAttendance'])->name('live.attendance.save');
        // Live Classes (hours-based & date-based)
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

// ── Public Storage Route Fallback ──────────────────────────────────────────
// Ensures uploaded files (course thumbnails, previews, uploads) are served
// seamlessly even if the storage symlink (public/storage) is missing on Hostinger/cPanel.
Route::get('/storage/{path}', function (string $path) {
    $cleanPath = ltrim(str_replace(['..', "\0"], '', $path), '/');
    
    $candidates = [
        public_path('storage/' . $cleanPath),
        storage_path('app/public/' . $cleanPath),
        base_path('storage/app/public/' . $cleanPath),
        base_path('../storage/app/public/' . $cleanPath),
        public_path('uploads/' . $cleanPath),
    ];

    $fullPath = null;
    foreach ($candidates as $candidate) {
        if (file_exists($candidate) && !is_dir($candidate)) {
            $fullPath = $candidate;
            break;
        }
    }

    if (!$fullPath) {
        abort(404);
    }

    // Auto-copy to public_path('storage/' . $cleanPath) so future requests are served as static files by Apache/LiteSpeed
    try {
        $publicDest = public_path('storage/' . $cleanPath);
        if ($fullPath !== $publicDest && !file_exists($publicDest)) {
            if (!file_exists(dirname($publicDest))) {
                @mkdir(dirname($publicDest), 0755, true);
            }
            @copy($fullPath, $publicDest);
        }
    } catch (\Throwable $e) {}

    $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
    return response()->file($fullPath, [
        'Content-Type'  => $mime,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*')->name('storage.fallback');

// ── Web Artisan Runner (for Hostinger/cPanel environments without SSH) ───────
// Accessible directly via browser: /system/migrate or /phpartisan-migrate
Route::match(['GET', 'POST'], '/system/migrate', function (\Illuminate\Http\Request $request) {
    $authorized = (auth()->check() && auth()->user()->hasRole(['super-admin', 'admin']))
        || $request->query('secret') === 'skillspot2026';

    if (!$authorized) {
        return response("
            <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:550px;margin:60px auto;padding:32px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 10px 25px rgba(0,0,0,0.05);text-align:center;'>
                <div style='font-size:40px;margin-bottom:12px;'>🔒</div>
                <h2 style='margin:0 0 8px;color:#0f172a;font-size:20px;'>Admin Authorization Required</h2>
                <p style='color:#64748b;font-size:13px;line-height:1.6;margin-bottom:20px;'>
                    Please <a href='/login' style='color:#2563eb;font-weight:600;'>log in as an Administrator</a> first, or supply the secret token in the URL: <br>
                    <code style='background:#f1f5f9;padding:4px 8px;border-radius:6px;font-size:12px;color:#0f172a;'>?secret=skillspot2026</code>
                </p>
                <a href='/login' style='display:inline-block;background:#2563eb;color:#fff;font-weight:700;font-size:13px;padding:10px 20px;border-radius:10px;text-decoration:none;'>Sign In to LMS</a>
            </div>
        ", 403)->header('Content-Type', 'text/html');
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOut = trim(\Illuminate\Support\Facades\Artisan::output());

        $linkOut = '';
        $copiedCount = 0;
        try {
            // Recursive sync from storage/app/public to public/storage
            $srcBase = storage_path('app/public');
            $dstBase = public_path('storage');
            $syncStorage = function ($src, $dst) use (&$syncStorage, &$copiedCount) {
                if (!is_dir($src)) return;
                if (!file_exists($dst)) @mkdir($dst, 0755, true);
                $items = @scandir($src) ?: [];
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $s = $src . '/' . $item;
                    $d = $dst . '/' . $item;
                    if (is_dir($s)) {
                        $syncStorage($s, $d);
                    } else {
                        if (!file_exists($d) || filesize($d) !== filesize($s)) {
                            if (@copy($s, $d)) $copiedCount++;
                        }
                    }
                }
            };
            $syncStorage($srcBase, $dstBase);
            $linkOut = "Storage sync complete: {$copiedCount} media file(s) synchronized to public/storage for direct web serving.";
        } catch (\Throwable $stEx) {
            $linkOut = 'Storage sync notice: ' . $stEx->getMessage();
        }

        $cacheOut = '';
        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            $cacheOut = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $cEx) {
            $cacheOut = 'Cache clear note: ' . $cEx->getMessage();
        }

        return response("
            <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:680px;margin:50px auto;padding:36px;background:#ffffff;border:2px solid #86efac;border-radius:20px;box-shadow:0 15px 35px rgba(22,101,52,0.08);'>
                <div style='display:flex;align-items:center;gap:14px;margin-bottom:18px;'>
                    <div style='width:48px;height:48px;background:#dcfce7;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;'>✅</div>
                    <div>
                        <h2 style='margin:0;color:#14532d;font-size:22px;font-weight:800;'>Artisan Commands Executed!</h2>
                        <p style='margin:4px 0 0;color:#166534;font-size:13px;'>Your database and storage links are now up to date on <strong>lms.skillspot.in</strong>.</p>
                    </div>
                </div>

                <div style='margin-bottom:14px;'>
                    <div style='font-weight:700;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;'>1. Migration Output</div>
                    <pre style='background:#f8fafc;border:1px solid #e2e8f0;padding:12px 14px;border-radius:10px;font-size:12px;color:#0f172a;overflow-x:auto;margin:0;'>" . htmlspecialchars($migrateOut ?: 'Nothing to migrate. Database is already up to date.') . "</pre>
                </div>

                <div style='margin-bottom:20px;'>
                    <div style='font-weight:700;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;'>2. Storage Symlink & Cache Output</div>
                    <pre style='background:#f8fafc;border:1px solid #e2e8f0;padding:12px 14px;border-radius:10px;font-size:12px;color:#0f172a;overflow-x:auto;margin:0;'>" . htmlspecialchars(($linkOut ? $linkOut . "\n" : '') . $cacheOut) . "</pre>
                </div>

                <div style='display:flex;gap:10px;align-items:center;'>
                    <a href='/admin' style='display:inline-block;background:#16a34a;color:#fff;font-weight:700;font-size:13px;padding:11px 22px;border-radius:10px;text-decoration:none;'>Go to Admin Dashboard →</a>
                    <a href='/admin/courses' style='display:inline-block;background:#f1f5f9;color:#334155;font-weight:700;font-size:13px;padding:11px 22px;border-radius:10px;text-decoration:none;'>View Courses</a>
                </div>
            </div>
        ", 200)->header('Content-Type', 'text/html');
    } catch (\Throwable $e) {
        return response("
            <div style='font-family:-apple-system,BlinkMacSystemFont,sans-serif;max-width:650px;margin:50px auto;padding:32px;background:#fff;border:2px solid #fca5a5;border-radius:16px;'>
                <h2 style='color:#991b1b;margin-top:0;'>❌ Execution Error</h2>
                <pre style='background:#fef2f2;padding:14px;border-radius:8px;color:#b91c1c;font-size:13px;white-space:pre-wrap;'>" . htmlspecialchars($e->getMessage()) . "</pre>
            </div>
        ", 500)->header('Content-Type', 'text/html');
    }
})->name('system.migrate');

Route::match(['GET', 'POST'], '/phpartisan-migrate', fn(\Illuminate\Http\Request $r) => redirect()->route('system.migrate', $r->query()));
Route::match(['GET', 'POST'], '/artisan/migrate', fn(\Illuminate\Http\Request $r) => redirect()->route('system.migrate', $r->query()));

// ── Web Runner: Delete All Courses ──────────────────────────────────────────
Route::match(['GET', 'POST'], '/system/delete-all-courses', function (\Illuminate\Http\Request $request) {
    $authorized = (auth()->check() && auth()->user()->hasRole(['super-admin', 'admin']))
        || $request->query('secret') === 'skillspot2026';

    if (!$authorized) {
        return response("
            <div style='font-family:-apple-system,BlinkMacSystemFont,sans-serif;max-width:550px;margin:60px auto;padding:32px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;text-align:center;'>
                <div style='font-size:40px;margin-bottom:12px;'>🔒</div>
                <h2 style='margin:0 0 8px;color:#0f172a;'>Admin Authorization Required</h2>
                <p style='color:#64748b;font-size:13px;'>Please log in as an Admin, or use <code>?secret=skillspot2026</code> in the URL.</p>
                <a href='/login' style='display:inline-block;margin-top:14px;background:#2563eb;color:#fff;font-weight:700;font-size:13px;padding:10px 20px;border-radius:10px;text-decoration:none;'>Log In to LMS</a>
            </div>
        ", 403)->header('Content-Type', 'text/html');
    }

    $confirmed = $request->query('confirm') === 'yes' || $request->input('confirm') === 'yes';

    if (!$confirmed) {
        $count = \App\Models\Course::count();
        $courses = \App\Models\Course::select('id', 'title')->limit(20)->get();
        $courseListHtml = '';
        foreach ($courses as $c) {
            $courseListHtml .= "<li style='padding:4px 0;'>" . htmlspecialchars($c->title) . " (ID: {$c->id})</li>";
        }
        if ($count > 20) {
            $courseListHtml .= "<li style='padding:4px 0;color:#94a3b8;'>...and " . ($count - 20) . " more courses</li>";
        }

        $secretParam = $request->query('secret') ? '&secret=' . urlencode($request->query('secret')) : '';

        return response("
            <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:620px;margin:50px auto;padding:36px;background:#fff;border:2px solid #f87171;border-radius:20px;box-shadow:0 15px 35px rgba(239,68,68,0.1);'>
                <div style='display:flex;align-items:center;gap:14px;margin-bottom:16px;'>
                    <div style='width:52px;height:52px;background:#fee2e2;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;'>⚠️</div>
                    <div>
                        <h2 style='margin:0;color:#991b1b;font-size:22px;font-weight:800;'>Delete All Courses?</h2>
                        <p style='margin:4px 0 0;color:#7f1d1d;font-size:13px;'>This action is <strong>irreversible</strong> and will permanently wipe course data.</p>
                    </div>
                </div>

                <div style='background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;margin-bottom:20px;'>
                    <div style='font-weight:700;color:#991b1b;font-size:14px;margin-bottom:6px;'>Courses to be deleted: <span style='background:#fee2e2;padding:2px 8px;border-radius:6px;'>{$count}</span></div>
                    <ul style='margin:8px 0 0;padding-left:20px;color:#7f1d1d;font-size:13px;max-height:180px;overflow-y:auto;'>
                        {$courseListHtml}
                    </ul>
                </div>

                <p style='color:#64748b;font-size:13px;line-height:1.6;margin-bottom:24px;'>
                    This will delete all <strong>courses, chapters/sections, lessons, quizzes, assignments, and course enrollments</strong>.
                    <br><strong style='color:#0f172a;'>Note:</strong> Your users, admins, teachers, and system settings will remain safe and untouched.
                </p>

                <div style='display:flex;gap:12px;align-items:center;'>
                    <a href='/system/delete-all-courses?confirm=yes{$secretParam}' 
                       onclick='return confirm(\"Are you 100% sure you want to delete ALL {$count} courses?\");'
                       style='display:inline-block;background:#dc2626;color:#fff;font-weight:700;font-size:13px;padding:12px 24px;border-radius:10px;text-decoration:none;'>
                        🗑️ Yes, Permanently Delete All Courses
                    </a>
                    <a href='/admin/courses' style='display:inline-block;background:#f1f5f9;color:#334155;font-weight:700;font-size:13px;padding:12px 20px;border-radius:10px;text-decoration:none;'>
                        Cancel
                    </a>
                </div>
            </div>
        ", 200)->header('Content-Type', 'text/html');
    }

    try {
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

        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        } catch (\Throwable $cEx) {}

        return response("
            <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:620px;margin:50px auto;padding:36px;background:#fff;border:2px solid #86efac;border-radius:20px;box-shadow:0 15px 35px rgba(22,101,52,0.08);text-align:center;'>
                <div style='font-size:48px;margin-bottom:12px;'>✅</div>
                <h2 style='margin:0 0 8px;color:#14532d;font-size:22px;font-weight:800;'>All Courses Deleted Successfully!</h2>
                <p style='color:#166534;font-size:14px;line-height:1.6;margin-bottom:24px;'>
                    Permanently deleted <strong>{$count} courses</strong> and all associated sections, lessons, quizzes, and enrollments.<br>
                    Your LMS course catalog is now fresh and empty.
                </p>
                <div style='display:flex;gap:12px;justify-content:center;'>
                    <a href='/admin/courses' style='display:inline-block;background:#16a34a;color:#fff;font-weight:700;font-size:13px;padding:12px 24px;border-radius:10px;text-decoration:none;'>
                        View Courses (0) →
                    </a>
                    <a href='/admin' style='display:inline-block;background:#f1f5f9;color:#334155;font-weight:700;font-size:13px;padding:12px 20px;border-radius:10px;text-decoration:none;'>
                        Admin Dashboard
                    </a>
                </div>
            </div>
        ", 200)->header('Content-Type', 'text/html');
    } catch (\Throwable $e) {
        return response("
            <div style='font-family:-apple-system,BlinkMacSystemFont,sans-serif;max-width:650px;margin:50px auto;padding:32px;background:#fff;border:2px solid #fca5a5;border-radius:16px;'>
                <h2 style='color:#991b1b;margin-top:0;'>❌ Deletion Error</h2>
                <pre style='background:#fef2f2;padding:14px;border-radius:8px;color:#b91c1c;font-size:13px;white-space:pre-wrap;'>" . htmlspecialchars($e->getMessage()) . "</pre>
            </div>
        ", 500)->header('Content-Type', 'text/html');
    }
})->name('system.delete-all-courses');


