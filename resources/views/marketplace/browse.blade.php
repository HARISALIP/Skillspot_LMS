@extends('layouts.landing')

@section('title', 'Explore Courses — Skillspot.in')

@section('content')
<main class="pt-28 pb-16">
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
      <p class="text-sm font-bold tracking-widest text-brand-600 uppercase">Skillspot Marketplace</p>
      <h1 class="mt-2 text-3xl sm:text-4xl font-black text-slate-900">Learn from verified instructors</h1>
      <p class="mt-3 text-slate-600">Browse live courses, preview the curriculum, and enroll when you are ready.</p>
    </div>

    <form method="GET" class="mb-8 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
      <input name="search" value="{{ request('search') }}" placeholder="Search courses" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
      <select name="category" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
        <option value="">All categories</option>
        @foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach
      </select>
      <button class="rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700">Search</button>
    </form>

    @if($courses->count())
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      @foreach($courses as $course)
      <a href="{{ route('student.course-detail', $course->slug) }}" class="card-hover overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="h-44 bg-gradient-to-br from-brand-100 to-accent-100">
          @if($course->thumbnail)<img src="{{ $course->thumbnail }}" alt="{{ $course->title }}" class="h-full w-full object-cover">@endif
        </div>
        <div class="p-5">
          <p class="text-xs font-bold uppercase tracking-wide text-brand-600">{{ $course->category ?: 'Course' }}</p>
          <h2 class="mt-2 line-clamp-2 text-lg font-bold text-slate-900">{{ $course->title }}</h2>
          <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $course->short_introduction }}</p>
          <div class="mt-4 flex items-center justify-between gap-3 text-sm">
            <span class="font-semibold text-slate-700">{{ $course->vendor?->brand_name ?: $course->primaryInstructor?->name }}</span>
            <span class="font-black text-slate-900">{{ $course->is_free ? 'Free' : '₹' . number_format($course->sale_price ?? $course->price, 0) }}</span>
          </div>
        </div>
      </a>
      @endforeach
    </div>
    <div class="mt-8">{{ $courses->links() }}</div>
    @else
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-600">No live courses match those filters yet.</div>
    @endif
  </section>
</main>
@endsection
