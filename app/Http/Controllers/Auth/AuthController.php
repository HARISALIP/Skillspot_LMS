<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Models\Otp;
use App\Services\SmsOtpService;
use App\Services\HumanVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // ── Show Login ─────────────────────────────────────────────────────
    public function showLogin()
    {
        // Maintenance mode: show maintenance page UNLESS ?admin=1 param present
        if (Setting::get('maintenance_mode', '0') === '1' && !request()->has('admin')) {
            return view('auth.maintenance');
        }
        return view('auth.login');
    }

    // ── Process Login — accept email or phone ──────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Human verification
        $hv = HumanVerifier::verify($request, 'login');
        if (!$hv['pass']) {
            return back()->withErrors(['_hv' => $hv['reason']])->withInput($request->only('login'));
        }

        $login    = trim($request->input('login'));
        $password = $request->input('password');

        // Detect phone number (digits, spaces, +, -, brackets)
        $isPhone = preg_match('/^[\+\d][\d\s\-\(\)]{6,14}$/', $login);

        // Normalize phone — strip +91 prefix for India
        $value = $login;
        $field = 'email';
        if ($isPhone) {
            $field = 'phone';
            $value = preg_replace('/[\s\-\(\)]/', '', $login);
            $value = ltrim($value, '+');
            if (strlen($value) === 12 && str_starts_with($value, '91')) {
                $value = substr($value, 2);
            }
        }

        if (Auth::attempt([$field => $value, 'password' => $password], true)) {
            $request->session()->regenerate();
            $user = Auth::user();

            // During maintenance, only admins can login
            if (Setting::get('maintenance_mode', '0') === '1'
                && !($user->hasRole('super-admin') || $user->hasRole('admin'))) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors(['login' => 'Platform is under maintenance. Only admins can login right now.'])->withInput($request->only('login'));
            }

            // Block vendor_only students from main Skillspot login
            if ($user->hasRole('student') && $user->portal_access === 'vendor_only') {
                $vendors = $user->vendorAccess()->where('vendors.status','active')->get();
                Auth::logout();
                $request->session()->invalidate();
                if ($vendors->count() === 1) {
                    return redirect()->route('vendor.portal.login', $vendors->first()->slug)
                        ->with('success', 'Please login via your institution portal.');
                }
                return back()->withErrors(['login' => 'Your account is not enabled for this portal. Please use your institution portal to login.'])->withInput($request->only('login'));
            }

            return $this->redirectByRole($user);
        }

        $label = $isPhone ? 'phone number' : 'email';
        return back()
            ->withErrors(['login' => "Incorrect {$label} or password. Please try again."])
            ->withInput($request->only('login'));
    }

    // ── Show Register ──────────────────────────────────────────────────
    public function showRegister()
    {
        // Block if maintenance mode
        if (Setting::get('maintenance_mode', '0') === '1') {
            return view('auth.maintenance');
        }
        // Block if registration disabled
        if (Setting::get('allow_registration', '1') === '0') {
            return view('auth.registration-closed');
        }
        return view('auth.register');
    }

    // ── Process Register ───────────────────────────────────────────────
    public function register(Request $request)
    {
        if (Setting::get('maintenance_mode', '0') === '1') {
            return redirect('/')->with('error', 'Platform is under maintenance. Please try again later.');
        }
        if (Setting::get('allow_registration', '1') === '0') {
            return redirect('/')->with('error', 'Registration is currently closed.');
        }
        // Human verification
        $hv = HumanVerifier::verify($request, 'register');
        if (!$hv['pass']) {
            return back()->withErrors(['_hv' => $hv['reason']])->withInput();
        }

        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'phone'        => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'country'      => ['nullable', 'string', 'max:5'],
            'country_name' => ['nullable', 'string', 'max:100'],
            'password'     => ['required', 'confirmed', Password::min(8)],
            'terms'        => ['accepted'],
        ]);

        // Store pending registration in session
        $pending = [
            'name'         => $request->name,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'country'      => $request->input('country', 'IN'),
            'country_name' => $request->input('country_name', 'India'),
            'password'     => Hash::make($request->password),
        ];

        // ── Check Phone OTP (India only — IN country code) ────────────
        $isIndia = $request->input('country', 'IN') === 'IN';
        if ($this->phoneOtpRequired() && !empty($request->phone) && $isIndia) {
            $phone  = SmsOtpService::normalizePhone($request->phone);
            $result = (new SmsOtpService())->sendOtp($phone, 'register');

            if (!$result['success']) {
                return back()
                    ->withInput()
                    ->with('error', 'Could not send SMS OTP: ' . $result['message']);
            }

            $request->session()->put('pending_registration', $pending);
            return back()
                ->withInput()
                ->with('otp_pending', true)
                ->with('otp_type', 'phone')
                ->with('otp_identifier', $phone);
        }

        // ── Check Email OTP ────────────────────────────────────────────
        if ($this->emailOtpRequired()) {
            $request->session()->put('pending_registration', $pending);
            $this->sendEmailOtp($request->email, 'register');
            return back()
                ->withInput()
                ->with('otp_pending', true)
                ->with('otp_type', 'email')
                ->with('otp_identifier', $request->email);
        }

        // ── No OTP — register directly ─────────────────────────────────
        return $this->createUser($pending);
    }

    // ── Verify OTP (email or phone) ────────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'type'       => ['required', 'in:email,phone'],
            'otp'        => ['required', 'digits:6'],
        ]);

        $verified = false;

        if ($request->type === 'phone') {
            $verified = (new SmsOtpService())->verifyOtp($request->identifier, $request->otp, 'register');
        } else {
            $record = Otp::where('identifier', $request->identifier)
                ->where('type', 'email')
                ->where('purpose', 'register')
                ->where('used', false)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if ($record && $record->otp === $request->otp) {
                $record->update(['used' => true]);
                $verified = true;
            }
        }

        if (!$verified) {
            return back()
                ->with('otp_pending', true)
                ->with('otp_type', $request->type)
                ->with('otp_identifier', $request->identifier)
                ->withErrors(['otp' => 'Invalid or expired OTP. Please try again.']);
        }

        $pending = $request->session()->pull('pending_registration');
        if (!$pending) {
            return redirect('/register')->with('error', 'Session expired. Please register again.');
        }

        if ($request->type === 'email') {
            $pending['email_verified_at'] = now();
        } elseif ($request->type === 'phone') {
            $pending['phone_verified_at'] = now()->toDateTimeString();
        }

        return $this->createUser($pending);
    }

    // ── Resend OTP ─────────────────────────────────────────────────────
    public function resendOtp(Request $request)
    {
        $pending = $request->session()->get('pending_registration');
        if (!$pending) {
            return redirect('/register')->with('error', 'Session expired. Please register again.');
        }

        $type = session('otp_type', 'email');

        if ($type === 'phone' && !empty($pending['phone'])) {
            $phone  = SmsOtpService::normalizePhone($pending['phone']);
            $result = (new SmsOtpService())->sendOtp($phone, 'register');
            $msg    = $result['success'] ? "OTP resent to {$phone}" : 'Failed: ' . $result['message'];
            return back()
                ->with('otp_pending', true)
                ->with('otp_type', 'phone')
                ->with('otp_identifier', $phone)
                ->with($result['success'] ? 'success' : 'error', $msg);
        }

        $this->sendEmailOtp($pending['email'], 'register');
        return back()
            ->with('otp_pending', true)
            ->with('otp_type', 'email')
            ->with('otp_identifier', $pending['email'])
            ->with('success', 'OTP resent to ' . $pending['email']);
    }

    // ── Logout ─────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'You have been logged out.');
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function phoneOtpRequired(): bool
    {
        return Setting::get('otp_phone_enabled', '0') === '1'
            && SmsOtpService::isReady();
    }

    private function emailOtpRequired(): bool
    {
        $smtpReady = !empty(Setting::get('mail_host'))
                  && !empty(Setting::get('mail_username'));
        return Setting::get('otp_email_enabled', '0') === '1' && $smtpReady;
    }

    private function sendEmailOtp(string $email, string $purpose): void
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::where('identifier', $email)
           ->where('type', 'email')
           ->where('purpose', $purpose)
           ->update(['used' => true]);

        Otp::create([
            'identifier' => $email,
            'type'       => 'email',
            'purpose'    => $purpose,
            'otp'        => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        $siteName = Setting::get('site_name', 'Skillspot');

        Mail::send([], [], function ($msg) use ($email, $code, $siteName) {
            $msg->to($email)
                ->subject("{$siteName} — Your OTP: {$code}")
                ->html("
                  <div style='font-family:Inter,sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#f8fafc;'>
                    <div style='background:white;border-radius:20px;padding:32px;box-shadow:0 4px 24px rgba(0,0,0,.06);'>
                      <h2 style='color:#1e3a8a;margin:0 0 4px;font-size:22px;'>{$siteName}</h2>
                      <p style='color:#6b7280;margin:0 0 24px;font-size:14px;'>Your email verification code:</p>
                      <div style='background:#eff6ff;border:2px dashed #2563eb;border-radius:16px;padding:28px;text-align:center;margin-bottom:20px;'>
                        <span style='font-size:44px;font-weight:900;letter-spacing:14px;color:#2563eb;font-family:monospace;'>{$code}</span>
                      </div>
                      <p style='color:#9ca3af;font-size:13px;margin:0;'>⏱ Expires in <strong>10 minutes</strong>. Do not share this code with anyone.</p>
                    </div>
                  </div>
                ");
        });
    }

    private function createUser(array $data)
    {
        $user = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'phone'              => $data['phone'] ?? null,
            'country'            => $data['country'] ?? 'IN',
            'country_name'       => $data['country_name'] ?? 'India',
            'password'           => $data['password'],
            'email_verified_at'  => $data['email_verified_at'] ?? null,
            'registered_via'     => 'direct',
        ]);

        $user->assignRole('student');
        Auth::login($user, true);

        return redirect()->route('student.dashboard')
            ->with('success', 'Welcome to Skillspot! 🎉');
    }

    private function redirectByRole(User $user)
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            if (Setting::get('site_email', '') === '') {
                return redirect()->route('admin.settings')
                    ->with('success', 'Welcome! Please configure your academy settings first. 👋');
            }
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('teacher')) {
            $teacherVendors = \App\Models\Vendor::whereHas('teachers', fn($q) => $q->where('user_id', $user->id))
                ->where('status','active')->get();
            if ($teacherVendors->isEmpty()) {
                Auth::logout();
                request()->session()->invalidate();
                return redirect('/login')->withErrors([
                    'login' => 'Your teacher account is not assigned to any active vendor. Contact admin.'
                ]);
            }
            if ($teacherVendors->count() === 1) {
                session(['active_vendor_id' => $teacherVendors->first()->id]);
            }
            return redirect()->route('vendor.dashboard');
        }

        if ($user->hasRole('vendor')) {
            // Check via pivot table — user may manage multiple vendors
            $vendors = \App\Models\Vendor::whereHas('managers', fn($q) => $q->where('user_id', $user->id))
                ->where('status','active')
                ->get();

            if ($vendors->isEmpty()) {
                Auth::logout();
                request()->session()->invalidate();
                return redirect('/login')->withErrors([
                    'login' => 'Your vendor account is not set up or is inactive. Please contact admin.'
                ]);
            }

            // Single vendor: set in session, go direct
            if ($vendors->count() === 1) {
                session(['active_vendor_id' => $vendors->first()->id]);
                return redirect()->route('vendor.dashboard');
            }

            // Multiple vendors: show picker
            return redirect()->route('vendor.pick');
}

        // ── Student routing by portal_access ──────────────────────────
        $access       = $user->portal_access ?? 'Skillspot_only';
        $vendorAccess = $user->vendorAccess()->where('vendors.status','active')->get();

        if ($access === 'vendor_only') {
            // No Skillspot access — must go to a vendor portal
            if ($vendorAccess->count() === 1) {
                return redirect()->route('vendor.portal.dashboard', $vendorAccess->first()->slug)
                    ->with('success', 'Welcome back, ' . $user->name . '! 👋');
            }
            if ($vendorAccess->count() > 1) {
                // Multiple vendor portals — show picker
                return redirect()->route('student.vendor.pick');
            }
            // Fallback — no vendor assigned, go Skillspot
            return redirect()->route('student.dashboard');
        }

        if ($access === 'both') {
            // Has Skillspot + vendor(s): go Skillspot dashboard (can switch portals from there)
            return redirect()->route('student.dashboard');
        }

        // Skillspot_only
        return redirect()->route('student.dashboard');
    }
}
