@extends('layouts.student')
@section('title','Teach on Skillspot')
@section('page-title','Teach on Skillspot')
@section('page-sub','Apply once; keep learning with the same account.')
@section('student-content')
<div class="max-w-3xl mx-auto space-y-5">
  @if(session('success'))<div class="rounded-xl bg-green-50 border border-green-200 p-4 text-green-700 text-sm">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="rounded-xl bg-red-50 border border-red-200 p-4 text-red-700 text-sm">{{ $errors->first() }}</div>@endif
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
    <h2 class="font-black text-xl text-gray-900">Instructor application</h2>
    <p class="text-sm text-gray-500 mt-2">Skillspot verifies your teaching profile and payout details before you can submit a paid course. Your bank details are encrypted and shown only to authorized administrators.</p>
    @if($profile)<div class="mt-4 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">Current status: <strong>{{ ucfirst(str_replace('_',' ', $profile->status)) }}</strong>{{ $profile->review_note ? ' — '.$profile->review_note : '' }}</div>@endif
    <form method="POST" action="{{ route('marketplace.apply.store') }}" class="mt-6 grid gap-5">@csrf
      <div><label class="block text-sm font-semibold text-gray-700 mb-1">Legal/payee name</label><input required name="legal_name" value="{{ old('legal_name', $profile->legal_name ?? auth()->user()->name) }}" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm"></div>
      <div><label class="block text-sm font-semibold text-gray-700 mb-1">Instructor bio</label><textarea required name="bio" rows="5" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm">{{ old('bio', $profile->bio ?? '') }}</textarea><p class="text-xs text-gray-400 mt-1">At least 40 characters. This can appear on your course page after approval.</p></div>
      <div class="grid md:grid-cols-2 gap-5"><div><label class="block text-sm font-semibold text-gray-700 mb-1">Bank account holder</label><input required name="payout_account_name" value="{{ old('payout_account_name', $profile->payout_account_name ?? '') }}" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm"></div><div><label class="block text-sm font-semibold text-gray-700 mb-1">Account number</label><input required name="payout_account_number" value="{{ old('payout_account_number', $profile->payout_account_number ?? '') }}" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm"></div></div>
      <div><label class="block text-sm font-semibold text-gray-700 mb-1">IFSC code</label><input required name="payout_ifsc" value="{{ old('payout_ifsc', $profile->payout_ifsc ?? '') }}" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm"></div>
      <button class="justify-self-start rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white hover:bg-brand-700">{{ $profile ? 'Update and resubmit' : 'Submit application' }}</button>
    </form>
  </div>
</div>
@endsection
