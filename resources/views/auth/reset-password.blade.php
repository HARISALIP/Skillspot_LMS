@extends('layouts.auth')
@section('title', 'Choose a password — Skillspot.in LMS')
@section('container-class', 'max-w-md')
@section('content')
<div class="p-7 sm:p-9">
  <h1 class="text-2xl font-black text-slate-900">Choose your password</h1>
  <p class="mt-2 text-sm text-slate-500">Use at least eight characters.</p>
  @if($errors->any())<div class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label class="block text-sm font-semibold text-slate-700">Email address
      <input name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
    </label>
    <label class="block text-sm font-semibold text-slate-700">New password
      <input name="password" type="password" required autocomplete="new-password" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
    </label>
    <label class="block text-sm font-semibold text-slate-700">Confirm password
      <input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
    </label>
    <button class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-bold text-white hover:bg-brand-700">Set password</button>
  </form>
</div>
@endsection
