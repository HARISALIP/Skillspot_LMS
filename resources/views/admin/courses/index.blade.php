@extends('layouts.admin')
@section('title','Courses — Skillspot.in Admin')
@section('page-title','Courses')
@section('page-sub','')
@section('page-actions')
<a href="{{ route('admin.courses.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#f7f7f7] px-3 py-1.5 text-sm font-medium text-[#202020] transition hover:bg-white">
  <i class="fa-solid fa-plus text-xs"></i>
  <span>Create</span>
  <i class="fa-solid fa-chevron-down ml-1 text-[10px]"></i>
</a>
@endsection

@push('styles')
<style>
  body:has(.courses-workspace) { background: #1b1b1b; }
  body:has(.courses-workspace) header { background: #1b1b1b; border-color: #303030; }
  body:has(.courses-workspace) header h1 { color: #f5f5f5; }
  body:has(.courses-workspace) main { background: #1b1b1b; }
  .course-tab-active { background: #444; color: #fff; box-shadow: inset 0 0 0 1px #555; }
</style>
@endpush

@section('admin-content')
<div class="courses-workspace -m-4 min-h-[calc(100vh-4rem)] bg-[#1b1b1b] px-5 py-3 text-[#f5f5f5] md:-m-6 md:px-5">
  @if(session('success'))
  <div class="flex items-center gap-3 rounded-lg border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">
    {{ session('success') }}
    <button onclick="this.parentElement.remove()" class="ml-auto" aria-label="Dismiss">×</button>
  </div>
  @endif

  <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
    <h3 class="text-base font-bold">All Courses</h3>
    <form method="GET" action="{{ route('admin.courses') }}" class="flex flex-wrap items-center gap-3">
      <div class="flex rounded-lg bg-[#303030] p-1 text-sm text-[#a0a0a0]">
        @foreach([
          'published' => 'Published',
          'upcoming' => 'Upcoming',
          'created' => 'Created',
          'unpublished' => 'Unpublished',
        ] as $value => $label)
        <a href="{{ route('admin.courses', array_merge(request()->except('tab'), ['tab' => $value])) }}"
           class="rounded-md px-2.5 py-1 transition {{ request('tab', 'published') === $value ? 'course-tab-active' : 'hover:text-[#e6e6e6]' }}">
          {{ $label }}
        </a>
        @endforeach
      </div>

      <label class="flex h-7 w-44 items-center gap-2 rounded-lg bg-[#303030] px-3 text-sm text-[#a0a0a0]">
        <i class="fa-solid fa-magnifying-glass text-xs"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search" class="min-w-0 flex-1 bg-transparent outline-none placeholder:text-[#8d8d8d]">
      </label>

      <select name="category" onchange="this.form.submit()" class="h-7 w-44 rounded-lg border-0 bg-[#303030] px-3 text-sm text-[#a0a0a0] outline-none">
        <option value="">Category</option>
        @foreach($categories as $category)
        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
        @endforeach
      </select>

      {{-- shortcut: certification is not modeled on Course, wire this filter when the offering flag exists. --}}
      <label class="flex items-center gap-2 whitespace-nowrap text-sm text-[#d0d0d0]">
        <input type="checkbox" name="certification" value="1" {{ request('certification') ? 'checked' : '' }} class="h-3.5 w-3.5 rounded border-[#5a5a5a] bg-transparent text-[#f3f3f3] focus:ring-0">
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
    <article class="overflow-hidden rounded-lg border border-[#353535] bg-[#262626] transition hover:border-[#4a4a4a]">
      <a href="{{ route('admin.courses.edit', $course) }}" class="block aspect-[16/7] overflow-hidden bg-[#f5f1e6]">
        @if($course->thumbnail)
        <img src="{{ $course->thumbnail }}" alt="" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden')">
        <div class="hidden h-full items-center justify-center text-5xl">📚</div>
        @else
        <div class="flex h-full items-center justify-center text-5xl">📚</div>
        @endif
      </a>
      <div class="p-4">
        <div class="flex items-center justify-between text-sm text-[#f0f0f0]">
          <span><i class="fa-regular fa-book-open mr-1.5 text-[#b7b7b7]"></i>{{ $lessonCount }}</span>
          <span><i class="fa-solid fa-users mr-1.5 text-[#b7b7b7]"></i>{{ $course->enrollments_count }}</span>
          <span class="text-[#f3b23c]"><i class="fa-solid fa-star mr-1.5"></i>{{ $rating }}</span>
        </div>
        <h4 class="mt-3 truncate text-base font-bold text-white">{{ $course->title }}</h4>
        <p class="mt-1.5 line-clamp-2 min-h-10 text-sm leading-5 text-[#f1f1f1]">{{ Str::limit(strip_tags($course->description), 110) }}</p>
        <div class="mt-5 flex items-center justify-between">
          <span class="text-sm text-[#b8b8b8]">{{ $course->category }}</span>
          <a href="{{ route('admin.courses.edit', $course) }}" class="text-sm font-medium text-white hover:text-[#d0d0d0]">Manage</a>
        </div>
      </div>
    </article>
    @endforeach
  </div>
  @else
  <div class="mt-5 rounded-lg border border-dashed border-[#3c3c3c] py-16 text-center text-sm text-[#969696]">
    No courses match these filters.
  </div>
  @endif

  @if($courses->hasPages())
  <div class="mt-8 border-t border-[#282828] pt-4">
    {{ $courses->withQueryString()->links('pagination::tailwind') }}
  </div>
  @endif
</div>
@endsection
