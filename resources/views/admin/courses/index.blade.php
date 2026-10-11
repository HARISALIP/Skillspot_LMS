@extends('layouts.admin')
@section('title','Courses — Skillspot.in Admin')
@section('page-title','Courses')
@section('page-sub','')
@section('page-actions')
<div class="relative" id="courseCreateMenu">
  <button type="button" id="courseCreateMenuButton" aria-expanded="false" aria-controls="courseCreateMenuItems" class="inline-flex items-center gap-2 rounded-lg bg-[#f7f7f7] px-3 py-1.5 text-sm font-medium text-[#202020] transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
    <i class="fa-solid fa-plus text-xs"></i>
    <span>Create</span>
    <i class="fa-solid fa-chevron-down ml-1 text-[10px]"></i>
  </button>
  <div id="courseCreateMenuItems" class="absolute right-0 z-30 mt-2 hidden w-56 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg" role="menu">
    <a href="{{ route('admin.courses.create') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50" role="menuitem">
      <i class="fa-regular fa-book-open w-4 text-gray-500"></i> New Course
    </a>
    <a href="{{ route('admin.courses.import') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50" role="menuitem">
      <i class="fa-solid fa-folder-plus w-4 text-gray-500"></i> Import via ZIP
    </a>
  </div>
</div>
@endsection

@push('styles')
<style>.course-tab-active { background: #fff; color: #111827; box-shadow: 0 1px 2px rgb(0 0 0 / 0.08); }</style>
@endpush

@section('admin-content')
<div class="courses-workspace -m-4 min-h-[calc(100vh-4rem)] bg-gray-50 px-5 py-5 text-gray-900 md:-m-6 md:px-6">
  @if(session('success'))
  <div class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
    {{ session('success') }}
    <button onclick="this.parentElement.remove()" class="ml-auto" aria-label="Dismiss">×</button>
  </div>
  @endif

  <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
    <h3 class="text-base font-bold">All Courses</h3>
    <form method="GET" action="{{ route('admin.courses') }}" class="flex flex-wrap items-center gap-3">
      <div class="flex rounded-lg bg-gray-100 p-1 text-sm text-gray-500">
        @foreach([
          'published' => 'Published',
          'upcoming' => 'Upcoming',
          'created' => 'Created',
          'unpublished' => 'Unpublished',
        ] as $value => $label)
        <a href="{{ route('admin.courses', array_merge(request()->except('tab'), ['tab' => $value])) }}"
           class="rounded-md px-2.5 py-1 transition {{ request('tab', 'published') === $value ? 'course-tab-active' : 'hover:text-gray-900' }}">
          {{ $label }}
        </a>
        @endforeach
      </div>

      <label class="flex h-8 w-44 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-500">
        <i class="fa-solid fa-magnifying-glass text-xs"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search" class="min-w-0 flex-1 bg-transparent outline-none placeholder:text-gray-400">
      </label>

      <select name="category" onchange="this.form.submit()" class="h-8 w-44 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-500 outline-none">
        <option value="">Category</option>
        @foreach($categories as $category)
        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
        @endforeach
      </select>

      {{-- shortcut: certification is not modeled on Course, wire this filter when the offering flag exists. --}}
      <label class="flex items-center gap-2 whitespace-nowrap text-sm text-gray-600">
        <input type="checkbox" name="certification" value="1" {{ request('certification') ? 'checked' : '' }} class="h-3.5 w-3.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
        Certification
      </label>
    </form>
  </div>

  @if($courses->count())
  <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach($courses as $course)
    @php
      $lessonCount = $course->sections->sum(fn ($section) => $section->lessons->count());
      $rating = $course->reviews_avg_rating ? number_format($course->reviews_avg_rating, 1) : '—';
    @endphp
    <article class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
      <a href="{{ route('admin.courses.edit', $course) }}" class="block aspect-[16/7] overflow-hidden bg-[#f5f1e6]">
        @if($course->thumbnail)
        <img src="{{ $course->thumbnail }}" alt="" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden')">
        <div class="hidden h-full items-center justify-center text-5xl">📚</div>
        @else
        <div class="flex h-full items-center justify-center text-5xl">📚</div>
        @endif
      </a>
      <div class="p-4">
        <div class="flex items-center justify-between text-sm text-gray-500">
          <span><i class="fa-regular fa-book-open mr-1.5"></i>{{ $lessonCount }}</span>
          <span><i class="fa-solid fa-users mr-1.5"></i>{{ $course->enrollments_count }}</span>
          <span class="text-[#f3b23c]"><i class="fa-solid fa-star mr-1.5"></i>{{ $rating }}</span>
        </div>
        <h4 class="mt-3 truncate text-base font-bold text-gray-900">{{ $course->title }}</h4>
        <p class="mt-1.5 line-clamp-2 min-h-10 text-sm leading-5 text-gray-600">{{ Str::limit(strip_tags($course->description), 110) }}</p>
        <div class="mt-5 flex items-center justify-between">
          <span class="text-sm text-gray-500">{{ $course->category }}</span>
          <a href="{{ route('admin.courses.edit', $course) }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Manage</a>
        </div>
      </div>
    </article>
    @endforeach
  </div>
  @else
  <div class="mt-5 rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center text-sm text-gray-500">
    No courses match these filters.
  </div>
  @endif

  @if($courses->hasPages())
  <div class="mt-8 border-t border-gray-200 pt-4">
    {{ $courses->withQueryString()->links('pagination::tailwind') }}
  </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
  const courseCreateMenu = document.getElementById('courseCreateMenu');
  const courseCreateMenuButton = document.getElementById('courseCreateMenuButton');
  const courseCreateMenuItems = document.getElementById('courseCreateMenuItems');
  courseCreateMenuButton?.addEventListener('click', () => {
    const isOpen = !courseCreateMenuItems.classList.toggle('hidden');
    courseCreateMenuButton.setAttribute('aria-expanded', String(isOpen));
  });
  document.addEventListener('click', (event) => {
    if (!courseCreateMenu?.contains(event.target)) {
      courseCreateMenuItems?.classList.add('hidden');
      courseCreateMenuButton?.setAttribute('aria-expanded', 'false');
    }
  });
</script>
@endpush
