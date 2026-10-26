@extends('layouts.admin')
@section('title','Courses — Skillspot.in Admin')
@section('page-title','Courses')
@section('page-sub','')
@section('body-class','courses-page')
@section('page-actions')
<div class="relative" id="courseCreateMenu">
  <button type="button" id="courseCreateMenuButton" aria-expanded="false" aria-controls="courseCreateMenuItems" class="inline-flex items-center gap-2 rounded-md bg-[#171717] px-3 py-1 text-sm font-medium text-white transition hover:bg-[#333] focus:outline-none focus:ring-2 focus:ring-gray-500">
    <i class="fa-solid fa-plus text-xs"></i>
    <span>Create</span>
    <i class="fa-solid fa-chevron-down ml-1 text-[10px]"></i>
  </button>
  <div id="courseCreateMenuItems" class="absolute right-0 z-30 mt-2 hidden w-56 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg" role="menu">
    <button type="button" id="openNewCourseModal" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 transition hover:bg-gray-50" role="menuitem">
      <i class="fa-solid fa-book-open w-4 text-gray-500"></i> New Course
    </button>
    <a href="{{ route('admin.courses.import') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50" role="menuitem">
      <i class="fa-solid fa-folder-plus w-4 text-gray-500"></i> Import via ZIP
    </a>
    <div class="border-t border-gray-100 my-1"></div>
    <a href="{{ route('system.delete-all-courses') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 transition hover:bg-red-50" role="menuitem">
      <i class="fa-solid fa-trash w-4 text-red-500"></i> Delete All Courses
    </a>
  </div>
</div>
@endsection

