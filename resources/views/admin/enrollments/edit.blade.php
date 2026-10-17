@extends('layouts.admin')
@section('title','Edit Enrollment — Skillspot.in Admin')
@section('page-title','Edit Enrollment')
@section('page-sub','Manage course access without changing learner progress')

@section('admin-content')
<div class="max-w-2xl space-y-5">
  @if(session('success'))
  <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">{{ session('success') }}</div>
  @endif
  @if($errors->any())
  <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
    @foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach
  </div>
  @endif

  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Learner</p>
    <h2 class="mt-1 text-lg font-black text-gray-900">{{ $enrollment->user?->name ?? 'Unavailable learner' }}</h2>
    <p class="text-sm text-gray-500">{{ $enrollment->user?->email }}</p>
    <p class="mt-3 text-sm font-semibold text-brand-700">{{ $enrollment->course?->title ?? 'Unavailable course' }}</p>
  </div>

  <form method="POST" action="{{ route('admin.enrollments.update', $enrollment) }}" class="space-y-5 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
    @csrf @method('PUT')
    <div>
      <label for="edit-enrollment-access" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Access type</label>
      <select id="edit-enrollment-access" name="access_type" required class="min-h-11 w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:border-brand-500 focus:ring-brand-500">
        <option value="lifetime" @selected(old('access_type', $enrollment->access_type) === 'lifetime')>Lifetime</option>
        <option value="limited" @selected(old('access_type', $enrollment->access_type) === 'limited')>Limited</option>
      </select>
    </div>
    <div>
      <label for="edit-enrollment-expiry" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Expiry date <span class="font-normal normal-case text-gray-400">required for limited access</span></label>
      <input id="edit-enrollment-expiry" type="date" name="expires_at" value="{{ old('expires_at', $enrollment->expires_at?->format('Y-m-d')) }}" class="min-h-11 w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:border-brand-500 focus:ring-brand-500">
    </div>
    <div>
      <label for="edit-enrollment-status" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</label>
      <select id="edit-enrollment-status" name="status" required class="min-h-11 w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:border-brand-500 focus:ring-brand-500">
        <option value="active" @selected(old('status', $enrollment->status) === 'active')>Active</option>
        <option value="completed" @selected(old('status', $enrollment->status) === 'completed')>Completed</option>
        <option value="refunded" @selected(old('status', $enrollment->status) === 'refunded')>Access revoked or refunded</option>
      </select>
    </div>
    <div>
      <label for="edit-enrollment-notes" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Internal note <span class="font-normal normal-case text-gray-400">optional</span></label>
      <textarea id="edit-enrollment-notes" name="notes" rows="3" maxlength="500" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:border-brand-500 focus:ring-brand-500">{{ old('notes', $enrollment->notes) }}</textarea>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
      <a href="{{ route('admin.enrollments') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-gray-600 hover:text-gray-900">← Enrollments</a>
      <button type="submit" class="min-h-11 rounded-xl bg-brand-600 px-6 text-sm font-bold text-white hover:bg-brand-700">Save changes</button>
    </div>
  </form>
</div>
@endsection
