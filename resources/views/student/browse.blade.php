@extends('layouts.student')
@section('title','Browse Courses — Skillspot.in')
@section('page-title','Browse Courses')
@section('page-sub','Find your next skill — {{ $courses->total() }} courses available')

@section('student-content')

<!-- ── Filter bar ─────────────────────────────────────────────────── -->
<form method="GET" action="{{ route('student.browse') }}" id="filterForm"
      class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-5">
  <div class="flex flex-col gap-3">

    {{-- Search --}}
    <div class="relative">
      <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="Search courses by title or keyword…"
             class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white transition">
    </div>

    {{-- Filters row --}}
    <div class="flex flex-wrap gap-2">
      {{-- Category --}}
      <select name="category" onchange="this.form.submit()"
              class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-gray-50 focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer flex-1 min-w-[140px]">
        <option value="">📚 All Categories</option>
        @foreach($categories as $cat)
        <option value="{{ $cat->name }}" {{ request('category')===$cat->name?'selected':'' }}>
          {{ $cat->icon ?? '' }} {{ $cat->name }}
        </option>
        @endforeach
      </select>

      {{-- Level --}}
      <select name="level" onchange="this.form.submit()"
              class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-gray-50 focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer flex-1 min-w-[120px]">
        <option value="">📊 All Levels</option>
        @foreach($levels as $lv)
        <option value="{{ $lv->slug }}" {{ request('level')===$lv->slug?'selected':'' }}>
          {{ $lv->name }}
        </option>
        @endforeach
      </select>

      {{-- Sort --}}
      <select name="sort" onchange="this.form.submit()"
              class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-gray-50 focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer flex-1 min-w-[130px]">
        <option value=""         {{ !request('sort')?'selected':'' }}>🕐 Newest</option>
        <option value="popular"  {{ request('sort')==='popular' ?'selected':'' }}>🔥 Most Popular</option>
        <option value="price_lo" {{ request('sort')==='price_lo'?'selected':'' }}>💰 Price: Low to High</option>
        <option value="price_hi" {{ request('sort')==='price_hi'?'selected':'' }}>💎 Price: High to Low</option>
      </select>

      {{-- Free toggle --}}
      <label class="flex items-center gap-2 px-3 py-2 bg-green-50 border {{ request('free') ? 'border-green-400' : 'border-green-200' }} rounded-xl cursor-pointer hover:bg-green-100 transition select-none">
        <input type="checkbox" name="free" value="1" {{ request('free')?'checked':'' }}
               onchange="this.form.submit()"
               class="w-4 h-4 rounded text-green-600 focus:ring-green-500">
        <span class="text-xs font-bold text-green-800">🆓 Free only</span>
      </label>

      {{-- Search button --}}
      <button type="submit"
              class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl text-sm transition flex-shrink-0">
        Search
      </button>

      {{-- Clear --}}
      @if(request()->hasAny(['search','category','level','sort','free']))
      <a href="{{ route('student.browse') }}"
         class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold rounded-xl text-sm transition flex-shrink-0 flex items-center gap-1">
        ✕ Clear
      </a>
      @endif
    </div>
  </div>
</form>

{{-- Active filters display --}}
@if(request()->hasAny(['search','category','level','sort','free']))
<div class="flex flex-wrap gap-2 mb-4">
  @if(request('search'))
  <span class="text-xs bg-brand-100 text-brand-700 font-semibold px-3 py-1.5 rounded-full flex items-center gap-1">
    🔍 "{{ request('search') }}"
    <a href="{{ request()->fullUrlWithoutQuery(['search']) }}" class="ml-1 hover:text-red-600">×</a>
  </span>
  @endif
  @if(request('category'))
  <span class="text-xs bg-brand-100 text-brand-700 font-semibold px-3 py-1.5 rounded-full flex items-center gap-1">
    📚 {{ request('category') }}
    <a href="{{ request()->fullUrlWithoutQuery(['category']) }}" class="ml-1 hover:text-red-600">×</a>
  </span>
  @endif
  @if(request('level'))
  <span class="text-xs bg-brand-100 text-brand-700 font-semibold px-3 py-1.5 rounded-full flex items-center gap-1">
    📊 {{ ucfirst(request('level')) }}
    <a href="{{ request()->fullUrlWithoutQuery(['level']) }}" class="ml-1 hover:text-red-600">×</a>
  </span>
  @endif
  @if(request('free'))
  <span class="text-xs bg-green-100 text-green-700 font-semibold px-3 py-1.5 rounded-full flex items-center gap-1">
    🆓 Free only
    <a href="{{ request()->fullUrlWithoutQuery(['free']) }}" class="ml-1 hover:text-red-600">×</a>
  </span>
  @endif
