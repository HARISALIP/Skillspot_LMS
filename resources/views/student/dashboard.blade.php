@extends('layouts.student')
@section('title','My Dashboard — Skillspot.in')
@section('page-title','Dashboard')
@section('page-sub','Welcome back!')

@section('student-content')

@if(session('success'))
<div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-4 py-3 text-sm font-semibold">
  ✅ {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-xl leading-none">×</button>
</div>
@endif

<!-- ── Welcome Banner ──────────────────────────────────────────────── -->
<div class="bg-gradient-to-r from-brand-600 to-accent-600 rounded-2xl p-5 md:p-6 mb-5 text-white relative overflow-hidden">
  <div class="absolute -right-8 -top-8 w-40 h-40 bg-white/10 rounded-full pointer-events-none"></div>
  <div class="absolute -right-4 -bottom-10 w-32 h-32 bg-white/5 rounded-full pointer-events-none"></div>
  <div class="relative z-10">
    <div class="text-sm text-blue-200 mb-1">
      Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }} 👋
    </div>
    <h2 class="text-xl md:text-2xl font-black mb-3">{{ auth()->user()->name }}</h2>
    <div class="grid grid-cols-4 gap-2 md:gap-4 max-w-sm">
      @foreach([
        [$enrollments->count(),'Enrolled'],
        [$completedCount,'Completed'],
        [$certCount,'Certs'],
        [$streak,'Day Streak'],
      ] as [$val,$lbl])
      <div class="text-center">
        <div class="text-lg md:text-2xl font-black">{{ $val }}</div>
        <div class="text-xs text-blue-200 leading-tight">{{ $lbl }}</div>
      </div>
      @endforeach
    </div>
  </div>
</div>

<!-- ── Upcoming Live Classes ──────────────────────────────────────── -->
@if($upcomingLive->count())
<div class="mb-5">
  <h3 class="font-black text-gray-900 text-sm mb-3 flex items-center gap-2">
    📡 Upcoming Live Classes
    <span class="text-xs bg-red-100 text-red-600 font-bold px-2 py-0.5 rounded-full">{{ $upcomingLive->count() }}</span>
  </h3>
  <div class="space-y-3">
    @foreach($upcomingLive as $lesson)
      <x-live-class-card :lesson="$lesson"/>
    @endforeach
  </div>
</div>
@endif

<!-- ── Continue Learning ──────────────────────────────────────────── -->
@php $inProgressEnrollments = $enrollments->where('progress','>',0)->where('status','active')->take(3); @endphp
@if($inProgressEnrollments->count())
<div class="mb-5">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-black text-gray-900 text-sm">▶️ Continue Learning</h3>
    <a href="{{ route('student.courses') }}" class="text-xs text-brand-600 font-semibold hover:underline">All courses →</a>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($inProgressEnrollments as $en)
    @php $course = $en->course; @endphp
    <a href="{{ route('student.learn', $course->id) }}"
       class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md hover:border-brand-200 transition group block">
      <div class="h-28 bg-gradient-to-br from-brand-100 to-accent-100 flex items-center justify-center relative overflow-hidden">
        @if($course->thumbnail)
          <img src="{{ $course->thumbnail }}" class="w-full h-full object-cover" alt="">
        @else
          <span class="text-4xl">📚</span>
        @endif
        <div class="absolute top-2 right-2 bg-black/60 text-white text-xs font-bold px-2 py-0.5 rounded-lg">
          {{ $en->progress }}%
        </div>
        @php
          $isExpiring = $en->access_type==='limited' && $en->expires_at && $en->expires_at->diffInDays(now())<=7;
        @endphp
        @if($isExpiring)
        <div class="absolute top-2 left-2 bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-lg">
          ⚠️ Expiring
        </div>
        @endif
      </div>
      <div class="p-4">
        <div class="text-xs text-brand-600 font-semibold mb-1">{{ $course->category }}</div>
        <h4 class="font-bold text-gray-900 text-sm line-clamp-2 group-hover:text-brand-600 transition mb-2">
          {{ $course->title }}
        </h4>
        <div class="bg-gray-100 rounded-full h-1.5 overflow-hidden mb-2">
          <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500 transition-all"
               style="width:{{ $en->progress }}%"></div>
        </div>
        <div class="flex items-center justify-between text-xs text-gray-400">
          <span>{{ $en->progress }}% complete</span>
          @if($en->access_type==='limited' && $en->expires_at)
          <span class="{{ $isExpiring?'text-orange-500 font-semibold':'' }}">
            Exp: {{ $en->expires_at->format('d M Y') }}
          </span>
          @else
          <span class="text-green-600 font-semibold">♾️ Lifetime</span>
          @endif
        </div>
      </div>
    </a>
    @endforeach
  </div>
