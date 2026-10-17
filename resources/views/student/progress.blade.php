@extends('layouts.student')
@section('title','Progress — Skillspot.in')
@section('page-title','My Progress')
@section('page-sub','Track your learning journey')
@section('student-content')
@if($enrollments->isNotEmpty())
@php
  $average = (int) round($enrollments->avg('progress'));
  $completedCourses = $enrollments->where('status', 'completed')->count();
@endphp
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
  <div class="rounded-2xl border border-gray-100 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-gray-400">Courses</p><p class="mt-2 text-2xl font-black text-gray-900">{{ $enrollments->count() }}</p></div>
  <div class="rounded-2xl border border-gray-100 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-gray-400">Average progress</p><p class="mt-2 text-2xl font-black text-gray-900">{{ $average }}%</p></div>
  <div class="rounded-2xl border border-gray-100 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-gray-400">Completed</p><p class="mt-2 text-2xl font-black text-gray-900">{{ $completedCourses }}</p></div>
</div>
<div class="space-y-4">
@foreach($enrollments as $enrollment)
  @php
    $lessonIds = $enrollment->course->sections->flatMap->lessons->pluck('id');
    $completedLessons = $lessonIds->intersect($completedLessonIds)->count();
    $progress = min(100, max(0, (int) $enrollment->progress));
  @endphp
  <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-wide text-brand-600">{{ $enrollment->course->category ?: 'Course' }}</p>
        <h2 class="mt-1 font-bold text-gray-900">{{ $enrollment->course->title }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $completedLessons }}/{{ $lessonIds->count() }} lessons complete</p>
      </div>
      <a href="{{ route('student.learn', $enrollment->course) }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-brand-600 px-4 text-sm font-bold text-white hover:bg-brand-700">{{ $progress > 0 ? 'Continue learning' : 'Start learning' }}</a>
    </div>
    <div class="mt-4 flex items-center gap-3">
      <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-label="{{ $enrollment->course->title }} progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500" style="width:{{ $progress }}%"></div></div>
      <span class="w-12 text-right text-sm font-bold text-gray-700">{{ $progress }}%</span>
    </div>
  </article>
@endforeach
</div>
@else
<div class="text-center py-10">
  <div class="text-5xl mb-4">📊</div>
  <h3 class="text-lg font-black text-gray-900 mb-2">No course progress yet</h3>
  <p class="text-gray-500 mb-4">Enroll in a course to start tracking your learning.</p>
  <a href="{{ route('student.browse') }}" class="inline-flex min-h-11 items-center rounded-xl bg-brand-600 px-6 font-bold text-white hover:bg-brand-700">Browse Courses →</a>
</div>
@endif
@endsection
