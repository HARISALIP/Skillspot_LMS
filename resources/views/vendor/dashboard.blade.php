@extends('layouts.vendor')
@section('title','Dashboard — Organization — Skillspot')
@section('header','Dashboard')
@section('content')
<div class="space-y-6">

  @if($isVendorOnly ?? false)
  <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3">
    <span class="text-2xl flex-shrink-0">👁️</span>
    <div>
      <div class="font-semibold text-amber-900 text-sm">View Only Mode</div>
      <div class="text-xs text-amber-700 mt-0.5">You have view-only access. To schedule classes, manage courses, or mark attendance, contact an organization teacher.</div>
    </div>
  </div>
  @endif

  {{-- Stats --}}
  <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Courses</div>
      <div class="text-3xl font-black text-gray-900">{{ $courses->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Students</div>
      <div class="text-3xl font-black text-gray-900">{{ $totalStudents }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Upcoming Live</div>
      <div class="text-3xl font-black text-gray-900">{{ $upcomingLive->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Attendance Rate</div>
      @if($attendanceStats['present'] + $attendanceStats['absent'] > 0)<div class="text-3xl font-black {{ $attendanceStats['rate'] >= 75 ? 'text-green-600' : 'text-yellow-500' }}">{{ $attendanceStats['rate'] }}%</div>@else<div class="text-2xl font-black text-gray-400" aria-label="No attendance recorded">—</div><div class="mt-1 text-xs text-gray-500">No attendance recorded</div>@endif
    </div>
  </div>

  {{-- Portal Link --}}
  <div class="bg-gradient-to-r from-brand-600 to-accent-600 rounded-2xl p-5 text-white flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <div class="font-bold text-lg">{{ $vendor->brand_name }}</div>
      <label for="organization-learner-link" class="mt-0.5 block text-sm text-white/80">Learner URL</label>
      <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2">
        <input id="organization-learner-link" readonly value="{{ route('organization.dashboard', $vendor->slug) }}" class="min-w-0 flex-1 rounded-lg border border-white/30 bg-white/10 px-3 py-2 font-mono text-xs text-white">
        <button type="button" data-copy-target="organization-learner-link" class="min-h-11 rounded-lg bg-white/20 px-3 text-sm font-semibold text-white hover:bg-white/30">Copy link</button>
      </div>
      <p data-copy-status role="status" class="mt-1 text-xs text-white/90" aria-live="polite"></p>
    </div>
    <a href="{{ route('organization.dashboard', $vendor->slug) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center justify-center self-stretch rounded-xl bg-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/30 sm:self-auto">Open learner view →</a>
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
        <div class="px-5 py-6 text-center text-sm text-gray-500">No batch courses yet. <a href="{{ route('vendor.batches') }}" class="font-semibold text-brand-700 underline">View batches</a></div>
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
        <div class="px-5 py-6 text-center text-sm text-gray-500">No live lessons are scheduled. <a href="{{ route('vendor.live_classes.create') }}" class="font-semibold text-brand-700 underline">Create a live class</a></div>
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
    <div class="divide-y divide-gray-100 sm:hidden">
      @forelse($courses->take(6) as $course)
        <article class="p-4">
          <div class="flex min-w-0 items-start justify-between gap-3">
            <div class="min-w-0"><h3 class="break-words font-semibold text-gray-900">{{ $course->title }}</h3><p class="mt-1 text-xs text-gray-500">{{ $course->enrollments_count }} learners · {{ ucfirst(str_replace('_',' ', $course->course_type)) }}</p></div>
            <span class="shrink-0 rounded-full px-2 py-1 text-xs font-medium {{ $course->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $course->is_published ? 'Published' : 'Draft' }}</span>
          </div>
          <a href="{{ route('instructor.courses.show', $course) }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-semibold text-brand-700">Edit course →</a>
        </article>
      @empty
        <p class="p-5 text-sm text-gray-500">No courses yet. <a class="font-semibold text-brand-700 underline" href="{{ route('vendor.courses') }}">Open courses</a> to create one.</p>
      @endforelse
    </div>
    <table class="hidden w-full text-sm sm:table">
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
        <tr><td colspan="4" class="px-5 py-6 text-center text-sm text-gray-500">No courses yet. <a class="font-semibold text-brand-700 underline" href="{{ route('vendor.courses') }}">Open courses</a> to create one.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @endif

</div>
@endsection