</div>
@endif

<!-- ── New enrollments (not started) ─────────────────────────────── -->
@php $newEnrollments = $enrollments->where('progress',0)->where('status','active')->take(3); @endphp
@if($newEnrollments->count())
<div class="mb-5">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-black text-gray-900 text-sm">🆕 Start Learning</h3>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($newEnrollments as $en)
    @php $course = $en->course; @endphp
    <a href="{{ route('student.learn', $course->id) }}"
       class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md hover:border-brand-200 transition group block">
      <div class="h-24 bg-gradient-to-br from-green-100 to-brand-100 flex items-center justify-center">
        @if($course->thumbnail)
          <img src="{{ $course->thumbnail }}" class="w-full h-full object-cover" alt="">
        @else
          <span class="text-4xl">📚</span>
        @endif
      </div>
      <div class="p-4">
        <div class="text-xs text-brand-600 font-semibold mb-1">{{ $course->category }}</div>
        <h4 class="font-bold text-gray-900 text-sm line-clamp-1 group-hover:text-brand-600 transition">{{ $course->title }}</h4>
        <div class="mt-2 inline-flex items-center gap-1 text-xs bg-green-50 text-green-700 font-bold px-2.5 py-1 rounded-lg">
          ▶️ Start Now
        </div>
      </div>
    </a>
    @endforeach
  </div>
</div>
@endif

<!-- ── Stats row ──────────────────────────────────────────────────── -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
  @foreach([
    ['📚','Enrolled',  $enrollments->count(),   'bg-blue-50',  'text-blue-700'],
    ['✅','Completed', $completedCount,          'bg-green-50', 'text-green-700'],
    ['📜','Certs',     $certCount,               'bg-yellow-50','text-yellow-700'],
    ['⏱','Hours',     $totalHours.'h',           'bg-purple-50','text-purple-700'],
  ] as [$icon,$lbl,$val,$bg,$text])
  <div class="{{ $bg }} rounded-2xl p-4 text-center border border-transparent">
    <div class="text-2xl mb-1">{{ $icon }}</div>
    <div class="text-xl font-black {{ $text }}">{{ $val }}</div>
    <div class="text-xs font-medium text-gray-600">{{ $lbl }}</div>
  </div>
  @endforeach
</div>

<!-- ── Bottom grid: Certs + Recommended ──────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- Certificates -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h3 class="font-bold text-gray-900 text-sm">📜 My Certificates</h3>
      <a href="{{ route('student.certs') }}" class="text-xs text-brand-600 font-semibold hover:underline">View all →</a>
    </div>
    <div class="p-4 space-y-3">
      @forelse($certificates as $cert)
      <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-xl border border-yellow-100">
        <span class="text-2xl flex-shrink-0">📜</span>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold text-gray-900 truncate">{{ $cert->course?->title }}</div>
          <div class="text-xs text-gray-400">{{ $cert->issued_at?->format('d M Y') ?? $cert->created_at?->format('d M Y') }}</div>
        </div>
        <a href="#" class="text-xs text-yellow-700 font-bold hover:underline flex-shrink-0">Download</a>
      </div>
      @empty
      <div class="text-center py-8">
        <div class="text-3xl mb-2">🏆</div>
        <p class="text-sm text-gray-500">Complete a course to earn your first certificate!</p>
      </div>
      @endforelse
    </div>
  </div>

  <!-- Recommended -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h3 class="font-bold text-gray-900 text-sm">🔍 Explore Courses</h3>
      <a href="{{ route('student.browse') }}" class="text-xs text-brand-600 font-semibold hover:underline">Browse all →</a>
    </div>
    <div class="p-4 space-y-2">
      @forelse($recommended->take(5) as $course)
      <a href="{{ route('student.course-detail', $course->slug) }}"
         class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition group">
        <div class="w-10 h-10 bg-gradient-to-br from-brand-100 to-accent-100 rounded-xl flex items-center justify-center text-lg flex-shrink-0">📚</div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold text-gray-900 truncate group-hover:text-brand-600 transition">{{ $course->title }}</div>
          <div class="text-xs text-gray-400">{{ ucfirst($course->level) }} · {{ $course->is_free ? 'Free' : '₹'.number_format($course->sale_price ?? $course->price) }}</div>
        </div>
        <svg class="w-4 h-4 text-gray-300 group-hover:text-brand-500 transition flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
      </a>
      @empty
      <div class="text-center py-8 text-gray-400 text-sm">
        <div class="text-3xl mb-2">🎉</div>
        You're enrolled in all available courses!
      </div>
      @endforelse
    </div>
  </div>
</div>

@endsection
