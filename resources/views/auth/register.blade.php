@extends('layouts.auth')
@section('title', 'Join Skillspot.in')
@section('container-class', 'max-w-5xl')

@section('top-link')
  <a href="/login" class="text-slate-600 hover:text-brand-600 transition text-sm">
    Have an account? <span class="text-brand-600 font-bold">Sign in →</span>
  </a>
@endsection

@section('content')

@php
  $otpPending    = session('otp_pending', false);
  $otpType       = session('otp_type', 'email');
  $otpIdentifier = session('otp_identifier', '');

  // Countries list — code => [name, dial_code, flag, india_only_otp]
  $countries = [
    'IN' => ['name'=>'India',          'dial'=>'+91',  'flag'=>'🇮🇳', 'otp'=>true],
    'US' => ['name'=>'United States',  'dial'=>'+1',   'flag'=>'🇺🇸', 'otp'=>false],
    'GB' => ['name'=>'United Kingdom', 'dial'=>'+44',  'flag'=>'🇬🇧', 'otp'=>false],
    'AE' => ['name'=>'UAE',            'dial'=>'+971', 'flag'=>'🇦🇪', 'otp'=>false],
    'SA' => ['name'=>'Saudi Arabia',   'dial'=>'+966', 'flag'=>'🇸🇦', 'otp'=>false],
    'QA' => ['name'=>'Qatar',          'dial'=>'+974', 'flag'=>'🇶🇦', 'otp'=>false],
    'KW' => ['name'=>'Kuwait',         'dial'=>'+965', 'flag'=>'🇰🇼', 'otp'=>false],
    'BH' => ['name'=>'Bahrain',        'dial'=>'+973', 'flag'=>'🇧🇭', 'otp'=>false],
    'OM' => ['name'=>'Oman',           'dial'=>'+968', 'flag'=>'🇴🇲', 'otp'=>false],
    'SG' => ['name'=>'Singapore',      'dial'=>'+65',  'flag'=>'🇸🇬', 'otp'=>false],
    'MY' => ['name'=>'Malaysia',       'dial'=>'+60',  'flag'=>'🇲🇾', 'otp'=>false],
    'AU' => ['name'=>'Australia',      'dial'=>'+61',  'flag'=>'🇦🇺', 'otp'=>false],
    'CA' => ['name'=>'Canada',         'dial'=>'+1',   'flag'=>'🇨🇦', 'otp'=>false],
    'DE' => ['name'=>'Germany',        'dial'=>'+49',  'flag'=>'🇩🇪', 'otp'=>false],
    'FR' => ['name'=>'France',         'dial'=>'+33',  'flag'=>'🇫🇷', 'otp'=>false],
    'NP' => ['name'=>'Nepal',          'dial'=>'+977', 'flag'=>'🇳🇵', 'otp'=>false],
    'LK' => ['name'=>'Sri Lanka',      'dial'=>'+94',  'flag'=>'🇱🇰', 'otp'=>false],
    'BD' => ['name'=>'Bangladesh',     'dial'=>'+880', 'flag'=>'🇧🇩', 'otp'=>false],
    'PK' => ['name'=>'Pakistan',       'dial'=>'+92',  'flag'=>'🇵🇰', 'otp'=>false],
    'NG' => ['name'=>'Nigeria',        'dial'=>'+234', 'flag'=>'🇳🇬', 'otp'=>false],
    'ZA' => ['name'=>'South Africa',   'dial'=>'+27',  'flag'=>'🇿🇦', 'otp'=>false],
    'OTHER'=>['name'=>'Other',         'dial'=>'',     'flag'=>'🌍', 'otp'=>false],
  ];

  $selectedCountry = old('country', 'IN');
  $smsEnabled      = \App\Models\Setting::get('otp_phone_enabled','0') === '1'
                     && \App\Services\SmsOtpService::isReady();
@endphp

