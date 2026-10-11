@extends('layouts.auth')
@section('title', 'Set your password — Skillspot.in LMS')
@section('container-class', 'max-w-md')
@section('top-link')
  <a href="{{ route('login') }}" class="text-sm text-brand-600 hover:text-brand-700">← Back to sign in</a>
@endsection
@section('content')
<div class="p-7 sm:p-9">
  <h1 class="text-2xl font-black text-slate-900">Set your password</h1>
  <p class="mt-2 text-sm text-slate-500">Enter your email and we’ll send a secure set-password link.</p>
  @if(session('status'))<div class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
    @csrf
    <label class="block text-sm font-semibold text-slate-700">Email address
      <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
    </label>
    <button class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-bold text-white hover:bg-brand-700">Send set-password link</button>
  </form>
</div>
@endsection
