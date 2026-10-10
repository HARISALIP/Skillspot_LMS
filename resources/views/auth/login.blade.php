@extends('layouts.auth')
@section('title', 'Login — Skillspot.in LMS')

@section('top-link')
  <a href="/register" class="text-slate-600 hover:text-brand-600 transition text-sm">No account? <span class="text-brand-600 font-bold">Sign up →</span></a>
@endsection

@section('content')

<!-- Header strip -->
<div class="bg-gradient-to-r from-brand-600 to-accent-600 px-6 py-5 text-center">
  <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white text-xl mx-auto mb-2 backdrop-blur-sm shadow-sm">🔐</div>
  <h1 class="text-xl font-black text-white">Welcome back</h1>
  <p class="text-blue-100 text-xs mt-0.5">Sign in to your Skillspot.in account</p>
</div>

<!-- Form -->
<div class="px-6 py-6">

  @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-2.5 text-xs mb-4 flex items-start gap-2">
      <span class="mt-0.5 flex-shrink-0">⚠️</span>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-2.5 text-xs mb-4">
      @foreach($errors->all() as $e)<div class="flex items-center gap-1.5"><span>•</span>{{ $e }}</div>@endforeach
    </div>
  @endif

  <form method="POST" action="/login" id="loginForm">
    @csrf

    <!-- Email -->
    <div class="mb-4">
      <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Email or Phone Number</label>
      <div class="relative">
        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">👤</span>
        <input type="text" name="login" value="{{ old('login') }}" required autocomplete="username"
               class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-slate-50 focus:bg-white transition @error('login') border-red-400 @enderror"
               placeholder="Email or mobile number" inputmode="email">
      </div>
    </div>

    <!-- Password -->
    <div class="mb-2">
      <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Password</label>
      <div class="relative">
        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔑</span>
        <input type="password" name="password" id="passwordInput" required autocomplete="current-password"
               class="w-full pl-9 pr-10 py-2.5 rounded-xl border border-slate-200 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-slate-50 focus:bg-white transition"
               placeholder="••••••••">
        <button type="button" id="togglePwd" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs transition focus:outline-none">👁</button>
      </div>
    </div>

    <!-- Human Verification -->
    <div class="mt-3">
      <x-human-verify :formKey="'login'"/>
    </div>

    <!-- Remember & Forgot -->
    <div class="flex items-center justify-between mb-5 mt-2.5">
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
        <span class="text-xs text-slate-600">Remember me</span>
      </label>
      <a href="#" class="text-xs text-brand-600 hover:text-brand-700 font-semibold transition">Forgot password?</a>
    </div>

    <!-- Submit -->
    <button type="submit" id="loginBtn"
            class="w-full bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white font-bold py-3 rounded-xl transition-all shadow-md shadow-brand-600/20 text-sm flex items-center justify-center gap-2">
      <span id="btnText">Sign In</span>
      <span id="btnSpinner" class="hidden">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
      </span>
    </button>
  </form>

  <!-- Divider -->
  <div class="flex items-center gap-3 my-4">
    <div class="flex-1 h-px bg-slate-200"></div>
    <span class="text-[11px] text-slate-400 font-medium">OR</span>
    <div class="flex-1 h-px bg-slate-200"></div>
  </div>

  <!-- Sign up link -->
  <p class="text-center text-xs text-slate-500">
    Don't have an account?
    <a href="/register" class="text-brand-600 hover:text-brand-700 font-bold transition">Create one free →</a>
  </p>
</div>

@endsection

@push('scripts')
<script>
  // Toggle password visibility
  document.getElementById('togglePwd').addEventListener('click', function() {
    const input = document.getElementById('passwordInput');
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    this.textContent = isText ? '👁' : '🙈';
  });
  // Spinner on submit
  document.getElementById('loginForm').addEventListener('submit', function() {
    document.getElementById('btnText').textContent = 'Signing in…';
    document.getElementById('btnSpinner').classList.remove('hidden');
    document.getElementById('loginBtn').disabled = true;
  });
</script>
@endpush
