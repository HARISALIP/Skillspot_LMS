@extends('layouts.auth')
@section('title', 'Under Maintenance — Skillspot.in')
@section('content')

<div class="bg-gradient-to-r from-gray-700 to-gray-900 px-7 py-8 text-center">
  <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center text-4xl mx-auto mb-3">🔧</div>
  <h1 class="text-2xl font-black text-white">Under Maintenance</h1>
  <p class="text-gray-300 text-sm mt-1">We'll be back soon</p>
</div>

<div class="px-7 py-10 text-center">
  <div class="text-5xl mb-5">⚙️</div>
  <h2 class="text-xl font-black text-gray-900 mb-3">Platform Under Maintenance</h2>
  <p class="text-gray-500 text-sm leading-relaxed mb-6 max-w-xs mx-auto">
    Skillspot.in is currently undergoing scheduled maintenance.
    We'll be back online shortly. Thank you for your patience!
  </p>

  <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4 text-sm text-yellow-700 mb-6 flex items-center gap-3">
    <span class="text-xl flex-shrink-0">⏳</span>
    <span>Estimated downtime: A few minutes</span>
  </div>

  @php $email = \App\Models\Setting::get('site_email',''); @endphp
  @if($email)
  <p class="text-xs text-gray-400 mb-6">
    Need urgent help?
    <a href="mailto:{{ $email }}" class="text-brand-600 font-semibold hover:underline">{{ $email }}</a>
  </p>
  @endif

  {{-- Admin login bypass --}}
  <div class="border-t border-gray-100 pt-5">
    <a href="/login?admin=1"
       class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-brand-600 transition font-medium">
      🔐 Admin Login
    </a>
  </div>
</div>

@endsection
