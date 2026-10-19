@extends('layouts.student')
@section('title','Browse Courses — Skillspot.in')
@section('page-title','Courses')
@section('page-sub', 'Find your next skill — ' . $courses->total() . ' ' . \Illuminate\Support\Str::plural('course', $courses->total()) . ' available')

@section('student-content')
<div class="skillspot-course-catalog -m-4 min-h-full p-4 md:-m-6 md:p-5">
  <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
    <h1 class="text-lg font-semibold">All Courses</h1>
    <form method="GET" action="{{ route('student.browse') }}" class="flex max-w-full flex-wrap items-center gap-2 text-sm">
      <label for="course-search" class="sr-only">Search courses</label>
      <input id="course-search" name="search" type="search" value="{{ request('search') }}" placeholder="Search" class="h-8 min-w-0 rounded-md border border-gray-200 bg-gray-50 px-3 sm:w-40">
      <label for="course-category" class="sr-only">Course category</label>
      <select id="course-category" name="category" onchange="this.form.submit()" class="h-8 rounded-md border border-gray-200 bg-gray-50 px-2 sm:w-36">
        <option value="">Category</option>
        @foreach($categories as $cat)<option value="{{ $cat->name }}" @selected(request('category') === $cat->name)>{{ $cat->name }}</option>@endforeach
      </select>
      <label for="course-level" class="sr-only">Course level</label>
      <select id="course-level" name="level" onchange="this.form.submit()" class="h-8 rounded-md border border-gray-200 bg-gray-50 px-2 sm:w-32">
        <option value="">Level</option>
        @foreach($levels as $lv)<option value="{{ $lv->slug }}" @selected(request('level') === $lv->slug)>{{ $lv->name }}</option>@endforeach
      </select>
      <label for="course-sort" class="sr-only">Sort courses</label>
      <select id="course-sort" name="sort" onchange="this.form.submit()" class="h-8 rounded-md border border-gray-200 bg-gray-50 px-2 sm:w-36">
        <option value="" @selected(!request('sort'))>Newest</option>
        <option value="popular" @selected(request('sort') === 'popular')>Most popular</option>
        <option value="price_lo" @selected(request('sort') === 'price_lo')>Price: low to high</option>
        <option value="price_hi" @selected(request('sort') === 'price_hi')>Price: high to low</option>
      </select>
      <label class="flex min-h-8 items-center gap-1.5 whitespace-nowrap"><input type="checkbox" name="free" value="1" onchange="this.form.submit()" @checked(request('free'))> Free only</label>
      <button type="submit" class="rounded-md bg-gray-900 px-3 py-1.5 text-white">Search</button>
      @if(request()->hasAny(['search','category','level','sort','free']))<a href="{{ route('student.browse') }}" class="text-brand-700 hover:underline">Clear</a>@endif
    </form>
  </div>

  @if($courses->count())
    <div class="course-grid">
      @foreach($courses as $course)
        @include('partials.course-catalog-card', ['href' => route('student.course-detail', $course->slug)])
      @endforeach
    </div>
    @if($courses->hasPages())<div class="mt-6">{{ $courses->withQueryString()->links('pagination::tailwind') }}</div>@endif
  @else
    <div class="rounded-lg border border-dashed border-gray-300 p-12 text-center text-gray-600">No courses match these filters. <a href="{{ route('student.browse') }}" class="text-brand-700 hover:underline">Clear filters</a></div>
  @endif
  <div class="mt-6 flex justify-end border-t border-gray-200 pt-2 text-sm text-gray-500">{{ $courses->count() }} of {{ $courses->total() }}</div>
</div>
@endsection
