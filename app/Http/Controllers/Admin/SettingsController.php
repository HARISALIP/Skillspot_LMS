<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\PaymentGatewayAccount;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    private array $schema = [
        'general' => [
            'system_language'  => ['label' => 'System Language', 'type' => 'select',  'default' => 'en',           'options' => ['en' => 'English']],
            'system_timezone'  => ['label' => 'System Timezone', 'type' => 'select',  'default' => 'Asia/Kolkata', 'options' => ['Asia/Kolkata' => 'Asia/Kolkata', 'UTC' => 'UTC', 'Europe/London' => 'Europe/London', 'America/New_York' => 'America/New_York']],
            'text_direction'   => ['label' => 'Text Direction',  'type' => 'select',  'default' => 'auto',         'options' => ['auto' => 'Auto', 'ltr' => 'Left to right', 'rtl' => 'Right to left']],
            'allow_guest_access' => ['label' => 'Allow Guest Access', 'type' => 'boolean', 'default' => '1'],
            'disable_pwa' => ['label' => 'Disable PWA', 'type' => 'boolean', 'default' => '0'],
            'allow_job_posting' => ['label' => 'Allow Job Posting', 'type' => 'boolean', 'default' => '0'],
            'send_evaluation_invites' => ['label' => 'Send calendar invite for evaluations', 'type' => 'boolean', 'default' => '0'],
            'published_course_notification' => ['label' => 'Send Notification for Published Courses', 'type' => 'select', 'options' => ['' => 'Select option', 'all' => 'All members', 'enrolled' => 'Enrolled members'], 'default' => ''],
            'published_batch_notification' => ['label' => 'Send Notification for Published Batches', 'type' => 'select', 'options' => ['' => 'Select option', 'all' => 'All members', 'enrolled' => 'Enrolled members'], 'default' => ''],
        ],
        'signup' => [
            'user_category' => ['label' => 'Identify User Category', 'type' => 'boolean', 'default' => '0'],
            'disable_signup' => ['label' => 'Disable signup', 'type' => 'boolean', 'default' => '1'],
            'custom_signup_content' => ['label' => 'Signup Consent HTML', 'type' => 'textarea', 'default' => ''],
        ],
        'course-progress' => [
            'lesson_dwell_time' => ['label' => 'Lesson Completion Time (seconds)', 'type' => 'text', 'default' => '30'],
            'enforce_video_completion' => ['label' => 'Enforce video completion', 'type' => 'boolean', 'default' => '0'],
            'enforce_assignment_completion' => ['label' => 'Enforce assignment completion', 'type' => 'boolean', 'default' => '0'],
            'enforce_quiz_completion' => ['label' => 'Enforce quiz completion', 'type' => 'boolean', 'default' => '0'],
            'prevent_skipping_videos' => ['label' => 'Prevent Skipping Videos', 'type' => 'boolean', 'default' => '0'],
        ],
        'certificates' => [
            'certificate_auto_issue' => ['label' => 'Automatically issue certificates', 'type' => 'boolean', 'default' => '1'],
            'certificate_title' => ['label' => 'Certificate title', 'type' => 'text', 'default' => 'Certificate of Completion'],
            'certificate_intro' => ['label' => 'Introduction', 'type' => 'text', 'default' => 'This certifies that'],
            'certificate_completion_text' => ['label' => 'Completion text', 'type' => 'text', 'default' => 'has successfully completed ' . '{' . '{course_title}}'],
            'certificate_signatory' => ['label' => 'Authorized signatory', 'type' => 'text', 'default' => 'Skillspot Academy'],
            'certificate_footer' => ['label' => 'Footer text', 'type' => 'text', 'default' => 'Issued by Skillspot LMS'],
        ],
        'seo' => [
            'meta_description' => ['label' => 'Meta Description', 'type' => 'textarea', 'default' => ''],
            'meta_keywords' => ['label' => 'Meta Keywords', 'type' => 'textarea', 'default' => ''],
            'meta_image' => ['label' => 'Meta Image URL', 'type' => 'text', 'default' => ''],
        ],
        'communication' => [
            'site_email'    => ['label' => 'Contact Email',       'type' => 'text', 'default' => ''],
            'site_phone'    => ['label' => 'Contact Phone',       'type' => 'text', 'default' => ''],
            'site_whatsapp' => ['label' => 'WhatsApp Number',     'type' => 'text', 'default' => ''],
            'site_maps_url' => ['label' => 'Contact / Maps URL',  'type' => 'text', 'default' => ''],
        ],
        'branding' => [
            'app_url'          => ['label' => 'App / Domain URL',         'type' => 'text',     'default' => 'https://Skillspot.in'],
            'site_name'        => ['label' => 'Academy Name',             'type' => 'text',     'default' => 'Skillspot'],
            'site_tagline'     => ['label' => 'Tagline',                  'type' => 'text',     'default' => 'Learn. Build. Grow.'],
            'site_email'       => ['label' => 'Contact Email',            'type' => 'text',     'default' => ''],
            'site_address'     => ['label' => 'Address',                  'type' => 'textarea', 'default' => ''],
            'site_phone'       => ['label' => 'Contact Phone',             'type' => 'text',     'default' => ''],
            'site_whatsapp'    => ['label' => 'WhatsApp Number',           'type' => 'text',     'default' => ''],
            'site_maps_url'    => ['label' => 'Google Maps URL',           'type' => 'text',     'default' => ''],
            'logo_url'         => ['label' => 'Logo URL',                 'type' => 'text',     'default' => ''],
            'favicon_url'      => ['label' => 'Favicon URL',              'type' => 'text',     'default' => ''],
            'primary_color'    => ['label' => 'Primary Color',            'type' => 'text',     'default' => '#2563eb'],
            'accent_color'     => ['label' => 'Accent Color',             'type' => 'text',     'default' => '#7c3aed'],
            'footer_text'      => ['label' => 'Footer Text',              'type' => 'textarea', 'default' => ''],
        ],
        'mail' => [
            'mail_mailer'       => ['label' => 'Mail Driver',             'type' => 'text',     'default' => 'smtp'],
            'mail_host'         => ['label' => 'SMTP Host',               'type' => 'text',     'default' => ''],
            'mail_port'         => ['label' => 'SMTP Port',               'type' => 'text',     'default' => '587'],
            'mail_username'     => ['label' => 'SMTP Username',           'type' => 'text',     'default' => ''],
            'mail_password'     => ['label' => 'SMTP Password',           'type' => 'password', 'default' => ''],
            'mail_encryption'   => ['label' => 'Encryption (tls/ssl)',    'type' => 'text',     'default' => 'tls'],
            'mail_from_address' => ['label' => 'From Address',            'type' => 'text',     'default' => ''],
            'mail_from_name'    => ['label' => 'From Name',               'type' => 'text',     'default' => 'Skillspot'],
        ],
        'storage' => [
            'storage_driver'       => ['label' => 'Storage Driver (local/r2)', 'type' => 'text',     'default' => 'r2'],
            'r2_endpoint'          => ['label' => 'R2 Endpoint URL',           'type' => 'text',     'default' => ''],
            'r2_access_key_id'     => ['label' => 'R2 Access Key ID',          'type' => 'text',     'default' => ''],
            'r2_secret_access_key' => ['label' => 'R2 Secret Access Key',      'type' => 'password', 'default' => ''],
            'r2_bucket'            => ['label' => 'R2 Bucket Name',            'type' => 'text',     'default' => 'Skillspot'],
            'r2_token'             => ['label' => 'R2 API Token',              'type' => 'password', 'default' => ''],
            'r2_public_url'        => ['label' => 'R2 Public CDN URL (opt.)',  'type' => 'text',     'default' => ''],
            'max_upload_mb'        => ['label' => 'Max Upload Size (MB)',       'type' => 'text',     'default' => '500'],
        ],
        'payment' => [
            'currency'                => ['label' => 'Currency Code',                 'type' => 'text',     'default' => 'INR'],
            'currency_symbol'         => ['label' => 'Currency Symbol',               'type' => 'text',     'default' => '₹'],
            'enable_free_courses'     => ['label' => 'Enable Free Courses',           'type' => 'boolean',  'default' => '1'],
            'razorpay_mode'           => ['label' => 'Razorpay mode',                 'type' => 'select',   'default' => 'live', 'options' => ['test' => 'Test', 'live' => 'Live']],
            'razorpay_enabled'        => ['label' => 'Accept paid course purchases',  'type' => 'boolean',  'default' => '0'],
        ],
        'payment-gateways' => [
            'razorpay_key_id'         => ['label' => 'API Key',                       'type' => 'text',     'default' => ''],
            'razorpay_key_secret'     => ['label' => 'API Secret',                    'type' => 'password', 'default' => ''],
            'razorpay_webhook_secret' => ['label' => 'Webhook Secret',                'type' => 'password', 'default' => ''],
        ],
        'license' => [
            'license_key'      => ['label' => 'Platform License Key',        'type' => 'text',     'default' => ''],
            'license_status'   => ['label' => 'License Status',              'type' => 'text',     'default' => 'active'],
            'license_plan'     => ['label' => 'Plan (starter/pro/ent)',       'type' => 'text',     'default' => 'pro'],
            'license_expires'  => ['label' => 'Expires At (YYYY-MM-DD)',      'type' => 'text',     'default' => ''],
            'max_students'     => ['label' => 'Max Students (0 = unlimited)', 'type' => 'text',     'default' => '0'],
            'max_courses'      => ['label' => 'Max Courses (0 = unlimited)',  'type' => 'text',     'default' => '0'],
            'storage_limit_gb' => ['label' => 'Storage Limit GB (0 = ∞)',    'type' => 'text',     'default' => '0'],
        ],
        'security' => [
            'human_verify_login'      => ['label' => 'Human Verify on Login',                        'type' => 'boolean', 'default' => '1'],
            'human_verify_register'   => ['label' => 'Human Verify on Register',                     'type' => 'boolean', 'default' => '1'],
            'allow_registration'      => ['label' => 'Allow Public Registration',                   'type' => 'boolean', 'default' => '1'],
            'require_user_approval'   => ['label' => 'Require Admin Approval for Registrations',      'type' => 'boolean', 'default' => '1'],
            'maintenance_mode'        => ['label' => 'Maintenance Mode',                            'type' => 'boolean', 'default' => '0'],
            'fast2sms_api_key'         => ['label' => 'Fast2SMS API Key',                           'type' => 'password','default' => ''],
            'otp_email_enabled'       => ['label' => 'Email OTP on Register (requires SMTP)',       'type' => 'boolean', 'default' => '0'],
            'otp_phone_enabled'       => ['label' => 'Phone OTP on Register (requires Fast2SMS key)','type' => 'boolean', 'default' => '0'],
            'session_lifetime_min'    => ['label' => 'Session Lifetime (minutes)',                  'type' => 'text',    'default' => '120'],
            'max_login_attempts'      => ['label' => 'Max Login Attempts',                          'type' => 'text',    'default' => '5'],
        ],
    ];

    // ── Show settings ──────────────────────────────────────────────────
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'general');
        if ($activeTab === 'badges') $activeTab = 'certificates';
        $knownTabs = ['general', 'signup', 'course-progress', 'certificates', 'badge-assignments', 'categories', 'communication', 'email-template', 'mail', 'members', 'payment', 'payment-gateways', 'transactions', 'coupons', 'storage', 'google-calendar', 'google-meet', 'zoom', 'branding', 'sidebar', 'seo'];
        if (!in_array($activeTab, $knownTabs, true)) {
            return redirect()->route('admin.settings', array_filter(['tab' => 'general', 'embed' => $request->boolean('embed') ? 1 : null]));
        }
        $settings  = Setting::allKeyed();
        $gatewayRecord = $activeTab === 'payment-gateways' ? $request->query('gateway') : null;
        $legacyGatewayCount = $activeTab === 'payment-gateways' ? PaymentGatewayAccount::count() : 0;
        if ($gatewayRecord && !in_array($gatewayRecord, ['new', 'razorpay'], true)) {
            return redirect()->route('admin.settings', array_filter(['tab' => 'payment-gateways', 'embed' => $request->boolean('embed') ? 1 : null]));
        }
        $gatewayExists = ($settings['razorpay_gateway_added'] ?? null) === '1'
            || (!array_key_exists('razorpay_gateway_added', $settings)
                && (filled($settings['razorpay_key_id'] ?? null) || filled($settings['razorpay_test_key_id'] ?? null)));
        if ($gatewayRecord === 'razorpay' && !$gatewayExists) {
            return redirect()->route('admin.settings', array_filter(['tab' => 'payment-gateways', 'embed' => $request->boolean('embed') ? 1 : null]));
        }
        $schema    = $this->schema;
        $categories = $activeTab === 'categories'
            ? CourseCategory::latest()->get()
            : collect();
        return view('admin.settings', compact('activeTab', 'settings', 'schema', 'categories', 'gatewayRecord', 'gatewayExists', 'legacyGatewayCount'));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100|unique:course_categories,name']);

        CourseCategory::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => '📚',
            'is_active' => true,
            'order_col' => (CourseCategory::max('order_col') ?? 0) + 1,
        ]);

        return $this->categorySettingsRedirect($request, 'Category created.');
    }

    public function updateCategory(Request $request, CourseCategory $category)
    {
        $data = $request->validate(['name' => 'required|string|max:100|unique:course_categories,name,'.$category->id]);

        $category->update(['name' => $data['name'], 'slug' => Str::slug($data['name'])]);

        return $this->categorySettingsRedirect($request, 'Category updated.');
    }

    public function destroyCategory(Request $request, CourseCategory $category)
    {
        if (Course::where('category', $category->name)->exists()) {
            return $this->categorySettingsRedirect($request, 'This category is still used by a course and cannot be deleted.', 'error');
        }

        $category->delete();
        return $this->categorySettingsRedirect($request, 'Category deleted.');
    }

    private function categorySettingsRedirect(Request $request, string $message, string $type = 'success')
    {
        $parameters = ['tab' => 'categories'];
        if ($request->boolean('embed')) $parameters['embed'] = 1;

        return redirect()->route('admin.settings', $parameters)->with($type, $message);
    }

    // ── Save a group ───────────────────────────────────────────────────
    public function save(Request $request, string $group)
    {
        if (!array_key_exists($group, $this->schema)) {
            return back()->with('error', 'Invalid settings group.');
        }

        if ($group === 'payment') {
            $request->validate(['razorpay_mode' => ['required', 'in:test,live']]);
        }

        if ($group === 'payment-gateways') {
            $mode = Setting::get('razorpay_mode', 'live') === 'test' ? 'test' : 'live';
            $keyField = $mode === 'test' ? 'razorpay_test_key_id' : 'razorpay_key_id';
            $secretField = $mode === 'test' ? 'razorpay_test_key_secret' : 'razorpay_key_secret';
            $request->validate([
                'provider' => ['required', 'in:razorpay'],
                'api_key' => ['required', 'string', 'max:255'],
                'api_secret' => [filled(Setting::get($secretField)) ? 'nullable' : 'required', 'string', 'max:255'],
                'webhook_secret' => ['nullable', 'string', 'max:255'],
                'redirect_to' => ['nullable', 'string', 'max:2048', function ($attribute, $value, $fail) {
                    if (!str_starts_with($value, '/') || str_starts_with($value, '//') || str_contains($value, '\\') || preg_match('/[\x00-\x1f\x7f]/', $value)) {
                        $fail('Redirect To must be a path on this site, starting with a single /.');
                    }
                }],
            ]);
            Setting::set($keyField, $request->input('api_key'), $group, 'text', 'API Key');
            if ($request->filled('api_secret')) Setting::set($secretField, $request->input('api_secret'), $group, 'password', 'API Secret');
            if ($request->filled('webhook_secret')) Setting::set('razorpay_webhook_secret', $request->input('webhook_secret'), $group, 'password', 'Webhook Secret');
            Setting::set('razorpay_redirect_to', $request->input('redirect_to', ''), $group, 'text', 'Redirect To');
            Setting::set('razorpay_gateway_added', '1', $group, 'boolean', 'Razorpay configured');
        } else {
            foreach ($this->schema[$group] as $key => $meta) {
                if ($meta['type'] === 'boolean') {
                    $value = $request->has($key) ? '1' : '0';
                } elseif ($meta['type'] === 'password') {
                    $value = $request->input($key);
                    if (blank($value)) continue; // keep existing
                } else {
                    $value = $request->input($key, $meta['default']);
                }

                Setting::set($key, $value, $group, $meta['type'], $meta['label']);
            }
        }

        // Apply platform-wide preferences on the next request as well as retaining them in settings.
        if ($group === 'general') {
            config(['app.locale' => $request->input('system_language', 'en')]);
            config(['app.timezone' => $request->input('system_timezone', 'Asia/Kolkata')]);
            app()->setLocale($request->input('system_language', 'en'));
            date_default_timezone_set($request->input('system_timezone', 'Asia/Kolkata'));
        }

        // Special: sync app_url back to .env APP_URL so artisan commands work too
        if ($group === 'branding' && $request->filled('app_url')) {
            $this->updateEnvKey('APP_URL', rtrim($request->input('app_url'), '/'));
            $this->updateEnvKey('APP_NAME', '"' . $request->input('site_name', 'Skillspot') . '"');
        }

        // Sync mail to .env so queue/jobs pick it up
        if ($group === 'mail') {
            if ($request->filled('mail_mailer'))       $this->updateEnvKey('MAIL_MAILER',       $request->input('mail_mailer'));
            if ($request->filled('mail_host'))         $this->updateEnvKey('MAIL_HOST',         $request->input('mail_host'));
            if ($request->filled('mail_port'))         $this->updateEnvKey('MAIL_PORT',         $request->input('mail_port'));
            if ($request->filled('mail_username'))     $this->updateEnvKey('MAIL_USERNAME',     $request->input('mail_username'));
            if ($request->filled('mail_password'))     $this->updateEnvKey('MAIL_PASSWORD',     $request->input('mail_password'));
            if ($request->filled('mail_encryption'))   $this->updateEnvKey('MAIL_SCHEME',       $request->input('mail_encryption'));
            if ($request->filled('mail_from_address')) $this->updateEnvKey('MAIL_FROM_ADDRESS', $request->input('mail_from_address'));
            if ($request->filled('mail_from_name'))    $this->updateEnvKey('MAIL_FROM_NAME',    '"' . $request->input('mail_from_name') . '"');
            Setting::set('mail_invites_verified_at', '', 'mail', 'text', 'Member invites verified at');
        }

        Cache::flush();
        Artisan::call('config:clear');

        $settingsRoute = ['tab' => $group];
        if ($group === 'payment-gateways' && $request->input('gateway_record') === 'razorpay') $settingsRoute['gateway'] = 'razorpay';
        if ($request->boolean('embed')) {
            $settingsRoute['embed'] = 1;
        }

        return redirect()
            ->route('admin.settings', $settingsRoute)
            ->with('success', ucfirst($group) . ' settings saved successfully ✅');
    }

    public function deleteGateway(Request $request)
    {
        Setting::set('razorpay_enabled', '0', 'payment-gateways', 'boolean', 'Accept paid course purchases');
        Setting::set('razorpay_gateway_added', '0', 'payment-gateways', 'boolean', 'Razorpay configured');

        return redirect()->route('admin.settings', array_filter(['tab' => 'payment-gateways', 'embed' => $request->boolean('embed') ? 1 : null]))
            ->with('success', 'Razorpay removed from checkout. Saved credentials were kept.');
    }

    // ── Test mail ──────────────────────────────────────────────────────
    /** Toggle a single boolean setting */
    public function toggle(Request $request, string $key)
    {
        $allowed = ['human_verify_login','human_verify_register','allow_registration','require_user_approval','maintenance_mode','otp_email_enabled','otp_phone_enabled','enable_free_courses'];
        if (!in_array($key, $allowed)) {
            return back()->with('error', 'Invalid setting key.');
        }
        $current = \App\Models\Setting::get($key, '0');
        $new     = $current === '1' ? '0' : '1';
        \App\Models\Setting::set($key, $new, 'security', 'boolean', $key);
        $label = $new === '1' ? 'Enabled' : 'Disabled';
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'key' => $key, 'value' => $new, 'label' => $label]);
        }
        return back()->with('success', ucwords(str_replace('_',' ',$key)) . ': ' . $label . ' ✅');
    }

    public function testMail(Request $request)
    {
        if (config('mail.default') !== 'smtp' || blank(config('mail.mailers.smtp.host')) || blank(config('mail.from.address'))) {
            return back()->with('error', 'Save a complete SMTP configuration before sending a test email.');
        }
        $to = $request->input('test_email_addr', auth()->user()->email);
        try {
            \Illuminate\Support\Facades\Mail::raw(
                'This is a test email from Skillspot. If you received this, your SMTP is working! 🎉',
                fn($msg) => $msg->to($to)->subject('Skillspot — SMTP Test')
            );
            Setting::set('mail_invites_verified_at', now()->toIso8601String(), 'mail', 'text', 'Member invites verified at');
            return back()->with('success', "Test email sent to {$to} ✅");
        } catch (\Exception $e) {
            return back()->with('error', 'Mail failed: ' . $e->getMessage());
        }
    }

    // ── Clear cache ────────────────────────────────────────────────────
    public function clearCache()
    {
        Cache::flush();
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Cache cleared successfully ✅']);
        }
        return back()->with('success', 'Cache cleared successfully ✅');
    }

    // ── Run Migrations ──────────────────────────────────────────────────
    public function runMigrate(\Illuminate\Http\Request $request)
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'output' => $output, 'message' => 'Database migrations ran successfully ✅']);
            }
            return back()->with('success', 'Database migrations ran successfully: ' . ($output ?: 'Already up to date.'));
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }

    // ── Run Storage Link ────────────────────────────────────────────────
    public function runStorageLink(\Illuminate\Http\Request $request)
    {
        try {
            $target = storage_path('app/public');
            $link = public_path('storage');
            $output = '';

            if (file_exists($link) || is_link($link)) {
                $output = 'public/storage symlink is already connected.';
            } elseif (function_exists('symlink') && @symlink($target, $link)) {
                $output = 'Symlink successfully created from ' . $target . ' to ' . $link;
            } else {
                $output = 'Symlink cannot be created directly (server disabled exec/symlink). The built-in /storage/{path} fallback route is actively serving your files.';
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'output' => $output, 'message' => 'Storage link verified ✅']);
            }
            return back()->with('success', $output);
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'output' => 'Fallback active', 'message' => 'Files are served via /storage fallback route: ' . $e->getMessage()]);
            }
            return back()->with('info', 'Storage link notice: ' . $e->getMessage());
        }
    }

    // ── Write a key=value pair to .env ─────────────────────────────────
    private function updateEnvKey(string $key, string $value): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) return;

        $env = file_get_contents($envPath);
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $env)) {
            $env = preg_replace($pattern, "{$key}={$value}", $env);
        } else {
            $env .= PHP_EOL . "{$key}={$value}";
        }

        file_put_contents($envPath, $env);
    }
}
