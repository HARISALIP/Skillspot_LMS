@extends('layouts.auth')
@section('title', 'Login — Skillspot.in LMS')
@section('container-class', 'max-w-4xl')

@section('top-link')
  <a href="/register" class="text-slate-600 hover:text-brand-600 transition text-sm">
    No account? <span class="text-brand-600 font-bold">Sign up →</span>
  </a>
@endsection

@section('content')

<div class="lg:grid lg:grid-cols-12 min-h-[460px]">

  <!-- LEFT PANEL: Desktop Visual Branding (Visible on lg+ screens) -->
  <div class="hidden lg:flex lg:col-span-5 bg-gradient-to-br from-brand-600 via-brand-700 to-accent-700 text-white p-7 flex-col justify-between relative overflow-hidden">
    <!-- Ambient glow shapes -->
    <div class="absolute -top-20 -left-20 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-accent-400/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10">
      <!-- Badge -->
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-[11px] font-semibold tracking-wide uppercase text-blue-100 mb-5">
        <i class="fa-solid fa-shield-halved text-amber-300"></i> Secure LMS Portal
      </div>

      <h2 class="text-2xl font-black leading-tight mb-3">
        Welcome Back to Skillspot.in
      </h2>
      <p class="text-blue-100 text-xs leading-relaxed mb-6">
        Continue your learning journey. Access your enrolled courses, live interactive classes, and verified certificates.
      </p>

      <!-- Highlights List -->
      <div class="space-y-3.5">
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/15 backdrop-blur-md flex items-center justify-center text-amber-300 text-xs flex-shrink-0 border border-white/10">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <div>
            <h4 class="font-bold text-xs text-white">Access Enrolled Courses</h4>
            <p class="text-[11px] text-blue-100/80">Resume video lessons & track progress</p>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/15 backdrop-blur-md flex items-center justify-center text-sky-300 text-xs flex-shrink-0 border border-white/10">
            <i class="fa-solid fa-video"></i>
          </div>
          <div>
            <h4 class="font-bold text-xs text-white">Live Classes & Webinars</h4>
            <p class="text-[11px] text-blue-100/80">Join live interactive mentor sessions</p>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/15 backdrop-blur-md flex items-center justify-center text-emerald-300 text-xs flex-shrink-0 border border-white/10">
            <i class="fa-solid fa-certificate"></i>
          </div>
          <div>
            <h4 class="font-bold text-xs text-white">Verified Skill Certs</h4>
            <p class="text-[11px] text-blue-100/80">Download and share earned certificates</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom trust badge -->
    <div class="relative z-10 pt-4 mt-6 border-t border-white/15 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="flex -space-x-1.5">
          <div class="w-6 h-6 rounded-full bg-brand-400 border border-white flex items-center justify-center text-white text-[9px] font-bold">A</div>
          <div class="w-6 h-6 rounded-full bg-purple-500 border border-white flex items-center justify-center text-white text-[9px] font-bold">R</div>
          <div class="w-6 h-6 rounded-full bg-emerald-500 border border-white flex items-center justify-center text-white text-[9px] font-bold">S</div>
        </div>
        <span class="text-[11px] text-white font-medium">10,000+ Active Students</span>
      </div>
      <span class="text-[10px] text-blue-100 font-semibold px-2 py-0.5 rounded-full bg-white/15 border border-white/20">Skillspot.in</span>
    </div>
  </div>

  <!-- RIGHT PANEL: Login Form (Full width on mobile, 7 cols on desktop) -->
  <div class="lg:col-span-7 flex flex-col justify-center">

    <!-- Mobile Header Strip (Visible ONLY on mobile screens < lg) -->
    <div class="lg:hidden bg-gradient-to-r from-brand-600 to-accent-600 px-6 py-4 text-center">
      <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center text-white text-base mx-auto mb-1.5 backdrop-blur-sm shadow-sm">
        <i class="fa-solid fa-lock"></i>
      </div>
      <h1 class="text-lg font-black text-white">Welcome back</h1>
      <p class="text-blue-100 text-xs mt-0.5">Sign in to your Skillspot.in account</p>
    </div>

    <!-- Desktop Title Header (Visible ONLY on lg+ desktop) -->
    <div class="hidden lg:block px-7 pt-6 pb-1">
      <h1 class="text-2xl font-black text-slate-900">Welcome back!</h1>
      <p class="text-slate-500 text-xs mt-0.5">Sign in to access your student dashboard</p>
    </div>

    <!-- Form container -->
    <div class="px-6 py-5 lg:px-7 lg:py-5">

      @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-3.5 py-2 text-xs mb-3 flex items-start gap-2">
          <i class="fa-solid fa-circle-exclamation text-red-600 mt-0.5"></i>
          <span>{{ session('error') }}</span>
        </div>
      @endif

      @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-3.5 py-2 text-xs mb-3">
          @foreach($errors->all() as $e)<div class="flex items-center gap-1.5">• {{ $e }}</div>@endforeach
        </div>
      @endif

      <form method="POST" action="/login" id="loginForm">
        @csrf

        <!-- Email or Phone -->
        <div class="mb-3">
          <label class="block text-[11px] font-semibold text-slate-600 mb-1 uppercase tracking-wide">Email or Phone Number</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"><i class="fa-solid fa-user"></i></span>
            <input type="text" name="login" value="{{ old('login') }}" required autocomplete="username"
                   class="w-full pl-8 pr-3 py-2 rounded-lg border border-slate-200 text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 bg-slate-50 focus:bg-white transition @error('login') border-red-400 @enderror"
                   placeholder="Email or mobile number" inputmode="email">
          </div>
        </div>

        <!-- Password -->
        <div class="mb-3">
          <label class="block text-[11px] font-semibold text-slate-600 mb-1 uppercase tracking-wide">Password</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"><i class="fa-solid fa-key"></i></span>
            <input type="password" name="password" id="passwordInput" required autocomplete="current-password"
                   class="w-full pl-8 pr-8 py-2 rounded-lg border border-slate-200 text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 bg-slate-50 focus:bg-white transition"
                   placeholder="••••••••">
            <button type="button" id="togglePwd" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs transition focus:outline-none"><i class="fa-solid fa-eye"></i></button>
          </div>
        </div>

        <!-- Human Verification -->
        <div class="mb-2.5">
          <x-human-verify :formKey="'login'"/>
        </div>

        <!-- Remember & Forgot -->
        <div class="flex items-center justify-between mb-4 mt-2">
          <label class="flex items-center gap-1.5 cursor-pointer select-none">
            <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            <span class="text-xs text-slate-600">Remember me</span>
          </label>
          <a href="#" class="text-xs text-brand-600 hover:text-brand-700 font-semibold transition">Forgot password?</a>
        </div>

        <!-- Submit -->
        <button type="submit" id="loginBtn"
                class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-700 hover:to-accent-700 active:scale-[0.98] text-white font-bold py-2.5 rounded-xl transition-all shadow-md shadow-brand-600/20 text-xs flex items-center justify-center gap-2">
          <span id="btnText">Sign In →</span>
          <span id="btnSpinner" class="hidden">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
          </span>
        </button>
      </form>

      <!-- Divider -->
      <div class="flex items-center gap-2.5 my-3">
        <div class="flex-1 h-px bg-slate-200"></div>
        <span class="text-[10px] text-slate-400 font-medium">OR</span>
        <div class="flex-1 h-px bg-slate-200"></div>
      </div>

      <!-- Sign up link -->
      <p class="text-center text-xs text-slate-500">
        Don't have an account?
        <a href="/register" class="text-brand-600 hover:text-brand-700 font-bold transition">Create one free →</a>
      </p>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
  // Toggle password visibility
  const toggleBtn = document.getElementById('togglePwd');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
      const input = document.getElementById('passwordInput');
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      const icon = this.querySelector('i');
      if (icon) {
        icon.className = isText ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
      }
    });
  }

  // Spinner on submit
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', function() {
      document.getElementById('btnText').textContent = 'Signing in…';
      document.getElementById('btnSpinner').classList.remove('hidden');
      document.getElementById('loginBtn').disabled = true;
    });
  }
</script>
@endpush
