@extends('layouts.student')
@section('title','My Courses — Skillspot.in')
@section('page-title','My Courses')
@section('page-sub','Your enrolled courses')

@section('student-content')

@if($enrollments->count())
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
  @foreach($enrollments as $en)
  @php
    $course = $en->course;
    if(!$course) continue;
    $isExpired  = $en->access_type==='limited' && $en->expires_at?->isPast();
    $isExpiring = !$isExpired && $en->access_type==='limited' && $en->expires_at && $en->expires_at->diffInDays(now())<=7;
    $totalLessons = $course->sections->sum(fn($s)=>$s->lessons->count());
    $completedLessons = \DB::table('lesson_progress')->where('user_id',auth()->id())
      ->whereIn('lesson_id',$course->sections->flatMap->lessons->pluck('id'))->where('completed',1)->count();
  @endphp
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden {{ $isExpired?'opacity-60':'' }}">
    <div class="h-32 bg-gradient-to-br from-brand-100 to-accent-100 flex items-center justify-center relative overflow-hidden">
      @if($course->thumbnail)
        <img src="{{ $course->thumbnail }}" class="w-full h-full object-cover" alt="">
      @else
        <span class="text-5xl">📚</span>
      @endif
      @if($isExpired)
      <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
        <span class="text-white font-black text-sm">Access Expired</span>
      </div>
      @elseif($isExpiring)
      <div class="absolute top-2 left-2 bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-lg">⚠️ Expiring Soon</div>
      @endif
      <div class="absolute top-2 right-2 bg-black/60 text-white text-xs font-bold px-2 py-0.5 rounded-lg">
        {{ $en->progress }}%
      </div>
    </div>
    <div class="p-4">
      <div class="text-xs text-brand-600 font-semibold mb-1">{{ $course->category }}</div>
      <h3 class="font-bold text-gray-900 text-sm line-clamp-2 mb-2">{{ $course->title }}</h3>
      <!-- Progress -->
      <div class="mb-3">
        <div class="flex justify-between text-xs text-gray-400 mb-1">
          <span>{{ $completedLessons }}/{{ $totalLessons }} lessons</span>
          <span>{{ $en->progress }}%</span>
        </div>
        <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
          <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500" style="width:{{ $en->progress }}%"></div>
        </div>
      </div>
      <!-- Access info -->
      <div class="flex items-center justify-between mb-3 text-xs">
        @if($en->access_type==='lifetime')
          <span class="text-green-600 font-semibold">♾️ Lifetime</span>
        @elseif($isExpired)
          <span class="text-red-600 font-semibold">❌ Expired</span>
        @elseif($isExpiring)
          <span class="text-orange-600 font-semibold">⚠️ Expires {{ $en->expires_at->format('d M') }}</span>
        @else
          <span class="text-blue-600 font-semibold">📅 Until {{ $en->expires_at->format('d M Y') }}</span>
        @endif
        @if($en->status==='completed')
          <span class="text-green-600 font-bold">✅ Done</span>
        @endif
      </div>
      @if(!$isExpired)
      <a href="{{ route('student.learn', $course->id) }}"
         class="block w-full text-center py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl text-sm transition">
        {{ $en->progress > 0 ? '▶️ Continue' : '▶️ Start Learning' }}
      </a>
      @else
      <div class="block w-full text-center py-2.5 bg-gray-100 text-gray-400 font-bold rounded-xl text-sm cursor-not-allowed">
        Access Expired
      </div>
      @endif
    </div>
  </div>
  @endforeach
</div>

@if($enrollments->hasPages())
<div class="mt-6">{{ $enrollments->links('pagination::tailwind') }}</div>
@endif

@else
<div class="text-center py-20">
  <div class="text-6xl mb-4">📚</div>
  <h3 class="text-xl font-black text-gray-900 mb-2">No courses yet</h3>
  <p class="text-gray-500 mb-6">Explore our catalog and enroll in your first course!</p>
  <a href="{{ route('student.browse') }}" class="inline-block bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3 rounded-2xl transition">Browse Courses →</a>
</div>
@endif

@endsection
