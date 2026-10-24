@extends('layouts.student')
@section('title','My Courses — Skillspot.in')
@section('page-title','My Courses')
@section('page-sub','Your marketplace courses')
@section('student-content')
  @if($enrollments->count())
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      @foreach($enrollments as $enrollment)<x-learner-enrollment-card :enrollment="$enrollment" />@endforeach
    </div>
    @if($enrollments->hasPages())<div class="mt-6">{{ $enrollments->links('pagination::tailwind') }}</div>@endif
  @else
    <div class="rounded-2xl border border-gray-100 bg-white px-6 py-16 text-center">
      <i class="fa-solid fa-book-open text-4xl text-brand-500" aria-hidden="true"></i>
      <h2 class="mt-4 text-xl font-bold text-gray-900">No marketplace courses yet</h2>
      <p class="mt-2 text-sm text-gray-500">Explore courses and start learning.</p>
      <a href="{{ route('student.browse') }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-brand-600 px-5 text-sm font-bold text-white">Browse courses</a>
    </div>
  @endif
@endsection
