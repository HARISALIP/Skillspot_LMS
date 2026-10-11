@extends('layouts.admin')
@section('title','Courses — Skillspot.in Admin')
@section('page-title','Courses')
@section('page-sub','Create and manage your academy courses')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  <span class="text-xl">✅</span> {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600 text-xl">×</button>
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  <span class="text-xl">❌</span> {{ session('error') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-600 text-xl">×</button>
</div>
@endif

<div class="space-y-5">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <p class="text-sm text-gray-500">{{ $totalCourses }} {{ Str::plural('course', $totalCourses) }} · {{ $totalEnroll }} {{ Str::plural('learner', $totalEnroll) }}</p>
    </div>
    <a href="{{ route('admin.courses.create') }}"
       class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-700">
      <i class="fa-solid fa-plus"></i> Create course
    </a>
  </div>

  <form method="GET" action="{{ route('admin.courses') }}" class="flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-3 shadow-sm lg:flex-row lg:items-center">
    <div class="flex items-center gap-1 overflow-x-auto rounded-xl bg-gray-100 p-1 text-sm font-semibold">
      <a href="{{ route('admin.courses', request()->except('status')) }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ !request('status') ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">All <span class="ml-1 text-xs text-gray-400">{{ $totalCourses }}</span></a>
      <a href="{{ route('admin.courses', array_merge(request()->except('status'), ['status' => 'published'])) }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ request('status') === 'published' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">Published <span class="ml-1 text-xs text-gray-400">{{ $published }}</span></a>
      <a href="{{ route('admin.courses', array_merge(request()->except('status'), ['status' => 'draft'])) }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ request('status') === 'draft' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">Drafts <span class="ml-1 text-xs text-gray-400">{{ $draft }}</span></a>
    </div>
    <div class="relative flex-1 min-w-[200px]">
      <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="Search courses…"
              class="w-full rounded-xl border border-gray-200 bg-white py-2 pl-8 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <select name="category" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
      <option value="">All Categories</option>
      @foreach($categories as $cat)
      <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
      @endforeach
    </select>
    <select name="level" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
      <option value="">All Levels</option>
      @foreach(['beginner','intermediate','advanced'] as $lv)
      <option value="{{ $lv }}" {{ request('level') === $lv ? 'selected' : '' }}>{{ ucfirst($lv) }}</option>
      @endforeach
    </select>
    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold transition">Search</button>
    @if(request()->hasAny(['search','category','level','status']))
    <a href="{{ route('admin.courses') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold transition">Clear</a>
    @endif
  </form>

  @if($courses->count())
  <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @foreach($courses as $course)
    @php $vis = $course->visibility ?? ($course->is_published ? 'public' : 'draft'); @endphp
    <article class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
      <a href="{{ route('admin.courses.edit', $course) }}" class="block aspect-[16/8] overflow-hidden bg-gradient-to-br from-brand-100 to-accent-100">
        @if($course->thumbnail)
        <img src="{{ $course->thumbnail }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
        <div class="flex h-full items-center justify-center text-5xl">📚</div>
        @endif
      </a>
      <div class="space-y-4 p-5">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="truncate text-xs font-semibold uppercase tracking-wide text-brand-600">{{ $course->category ?? 'Uncategorised' }}</p>
            <h2 class="mt-1 truncate text-base font-bold text-gray-900">{{ $course->title }}</h2>
          </div>
          <form method="POST" action="{{ route('admin.courses.toggle-publish', $course) }}" title="Change course visibility">
            @csrf @method('PATCH')
            <button type="submit" class="whitespace-nowrap rounded-lg px-2.5 py-1 text-xs font-bold {{ $vis === 'public' ? 'bg-green-100 text-green-700 hover:bg-green-200' : ($vis === 'restricted' ? 'bg-brand-100 text-brand-700 hover:bg-brand-200' : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200') }}">
              {{ $vis === 'public' ? 'Published' : ($vis === 'restricted' ? 'Restricted' : 'Draft') }}
            </button>
          </form>
        </div>
        <div class="flex items-center justify-between border-t border-gray-100 pt-4 text-xs font-medium text-gray-500">
          <span><i class="fa-regular fa-folder-open mr-1.5 text-brand-500"></i>{{ $course->sections->count() }} {{ Str::plural('section', $course->sections->count()) }}</span>
          <span><i class="fa-solid fa-user-group mr-1.5 text-accent-500"></i>{{ $course->enrollments_count }} {{ Str::plural('learner', $course->enrollments_count) }}</span>
          <a href="{{ route('admin.courses.edit', $course) }}" class="font-bold text-brand-600 hover:text-brand-700">Manage <i class="fa-solid fa-arrow-right ml-1"></i></a>
        </div>
      </div>
    </article>
    @endforeach
  </div>
  @else
  <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-5 py-16 text-center">
    <div class="mb-3 text-4xl">📚</div>
    <p class="font-bold text-gray-900">No courses found</p>
    <p class="mt-1 text-sm text-gray-500">Create a course or adjust your filters.</p>
    <a href="{{ route('admin.courses.create') }}" class="mt-4 inline-block text-sm font-bold text-brand-600 hover:text-brand-700">Create your first course <i class="fa-solid fa-arrow-right ml-1"></i></a>
  </div>
  @endif

  @if($courses->hasPages())
  <div class="rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm">
    {{ $courses->withQueryString()->links('pagination::tailwind') }}
  @endif
</div>

@endsection