</div>
@endif

{{-- Results count --}}
<div class="flex items-center justify-between mb-4">
  <p class="text-sm text-gray-500">
    <strong class="text-gray-900">{{ $courses->total() }}</strong> course{{ $courses->total() !== 1 ? 's' : '' }} found
    @if(request()->hasAny(['search','category','level','free']))
    <span class="text-gray-400">— filtered results</span>
    @endif
  </p>
</div>

{{-- Course grid --}}
@if($courses->count())
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
  @foreach($courses as $course)
  @php
    $levelColor = match($course->level) {
      'intermediate' => 'bg-yellow-50 text-yellow-700',
      'advanced'     => 'bg-red-50 text-red-700',
      default        => 'bg-blue-50 text-blue-700'
    };
    $isRestricted = $course->visibility === 'restricted';
  @endphp
  <a href="{{ route('student.course-detail', $course->slug) }}"
     class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md hover:border-brand-200 transition group block">

    {{-- Thumbnail --}}
    <div class="h-40 bg-gradient-to-br from-brand-100 to-accent-100 flex items-center justify-center relative overflow-hidden">
      @if($course->thumbnail)
        <img src="{{ $course->thumbnail }}" alt="{{ $course->title }}"
             class="w-full h-full object-cover" loading="lazy">
      @else
        <span class="text-5xl">📚</span>
      @endif

      {{-- Badges --}}
      <div class="absolute top-2 left-2 flex gap-1.5">
        @if($course->is_free)
          <span class="bg-green-500 text-white text-xs font-bold px-2 py-0.5 rounded-lg">Free</span>
        @elseif($course->sale_price)
          <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-lg">Sale</span>
        @endif
        @if($isRestricted)
          <span class="bg-brand-600 text-white text-xs font-bold px-2 py-0.5 rounded-lg">🔑 Private</span>
        @endif
      </div>

      @if($course->enrollments_count > 0)
      <div class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-0.5 rounded-lg">
        👥 {{ $course->enrollments_count }}
      </div>
      @endif
    </div>

    {{-- Info --}}
    <div class="p-4">
      <div class="text-xs text-brand-600 font-semibold mb-1">{{ $course->category }}</div>
      <h3 class="font-bold text-gray-900 text-sm line-clamp-2 group-hover:text-brand-600 transition mb-2 leading-snug">
        {{ $course->title }}
      </h3>
      @if($course->description)
      <p class="text-xs text-gray-400 line-clamp-2 mb-3">{{ $course->description }}</p>
      @endif
      <div class="flex items-center gap-2 flex-wrap mb-3">
        <span class="text-xs {{ $levelColor }} px-2.5 py-0.5 rounded-lg font-semibold capitalize">
          {{ $course->level }}
        </span>
        @php $lessonCount = $course->sections_count ?? 0; @endphp
      </div>
      <div class="flex items-center justify-between">
        <div>
          @if($course->is_free)
            <span class="font-black text-green-600 text-sm">Free</span>
          @else
            <span class="font-black text-gray-900 text-sm">₹{{ number_format($course->sale_price ?? $course->price) }}</span>
            @if($course->sale_price && $course->price > $course->sale_price)
            <span class="text-xs text-gray-400 line-through ml-1">₹{{ number_format($course->price) }}</span>
            @endif
          @endif
        </div>
        <span class="text-xs bg-brand-50 text-brand-600 font-bold px-2.5 py-1 rounded-xl group-hover:bg-brand-600 group-hover:text-white transition">
          View →
        </span>
      </div>
    </div>
  </a>
  @endforeach
</div>

{{-- Pagination --}}
@if($courses->hasPages())
<div class="mt-2">{{ $courses->withQueryString()->links('pagination::tailwind') }}</div>
@endif

@else
{{-- Empty state --}}
<div class="text-center py-20 bg-white rounded-2xl border border-gray-100 shadow-sm">
  <div class="text-6xl mb-4">🔍</div>
  <h3 class="text-xl font-black text-gray-900 mb-2">No courses found</h3>
  <p class="text-gray-500 mb-6">Try adjusting your filters or search term.</p>
  <a href="{{ route('student.browse') }}"
     class="inline-block bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3 rounded-2xl transition">
    Clear Filters →
  </a>
</div>
@endif

@endsection
