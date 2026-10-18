@extends('layouts.student')
@section('title','My Courses — Skillspot.in')
@section('page-title','Courses')
@section('page-sub','Your enrolled courses')

@section('student-content')
<div class="skillspot-course-catalog -m-4 min-h-full p-4 md:-m-6 md:p-5">
  <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><h1 class="text-lg font-semibold">My Courses</h1><a href="{{ route('student.browse') }}" class="text-sm font-medium text-brand-700 hover:underline">Browse courses</a></div>
  @if($enrollments->count())
    <div class="course-grid">
      @foreach($enrollments as $enrollment)
        @php
          $course = $enrollment->course;
          if (!$course) continue;
          $isExpired = $enrollment->access_type === 'limited' && $enrollment->expires_at?->isPast();
          $totalLessons = $course->sections->sum(fn ($section) => $section->lessons->count());
          $completedLessons = \DB::table('lesson_progress')->where('user_id', auth()->id())
              ->whereIn('lesson_id', $course->sections->flatMap->lessons->pluck('id'))->where('completed', 1)->count();
        @endphp
        @include('partials.course-catalog-card', ['href' => $isExpired ? route('student.course-detail', $course->slug) : route('student.learn', $course), 'enrollment' => $enrollment])
      @endforeach
    </div>
    @if($enrollments->hasPages())<div class="mt-6">{{ $enrollments->links('pagination::tailwind') }}</div>@endif
  @else
    <div class="rounded-lg border border-dashed border-gray-300 p-12 text-center text-gray-600">No courses yet. <a href="{{ route('student.browse') }}" class="text-brand-700 hover:underline">Browse courses</a> to get started.</div>
  @endif
  <div class="mt-6 flex justify-end border-t border-gray-200 pt-2 text-sm text-gray-500">{{ $enrollments->count() }} of {{ $enrollments->total() }}</div>
</div>
@endsection
