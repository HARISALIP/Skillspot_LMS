@extends('layouts.vendor')
@section('title','Courses — Organization — Skillspot')
@section('header','Courses')
@push('styles')<style>@include('admin.courses.creation-styles')</style>@endpush
@section('content')
<div class="skillspot-course-catalog -m-4 min-h-full p-4 md:-m-6 md:p-5">
  <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-lg font-semibold">All Courses</h1>
    @unless($isVendorOnly)<a id="openNewCourseModal" href="{{ route('vendor.courses.create') }}" class="inline-flex min-h-8 items-center rounded-md bg-gray-900 px-3 text-sm font-semibold text-white hover:bg-gray-700">+ Create</a>@endunless
  </div>
  @if($isVendorOnly)
    <p class="mb-5 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"><strong>View Only</strong> — You can view courses and student progress. Contact a teacher to make changes.</p>
  @endif
  @if($courses->count())
    <div class="course-grid">
      @foreach($courses as $course)
        @include('partials.course-catalog-card', ['href' => $isVendorOnly ? route('vendor.courses.students', $course) : route('vendor.courses.edit', $course), 'manage' => true])
      @endforeach
    </div>
    @if($courses->hasPages())<div class="mt-6">{{ $courses->links() }}</div>@endif
  @else
    <div class="rounded-lg border border-dashed border-gray-300 p-12 text-center text-gray-600">No courses yet. @unless($isVendorOnly)<a href="{{ route('vendor.courses.create') }}" class="text-brand-700 hover:underline">Create the first course</a>.@endunless</div>
  @endif
  <div class="mt-6 flex justify-end border-t border-gray-200 pt-2 text-sm text-gray-500">{{ $courses->count() }} of {{ $courses->total() }}</div>
</div>
@unless($isVendorOnly)
  @include('admin.courses.creation-modal', ['courseCreateAction' => route('vendor.courses.modal.store')])
@endunless
@endsection
@unless($isVendorOnly)
  @push('scripts')@include('admin.courses.creation-script')@endpush
@endunless
