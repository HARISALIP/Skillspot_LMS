@extends('layouts.student')
@section('title','Profile — Skillspot.in')
@section('page-title','My Profile')
@section('page-sub','Manage your account details')

@section('student-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ✅ {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-xl">×</button>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Avatar card -->
  <div class="lg:col-span-1">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="bg-gradient-to-br from-brand-600 to-accent-600 px-6 py-10 flex flex-col items-center text-center">
        <div class="w-20 h-20 bg-white/20 rounded-3xl flex items-center justify-center text-white font-black text-4xl mb-4 border border-white/30">
          {{ strtoupper(substr($user->name,0,1)) }}
        </div>
        <h3 class="text-white font-black text-lg">{{ $user->name }}</h3>
        <p class="text-blue-200 text-sm mt-0.5">{{ $user->email }}</p>
        <span class="mt-2 text-xs bg-white/20 text-white px-3 py-1 rounded-full font-semibold capitalize">Student</span>
      </div>
      <div class="p-5 space-y-3">
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">📧</span>
          <div><div class="text-xs text-gray-400">Email</div><div class="font-semibold text-gray-800 text-sm">{{ $user->email }}</div></div>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">📱</span>
          <div><div class="text-xs text-gray-400">Phone</div><div class="font-semibold text-gray-800 text-sm">{{ $user->phone ?? '—' }}</div></div>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">🌍</span>
          <div><div class="text-xs text-gray-400">Country</div><div class="font-semibold text-gray-800 text-sm">{{ $user->country_name ?? 'India' }}</div></div>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-yellow-50 rounded-lg flex items-center justify-center">📅</span>
          <div><div class="text-xs text-gray-400">Member since</div><div class="font-semibold text-gray-800 text-sm">{{ $user->created_at?->format('d M Y') }}</div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Forms -->
  <div class="lg:col-span-2 space-y-5">

    <!-- Update profile -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-9 h-9 bg-brand-100 rounded-xl flex items-center justify-center">👤</div>
        <div><h3 class="font-black text-gray-900 text-sm">Profile Information</h3></div>
      </div>
      <form method="POST" action="{{ route('student.profile.update') }}" class="p-6 space-y-4">
        @csrf @method('PUT')
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Full Name</label>
          <input type="text" name="name" value="{{ old('name',$user->name) }}" required
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Phone Number</label>
          <input type="tel" name="phone" value="{{ old('phone',$user->phone) }}" placeholder="+91 9876543210"
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end">
          <button type="submit" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            Save Profile
          </button>
        </div>
      </form>
    </div>

    <!-- Change password -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center">🔑</div>
        <div><h3 class="font-black text-gray-900 text-sm">Change Password</h3></div>
      </div>
      <form method="POST" action="{{ route('student.profile.password') }}" class="p-6 space-y-4" id="pwdForm">
        @csrf @method('PUT')
        @foreach([['current_password','Current Password','cpwd'],['password','New Password','npwd'],['password_confirmation','Confirm New Password','cpwd2']] as [$n,$l,$id])
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">{{ $l }}</label>
          <div class="relative">
            <input type="password" name="{{ $n }}" id="{{ $id }}" required="{{ $n!=='password_confirmation'?'required':'' }}"
                   class="w-full pl-4 pr-11 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            <button type="button" onclick="const i=document.getElementById('{{ $id }}');i.type=i.type==='text'?'password':'text';this.textContent=i.type==='password'?'👁':'🙈'"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">👁</button>
          </div>
          @error($n)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        @endforeach
        <div class="flex justify-end">
          <button type="submit" class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition shadow-md">
            🔒 Update Password
          </button>
        </div>
      </form>
    </div>

    <!-- Logout all devices -->
    <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <div class="font-semibold text-gray-900 text-sm">Sign out of all devices</div>
        <div class="text-xs text-gray-400 mt-0.5">Invalidates all active sessions</div>
      </div>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold px-5 py-2.5 rounded-xl text-sm transition">
          🚪 Sign Out All Devices
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
