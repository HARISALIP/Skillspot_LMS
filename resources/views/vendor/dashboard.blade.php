@extends('layouts.vendor')
@section('title','Dashboard — Vendor Panel')
@section('header','Dashboard')
@section('content')
@php $isVendorOnly = auth()->user()->hasRole('vendor') && !auth()->user()->hasRole(['teacher','admin','super-admin']); @endphp
<div class="space-y-6">

  @if($isVendorOnly ?? false)
  <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3">
    <span class="text-2xl flex-shrink-0">👁️</span>
    <div>
      <div class="font-semibold text-amber-900 text-sm">View Only Mode</div>
      <div class="text-xs text-amber-700 mt-0.5">You have view-only access. To schedule classes, manage courses, or mark attendance — contact a teacher assigned to this vendor.</div>
    </div>
  </div>
  @endif

  {{-- Stats --}}
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Courses</div>
      <div class="text-3xl font-black text-gray-900">{{ $courses->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Students</div>
      <div class="text-3xl font-black text-gray-900">{{ $totalStudents }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Upcoming Live</div>
      <div class="text-3xl font-black text-gray-900">{{ $upcomingLive->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Attendance Rate</div>
      <div class="text-3xl font-black {{ $attendanceStats['rate'] >= 75 ? 'text-green-600' : 'text-yellow-500' }}">{{ $attendanceStats['rate'] }}%</div>
    </div>
  </div>

  {{-- Portal Link --}}
  <div class="bg-gradient-to-r from-brand-600 to-accent-600 rounded-2xl p-5 text-white flex items-center justify-between">
    <div>
      <div class="font-bold text-lg">{{ $vendor->brand_name }}</div>
      <div class="text-white/70 text-sm mt-0.5">Student Portal URL</div>
      <div class="font-mono text-white/90 text-sm mt-1">{{ $vendor->portalUrl() }}</div>
    </div>
    <a href="{{ $vendor->portalUrl() }}" target="_blank" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl font-semibold text-sm transition">Open Portal →</a>
  </div>

  {{-- Teacher/Admin only: Batches + Upcoming Live + Courses overview --}}
  @if(!$isVendorOnly)
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Trending Batches --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">🔥 Trending Batches</h2>
        <a href="{{ route('vendor.batches') }}" class="text-sm text-brand-600 hover:underline">View all</a>
      </div>
      <div class="divide-y divide-gray-50">
        @forelse($trendingBatches as $batch)
        <div class="px-5 py-3 flex items-center justify-between">
          <div>
            <div class="font-semibold text-gray-900 text-sm">{{ $batch->title }}</div>
            <div class="text-xs text-gray-400 mt-0.5">
              {{ $batch->batch_name ?? '—' }}
              · {{ $batch->enrollments_count }} total
              @if($batch->recent_enrollments > 0)
                · <span class="text-green-600 font-semibold">+{{ $batch->recent_enrollments }} this month</span>
              @endif
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $batch->registration_open ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
              {{ $batch->registration_open ? 'Open' : 'Closed' }}
            </span>
            <a href="{{ route('vendor.batches.show', $batch) }}" class="text-xs text-brand-600 hover:underline">Manage</a>
          </div>
        </div>
        @empty
        <div class="px-5 py-6 text-center text-gray-400 text-sm">No batch courses yet.</div>
        @endforelse
      </div>
    </div>

    {{-- Upcoming Live --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">📡 Upcoming Live Classes</h2>
        <a href="{{ route('vendor.live') }}" class="text-sm text-brand-600 hover:underline">Manage</a>
      </div>
      <div class="divide-y divide-gray-50">
        @forelse($upcomingLive as $lesson)
        <div class="px-5 py-3 flex items-center justify-between">
          <div>
            <div class="font-medium text-gray-900 text-sm">{{ $lesson->title }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ $lesson->section->course->title ?? '' }} · {{ ucfirst($lesson->live_platform ?? '') }}</div>
          </div>
          <div class="text-sm text-gray-600 font-medium">{{ $lesson->live_scheduled_at?->format('d M, h:i A') }}</div>
        </div>
        @empty
        <div class="px-5 py-6 text-center text-gray-400 text-sm">No upcoming live classes.</div>
        @endforelse
      </div>
    </div>

  </div>
  @endif

  @if(!$isVendorOnly)
  {{-- Courses overview --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-900">Courses Overview</h2>
      <a href="{{ route('vendor.courses') }}" class="text-sm text-brand-600 hover:underline">View all</a>
    </div>
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="text-left px-5 py-3 font-semibold text-gray-600">Course</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Type</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Students</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($courses->take(6) as $course)
        <tr class="hover:bg-gray-50">
          <td class="px-5 py-3 font-medium text-gray-900">{{ $course->title }}</td>
          <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700">{{ ucfirst(str_replace('_',' ',$course->course_type)) }}</span></td>
          <td class="px-4 py-3 text-center font-semibold">{{ $course->enrollments_count }}</td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $course->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
              {{ $course->is_published ? 'Published' : 'Draft' }}
            </span>
          </td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-5 py-6 text-center text-gray-400 text-sm">No courses yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @endif

</div>
@endsection
