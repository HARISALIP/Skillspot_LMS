@extends('layouts.vendor')
@section('title','My Profile — Vendor Panel')
@section('header','My Profile')
@section('content')
<div class="max-w-2xl space-y-6">

  @if(session('success'))
    <div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
  @endif

  {{-- Personal info card --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-900">👤 Personal Information</h2>
      <p class="text-xs text-gray-400 mt-0.5">Your account name, email and phone number</p>
    </div>
    <div class="p-5">

      {{-- Avatar + info display --}}
      <div class="flex items-center gap-4 mb-6 p-4 bg-gray-50 rounded-2xl">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center text-white text-2xl font-black flex-shrink-0">
          {{ strtoupper(substr($user->name,0,1)) }}
        </div>
        <div>
          <div class="font-bold text-gray-900">{{ $user->name }}</div>
          <div class="text-sm text-gray-500">{{ $user->email }}</div>
          @if($user->phone)<div class="text-sm text-gray-400">{{ $user->phone }}</div>@endif
          <div class="mt-1 flex items-center gap-2">
            @foreach($user->roles as $role)
              <span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-purple-100 text-purple-700">{{ ucfirst($role->name) }}</span>
            @endforeach
          </div>
        </div>
      </div>

      <form method="POST" action="{{ route('vendor.my-profile.update') }}" class="space-y-4">
        @csrf @method('PUT')
        @if($errors->hasAny(['name','email','phone']))
          <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ $errors->first() }}</div>
        @endif
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name <span class="text-red-400">*</span></label>
          <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email <span class="text-red-400">*</span></label>
          <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Phone</label>
          <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                 placeholder="+91 98765 43210"
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div class="pt-1">
          <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Password change card --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-900">🔒 Change Password</h2>
      <p class="text-xs text-gray-400 mt-0.5">Keep your account secure with a strong password</p>
    </div>
    <div class="p-5">
      @if($errors->hasAny(['current_password','password']))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-4">{{ $errors->first('current_password') ?: $errors->first('password') }}</div>
      @endif
      <form method="POST" action="{{ route('vendor.my-profile.password') }}" class="space-y-4">
        @csrf @method('PUT')
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Current Password <span class="text-red-400">*</span></label>
          <input type="password" name="current_password" required
                 placeholder="••••••••"
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">New Password <span class="text-red-400">*</span></label>
          <input type="password" name="password" required
                 placeholder="Min. 8 characters"
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm New Password <span class="text-red-400">*</span></label>
          <input type="password" name="password_confirmation" required
                 placeholder="Repeat new password"
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div class="pt-1">
          <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition">
            Change Password
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Account info --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h2 class="font-semibold text-gray-900 mb-3">ℹ️ Account Details</h2>
    <div class="grid grid-cols-2 gap-3 text-sm">
      <div><span class="text-gray-400">Account ID</span><div class="font-semibold text-gray-700">#{{ $user->id }}</div></div>
      <div><span class="text-gray-400">Member Since</span><div class="font-semibold text-gray-700">{{ $user->created_at?->format('d M Y') }}</div></div>
      <div><span class="text-gray-400">Managing Vendor</span><div class="font-semibold text-gray-700">{{ $vendor->brand_name }}</div></div>
      <div><span class="text-gray-400">Vendor Status</span>
        <div><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $vendor->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">{{ ucfirst($vendor->status) }}</span></div>
      </div>
    </div>
  </div>

</div>
@endsection