<div class="lg:grid lg:grid-cols-12 min-h-[580px]">

  <!-- LEFT PANEL: Desktop Visual Branding & Platform Highlights (Visible on lg+ screens) -->
  <div class="hidden lg:flex lg:col-span-5 bg-gradient-to-br from-brand-600 via-brand-700 to-accent-700 text-white p-8 lg:p-10 flex-col justify-between relative overflow-hidden">
    <!-- Ambient glow shapes -->
    <div class="absolute -top-20 -left-20 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-accent-400/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10">
      <!-- Badge -->
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-xs font-semibold tracking-wide uppercase text-blue-100 mb-6">
        <i class="fa-solid fa-sparkles text-amber-300"></i> Skillspot.in Academy
      </div>

      <h2 class="text-3xl font-black leading-tight mb-4">
        Unlock Your Full Learning Potential
      </h2>
      <p class="text-blue-100 text-sm leading-relaxed mb-8">
        Join thousands of students and instructors on the premier LMS platform. Master new skills, earn certificates, and advance your career today.
      </p>

      <!-- Feature List -->
      <div class="space-y-4">
        <div class="flex items-start gap-3.5">
          <div class="w-9 h-9 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center text-amber-300 text-sm flex-shrink-0 shadow-sm border border-white/10">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <div>
            <h4 class="font-bold text-sm text-white">500+ Premium Courses</h4>
            <p class="text-xs text-blue-100/80">Interactive video lessons & hands-on projects</p>
          </div>
        </div>

        <div class="flex items-start gap-3.5">
          <div class="w-9 h-9 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center text-emerald-300 text-sm flex-shrink-0 shadow-sm border border-white/10">
            <i class="fa-solid fa-certificate"></i>
          </div>
          <div>
            <h4 class="font-bold text-sm text-white">Verified Certificates</h4>
            <p class="text-xs text-blue-100/80">Earn shareable certificates upon completing courses</p>
          </div>
        </div>

        <div class="flex items-start gap-3.5">
          <div class="w-9 h-9 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center text-sky-300 text-sm flex-shrink-0 shadow-sm border border-white/10">
            <i class="fa-solid fa-mobile-screen"></i>
          </div>
          <div>
            <h4 class="font-bold text-sm text-white">Learn Anywhere</h4>
            <p class="text-xs text-blue-100/80">Seamlessly sync progress on desktop & mobile</p>
          </div>
        </div>

        <div class="flex items-start gap-3.5">
          <div class="w-9 h-9 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center text-purple-300 text-sm flex-shrink-0 shadow-sm border border-white/10">
            <i class="fa-solid fa-users"></i>
          </div>
          <div>
            <h4 class="font-bold text-sm text-white">Active Community</h4>
            <p class="text-xs text-blue-100/80">Connect with peers & expert mentors</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Testimonial / Trust stats -->
    <div class="relative z-10 pt-6 mt-8 border-t border-white/15 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="flex -space-x-2">
          <div class="w-8 h-8 rounded-full bg-brand-400 border-2 border-white flex items-center justify-center text-white text-xs font-bold shadow-sm">A</div>
          <div class="w-8 h-8 rounded-full bg-purple-500 border-2 border-white flex items-center justify-center text-white text-xs font-bold shadow-sm">R</div>
          <div class="w-8 h-8 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center text-white text-xs font-bold shadow-sm">S</div>
        </div>
        <div class="text-xs">
          <div class="flex text-amber-300 text-[10px] gap-0.5">
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
          </div>
          <span class="text-white font-semibold">10,000+ Students</span>
        </div>
      </div>
      <span class="text-xs text-blue-100 font-semibold px-2.5 py-1 rounded-full bg-white/15 border border-white/20 backdrop-blur-sm">100% Free</span>
    </div>
  </div>

  <!-- RIGHT PANEL: Registration Form & OTP Form (Full width on mobile, 7 cols on desktop) -->
  <div class="lg:col-span-7 flex flex-col justify-center">

    <!-- Mobile Header (Visible ONLY on mobile/tablet screens < lg) -->
    <div class="lg:hidden bg-gradient-to-r from-brand-600 to-accent-600 px-6 py-5 text-center">
      @if($otpPending)
        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white text-lg mx-auto mb-2 backdrop-blur-sm shadow-sm">
          <i class="fa-solid {{ $otpType === 'phone' ? 'fa-mobile-screen' : 'fa-envelope' }}"></i>
        </div>
        <h1 class="text-lg font-black text-white">Verify Your {{ $otpType === 'phone' ? 'Phone' : 'Email' }}</h1>
        <p class="text-blue-100 text-xs mt-0.5">Enter the 6-digit code we sent</p>
      @else
        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white text-lg mx-auto mb-2 backdrop-blur-sm shadow-sm">
          <i class="fa-solid fa-rocket"></i>
        </div>
        <h1 class="text-xl font-black text-white">Join Skillspot.in Academy</h1>
        <p class="text-blue-100 text-xs mt-0.5">Create your free student account</p>
      @endif
    </div>

    <!-- Desktop Title Header (Visible ONLY on lg+ desktop) -->
    <div class="hidden lg:block px-8 pt-8 pb-2">
      @if($otpPending)
        <h1 class="text-2xl font-black text-slate-900">Verify Your {{ $otpType === 'phone' ? 'Phone' : 'Email' }}</h1>
        <p class="text-slate-500 text-sm mt-1">Enter the 6-digit code sent to {{ $otpIdentifier }}</p>
      @else
        <h1 class="text-2xl font-black text-slate-900">Create Your Student Account</h1>
        <p class="text-slate-500 text-sm mt-1">Fill in the details below to join Skillspot.in Academy</p>
      @endif
    </div>

    <div class="px-6 py-6 lg:px-8 lg:py-6">

      {{-- Alerts --}}
      @if($errors->any())
      <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-2.5 text-xs mb-4">
        @foreach($errors->all() as $e)<div class="flex items-center gap-1.5">• {{ $e }}</div>@endforeach
      </div>
      @endif
      @if(session('success'))
      <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-2.5 text-xs mb-4 flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-green-600"></i> {{ session('success') }}
      </div>
      @endif
      @if(session('error'))
      <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-2.5 text-xs mb-4 flex items-center gap-2">
        <i class="fa-solid fa-circle-xmark text-red-600"></i> {{ session('error') }}
      </div>
      @endif

      {{-- ══ OTP STEP ══ --}}
      @if($otpPending)

      <div class="bg-brand-50 border border-brand-200 rounded-2xl p-4 mb-6 text-xs text-brand-700 flex items-start gap-3">
        <span class="text-base flex-shrink-0"><i class="fa-solid {{ $otpType === 'phone' ? 'fa-mobile-screen' : 'fa-envelope' }}"></i></span>
        <div>
          A 6-digit code was sent to
          <strong>{{ $otpIdentifier }}</strong>.
          Valid for <strong>10 minutes</strong>.
        </div>
      </div>

      <form method="POST" action="{{ route('register.verify-otp') }}" id="otpForm">
        @csrf
        <input type="hidden" name="identifier" value="{{ $otpIdentifier }}">
        <input type="hidden" name="type"       value="{{ $otpType }}">
        <input type="hidden" name="otp"        id="otpValue">

        <label class="block text-xs font-semibold text-slate-500 mb-3 uppercase tracking-wide text-center">Enter 6-Digit Code</label>

        <div class="flex gap-2 justify-center mb-6">
          @for($i = 0; $i < 6; $i++)
          <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="{{ $i }}"
                 class="otp-box w-10 h-11 sm:w-11 sm:h-12 text-center text-xl font-black border-2 border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:border-brand-500 transition">
          @endfor
        </div>

        <button type="submit" id="otpBtn"
                class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-700 hover:to-accent-700 active:scale-[0.98] text-white font-bold py-3 rounded-xl transition shadow-md text-sm flex items-center justify-center gap-2">
          <span id="otpBtnText">Verify & Continue →</span>
          <span id="otpSpinner" class="hidden"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg></span>
        </button>
      </form>

      <div class="text-center mt-5 flex flex-col items-center gap-2">
        <span class="text-xs text-slate-400">Didn't receive the code?</span>
        <form method="POST" action="{{ route('register.resend-otp') }}" class="inline">
          @csrf
          <button type="submit" class="text-xs text-brand-600 font-bold hover:text-brand-700 hover:underline transition"><i class="fa-solid fa-rotate-right"></i> Resend OTP</button>
        </form>
        <a href="/register" class="text-xs text-slate-400 hover:text-slate-600 transition">← Back to registration</a>
      </div>

      {{-- ══ REGISTER FORM ══ --}}
      @else

      <form method="POST" action="/register" id="registerForm" autocomplete="off">
        @csrf

        {{-- Name --}}
        <div class="mb-3.5">
          <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Full Name</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-user"></i></span>
            <input type="text" name="name" value="{{ old('name') }}" required autocomplete="name"
                   placeholder="Your full name"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50 focus:bg-white' }} text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
        </div>

        {{-- Email --}}
        <div class="mb-3.5">
          <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Email Address</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="you@example.com"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50 focus:bg-white' }} text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
        </div>

        {{-- Country selector --}}
        <div class="mb-3.5">
          <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Country</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-base pointer-events-none" id="flagEmoji">🇮🇳</span>
            <select name="country" id="countrySelect" onchange="onCountryChange(this)"
                    class="w-full pl-9 pr-8 py-2.5 rounded-xl border {{ $errors->has('country') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50 focus:bg-white' }} text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition appearance-none cursor-pointer">
              @foreach($countries as $code => $info)
              <option value="{{ $code }}"
                      data-dial="{{ $info['dial'] }}"
                      data-flag="{{ $info['flag'] }}"
                      data-otp="{{ $info['otp'] ? '1' : '0' }}"
                      {{ $selectedCountry === $code ? 'selected' : '' }}>
                {{ $info['name'] }}
              </option>
              @endforeach
            </select>
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"><i class="fa-solid fa-chevron-down"></i></span>
          </div>
        </div>

        {{-- Phone --}}
        <div class="mb-3.5">
          <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">
            Phone Number
            <span id="phoneOtpNote" class="text-brand-600 font-bold normal-case ml-1 {{ ($smsEnabled && $selectedCountry === 'IN') ? '' : 'hidden' }}">— SMS OTP required</span>
          </label>
          <div class="flex gap-2">
            {{-- Dial code badge --}}
            <div id="dialBadge" class="flex-shrink-0 flex items-center justify-center px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-700 text-sm font-mono font-semibold min-w-[60px] text-center">
              +91
            </div>
            <input type="tel" name="phone" id="phoneInput" value="{{ old('phone') }}" autocomplete="tel"
                   placeholder="9876543210"
                   class="flex-1 px-4 py-2.5 rounded-xl border {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50 focus:bg-white' }} text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          {{-- India OTP note --}}
          <div id="indiaOtpInfo" class="{{ ($smsEnabled && $selectedCountry === 'IN') ? '' : 'hidden' }} mt-1.5 flex items-center gap-1.5 text-xs text-brand-600">
            <i class="fa-solid fa-mobile-screen"></i> Indian numbers receive an SMS OTP for verification
          </div>
          {{-- Non-India note --}}
          <div id="otherCountryInfo" class="{{ ($smsEnabled && $selectedCountry !== 'IN') ? '' : 'hidden' }} mt-1.5 flex items-center gap-1.5 text-xs text-slate-400">
            <i class="fa-solid fa-circle-info"></i> Phone OTP not required for your region
          </div>
        </div>

        {{-- Hidden country name --}}
        <input type="hidden" name="country_name" id="countryName" value="{{ $countries[$selectedCountry]['name'] ?? 'India' }}">

        {{-- Password --}}
        <div class="mb-3.5">
          <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Password</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-key"></i></span>
            <input type="password" name="password" id="pwd1" required autocomplete="new-password"
                   placeholder="Min. 8 characters" oninput="checkStrength(this.value)"
                   class="w-full pl-9 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            <button type="button" onclick="togglePwd('pwd1',this)" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition focus:outline-none text-xs"><i class="fa-solid fa-eye"></i></button>
          </div>
          <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div id="strengthBar" class="h-full rounded-full transition-all duration-300 w-0"></div>
          </div>
          <div id="strengthText" class="text-xs text-slate-400 mt-1 h-4"></div>
        </div>

        {{-- Confirm Password --}}
        <div class="mb-4">
          <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Confirm Password</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password_confirmation" id="pwd2" required
                   placeholder="Re-enter password"
                   class="w-full pl-9 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            <button type="button" onclick="togglePwd('pwd2',this)" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition focus:outline-none text-xs"><i class="fa-solid fa-eye"></i></button>
          </div>
        </div>

        {{-- Human Verification --}}
        <div class="mb-4">
          <x-human-verify :formKey="'register'"/>
        </div>

        {{-- Terms --}}
        <label class="flex items-start gap-2.5 mb-5 cursor-pointer select-none">
          <input type="checkbox" name="terms" required class="w-4 h-4 mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500 flex-shrink-0">
          <span class="text-xs text-slate-600 leading-relaxed">
            I agree to the <a href="/terms" class="text-brand-600 font-semibold hover:underline">Terms</a> and
            <a href="/privacy" class="text-brand-600 font-semibold hover:underline">Privacy Policy</a>
          </span>
        </label>

        {{-- Submit --}}
        <button type="submit" id="regBtn"
                class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-700 hover:to-accent-700 active:scale-[0.98] text-white font-bold py-3 rounded-xl transition-all shadow-md shadow-brand-600/20 text-sm flex items-center justify-center gap-2">
          <span id="regBtnText">Create My Account →</span>
          <span id="regSpinner" class="hidden">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
          </span>
        </button>
      </form>

      <div class="flex items-center gap-3 my-4">
        <div class="flex-1 h-px bg-slate-200"></div>
        <span class="text-[11px] text-slate-400 font-medium">OR</span>
        <div class="flex-1 h-px bg-slate-200"></div>
      </div>
      <p class="text-center text-xs text-slate-500">
        Already have an account?
        <a href="/login" class="text-brand-600 hover:text-brand-700 font-bold transition">Sign in →</a>
      </p>

      @endif
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
  // Country data from PHP
  @php
    $jsCountries = [];
    foreach($countries as $code => $info) {
      $jsCountries[] = ['code'=>$code,'dial'=>$info['dial'],'flag'=>$info['flag'],'otp'=>$info['otp']];
    }
  @endphp
  const countryData = @json($jsCountries);
  const smsEnabled  = {{ $smsEnabled ? 'true' : 'false' }};

  function onCountryChange(sel) {
    const code   = sel.value;
    const opt    = sel.options[sel.selectedIndex];
    const dial   = opt.dataset.dial  || '';
    const flag   = opt.dataset.flag  || '🌍';
    const isOtp  = opt.dataset.otp   === '1';

    // Update flag emoji
    document.getElementById('flagEmoji').textContent = flag;

    // Update dial code badge
    const badge = document.getElementById('dialBadge');
    badge.textContent = dial || '—';
    badge.classList.toggle('hidden', !dial);

    // Update country name hidden
    document.getElementById('countryName').value = opt.text.trim();

    // OTP notes
    if (smsEnabled) {
      const isIndia      = code === 'IN';
      document.getElementById('indiaOtpInfo').classList.toggle('hidden', !isIndia);
      document.getElementById('otherCountryInfo').classList.toggle('hidden', isIndia);
      document.getElementById('phoneOtpNote').classList.toggle('hidden', !isIndia);
    }
  }

  // Init on load
  window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('countrySelect');
    if (sel) onCountryChange(sel);

    // OTP boxes
    const boxes = document.querySelectorAll('.otp-box');
    boxes.forEach((box, idx) => {
      box.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g,'').slice(-1);
        if (this.value && idx < 5) boxes[idx+1].focus();
        collectOtp();
      });
      box.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && idx > 0) boxes[idx-1].focus();
      });
      box.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        paste.split('').forEach((ch,i)=>{ if(boxes[i]) boxes[i].value=ch; });
        boxes[Math.min(paste.length,5)]?.focus();
        collectOtp();
      });
    });
    if (boxes.length) boxes[0].focus();
  });

  function collectOtp() {
    const val = document.getElementById('otpValue');
    if (val) val.value = [...document.querySelectorAll('.otp-box')].map(b=>b.value).join('');
  }

  function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'text') {
      input.type = 'password';
      if (icon) icon.className = 'fa-solid fa-eye';
    } else {
      input.type = 'text';
      if (icon) icon.className = 'fa-solid fa-eye-slash';
    }
  }

  function checkStrength(val) {
    const bar = document.getElementById('strengthBar');
    const txt = document.getElementById('strengthText');
    let s = 0;
    if (val.length >= 8)          s++;
    if (/[A-Z]/.test(val))        s++;
    if (/[0-9]/.test(val))        s++;
    if (/[^A-Za-z0-9]/.test(val)) s++;
    const m = {
      0:['w-0','',''],
      1:['w-1/4','bg-red-400','Weak 😟'],
      2:['w-2/4','bg-yellow-400','Fair 😐'],
      3:['w-3/4','bg-blue-400','Good 👍'],
      4:['w-full','bg-green-500','Strong 💪']
    };
    bar.className = 'h-full rounded-full transition-all duration-300 '+m[s][0]+' '+m[s][1];
    txt.textContent = m[s][2];
  }

  // Register submit
  const regForm = document.getElementById('registerForm');
  if (regForm) {
    regForm.addEventListener('submit', function(e) {
      if (document.getElementById('pwd1').value !== document.getElementById('pwd2').value) {
        e.preventDefault(); alert('Passwords do not match!'); return;
      }
      document.getElementById('regBtnText').textContent = 'Creating account…';
      document.getElementById('regSpinner').classList.remove('hidden');
      document.getElementById('regBtn').disabled = true;
    });
  }

  // OTP submit
  const otpForm = document.getElementById('otpForm');
  if (otpForm) {
    otpForm.addEventListener('submit', function() {
      collectOtp();
      document.getElementById('otpBtnText').textContent = 'Verifying…';
      document.getElementById('otpSpinner').classList.remove('hidden');
      document.getElementById('otpBtn').disabled = true;
    });
  }
</script>
@endpush
