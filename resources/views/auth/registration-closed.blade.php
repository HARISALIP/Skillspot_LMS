@extends('layouts.auth')
@section('title', 'Registration Closed — Skillspot.in')
@section('content')

<div class="bg-gradient-to-r from-brand-600 to-accent-600 px-7 py-8 text-center">
  <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center text-4xl mx-auto mb-3">🚫</div>
  <h1 class="text-2xl font-black text-white">Registration Closed</h1>
  <p class="text-blue-200 text-sm mt-1">Not accepting new registrations right now</p>
</div>

<div class="px-7 py-10 text-center">
  <div class="text-5xl mb-5">🔒</div>
  <h2 class="text-xl font-black text-gray-900 mb-3">New Registrations Paused</h2>
  <p class="text-gray-500 text-sm leading-relaxed mb-6 max-w-xs mx-auto">
    Skillspot.in is not accepting new student registrations at this time.
    Please check back later or contact us for more information.
  </p>

  <div class="space-y-3">
    <a href="/login"
       class="block w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-2xl transition text-sm">
      Already have an account? Login →
    </a>
    @php $email = \App\Models\Setting::get('site_email',''); @endphp
    @if($email)
    <a href="mailto:{{ $email }}"
       class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3.5 rounded-2xl transition text-sm">
      📧 Contact Us
    </a>
    @endif
    <a href="/" class="block text-sm text-gray-400 hover:text-gray-600 transition pt-1">← Back to Homepage</a>
  </div>
</div>

@endsection
