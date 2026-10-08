<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    private array $schema = [
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
            'razorpay_mode'           => ['label' => 'Mode (live / test)',            'type' => 'text',     'default' => 'live'],
            'razorpay_key_id'         => ['label' => 'Live Key ID',                   'type' => 'text',     'default' => ''],
            'razorpay_key_secret'     => ['label' => 'Live Key Secret',               'type' => 'password', 'default' => ''],
            'razorpay_webhook_secret' => ['label' => 'Live Webhook Secret',           'type' => 'password', 'default' => ''],
            'razorpay_test_key_id'    => ['label' => 'Test Key ID',                   'type' => 'text',     'default' => ''],
            'razorpay_test_key_secret'=> ['label' => 'Test Key Secret',               'type' => 'password', 'default' => ''],
            'currency'                => ['label' => 'Currency Code',                 'type' => 'text',     'default' => 'INR'],
            'currency_symbol'         => ['label' => 'Currency Symbol',               'type' => 'text',     'default' => '₹'],
            'enable_free_courses'     => ['label' => 'Enable Free Courses',           'type' => 'boolean',  'default' => '1'],
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
        $activeTab = $request->get('tab', 'branding');
        $settings  = Setting::allKeyed();
        $schema    = $this->schema;
        return view('admin.settings', compact('activeTab', 'settings', 'schema'));
    }

    // ── Save a group ───────────────────────────────────────────────────
    public function save(Request $request, string $group)
    {
        if (!array_key_exists($group, $this->schema)) {
            return back()->with('error', 'Invalid settings group.');
        }

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

        // Special: sync app_url back to .env APP_URL so artisan commands work too
        if ($group === 'branding' && $request->filled('app_url')) {
            $this->updateEnvKey('APP_URL', rtrim($request->input('app_url'), '/'));
            $this->updateEnvKey('APP_NAME', '"' . $request->input('site_name', 'Skillspot') . '"');
        }

        // Sync mail to .env so queue/jobs pick it up
        if ($group === 'mail') {
            if ($request->filled('mail_host'))         $this->updateEnvKey('MAIL_HOST',         $request->input('mail_host'));
            if ($request->filled('mail_port'))         $this->updateEnvKey('MAIL_PORT',         $request->input('mail_port'));
            if ($request->filled('mail_username'))     $this->updateEnvKey('MAIL_USERNAME',     $request->input('mail_username'));
            if ($request->filled('mail_password'))     $this->updateEnvKey('MAIL_PASSWORD',     $request->input('mail_password'));
            if ($request->filled('mail_encryption'))   $this->updateEnvKey('MAIL_SCHEME',       $request->input('mail_encryption'));
            if ($request->filled('mail_from_address')) $this->updateEnvKey('MAIL_FROM_ADDRESS', $request->input('mail_from_address'));
            if ($request->filled('mail_from_name'))    $this->updateEnvKey('MAIL_FROM_NAME',    '"' . $request->input('mail_from_name') . '"');
        }

        Cache::flush();
        Artisan::call('config:clear');

        return redirect()
            ->route('admin.settings', ['tab' => $group])
            ->with('success', ucfirst($group) . ' settings saved successfully ✅');
    }

    // ── Test mail ──────────────────────────────────────────────────────
    /** Toggle a single boolean setting */
    public function toggle(Request $request, string $key)
    {
        $allowed = ['human_verify_login','human_verify_register','allow_registration','maintenance_mode','otp_email_enabled','otp_phone_enabled','enable_free_courses'];
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
        $to = $request->input('test_email_addr', auth()->user()->email);
        try {
            \Illuminate\Support\Facades\Mail::raw(
                'This is a test email from Skillspot. If you received this, your SMTP is working! 🎉',
                fn($msg) => $msg->to($to)->subject('Skillspot — SMTP Test')
            );
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
