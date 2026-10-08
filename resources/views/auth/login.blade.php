@extends('layouts.auth')
@section('title', 'Login — Skillspot.in LMS')

@section('top-link')
  <a href="/register" class="text-gray-300 hover:text-white transition text-sm">No account? <span class="text-brand-400 font-semibold">Sign up →</span></a>
@endsection

@section('content')

<!-- Header strip -->
<div class="bg-gradient-to-r from-brand-600 to-accent-600 px-8 py-8 text-center">
  <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-3 backdrop-blur-sm">🔐</div>
  <h1 class="text-2xl font-black text-white">Welcome back</h1>
  <p class="text-blue-200 text-sm mt-1">Sign in to your Skillspot.in account</p>
</div>

<!-- Form -->
<div class="px-7 py-8">

  @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-5 flex items-start gap-2">
      <span class="mt-0.5 flex-shrink-0">⚠️</span>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-5">
      @foreach($errors->all() as $e)<div class="flex items-center gap-1.5"><span>•</span>{{ $e }}</div>@endforeach
    </div>
  @endif

  <form method="POST" action="/login" id="loginForm">
    @csrf

    <!-- Email -->
    <div class="mb-5">
      <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Email or Phone Number</label>
      <div class="relative">
        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base">👤</span>
        <input type="text" name="login" value="{{ old('login') }}" required autocomplete="username"
               class="w-full pl-10 pr-4 py-3.5 rounded-xl border border-gray-200 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-gray-50 focus:bg-white transition @error('login') border-red-400 @enderror"
               placeholder="Email or mobile number" inputmode="email">
      </div>
    </div>

    <!-- Password -->
    <div class="mb-2">
      <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Password</label>
      <div class="relative">
        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base">🔑</span>
        <input type="password" name="password" id="passwordInput" required autocomplete="current-password"
               class="w-full pl-10 pr-12 py-3.5 rounded-xl border border-gray-200 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-gray-50 focus:bg-white transition"
               placeholder="••••••••">
        <button type="button" id="togglePwd" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm transition focus:outline-none">👁</button>
      </div>
    </div>

    <!-- Human Verification -->
    <div class="mt-4">
      <x-human-verify :formKey="'login'"/>
    </div>

    <!-- Remember & Forgot -->
    <div class="flex items-center justify-between mb-6 mt-3">
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
        <span class="text-sm text-gray-600">Remember me</span>
      </label>
      <a href="#" class="text-sm text-brand-600 hover:text-brand-700 font-medium transition">Forgot password?</a>
    </div>

    <!-- Submit -->
    <button type="submit" id="loginBtn"
            class="w-full bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 active:scale-[0.98] text-white font-bold py-4 rounded-2xl transition-all shadow-lg shadow-brand-200 text-sm flex items-center justify-center gap-2">
      <span id="btnText">Sign In</span>
      <span id="btnSpinner" class="hidden">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
      </span>
    </button>
  </form>

  <!-- Divider -->
  <div class="flex items-center gap-3 my-6">
    <div class="flex-1 h-px bg-gray-200"></div>
    <span class="text-xs text-gray-400 font-medium">OR</span>
    <div class="flex-1 h-px bg-gray-200"></div>
  </div>

  <!-- Sign up link -->
  <p class="text-center text-sm text-gray-500">
    Don't have an account?
    <a href="/register" class="text-brand-600 hover:text-brand-700 font-semibold transition">Create one free →</a>
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
