@extends('layouts.admin')
@section('title', 'My Profile — Skillspot.in Admin')
@section('page-title', 'My Profile')
@section('page-sub', 'Manage your account details and password')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold shadow-sm">
  <span class="text-xl flex-shrink-0">✅</span> {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600 text-xl leading-none">×</button>
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-3.5 text-sm font-semibold shadow-sm">
  <span class="text-xl flex-shrink-0">❌</span> {{ session('error') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-600 text-xl leading-none">×</button>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- ── Left: Avatar card ──────────────────────────────────────────── --}}
  <div class="lg:col-span-1">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="bg-gradient-to-br from-brand-600 to-accent-600 px-6 py-10 flex flex-col items-center text-center">
        <div class="w-20 h-20 bg-white/20 rounded-3xl flex items-center justify-center text-white font-black text-4xl mb-4 shadow-lg backdrop-blur-sm border border-white/30">
          {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <h3 class="text-white font-black text-lg">{{ auth()->user()->name }}</h3>
        <p class="text-blue-200 text-sm mt-0.5">{{ auth()->user()->email }}</p>
        <div class="mt-3 flex flex-wrap gap-2 justify-center">
          @foreach(auth()->user()->roles as $role)
          <span class="bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full border border-white/30">
            {{ ucfirst($role->name) }}
          </span>
          @endforeach
        </div>
      </div>
      <div class="p-5 space-y-3">
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-blue-50 rounded-xl flex items-center justify-center text-base flex-shrink-0">📧</span>
          <div class="min-w-0">
            <div class="text-xs text-gray-400">Email</div>
            <div class="font-semibold text-gray-800 truncate">{{ auth()->user()->email }}</div>
          </div>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-purple-50 rounded-xl flex items-center justify-center text-base flex-shrink-0">📱</span>
          <div class="min-w-0">
            <div class="text-xs text-gray-400">Phone</div>
            <div class="font-semibold text-gray-800">{{ auth()->user()->phone ?? '—' }}</div>
          </div>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-green-50 rounded-xl flex items-center justify-center text-base flex-shrink-0">📅</span>
          <div class="min-w-0">
            <div class="text-xs text-gray-400">Member since</div>
            <div class="font-semibold text-gray-800">{{ auth()->user()->created_at->format('d M Y') }}</div>
          </div>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-yellow-50 rounded-xl flex items-center justify-center text-base flex-shrink-0">🔐</span>
          <div class="min-w-0">
            <div class="text-xs text-gray-400">Last login</div>
            <div class="font-semibold text-gray-800">{{ auth()->user()->updated_at->diffForHumans() }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Right: Forms ────────────────────────────────────────────────── --}}
  <div class="lg:col-span-2 space-y-6">

    {{-- ── Update Profile ────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-9 h-9 bg-brand-100 rounded-xl flex items-center justify-center text-lg">👤</div>
        <div>
          <h3 class="font-black text-gray-900 text-sm">Profile Information</h3>
          <p class="text-xs text-gray-400">Update your name, email and phone number</p>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.profile.update') }}" class="p-6 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {{-- Name --}}
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Full Name</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">👤</span>
              <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required
                     class="w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            </div>
            @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- Email --}}
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Email Address</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📧</span>
              <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required
                     class="w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            </div>
            @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- Phone --}}
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Phone Number</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📱</span>
              <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                     placeholder="+91 9876543210"
                     class="w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            </div>
            @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit"
                  class="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white font-bold px-6 py-2.5 rounded-xl transition shadow-md shadow-brand-200 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            Save Profile
          </button>
        </div>
      </form>
    </div>

    {{-- ── Change Password ───────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center text-lg">🔑</div>
        <div>
          <h3 class="font-black text-gray-900 text-sm">Change Password</h3>
          <p class="text-xs text-gray-400">Use a strong password — min. 8 characters</p>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.profile.password') }}" class="p-6 space-y-4" id="pwdForm">
        @csrf
        @method('PUT')

        {{-- Current password --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Current Password</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔒</span>
            <input type="password" name="current_password" id="cpwd" required
                   placeholder="Enter current password"
                   class="w-full pl-10 pr-11 py-3 rounded-xl border {{ $errors->has('current_password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            <button type="button" onclick="togglePwd('cpwd',this)" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition focus:outline-none">👁</button>
          </div>
          @error('current_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {{-- New password --}}
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">New Password</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔑</span>
              <input type="password" name="password" id="npwd" required
                     placeholder="Min. 8 characters"
                     oninput="checkStrength(this.value)"
                     class="w-full pl-10 pr-11 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              <button type="button" onclick="togglePwd('npwd',this)" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition focus:outline-none">👁</button>
            </div>
            <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div id="strengthBar" class="h-full rounded-full transition-all duration-300 w-0"></div>
            </div>
            <div id="strengthText" class="text-xs text-gray-400 mt-1 h-4"></div>
            @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- Confirm password --}}
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Confirm New Password</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔒</span>
              <input type="password" name="password_confirmation" id="cpwd2" required
                     placeholder="Re-enter new password"
                     class="w-full pl-10 pr-11 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              <button type="button" onclick="togglePwd('cpwd2',this)" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition focus:outline-none">👁</button>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit" id="pwdBtn"
                  class="flex items-center gap-2 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 active:scale-[0.98] text-white font-bold px-6 py-2.5 rounded-xl transition shadow-md shadow-red-200 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span id="pwdBtnText">Update Password</span>
            <span id="pwdSpinner" class="hidden"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg></span>
          </button>
        </div>
      </form>
    </div>

    {{-- ── Danger Zone ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-red-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-red-100 bg-red-50/50">
        <div class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center text-lg">⚠️</div>
        <div>
          <h3 class="font-black text-red-700 text-sm">Danger Zone</h3>
          <p class="text-xs text-red-400">Irreversible actions — proceed with caution</p>
        </div>
      </div>
      <div class="p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <div class="font-semibold text-gray-900 text-sm">Sign out of all devices</div>
          <div class="text-xs text-gray-400 mt-0.5">Invalidates all active sessions across all browsers/devices</div>
        </div>
        <form method="POST" action="{{ route('admin.profile.logout-all') }}">
          @csrf
          <button type="submit"
                  onclick="return confirm('Sign out of ALL devices?')"
                  class="flex-shrink-0 flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold px-5 py-2.5 rounded-xl transition text-sm">
            🚪 Sign Out All Devices
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
  function togglePwd(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'text' ? 'password' : 'text';
    btn.textContent = input.type === 'password' ? '👁' : '🙈';
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
    bar.className = 'h-full rounded-full transition-all duration-300 ' + m[s][0] + ' ' + m[s][1];
    txt.textContent = m[s][2];
  }

  document.getElementById('pwdForm').addEventListener('submit', function(e) {
    const np = document.getElementById('npwd').value;
    const cp = document.getElementById('cpwd2').value;
    if (np !== cp) {
      e.preventDefault();
      alert('New passwords do not match!');
      return;
    }
    document.getElementById('pwdBtnText').textContent = 'Updating…';
    document.getElementById('pwdSpinner').classList.remove('hidden');
    document.getElementById('pwdBtn').disabled = true;
  });
</script>
@endpush
