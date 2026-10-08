@extends('layouts.admin')
@section('title','Courses — Skillspot.in Admin')
@section('page-title','Courses')
@section('page-sub','Create and manage all academy courses')

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

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  @foreach([
    ['📚','Total Courses', $totalCourses, 'bg-blue-50','text-blue-700'],
    ['✅','Published',     $published,    'bg-green-50','text-green-700'],
    ['📝','Draft',         $draft,        'bg-yellow-50','text-yellow-700'],
    ['🎓','Enrollments',   $totalEnroll,  'bg-purple-50','text-purple-700'],
  ] as [$icon,$label,$val,$bg,$text])
  <div class="{{ $bg }} rounded-2xl p-4 border border-transparent">
    <div class="text-2xl mb-1">{{ $icon }}</div>
    <div class="text-2xl font-black {{ $text }}">{{ $val }}</div>
    <div class="text-xs font-semibold text-gray-600">{{ $label }}</div>
  </div>
  @endforeach
</div>

<!-- Table card -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
    <h3 class="font-black text-gray-900 text-sm">All Courses</h3>
    <a href="{{ route('admin.courses.create') }}"
       class="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-sm">
      ➕ New Course
    </a>
  </div>

  <!-- Filters -->
  <form method="GET" action="{{ route('admin.courses') }}"
        class="flex flex-wrap gap-3 px-5 py-3 border-b border-gray-100 bg-gray-50/50">
    <div class="relative flex-1 min-w-[200px]">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="Search courses…"
             class="w-full pl-8 pr-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
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
    <select name="status" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
      <option value="">All Status</option>
      <option value="published" {{ request('status')==='published'?'selected':'' }}>Published</option>
      <option value="draft"     {{ request('status')==='draft'?'selected':'' }}>Draft</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold transition">Search</button>
    @if(request()->hasAny(['search','category','level','status']))
    <a href="{{ route('admin.courses') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold transition">Clear</a>
    @endif
  </form>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Course</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Category</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden md:table-cell">Level</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden md:table-cell">Price</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden lg:table-cell">Enrollments</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Status</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($courses as $course)
        <tr class="hover:bg-gray-50 transition">
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-3">
              @if($course->thumbnail)
                <img src="{{ $course->thumbnail }}" class="w-10 h-10 rounded-xl object-cover flex-shrink-0" alt="">
              @else
                <div class="w-10 h-10 bg-gradient-to-br from-brand-100 to-accent-100 rounded-xl flex items-center justify-center text-xl flex-shrink-0">📚</div>
              @endif
              <div class="min-w-0">
                <div class="font-semibold text-gray-900 text-sm truncate max-w-[180px]">{{ $course->title }}</div>
                <div class="text-gray-400 text-xs">{{ $course->sections->count() }} sections</div>
              </div>
            </div>
          </td>
          <td class="px-5 py-3.5 text-gray-600 text-xs hidden sm:table-cell">{{ $course->category ?? '—' }}</td>
          <td class="px-5 py-3.5 hidden md:table-cell">
            <span class="text-xs px-2.5 py-1 rounded-lg font-semibold
              {{ $course->level === 'beginner' ? 'bg-green-100 text-green-700' :
                ($course->level === 'intermediate' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
              {{ ucfirst($course->level) }}
            </span>
          </td>
          <td class="px-5 py-3.5 font-semibold text-gray-900 text-xs hidden md:table-cell">
            {{ $course->is_free ? 'Free' : '₹'.number_format($course->price) }}
            @if($course->sale_price && !$course->is_free)
            <span class="text-gray-400 line-through ml-1">₹{{ number_format($course->sale_price) }}</span>
            @endif
          </td>
          <td class="px-5 py-3.5 text-gray-600 text-xs hidden lg:table-cell">
            {{ $course->enrollments_count }} students
          </td>
          <td class="px-5 py-3.5">
            @php $vis = $course->visibility ?? ($course->is_published ? "public" : "draft"); @endphp
            <form method="POST" action="{{ route('admin.courses.toggle-publish', $course->id) }}" class="inline" title="Click to change visibility">
              @csrf @method('PATCH')
              <button type="submit"
                      class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-lg transition
                        {{ $vis==='public'     ? 'bg-green-100 text-green-700 hover:bg-green-200'
                         : ($vis==='restricted' ? 'bg-brand-100 text-brand-700 hover:bg-brand-200'
                         : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200') }}">
                {{ $vis==='public' ? '🌐 Public' : ($vis==='restricted' ? '🔑 Restricted' : '🔒 Draft') }}
              </button>
            </form>
          </td>
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-1.5">
              <a href="{{ route('admin.courses.edit', $course->id) }}"
                 class="p-1.5 rounded-lg hover:bg-brand-50 text-gray-400 hover:text-brand-600 transition" title="Edit">✏️</a>
              <form method="POST" action="{{ route('admin.courses.destroy', $course->id) }}"
                    onsubmit="return confirm('Delete \'{{ addslashes($course->title) }}\'?')">
                @csrf @method('DELETE')
                <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition" title="Delete">🗑️</button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="px-5 py-14 text-center">
            <div class="text-4xl mb-2">📚</div>
            <p class="text-gray-500 font-medium">No courses yet</p>
            <a href="{{ route('admin.courses.create') }}" class="text-brand-600 text-sm font-semibold hover:underline mt-1 inline-block">Create your first course →</a>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($courses->hasPages())
  <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
    {{ $courses->withQueryString()->links('pagination::tailwind') }}
  </div>
  @else
  <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
    Showing {{ $courses->count() }} of {{ $courses->total() }} courses
  </div>
  @endif
</div>

@endsection
