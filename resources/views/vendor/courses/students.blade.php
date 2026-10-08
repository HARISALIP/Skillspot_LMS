@extends('layouts.vendor')
@section('title','Students — Vendor Panel')
@section('header','Course Students')
@section('content')
<div class="space-y-6">
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center justify-between">
    <div>
      <h2 class="font-bold text-gray-900 text-lg">{{ $course->title }}</h2>
      <div class="text-sm text-gray-500 mt-0.5 flex items-center gap-3">
        <span>{{ $course->enrollments_count }} / {{ $course->max_students ?: '∞' }} seats</span>
        @if($course->batch_name)<span class="bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full text-xs font-medium">Batch: {{ $course->batch_name }}</span>@endif
        <span class="{{ $course->registration_open ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} px-2 py-0.5 rounded-full text-xs font-medium">{{ $course->registration_open ? 'Registration Open' : 'Closed' }}</span>
      </div>
    </div>
    <div class="text-right text-sm text-gray-500">
      Portal Reg. Link:<br>
      <a href="{{ route('vendor.portal.register', [$vendor->slug, $course->id]) }}" target="_blank" class="text-brand-600 hover:underline text-xs font-mono">/v/{{ $vendor->slug }}/register/{{ $course->id }}</a>
    </div>
  </div>

  @if(!isset($isVendorOnly) || !$isVendorOnly)
  <!-- Allow Student -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-semibold text-gray-900 mb-3">Allow a Student</h3>
    @if($errors->any())
      <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-2 mb-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('vendor.courses.allow-student', $course) }}" class="flex items-center gap-3">
      @csrf
      <input type="email" name="email" placeholder="Student email (must be registered)" required class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      <input type="text" name="note" placeholder="Note (optional)" class="w-48 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition whitespace-nowrap">Add Student</button>
    </form>
  </div>
  @endif

  <!-- Enrolled Students -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-900">Enrolled Students ({{ $enrolled->count() }})</h3></div>
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="text-left px-5 py-3 font-semibold text-gray-600">Student</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Progress</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Status</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Enrolled On</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($enrolled as $enrollment)
        <tr class="hover:bg-gray-50">
          <td class="px-5 py-3">
            <div class="font-medium text-gray-900">{{ $enrollment->user->name ?? '—' }}</div>
            <div class="text-xs text-gray-400">{{ $enrollment->user->email ?? '' }}</div>
          </td>
          <td class="px-4 py-3 text-center">
            <div class="flex items-center gap-2 justify-center">
              <div class="w-24 bg-gray-200 rounded-full h-1.5"><div class="bg-brand-500 h-1.5 rounded-full" style="width:{{ $enrollment->progress }}%"></div></div>
              <span class="text-xs text-gray-500">{{ $enrollment->progress }}%</span>
            </div>
          </td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $enrollment->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst($enrollment->status) }}</span>
          </td>
          <td class="px-4 py-3 text-center text-gray-500 text-xs">{{ $enrollment->created_at->format('d M Y') }}</td>
          <td class="px-4 py-3 text-right">
            @if(!isset($isVendorOnly) || !$isVendorOnly)
            <form method="POST" action="{{ route('vendor.courses.revoke-student', [$course, $enrollment->user_id]) }}">
              @csrf @method('DELETE')
              <button class="text-xs text-red-600 hover:text-red-800 font-medium" onclick="return confirm('Revoke access?')">Revoke</button>
            </form>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No students enrolled yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
