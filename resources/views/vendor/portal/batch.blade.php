@extends('vendor.portal.layout')
@section('title', $course->title . ' — ' . $vendor->brand_name)
@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 space-y-6">

  {{-- Batch header --}}
  <div class="rounded-2xl overflow-hidden shadow-sm" style="background: linear-gradient(135deg, {{ $vendor->primary_color ?? '#2563eb' }}, {{ $vendor->accent_color ?? '#7c3aed' }});">
    <div class="p-6 text-white">
      <div class="text-xs font-semibold uppercase tracking-widest opacity-70 mb-1">Your Batch</div>
      <h1 class="text-2xl font-black mb-1">{{ $course->title }}</h1>
      @if($course->batch_name)<div class="opacity-80 text-sm">{{ $course->batch_name }}</div>@endif
      <div class="flex flex-wrap gap-4 mt-4 text-sm">
        @if($course->batch_start_date)
          <span class="bg-white/20 px-3 py-1 rounded-full">📅 {{ $course->batch_start_date->format('d M Y') }} → {{ $course->batch_end_date?->format('d M Y') ?? 'Ongoing' }}</span>
        @endif
        <span class="bg-white/20 px-3 py-1 rounded-full">✅ Enrolled</span>
        <span class="bg-white/20 px-3 py-1 rounded-full">📊 {{ $attendPct }}% attendance</span>
      </div>
    </div>
  </div>

  {{-- Tabs --}}
  <div x-data="{tab:'live'}" class="space-y-4">
    <div class="flex gap-2 border-b border-gray-200 overflow-x-auto">
      <button @click="tab='live'" :class="tab==='live'?'border-b-2 border-brand-600 text-brand-600 font-semibold':'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 text-sm whitespace-nowrap transition">📡 Live Classes</button>
      <button @click="tab='recordings'" :class="tab==='recordings'?'border-b-2 border-brand-600 text-brand-600 font-semibold':'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 text-sm whitespace-nowrap transition">🎬 Recordings</button>
      <button @click="tab='docs'" :class="tab==='docs'?'border-b-2 border-brand-600 text-brand-600 font-semibold':'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 text-sm whitespace-nowrap transition">📄 Documents</button>
      <button @click="tab='attendance'" :class="tab==='attendance'?'border-b-2 border-brand-600 text-brand-600 font-semibold':'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 text-sm whitespace-nowrap transition">📋 My Attendance</button>
    </div>

    {{-- Live Classes --}}
    <div x-show="tab==='live'" class="space-y-3">
      @forelse($liveLessons as $lesson)
      @php
        $isPast = $lesson->live_scheduled_at && $lesson->live_scheduled_at->isPast();
        $isSoon = $lesson->live_scheduled_at && $lesson->live_scheduled_at->isFuture() && $lesson->live_scheduled_at->diffInHours(now()) < 2;
      @endphp
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center justify-between gap-4">
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <span class="font-semibold text-gray-900">{{ $lesson->title }}</span>
            @if($isSoon && !$isPast)
              <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-600 animate-pulse">🔴 Starting Soon</span>
            @elseif($isPast)
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Ended</span>
            @else
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Upcoming</span>
            @endif
          </div>
          <div class="text-xs text-gray-400">
            {{ $lesson->live_scheduled_at?->format('l, d M Y · h:i A') ?? 'TBD' }}
            @if($lesson->live_platform) · {{ ucfirst($lesson->live_platform) }} @endif
            @if($lesson->live_duration_min) · {{ $lesson->live_duration_min }} min @endif
          </div>
          @if($lesson->description)<div class="text-xs text-gray-500 mt-1">{{ Str::limit($lesson->description, 100) }}</div>@endif
        </div>
        @if($lesson->live_url && (!$isPast))
          <a href="{{ $lesson->live_url }}" target="_blank"
             class="flex-shrink-0 text-sm font-semibold text-white px-4 py-2 rounded-xl transition"
             style="background: {{ $vendor->primary_color ?? '#2563eb' }}">
            Join Class →
          </a>
        @elseif($lesson->recording_url && $lesson->recording_shared)
          <a href="{{ route('vendor.portal.recording', [$vendor->slug, $lesson]) }}"
             class="flex-shrink-0 text-sm font-semibold bg-purple-100 hover:bg-purple-200 text-purple-700 px-4 py-2 rounded-xl transition">
            Watch Recording
          </a>
        @endif
      </div>
      @empty
      <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-gray-400">No live classes scheduled yet.</div>
      @endforelse
    </div>

    {{-- Recordings --}}
    <div x-show="tab==='recordings'" class="space-y-3">
      @forelse($recordings as $lesson)
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center justify-between gap-4">
        <div class="flex-1">
          <div class="font-semibold text-gray-900 mb-1">🎬 {{ $lesson->title }}</div>
          <div class="text-xs text-gray-400">{{ $lesson->live_scheduled_at?->format('d M Y') ?? '' }}</div>
          @if($lesson->description)<div class="text-xs text-gray-500 mt-1">{{ Str::limit($lesson->description, 100) }}</div>@endif
        </div>
        <a href="{{ route('vendor.portal.recording', [$vendor->slug, $lesson]) }}"
           class="flex-shrink-0 text-sm font-semibold bg-purple-100 hover:bg-purple-200 text-purple-700 px-4 py-2 rounded-xl transition">
          Watch →
        </a>
      </div>
      @empty
      <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-gray-400">No recordings shared yet.</div>
      @endforelse
    </div>

    {{-- Documents --}}
    <div x-show="tab==='docs'" class="space-y-3">
      @forelse($docsLessons as $lesson)
      @php $resources = is_array($lesson->resources) ? $lesson->resources : json_decode($lesson->resources ?? '[]', true); @endphp
      @if(!empty($resources))
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="font-semibold text-gray-900 mb-3">{{ $lesson->title }}</div>
        <div class="space-y-2">
          @foreach($resources as $res)
          @php $resArr = is_array($res) ? $res : ['name'=>$res,'url'=>$res]; @endphp
          <a href="{{ $resArr['url'] ?? '#' }}" target="_blank"
             class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-brand-50 border border-gray-100 hover:border-brand-200 transition text-sm text-gray-700 hover:text-brand-700 font-medium">
            <span class="text-lg">📎</span>
            {{ $resArr['name'] ?? 'Download File' }}
            <span class="ml-auto text-xs text-gray-400">↓</span>
          </a>
          @endforeach
        </div>
      </div>
      @endif
      @empty
      <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-gray-400">No documents shared yet.</div>
      @endforelse
    </div>

    {{-- My Attendance --}}
    <div x-show="tab==='attendance'">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">My Attendance</h3>
          <span class="text-sm font-bold {{ $attendPct >= 75 ? 'text-green-600' : ($attendPct >= 50 ? 'text-yellow-500' : 'text-red-500') }}">{{ $attendPct }}% overall</span>
        </div>
        <div class="divide-y divide-gray-50">
          @forelse($liveLessons as $lesson)
          @php $status = $myAttendance[$lesson->id] ?? null; @endphp
          <div class="px-5 py-3 flex items-center justify-between">
            <div>
              <div class="text-sm font-medium text-gray-900">{{ $lesson->title }}</div>
              <div class="text-xs text-gray-400">{{ $lesson->live_scheduled_at?->format('d M Y, h:i A') ?? 'TBD' }}</div>
            </div>
            @if($status === 'present')
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">✅ Present</span>
            @elseif($status === 'late')
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">⏰ Late</span>
            @elseif($status === 'absent')
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-600">❌ Absent</span>
            @else
              <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">—</span>
            @endif
          </div>
          @empty
          <div class="px-5 py-6 text-center text-gray-400 text-sm">No classes yet.</div>
          @endforelse
        </div>
      </div>
    </div>

  </div>

</div>
@endsection
