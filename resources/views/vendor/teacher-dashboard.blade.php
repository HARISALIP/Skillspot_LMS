@extends('layouts.vendor')
@section('title', 'Teacher Dashboard — ITForge')
@section('header', 'Teacher Dashboard')
@section('page-sub', 'Manage your assigned vendors, courses & live classes')

@section('content')
<div class="space-y-6">

  {{-- Welcome bar --}}
  <div class="bg-gradient-to-r from-brand-600 to-accent-600 rounded-2xl p-5 text-white flex items-center justify-between flex-wrap gap-4">
    <div>
      <div class="text-lg font-black">👩‍🏫 Welcome, {{ auth()->user()->name }}</div>
      <div class="text-white/70 text-sm mt-0.5">You manage <strong>{{ $myVendors->count() }}</strong> vendor portal{{ $myVendors->count() != 1 ? 's' : '' }} · <strong>{{ $allCourses->count() }}</strong> courses · <strong>{{ $totalStudents }}</strong> students</div>
    </div>
    <a href="{{ route('vendor.live_classes.create') }}" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl font-bold text-sm transition flex items-center gap-2">
      ➕ New Live Class
    </a>
  </div>

  {{-- Stats row --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">My Vendors</div>
      <div class="text-3xl font-black text-brand-600">{{ $myVendors->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Courses</div>
      <div class="text-3xl font-black text-gray-900">{{ $allCourses->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Students</div>
      <div class="text-3xl font-black text-gray-900">{{ $totalStudents }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Attendance Rate</div>
      <div class="text-3xl font-black {{ $attendanceStats['rate'] >= 75 ? 'text-green-600' : 'text-yellow-500' }}">{{ $attendanceStats['rate'] }}%</div>
    </div>
  </div>

  {{-- Today's live classes --}}
  @if($todayLive->count())
  <div class="bg-green-50 border border-green-200 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-green-200 flex items-center gap-2">
      <span class="text-lg">🟢</span>
      <h2 class="font-bold text-green-900">Today's Live Classes ({{ $todayLive->count() }})</h2>
    </div>
    <div class="divide-y divide-green-100">
      @foreach($todayLive as $lesson)
      <div class="px-5 py-3 flex items-center justify-between gap-4">
        <div>
          <div class="font-semibold text-gray-900 text-sm">{{ $lesson->title }}</div>
          <div class="text-xs text-gray-500 mt-0.5">
            {{ $lesson->section->course->title ?? '—' }} ·
            🕐 {{ \Carbon\Carbon::parse($lesson->live_scheduled_at)->format('h:i A') }}
            @if($lesson->live_duration_min) · {{ $lesson->live_duration_min }}min @endif
          </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          @if($lesson->live_url)
          <a href="{{ $lesson->live_url }}" target="_blank"
             class="bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded-xl text-xs font-bold transition">
            Join →
          </a>
          @endif
          <a href="{{ route('vendor.live.attendance', $lesson->id) }}"
             class="bg-white border border-green-200 text-green-700 hover:bg-green-50 px-3 py-1.5 rounded-xl text-xs font-semibold transition">
            Attendance
          </a>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- My Vendor Portals --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">🏪 My Vendor Portals</h2>
      </div>
      <div class="divide-y divide-gray-50">
        @forelse($myVendors as $v)
        @php
          $activeVid = session('active_vendor_id');
          $isActive  = $activeVid == $v->id;
        @endphp
        <div class="px-5 py-4 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black text-sm flex-shrink-0 shadow-sm"
                 style="background: {{ $v->primary_color ?? '#2563eb' }}">
              {{ strtoupper(substr($v->brand_name,0,1)) }}
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-gray-900 text-sm truncate">{{ $v->brand_name }}</div>
              <div class="text-xs text-gray-400 mt-0.5">{{ $v->courses_count }} courses · {{ $v->enrollments_count }} enrollments</div>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            @if($isActive)
            <span class="text-xs bg-brand-100 text-brand-700 font-bold px-2 py-0.5 rounded-full">Active</span>
            @else
            <form method="POST" action="{{ route('vendor.switch') }}">
              @csrf
              <input type="hidden" name="vendor_id" value="{{ $v->id }}">
              <button class="text-xs bg-gray-100 hover:bg-brand-50 text-gray-600 hover:text-brand-700 px-3 py-1.5 rounded-xl font-semibold transition">Switch</button>
            </form>
            @endif
            <a href="{{ route('vendor.live_classes.index') }}" class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 px-3 py-1.5 rounded-xl font-semibold transition">Live Classes →</a>
          </div>
        </div>
        @empty
        <div class="px-5 py-6 text-sm text-gray-400 text-center">No vendor portals assigned yet. Contact admin.</div>
        @endforelse
      </div>
    </div>

    {{-- Upcoming Live Classes --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">📡 Upcoming Live Classes</h2>
        <a href="{{ route('vendor.live') }}" class="text-xs text-brand-600 hover:underline">View all</a>
      </div>
      <div class="divide-y divide-gray-50">
        @forelse($upcomingLive->take(6) as $lesson)
        <div class="px-5 py-3 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <div class="font-semibold text-gray-900 text-sm truncate">{{ $lesson->title }}</div>
            <div class="text-xs text-gray-400 mt-0.5">
              {{ $lesson->section->course->title ?? '—' }} ·
              {{ \Carbon\Carbon::parse($lesson->live_scheduled_at)->format('d M, h:i A') }}
            </div>
          </div>
          <span class="text-xs bg-blue-100 text-blue-700 font-semibold px-2 py-0.5 rounded-full flex-shrink-0">
            {{ \Carbon\Carbon::parse($lesson->live_scheduled_at)->diffForHumans() }}
          </span>
        </div>
        @empty
        <div class="px-5 py-6 text-sm text-gray-400 text-center">No upcoming live classes.</div>
        @endforelse
      </div>
    </div>

  </div>

  {{-- All Courses across vendors --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-900">📚 Vendor Courses</h2>
      <a href="{{ route('vendor.live_classes.create') }}" class="text-xs bg-brand-600 hover:bg-brand-700 text-white px-3 py-1.5 rounded-xl font-bold transition">➕ New Live Class</a>
    </div>
    @if($allCourses->count())
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
          <tr>
            <th class="px-5 py-3 text-left">Course</th>
            <th class="px-5 py-3 text-left">Vendor</th>
            <th class="px-5 py-3 text-left">Status</th>
            <th class="px-5 py-3 text-left">Students</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @foreach($allCourses as $course)
          <tr class="hover:bg-gray-50/50 transition">
            <td class="px-5 py-3">
              <div class="font-semibold text-gray-900">{{ $course->title }}</div>
              <div class="text-xs text-gray-400 mt-0.5">{{ ucfirst($course->course_type ?? 'open') }} · {{ $course->category }}</div>
            </td>
            <td class="px-5 py-3">
              @php $vc = $myVendors->firstWhere('id', $course->vendor_id); @endphp
              <span class="text-xs font-semibold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-full">{{ $vc->brand_name ?? '—' }}</span>
            </td>
            <td class="px-5 py-3">
              @if($course->is_published)
                <span class="text-xs font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">Published</span>
              @else
                <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">Draft</span>
              @endif
            </td>
            <td class="px-5 py-3 font-semibold text-gray-900">{{ $course->enrollments_count }}</td>
            <td class="px-5 py-3 text-right">
              <div class="flex items-center gap-1.5 justify-end">
                <a href="{{ route('vendor.courses.edit', $course->id) }}" class="text-xs bg-gray-100 hover:bg-brand-50 text-gray-600 hover:text-brand-700 px-3 py-1.5 rounded-xl font-semibold transition">Edit</a>
                <a href="{{ route('vendor.batches.show', $course->id) }}" class="text-xs bg-gray-100 hover:bg-blue-50 text-gray-600 hover:text-blue-700 px-3 py-1.5 rounded-xl font-semibold transition">Batch</a>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <div class="px-5 py-10 text-center text-gray-400 text-sm">
      No courses yet. <a href="{{ route('vendor.live_classes.create') }}" class="text-brand-600 font-semibold hover:underline">Create your first live class →</a>
    </div>
    @endif
  </div>

  {{-- ITForge Courses assigned to this teacher --}}
  @if(($itforgeCourses ?? collect())->count())
  <div class="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-brand-100 bg-brand-50 flex items-center justify-between">
      <h2 class="font-semibold text-brand-900">🎛️ ITForge Courses</h2>
      <span class="text-xs text-brand-600 font-semibold">{{ $itforgeCourses->count() }} course{{ $itforgeCourses->count()!=1?'s':'' }} assigned</span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
          <tr>
            <th class="px-5 py-3 text-left">Course</th>
            <th class="px-5 py-3 text-left">Status</th>
            <th class="px-5 py-3 text-left">Students</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @foreach($itforgeCourses as $course)
          <tr class="hover:bg-gray-50/50 transition">
            <td class="px-5 py-3">
              <div class="font-semibold text-gray-900">{{ $course->title }}</div>
              <div class="text-xs text-gray-400 mt-0.5">{{ ucfirst($course->course_type ?? 'open') }} · {{ $course->category }}</div>
            </td>
            <td class="px-5 py-3">
              @if($course->is_published)
                <span class="text-xs font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">Published</span>
              @else
                <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">Draft</span>
              @endif
            </td>
            <td class="px-5 py-3 font-semibold text-gray-900">{{ $course->enrollments_count }}</td>
            <td class="px-5 py-3 text-right">
              <div class="flex items-center gap-1.5 justify-end">
                <a href="{{ route('admin.courses.edit', $course->id) }}" class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 px-3 py-1.5 rounded-xl font-semibold transition">Edit</a>
                <a href="{{ route('admin.batches.show', $course->id) }}" class="text-xs bg-gray-100 hover:bg-blue-50 text-gray-600 hover:text-blue-700 px-3 py-1.5 rounded-xl font-semibold transition">Batch</a>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

</div>
@endsection
