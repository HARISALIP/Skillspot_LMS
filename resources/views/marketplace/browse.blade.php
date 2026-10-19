@extends('layouts.landing')

@section('title', 'Explore Courses — Skillspot.in')

@section('content')
<div class="pt-28 pb-16">
  <section class="skillspot-course-catalog max-w-7xl mx-auto px-4 py-5 sm:px-6 lg:px-8">
    <div class="mb-5">
      <p class="text-sm font-bold tracking-widest text-brand-600 uppercase">Skillspot Marketplace</p>
      <h1 class="mt-2 text-3xl sm:text-4xl font-black text-slate-900">Learn from verified instructors</h1>
      <p class="mt-3 text-slate-600">Browse live courses, preview the curriculum, and enroll when you are ready.</p>
    </div>

    <form method="GET" class="mb-5 flex flex-wrap items-center gap-2 text-sm">
      <h2 class="mr-auto text-lg font-semibold">All Courses</h2>
      <label for="marketplace-search" class="sr-only">Search courses</label>
      <input id="marketplace-search" name="search" type="search" value="{{ request('search') }}" placeholder="Search" class="h-8 rounded-md border border-gray-200 bg-gray-50 px-3">
      <label for="marketplace-category" class="sr-only">Course category</label>
      <select id="marketplace-category" name="category" onchange="this.form.submit()" class="h-8 rounded-md border border-gray-200 bg-gray-50 px-3">
        <option value="">Category</option>
        @foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach
      </select>
      <button class="rounded-md bg-gray-900 px-3 py-1.5 text-white">Search</button>
      @if(request()->hasAny(['search','category']))<a href="{{ route('marketplace.browse') }}" class="text-brand-700 hover:underline">Clear</a>@endif
    </form>

    @if($courses->count())
    <div class="course-grid">
      @foreach($courses as $course)
        @include('partials.course-catalog-card', ['href' => route('student.course-detail', $course->slug)])
      @endforeach
    </div>
    <div class="mt-8">{{ $courses->links() }}</div>
    @else
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-600">No live courses match those filters yet.</div>
    @endif
    <div class="mt-6 flex justify-end border-t border-gray-200 pt-2 text-sm text-gray-500">{{ $courses->count() }} of {{ $courses->total() }}</div>
  </section>
</div>
@endsection