@push('styles')
<style>
  .courses-page .admin-page-header{height:3rem;padding-inline:1.25rem}
  .courses-page .admin-page-header h1{font-size:1rem;font-weight:500}
  .courses-page .page-profile-action{display:none}
  .courses-page #main-content>div:last-child{height:100%;padding:0}
  .course-tab-active { background: #fff; color: #111827; box-shadow: 0 1px 2px rgb(0 0 0 / 0.08); }
  @include('admin.courses.creation-styles')
  .courses-workspace .course-filters{display:flex;align-items:center;justify-content:flex-end;gap:12px;flex-wrap:wrap}
  .courses-workspace .course-card{width:100%;min-height:350px;border-radius:10px;box-shadow:none}
  .courses-workspace .course-card-cover{height:168px;border:1px solid #e5e7eb;border-bottom:0;border-radius:10px 10px 0 0}
  .courses-workspace .course-card-body{display:flex;min-height:178px;flex-direction:column;border:2px solid #ededed;border-top:0;border-radius:0 0 10px 10px}
  .courses-workspace .course-card-author{margin-top:auto}
  @media(min-width:768px){.courses-workspace .course-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media(min-width:1024px){.courses-workspace .course-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
  @media(min-width:1536px){.courses-workspace .course-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
</style>
@endpush

@section('admin-content')
<div class="courses-workspace flex min-h-full flex-col bg-white text-gray-900">
  @if(session('success'))
  <div class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
    {{ session('success') }}
    <button onclick="this.parentElement.remove()" class="ml-auto" aria-label="Dismiss">×</button>
  </div>
  @endif

  <div class="mb-5 flex shrink-0 flex-col justify-between gap-4 border-b px-5 pb-4 pt-5 sm:border-b-0 sm:pb-0 md:flex-row md:items-center">
    <h2 class="min-w-0 text-lg font-semibold">All Courses</h2>
    <form method="GET" action="{{ route('admin.courses') }}" class="course-filters">
      <div class="flex max-w-full gap-1 overflow-x-auto rounded-lg bg-gray-100 p-1 text-sm text-gray-600 sm:w-fit" aria-label="Course status">
        @foreach([
          'published' => 'Published',
          'upcoming' => 'Upcoming',
          'created' => 'Created',
          'unpublished' => 'Unpublished',
        ] as $value => $label)
        <a href="{{ route('admin.courses', array_merge(request()->except('tab'), ['tab' => $value])) }}"
           @if(request('tab', 'published') === $value) aria-current="page" @endif
           class="whitespace-nowrap rounded-md px-3 py-1 transition {{ request('tab', 'published') === $value ? 'course-tab-active' : 'hover:text-gray-900' }}">
          {{ $label }}
        </a>
        @endforeach
      </div>
      <input type="hidden" name="tab" value="{{ request('tab', 'published') }}">
      <label class="flex h-8 min-w-[min(100%,11rem)] items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 text-sm text-gray-500 sm:w-44">
        <i class="fa-solid fa-magnifying-glass text-xs"></i>
        <span class="sr-only">Search</span><input name="search" type="search" value="{{ request('search') }}" placeholder="Search" aria-label="Search" class="min-w-0 flex-1 bg-transparent outline-none placeholder:text-gray-400">
      </label>

      <label class="sr-only" for="course-category-filter">Category</label>
      <select id="course-category-filter" name="category" onchange="this.form.submit()" class="h-8 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 sm:w-44">
        <option value="">Category</option>
        @foreach($categories as $category)
        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
        @endforeach
      </select>

      <label class="flex min-h-8 items-center gap-2 whitespace-nowrap text-sm text-gray-700">
        <input type="checkbox" name="certification" value="1" onchange="this.form.submit()" {{ request('certification') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
        Certification
      </label>
      <button type="submit" class="sr-only">Search</button>
      @if(request()->hasAny(['search','category','certification']))<a href="{{ route('admin.courses', ['tab'=>request('tab', 'published')]) }}" class="text-sm font-medium text-brand-700 hover:underline">Clear filters</a>@endif
    </form>
  </div>

  @if($courses->count())
  <div class="course-grid grid grid-cols-1 gap-5 px-5 pb-5">
    @foreach($courses as $course)
    @php
      $lessonCount = $course->sections->sum(fn ($section) => $section->lessons->count());
      $rating = rtrim(rtrim(number_format((float) ($course->reviews_avg_rating ?? 0), 1), '0'), '.');
      $creator = $course->primaryInstructor?->name ?? $course->vendor?->brand_name ?? 'Skillspot';
      $coverColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $course->thumbnail_color) ? $course->thumbnail_color : '#4b5563';
    @endphp
    <article class="course-card isolate flex h-full flex-col overflow-hidden bg-white">
      <a href="{{ route('admin.courses.edit', $course) }}" aria-label="Open {{ $course->title }}" class="course-card-cover block overflow-hidden bg-[#f5f1e6]">
        @if($course->thumbnail)
        <img src="{{ $course->thumbnail }}" alt="{{ $course->title }} course cover" loading="lazy" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden')">
        <div class="hidden h-full items-center justify-center bg-gradient-to-tr from-black px-5 text-center text-xl font-extrabold leading-6 text-white" style="--tw-gradient-to: {{ $coverColor }}">{{ $course->title }}</div>
        @else
        <div class="flex h-full items-center justify-center bg-gradient-to-tr from-black px-5 text-center font-extrabold leading-6 text-white {{ mb_strlen($course->title) > 32 ? 'text-lg' : (mb_strlen($course->title) > 20 ? 'text-2xl' : 'text-3xl') }}" style="--tw-gradient-to: {{ $coverColor }}">{{ $course->title }}</div>
        @endif
      </a>
      <div class="course-card-body p-4">
        <div class="mb-2 flex min-h-5 items-center justify-between text-sm text-gray-600">
          @if($lessonCount)
          <span aria-label="{{ $lessonCount }} {{ $lessonCount === 1 ? 'lesson' : 'lessons' }}"><i class="fa-solid fa-book-open mr-1.5" aria-hidden="true"></i>{{ $lessonCount }} <span class="sr-only">{{ $lessonCount === 1 ? 'lesson' : 'lessons' }}</span></span>
          @endif
          @if($course->enrollments_count)
          <span aria-label="{{ $course->enrollments_count }} {{ $course->enrollments_count === 1 ? 'learner' : 'learners' }}"><i class="fa-solid fa-users mr-1.5" aria-hidden="true"></i>{{ $course->enrollments_count }} <span class="sr-only">{{ $course->enrollments_count === 1 ? 'learner' : 'learners' }}</span></span>
          @endif
          <span aria-label="Rating {{ $rating }}" class="text-amber-700"><i class="fa-solid fa-star mr-1.5" aria-hidden="true"></i>{{ $rating }}</span>
          @if($course->is_featured)<span aria-label="Featured" class="text-amber-600"><i class="fa-solid fa-award" aria-hidden="true"></i></span>@endif
        </div>
        @if($course->thumbnail)<h3 class="font-semibold leading-6 text-gray-900 {{ mb_strlen($course->title) > 32 ? 'text-lg' : 'text-2xl' }}"><a href="{{ route('admin.courses.edit', $course) }}">{{ $course->title }}</a></h3>@endif
        <p class="mb-5 mt-1 line-clamp-2 w-full text-sm leading-5 text-gray-700" title="{{ $course->short_introduction }}">{{ $course->short_introduction }}</p>
        <div class="course-card-author flex items-center justify-between gap-2 text-sm text-gray-700">
          <span class="flex min-w-0 items-center gap-2"><span aria-hidden="true" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-700">{{ mb_strtoupper(mb_substr($creator, 0, 1)) }}</span><span class="truncate">{{ $creator }}</span></span>
          @if($course->enable_certification)<span aria-label="Get Certified" class="shrink-0 text-gray-600"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i></span>@endif
        </div>
      </div>
    </article>
    @endforeach
  </div>
  @else
  <div class="mx-5 rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center text-sm text-gray-500">
    <p>No courses match these filters.</p><a href="{{ route('admin.courses', ['tab'=>'all']) }}" class="mt-3 inline-block font-medium text-brand-700 hover:underline">View all courses</a>
  </div>
  @endif

  @if($courses->hasPages())
  <div class="mt-auto px-5 pb-4">
    {{ $courses->withQueryString()->links('pagination::tailwind') }}
  </div>
  @endif
  <div class="{{ $courses->hasPages() ? '' : 'mt-auto' }} flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-white px-5 py-2 text-sm text-gray-500">
    <div class="flex items-center gap-3">@foreach([24,60,120] as $limit)<a href="{{ route('admin.courses', array_merge(request()->except(['limit','page']), ['limit'=>$limit])) }}" @if($pageLength === $limit) aria-current="true" @endif class="rounded-md px-2 py-1 {{ $pageLength === $limit ? 'bg-gray-100 font-semibold text-gray-900' : '' }}">{{ $limit }}</a>@endforeach</div>
    <span>{{ $courses->count() }} of {{ $courses->total() }}</span>
  </div>
</div>

@include('admin.courses.creation-modal')
@endsection

@push('scripts')
@include('admin.courses.creation-script')
@endpush
