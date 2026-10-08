@extends('layouts.admin')
@section('title', ($isEdit ? 'Edit' : 'New').' Course — Skillspot.in Admin')
@section('page-title', $isEdit ? 'Edit Course' : 'Create New Course')
@section('page-sub',   $isEdit ? $course->title : 'Fill in course details, then add sections & lessons')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  <span>✅</span> {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 text-xl">×</button>
</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-4 text-sm">
  @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

  {{-- ── LEFT: Course details ──────────────────────────────────────── --}}
  <div class="xl:col-span-2 space-y-5">

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-9 h-9 bg-brand-100 rounded-xl flex items-center justify-center text-lg">📚</div>
        <h3 class="font-black text-gray-900">Course Details</h3>
      </div>

      <form method="POST"
            action="{{ $isEdit ? route('admin.courses.update',$course->id) : route('admin.courses.store') }}"
            id="courseForm" class="p-6 space-y-5">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Title --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Course Title <span class="text-red-400">*</span></label>
          <input type="text" name="title" value="{{ old('title',$course->title) }}" required
                 placeholder="e.g. Complete Web Development Bootcamp"
                 class="w-full px-4 py-3 rounded-xl border {{ $errors->has('title') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
        </div>

        {{-- Description --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Description</label>
          <textarea name="description" rows="4"
                    placeholder="What will students learn in this course?"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition resize-none">{{ old('description',$course->description) }}</textarea>
        </div>

        {{-- Vendor Assignment + Course Type --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Assign to Vendor</label>
            <select name="vendor_id" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              <option value="">— Skillspot.in (No Vendor) —</option>
              @foreach($vendors ?? [] as $v)
              <option value="{{ $v->id }}" {{ old('vendor_id', $course->vendor_id) == $v->id ? 'selected' : '' }}>{{ $v->brand_name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Course Type</label>
            <select name="course_type" id="courseTypeSelect" onchange="toggleBatchFields()" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              @foreach(['open'=>'Open (Anyone)','one_time'=>'One Time','batch'=>'Batch'] as $val=>$lbl)
              <option value="{{ $val }}" {{ old('course_type',$course->course_type ?? 'open') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Batch fields (shown only when type=batch) --}}
        <div id="batchFields" class="{{ old('course_type',$course->course_type ?? 'open') === 'batch' ? '' : 'hidden' }} grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-purple-50 border border-purple-200 rounded-2xl">
          <div class="col-span-2 flex items-center gap-2">
            <span class="text-purple-700 font-semibold text-sm">📦 Batch Settings</span>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Batch Name</label>
            <input type="text" name="batch_name" value="{{ old('batch_name',$course->batch_name) }}" placeholder="e.g. Batch 2026-A" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Max Students (0=unlimited)</label>
            <input type="number" name="max_students" value="{{ old('max_students',$course->max_students ?? 0) }}" min="0" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Start Date</label>
            <input type="date" name="batch_start_date" value="{{ old('batch_start_date', $course->batch_start_date?->format('Y-m-d')) }}" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">End Date</label>
            <input type="date" name="batch_end_date" value="{{ old('batch_end_date', $course->batch_end_date?->format('Y-m-d')) }}" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <div class="col-span-2">
            <label class="flex items-center gap-3 cursor-pointer">
              <div class="relative">
                <input type="checkbox" name="registration_open" value="1" {{ old('registration_open', $course->registration_open ?? true) ? 'checked' : '' }} class="sr-only peer">
                <div class="w-10 h-6 bg-gray-300 peer-checked:bg-purple-500 rounded-full transition cursor-pointer" onclick="this.previousElementSibling.click()"></div>
                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4 pointer-events-none"></div>
              </div>
              <span class="text-sm font-semibold text-gray-700">Registration Open</span>
            </label>
          </div>
        </div>

        {{-- Category + Level + Language --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Category <span class="text-red-400">*</span></label>
            <select name="category" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              <option value="">Select…</option>
              @foreach($categories as $cat)
              <option value="{{ $cat }}" {{ old('category',$course->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Level <span class="text-red-400">*</span></label>
            <select name="level" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              @foreach($levels as $lv)
              <option value="{{ $lv }}" {{ old('level',$course->level) === $lv ? 'selected' : '' }}>{{ ucfirst($lv) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Language</label>
            <select name="language" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              @foreach($languages as $code => $lang)
              <option value="{{ $code }}" {{ old('language',$course->language ?? 'en') === $code ? 'selected' : '' }}>{{ $lang }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Thumbnail + Intro video --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Thumbnail URL</label>
            <input type="text" name="thumbnail" value="{{ old('thumbnail',$course->thumbnail) }}"
                   placeholder="https://… or R2 path"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Intro Video URL</label>
            <input type="text" name="intro_video" value="{{ old('intro_video',$course->intro_video) }}"
                   placeholder="YouTube, Vimeo or R2 URL"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
        </div>

        {{-- Price --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Price (₹) <span class="text-red-400">*</span></label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm">₹</span>
              <input type="number" name="price" value="{{ old('price',$course->price ?? 0) }}" min="0" step="0.01"
                     class="w-full pl-7 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Sale Price (₹)</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm">₹</span>
              <input type="number" name="sale_price" value="{{ old('sale_price',$course->sale_price) }}" min="0" step="0.01"
                     placeholder="Optional"
                     class="w-full pl-7 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            </div>
          </div>
          <div class="flex flex-col gap-3 justify-end pb-1">
            <label class="flex items-center gap-2.5 cursor-pointer p-3 bg-green-50 rounded-xl border border-green-200">
              <input type="checkbox" name="is_free" value="1" {{ old('is_free',$course->is_free) ? 'checked' : '' }}
                     class="w-4 h-4 rounded text-green-600 focus:ring-green-500">
              <span class="text-sm font-semibold text-green-800">Free Course</span>
            </label>
            <label class="flex items-center gap-2.5 cursor-pointer p-3 bg-yellow-50 rounded-xl border border-yellow-200">
              <input type="checkbox" name="is_featured" value="1" {{ old('is_featured',$course->is_featured) ? 'checked' : '' }}
                     class="w-4 h-4 rounded text-yellow-600 focus:ring-yellow-500">
              <span class="text-sm font-semibold text-yellow-800">Featured</span>
            </label>
          </div>
        </div>

        {{-- Requirements + Outcomes --}}
        @php
          // Parse requirements
          $reqDisplay = old('requirements');
          if (!$reqDisplay) {
            $rr = $course->requirements;
            if (is_array($rr)) $reqDisplay = implode("\n", array_filter($rr));
            elseif (is_string($rr) && str_starts_with(trim((string)$rr), '[')) {
              $rd = json_decode($rr, true);
              $reqDisplay = is_array($rd) ? implode("\n", array_filter($rd)) : $rr;
            } else { $reqDisplay = $rr; }
          }
          // Parse outcomes
          $outDisplay = old('outcomes');
          if (!$outDisplay) {
            $or = $course->outcomes;
            if (is_array($or)) $outDisplay = implode("\n", array_filter($or));
            elseif (is_string($or) && str_starts_with(trim((string)$or), '[')) {
              $od = json_decode($or, true);
              $outDisplay = is_array($od) ? implode("\n", array_filter($od)) : $or;
            } else { $outDisplay = $or; }
          }
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Requirements</label>
            <textarea name="requirements" rows="4"
                      placeholder="One per line&#10;e.g. Basic computer skills"
                      class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition resize-none">{{ $reqDisplay }}</textarea>
            <p class="text-xs text-gray-400 mt-1">One requirement per line</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">What You'll Learn</label>
            <textarea name="outcomes" rows="4"
                      placeholder="One per line&#10;e.g. Build full-stack apps"
                      class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition resize-none">{{ $outDisplay }}</textarea>
            <p class="text-xs text-gray-400 mt-1">One outcome per line</p>
          </div>
        </div>

        {{-- Save --}}
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
          <a href="{{ route('admin.courses') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">← Back to Courses</a>
          <button type="submit"
                  class="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white font-black px-7 py-2.5 rounded-xl transition shadow-md text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            {{ $isEdit ? 'Save Changes' : 'Create Course' }}
          </button>
        </div>
      </form>
    </div>

    {{-- ── SECTIONS & LESSONS BUILDER ── --}}
    @if($isEdit)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-accent-100 rounded-xl flex items-center justify-center text-lg">📋</div>
          <div>
            <h3 class="font-black text-gray-900">Sections & Lessons</h3>
            <p class="text-xs text-gray-400">{{ $course->sections->count() }} sections · {{ $course->sections->sum(fn($s)=>$s->lessons->count()) }} lessons</p>
          </div>
        </div>
        {{-- Add section button --}}
        <button onclick="document.getElementById('addSectionForm').classList.toggle('hidden')"
                class="flex items-center gap-1.5 bg-accent-600 hover:bg-accent-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
          ➕ Add Section
        </button>
      </div>

      {{-- Add section form (hidden by default) --}}
      <div id="addSectionForm" class="hidden px-6 py-4 bg-accent-50 border-b border-accent-100">
        <form method="POST" action="{{ route('admin.courses.sections.store', $course->id) }}" class="flex gap-3">
          @csrf
          <input type="text" name="title" required placeholder="Section title e.g. Getting Started"
                 class="flex-1 px-4 py-2.5 rounded-xl border border-accent-200 bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 transition">
          <button type="submit" class="px-5 py-2.5 bg-accent-600 hover:bg-accent-700 text-white font-bold rounded-xl text-sm transition">Add</button>
          <button type="button" onclick="document.getElementById('addSectionForm').classList.add('hidden')"
                  class="px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl text-sm transition">✕</button>
        </form>
      </div>

      {{-- Sections list --}}
      <div class="p-4 space-y-3" id="sectionsList">
        @forelse($course->sections as $section)
        <div class="border border-gray-200 rounded-2xl overflow-hidden" id="section-{{ $section->id }}">

          {{-- Section header --}}
          <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 cursor-pointer"
               onclick="toggleSection({{ $section->id }})">
            <span class="text-gray-400 cursor-grab drag-handle">⠿</span>
            <span class="text-lg">📂</span>
            <span class="flex-1 font-bold text-gray-800 text-sm" id="sectionTitle-{{ $section->id }}">
              {{ $section->title }}
            </span>
            <span class="text-xs text-gray-400">{{ $section->lessons->count() }} lessons</span>
            {{-- Edit section --}}
            <button onclick="event.stopPropagation(); editSection({{ $section->id }}, '{{ addslashes($section->title) }}')"
                    class="p-1 rounded-lg hover:bg-brand-50 text-gray-400 hover:text-brand-600 transition text-sm">✏️</button>
            {{-- Delete section --}}
            <form method="POST" action="{{ route('admin.courses.sections.destroy', [$course->id,$section->id]) }}"
                  onsubmit="return confirm('Delete section and all its lessons?')" class="inline"
                  onclick="event.stopPropagation()">
              @csrf @method('DELETE')
              <button type="submit" class="p-1 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition text-sm">🗑️</button>
            </form>
            <svg class="w-4 h-4 text-gray-400 transition-transform" id="sectionArrow-{{ $section->id }}"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
          </div>

          {{-- Lessons list --}}
          <div id="sectionBody-{{ $section->id }}" class="divide-y divide-gray-100">
            @forelse($section->lessons as $lesson)
            <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition group">
              <span class="text-gray-300 text-xs cursor-grab">⠿</span>
              <span class="text-base">
                {{ $lesson->type === 'video' ? '🎬' : ($lesson->type === 'quiz' ? '📝' : ($lesson->type === 'pdf' ? '📄' : ($lesson->type === 'live' ? '📡' : '📖'))) }}
              </span>
              <span class="flex-1 text-sm text-gray-700 font-medium">{{ $lesson->title }}</span>
              @if($lesson->is_preview)
              <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold">Preview</span>
              @endif
              @if($lesson->duration)
              <span class="text-xs text-gray-400">{{ $lesson->duration }}m</span>
              @endif
              <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-lg capitalize">{{ $lesson->type }}</span>
              <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                <a href="{{ route('admin.courses.lessons.edit', [$course->id,$section->id,$lesson->id]) }}"
                   class="p-1 rounded-lg hover:bg-brand-50 text-gray-400 hover:text-brand-600 transition">✏️</a>
                <form method="POST" action="{{ route('admin.courses.lessons.destroy', [$course->id,$section->id,$lesson->id]) }}"
                      onsubmit="return confirm('Delete lesson?')" class="inline">
                  @csrf @method('DELETE')
                  <button class="p-1 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition">🗑️</button>
                </form>
              </div>
            </div>
            @empty
            <div class="px-4 py-3 text-xs text-gray-400 italic">No lessons yet</div>
            @endforelse

            {{-- Add lesson button --}}
            <div class="px-4 py-2.5 bg-gray-50/50">
              <a href="{{ route('admin.courses.lessons.create', [$course->id, $section->id]) }}"
                 class="inline-flex items-center gap-1.5 text-xs text-brand-600 hover:text-brand-700 font-semibold transition">
                ➕ Add Lesson
              </a>
            </div>
          </div>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
          <div class="text-3xl mb-2">📂</div>
          <p class="text-sm">No sections yet. Add your first section above.</p>
        </div>
        @endforelse
      </div>
    </div>

    {{-- Edit section modal --}}
    <div id="editSectionModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="font-black text-gray-900 mb-4">Edit Section</h3>
        <form method="POST" id="editSectionForm">
          @csrf @method('PUT')
          <input type="text" name="title" id="editSectionTitle" required
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 mb-4">
          <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-xl text-sm transition">Save</button>
            <button type="button" onclick="document.getElementById('editSectionModal').classList.add('hidden')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm transition">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    @endif {{-- end isEdit --}}
  </div>

  {{-- ── RIGHT: Sidebar info ───────────────────────────────────────── --}}
  <div class="space-y-5">
    {{-- Visibility card --}}
    @if($isEdit)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h4 class="font-bold text-gray-900 text-sm mb-3">Visibility</h4>
      @php $vis = $course->visibility ?? 'draft'; @endphp
      <div class="space-y-2 mb-4">
        @foreach(['draft'=>['🔒','Draft','Not visible to anyone','border-gray-200 bg-gray-50','text-gray-600'],'public'=>['🌐','Public','All students can see & enroll','border-green-200 bg-green-50','text-green-700'],'restricted'=>['🔑','Restricted','Only specific students','border-brand-200 bg-brand-50','text-brand-700']] as $key=>[$icon,$label,$desc,$bg,$text])
        <form method="POST" action="{{ route('admin.courses.update',$course->id) }}">
          @csrf @method('PUT')
          @foreach(request()->except(['_token','_method','visibility']) as $k=>$v)
            @if(is_array($v)) @foreach($v as $item)<input type="hidden" name="{{ $k }}[]" value="{{ $item }}">@endforeach
            @else <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
          @endforeach
          {{-- Pass current course values --}}
          <input type="hidden" name="title"       value="{{ $course->title }}">
          <input type="hidden" name="category"    value="{{ $course->category }}">
          <input type="hidden" name="level"       value="{{ $course->level }}">
          <input type="hidden" name="language"    value="{{ $course->language ?? 'en' }}">
          <input type="hidden" name="price"       value="{{ $course->price }}">
          <input type="hidden" name="is_free"     value="{{ $course->is_free ? 1 : 0 }}">
          <input type="hidden" name="is_featured" value="{{ $course->is_featured ? 1 : 0 }}">
          <input type="hidden" name="visibility"  value="{{ $key }}">
          <button type="submit"
                  class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl border-2 text-sm font-semibold transition text-left {{ $vis===$key ? $bg.' '.$text : 'border-gray-100 hover:border-gray-200 text-gray-600' }}">
            <span>{{ $icon }}</span>
            <div>
              <div class="font-bold">{{ $label }} {{ $vis===$key ? '✓' : '' }}</div>
              <div class="text-xs font-normal opacity-70">{{ $desc }}</div>
            </div>
          </button>
        </form>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Restricted students --}}
    @if($isEdit && ($course->visibility ?? 'draft') === 'restricted')
    <div class="bg-white rounded-2xl border border-brand-200 shadow-sm overflow-hidden">
      <div class="flex items-center gap-2 px-4 py-3 border-b border-brand-100 bg-brand-50">
        <span class="text-base">🔑</span>
        <div>
          <div class="font-bold text-brand-800 text-sm">Allowed Students</div>
          <div class="text-xs text-brand-600">{{ $course->allowedUsers->count() }} students have access</div>
        </div>
      </div>
      {{-- Add student --}}
      <div class="p-4">
        <form method="POST" action="{{ route('admin.courses.grant-access',$course->id) }}" class="mb-3">
          @csrf
          <div class="relative mb-2">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
            <input type="text" id="restrictedSearch" placeholder="Search student…" autocomplete="off"
                   class="w-full pl-8 pr-4 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50">
            <div id="restrictedDropdown" class="hidden absolute top-full left-0 right-0 mt-1 bg-white rounded-xl border border-gray-200 shadow-xl z-20 max-h-40 overflow-y-auto"></div>
          </div>
          <input type="hidden" name="user_id" id="restrictedUserId">
          <div id="restrictedSelected" class="hidden text-xs text-brand-600 font-semibold mb-2"></div>
          <input type="text" name="note" placeholder="Note (optional)" class="w-full px-3 py-1.5 rounded-lg border border-gray-200 text-xs bg-gray-50 focus:outline-none focus:ring-1 focus:ring-brand-400 mb-2">
          <button type="submit" class="w-full py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl text-xs transition">+ Grant Access</button>
        </form>
        {{-- Current list --}}
        @forelse($course->allowedUsers as $au)
        <div class="flex items-center gap-2 py-2 border-t border-gray-100">
          <div class="w-6 h-6 bg-gradient-to-br from-brand-400 to-accent-400 rounded-lg flex items-center justify-center text-white font-black text-xs flex-shrink-0">{{ strtoupper(substr($au->name,0,1)) }}</div>
          <div class="flex-1 min-w-0">
            <div class="text-xs font-semibold text-gray-800 truncate">{{ $au->name }}</div>
            <div class="text-xs text-gray-400 truncate">{{ $au->email }}</div>
            @if($au->pivot->note)<div class="text-xs text-gray-400 italic">{{ $au->pivot->note }}</div>@endif
          </div>
          <form method="POST" action="{{ route('admin.courses.revoke-access',[$course->id,$au->id]) }}" onsubmit="return confirm('Revoke access?')">
            @csrf @method('DELETE')
            <button class="text-red-400 hover:text-red-600 text-xs font-bold transition">✕</button>
          </form>
        </div>
        @empty
        <div class="text-xs text-gray-400 text-center py-3">No students added yet</div>
        @endforelse
      </div>
    </div>
    @elseif($isEdit)
    {{-- Show visibility in course form too --}}
    @endif

    {{-- Course stats --}}
    @if($isEdit)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h4 class="font-bold text-gray-900 text-sm mb-3">Course Stats</h4>
      <div class="space-y-2.5">
        @foreach([
          ['🎓','Enrollments', $course->enrollments->count()],
          ['📂','Sections',    $course->sections->count()],
          ['🎬','Lessons',     $course->sections->sum(fn($s)=>$s->lessons->count())],
          ['⭐','Reviews',     $course->reviews->count()],
        ] as [$icon,$label,$val])
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-500 flex items-center gap-2"><span>{{ $icon }}</span>{{ $label }}</span>
          <span class="font-bold text-gray-900">{{ $val }}</span>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Tips --}}
    <div class="bg-brand-50 border border-brand-200 rounded-2xl p-5">
      <h4 class="font-bold text-brand-800 text-sm mb-3">💡 Tips</h4>
      <ul class="space-y-2 text-xs text-brand-700 leading-relaxed">
        <li>• Add sections to organise your course content</li>
        <li>• Each section can have multiple lessons</li>
        <li>• Mark intro lessons as "Preview" — students can watch free</li>
        <li>• Publish only when all lessons are ready</li>
        <li>• Add requirements & outcomes to attract more students</li>
      </ul>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  function toggleSection(id) {
    const body  = document.getElementById('sectionBody-' + id);
    const arrow = document.getElementById('sectionArrow-' + id);
    body.classList.toggle('hidden');
    arrow.style.transform = body.classList.contains('hidden') ? 'rotate(-90deg)' : '';
  }

  function editSection(id, currentTitle) {
    const modal = document.getElementById('editSectionModal');
    const form  = document.getElementById('editSectionForm');
    document.getElementById('editSectionTitle').value = currentTitle;
    form.action = '/admin/courses/{{ $course->id ?? 0 }}/sections/' + id;
    modal.classList.remove('hidden');
  }

  // Close modal on backdrop click
  document.getElementById('editSectionModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
  });
</script>

<script>
// Restricted student search
const rsInput = document.getElementById('restrictedSearch');
if (rsInput) {
  let rsTimer;
  rsInput.addEventListener('input', function() {
    clearTimeout(rsTimer);
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('restrictedDropdown').classList.add('hidden'); return; }
    rsTimer = setTimeout(async () => {
      const res   = await fetch('{{ route("admin.enrollments.search-users") }}?q='+encodeURIComponent(q), {credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
      const users = await res.json();
      const dd    = document.getElementById('restrictedDropdown');
      dd.innerHTML = '';
      if (!users.length) {
        dd.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400">No students found</div>';
      } else {
        users.forEach(u => {
          const d = document.createElement('div');
          d.className = 'flex items-center gap-2 px-3 py-2 hover:bg-brand-50 cursor-pointer text-xs';
          d.innerHTML = `<div class="w-6 h-6 bg-brand-400 rounded-lg flex items-center justify-center text-white font-black text-xs">${u.name[0].toUpperCase()}</div><div><div class="font-semibold">${u.name}</div><div class="text-gray-400">${u.email}</div></div>`;
          d.onclick = () => {
            document.getElementById('restrictedUserId').value = u.id;
            rsInput.value = u.name;
            document.getElementById('restrictedSelected').textContent = '✓ '+u.name;
            document.getElementById('restrictedSelected').classList.remove('hidden');
            dd.classList.add('hidden');
          };
          dd.appendChild(d);
        });
      }
      dd.classList.remove('hidden');
    }, 300);
  });
  document.addEventListener('click', e => {
    if (!e.target.closest('#restrictedSearch')) document.getElementById('restrictedDropdown')?.classList.add('hidden');
  });
}
</script>

<script>
function toggleBatchFields() {
  const type   = document.getElementById('courseTypeSelect')?.value;
  const fields = document.getElementById('batchFields');
  if (fields) fields.classList.toggle('hidden', type !== 'batch');
}
toggleBatchFields();
</script>

@endpush
