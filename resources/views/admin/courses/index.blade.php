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
    <button type="button" id="openNewCourseModal" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 transition hover:bg-gray-50" role="menuitem">
      <i class="fa-regular fa-book-open w-4 text-gray-500"></i> New Course
    </button>
    <a href="{{ route('admin.courses.import') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50" role="menuitem">
      <i class="fa-solid fa-folder-plus w-4 text-gray-500"></i> Import via ZIP
    </a>
  </div>
</div>
@endsection

@push('styles')
<style>
  .course-tab-active { background: #fff; color: #111827; box-shadow: 0 1px 2px rgb(0 0 0 / 0.08); }
  .frappe-rich-editor { --editor-bg:#fff; --editor-border:#d1d5db; --editor-text:#374151; --editor-muted:#6b7280; --editor-hover:#f3f4f6; --editor-focus:#2563eb; overflow:hidden; border:1px solid var(--editor-border); border-radius:12px; background:var(--editor-bg); color:var(--editor-text); }
  body[data-theme="dark"] .frappe-rich-editor { --editor-bg:#171717; --editor-border:#444; --editor-text:#d4d4d4; --editor-muted:#a3a3a3; --editor-hover:#292929; --editor-focus:#f5f5f5; }
  .frappe-rich-editor:focus-within { border-color:var(--editor-focus); box-shadow:0 0 0 1px var(--editor-focus); }
  .frappe-rich-editor__toolbar { position:relative; display:flex; min-height:60px; flex-wrap:wrap; align-items:center; gap:2px; border-bottom:1px solid var(--editor-border); padding:5px 8px; }
  .frappe-rich-editor__tool { display:inline-flex; height:24px; min-width:24px; align-items:center; justify-content:center; border-radius:4px; padding:0 5px; color:var(--editor-muted); font-size:12px; line-height:1; }
  .frappe-rich-editor__tool:hover, .frappe-rich-editor__tool:focus-visible { background:var(--editor-hover); color:var(--editor-focus); outline:none; }
  .frappe-rich-editor__heading { min-width:auto; font-size:13px; font-weight:600; }
  .frappe-rich-editor__divider { width:1px; height:16px; margin:0 3px; background:var(--editor-border); }
  .frappe-rich-editor__history { display:flex; flex-basis:100%; gap:2px; }
  .frappe-rich-editor__popover { position:absolute; z-index:20; display:flex; width:240px; gap:4px; border:1px solid var(--editor-border); border-radius:8px; background:var(--editor-bg); padding:4px; box-shadow:0 8px 24px rgb(0 0 0 / .22); }
  .frappe-rich-editor__popover[hidden] { display:none; }
  .frappe-rich-editor__popover input { min-width:0; flex:1; border:1px solid var(--editor-border); border-radius:6px; background:var(--editor-bg); color:var(--editor-text); padding:5px 8px; font-size:12px; outline:none; }
  .frappe-rich-editor__popover input:focus { border-color:var(--editor-focus); }
  .frappe-rich-editor__content { min-height:160px; padding:12px; color:var(--editor-text); font-size:14px; line-height:1.5; outline:none; }
  .frappe-rich-editor__content:empty::before { content:attr(data-placeholder); color:var(--editor-muted); pointer-events:none; }
  .frappe-rich-editor__content :where(img, video, iframe) { max-width:100%; margin:.75rem 0; }
  .frappe-rich-editor__content iframe { min-height:220px; width:100%; border:0; }
  .frappe-rich-editor__content table { width:100%; border-collapse:collapse; }
  .frappe-rich-editor__content td { min-width:4rem; border:1px solid var(--editor-border); padding:.35rem; }
</style>
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

<div id="newCourseModal" class="fixed inset-0 z-50 {{ request()->has('new') ? '' : 'hidden' }} overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" aria-labelledby="newCourseModalTitle">
  <form id="newCourseForm" method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data" class="mx-auto my-6 w-full max-w-4xl rounded-2xl bg-white p-6 shadow-2xl md:my-12 md:p-8">
    @csrf
    <input type="hidden" name="modal_create" value="1">
    <input type="hidden" name="category" id="selectedCategory">
    <input type="hidden" name="new_category" id="newCategory">
    <input type="hidden" name="description" id="courseDescription">
    <input type="hidden" name="level" value="beginner">
    <input type="hidden" name="language" value="en">
    <input type="hidden" name="price" value="0">
    <input type="hidden" name="is_free" value="1">
    <input type="hidden" name="visibility" value="draft">
    <div class="flex items-center justify-between border-b border-gray-100 pb-5">
      <h2 id="newCourseModalTitle" class="text-xl font-bold text-gray-900">New Course</h2>
      <button type="button" class="closeNewCourseModal rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close new course form">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="grid gap-x-5 gap-y-5 pt-6 md:grid-cols-2">
      <label class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span>
        <input name="title" type="text" required maxlength="255" class="mt-2 h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100" aria-label="Course title">
      </label>
      <div class="relative text-sm font-medium text-gray-700" id="categoryPicker">Category <span class="text-red-500">*</span>
        <button type="button" id="categoryPickerButton" aria-expanded="false" class="mt-2 flex h-10 w-full items-center justify-between rounded-lg border border-gray-200 bg-white px-3 text-left text-sm font-normal text-gray-500 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"><span id="categoryPickerLabel">Select option</span><i class="fa-solid fa-chevron-down text-xs"></i></button>
        <div id="categoryPickerMenu" class="absolute z-20 mt-1 hidden w-full overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
          <div class="max-h-36 overflow-y-auto">
            @forelse($categoryRecords as $category)
            <button type="button" data-category="{{ $category->name }}" class="categoryOption block w-full px-3 py-2 text-left text-sm font-normal text-gray-700 hover:bg-gray-50">{{ $category->name }}</button>
            @empty
            <p class="px-3 py-2 text-sm font-normal text-gray-400">No categories yet.</p>
            @endforelse
          </div>
          <div class="border-t border-gray-100 p-2">
            <button type="button" id="showNewCategory" class="text-sm font-medium text-brand-600 hover:text-brand-700"><i class="fa-solid fa-plus mr-1"></i>Create New</button>
            <div id="newCategoryPanel" class="mt-2 hidden gap-2">
              <input id="newCategoryName" type="text" maxlength="100" placeholder="Enter category" class="h-9 min-w-0 flex-1 rounded-lg border border-gray-200 px-2 text-sm outline-none focus:border-brand-500">
              <button type="button" id="createCategory" class="rounded-lg bg-brand-600 px-3 text-sm font-medium text-white">Create</button>
            </div>
          </div>
        </div>
      </div>
      <div class="relative text-sm font-medium text-gray-700" id="instructorPicker">Instructors <span class="text-red-500">*</span>
        <button type="button" id="instructorPickerButton" aria-expanded="false" class="mt-2 flex h-10 w-full items-center justify-between rounded-lg border border-gray-200 bg-white px-3 text-left text-sm font-normal text-gray-500 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"><span id="instructorPickerLabel">Select instructors</span><i class="fa-solid fa-chevron-down text-xs"></i></button>
        <div id="instructorPickerMenu" class="absolute z-20 mt-1 hidden w-full overflow-hidden rounded-lg border border-gray-200 bg-white p-2 shadow-lg">
          <input id="instructorSearch" type="search" placeholder="Search instructors" class="h-9 w-full rounded-lg border border-gray-200 px-2 text-sm outline-none focus:border-brand-500">
          <div id="instructorOptions" class="mt-2 max-h-40 space-y-1 overflow-y-auto">
            @forelse($instructors as $instructor)
            <label class="instructorOption flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm font-normal text-gray-700 hover:bg-gray-50">
              <input type="checkbox" name="instructor_ids[]" value="{{ $instructor->id }}" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
              <span class="flex-1 truncate">{{ $instructor->name }}</span><span class="truncate text-xs text-gray-400">{{ $instructor->email }}</span>
            </label>
            @empty
            <p class="px-2 py-2 text-sm font-normal text-gray-400">No instructors available.</p>
            @endforelse
          </div>
          <div class="mt-2 flex items-center justify-between border-t border-gray-100 pt-2">
            <button type="button" id="clearInstructors" class="text-sm font-medium text-gray-600 hover:text-gray-900">Clear</button>
            @if($canCreateInstructors)
              @if($inviteMailReady)
            <button type="button" id="openNewInstructorModal" class="text-sm font-medium text-brand-600 hover:text-brand-700"><i class="fa-solid fa-plus mr-1"></i>Create New Instructor</button>
              @else
            <span class="text-xs font-medium text-amber-700">SMTP invite required</span>
              @endif
            @endif
          </div>
        </div>
      </div>
      <div class="text-sm font-medium text-gray-700">Course thumbnail
        <div class="mt-2 flex items-center gap-3">
          <label id="thumbnailPreview" for="thumbnailFile" class="flex h-24 flex-1 cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-dashed border-gray-200 bg-gray-50 text-gray-400"><i class="fa-regular fa-image text-xl"></i></label>
          <label for="thumbnailFile" class="cursor-pointer rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Upload</label>
          <input id="thumbnailFile" name="thumbnail_file" type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="sr-only">
        </div>
      </div>
    </div>

    <div class="mt-6 border-t border-gray-100 pt-5">
      <label class="block text-sm font-medium text-gray-700">Short introduction <span class="text-red-500">*</span>
        <textarea name="short_introduction" required maxlength="1000" rows="3" class="mt-2 w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></textarea>
      </label>
      <div class="mt-5 block text-sm font-medium text-gray-700">
        <span id="courseDescriptionLabel">Course description <span class="text-red-500">*</span></span>
        <div class="frappe-rich-editor mt-2" data-rich-editor>
          <div class="frappe-rich-editor__toolbar" role="toolbar" aria-label="Course description formatting">
            <button type="button" class="frappe-rich-editor__tool frappe-rich-editor__heading" data-editor-command="formatBlock" data-editor-value="p" title="Heading">Heading</button>
            <button type="button" class="frappe-rich-editor__tool" data-editor-command="formatBlock" data-editor-value="h2" title="Heading 2">H₂</button><button type="button" class="frappe-rich-editor__tool" data-editor-command="formatBlock" data-editor-value="h3" title="Heading 3">H₃</button><button type="button" class="frappe-rich-editor__tool" data-editor-command="formatBlock" data-editor-value="h4" title="Heading 4">H₄</button>
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-command="bold" title="Bold"><b>B</b></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="italic" title="Italic"><i>I</i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="strikeThrough" title="Strike"><s>S</s></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="link" title="Link"><i class="fa-solid fa-link"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="color" title="Font Color"><i class="fa-solid fa-a"></i></button><input id="courseEditorColor" type="color" value="#d4d4d4" class="sr-only" aria-label="Font color">
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-command="insertUnorderedList" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="insertOrderedList" title="Ordered List"><i class="fa-solid fa-list-ol"></i></button>
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-command="justifyLeft" title="Align Left"><i class="fa-solid fa-align-left"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="justifyCenter" title="Align Center"><i class="fa-solid fa-align-center"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="justifyRight" title="Align Right"><i class="fa-solid fa-align-right"></i></button>
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-action="image" title="Image / Gallery"><i class="fa-regular fa-image"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="video" title="Video"><i class="fa-solid fa-video"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="formatBlock" data-editor-value="blockquote" title="Blockquote"><i class="fa-solid fa-quote-right"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="formatBlock" data-editor-value="pre" title="Code"><i class="fa-solid fa-code"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="embed" title="Embed"><i class="fa-solid fa-code"></i></button>
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-action="rule" title="Horizontal Rule"><i class="fa-solid fa-minus"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="table" title="Table"><i class="fa-solid fa-table"></i></button>
            <div class="frappe-rich-editor__history"><button type="button" class="frappe-rich-editor__tool" data-editor-command="undo" title="Undo"><i class="fa-solid fa-rotate-left"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="redo" title="Redo"><i class="fa-solid fa-rotate-right"></i></button></div>
            <div id="courseEditorUrlPopover" class="frappe-rich-editor__popover" role="dialog" aria-label="Edit link" hidden>
              <input id="courseEditorUrl" type="url" inputmode="url" autocomplete="off" placeholder="https://example.com" aria-label="URL">
              <button id="courseEditorUrlApply" type="button" class="frappe-rich-editor__tool" aria-label="Apply URL"><i class="fa-solid fa-check"></i></button>
            </div>
          </div>
          <div id="courseEditor" contenteditable="true" class="frappe-rich-editor__content" data-placeholder="Write a detailed description of your course" aria-labelledby="courseDescriptionLabel"></div>
          <input id="courseEditorImage" type="file" accept="image/*" multiple class="sr-only">
          <input id="courseEditorVideo" type="file" accept="video/*" class="sr-only">
        </div>
      </div>
    </div>

    <div class="mt-6 flex justify-end">
      <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Save</button>
    </div>
  </form>
</div>

<div id="newInstructorModal" class="fixed inset-0 z-[60] hidden overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" aria-labelledby="newInstructorModalTitle">
  <form id="newInstructorForm" class="mx-auto my-16 w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" novalidate>
    @csrf
    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
      <div>
        <h2 id="newInstructorModalTitle" class="text-lg font-bold text-gray-900">Add New Member</h2>
        <p class="mt-1 text-sm text-gray-500">We’ll email a secure link so they can choose their own password.</p>
      </div>
      <button type="button" class="closeNewInstructorModal rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close member form"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <p id="newInstructorError" class="mt-4 hidden rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert"></p>
    <label class="mt-5 block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span><input name="email" type="email" required maxlength="255" placeholder="jane@doe.com" class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></label>
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
      <label class="text-sm font-medium text-gray-700">First name<input name="first_name" maxlength="120" placeholder="Jane" class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></label>
      <label class="text-sm font-medium text-gray-700">Last name<input name="last_name" maxlength="120" placeholder="Doe" class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></label>
    </div>
    <fieldset class="mt-5"><legend class="text-sm font-medium text-gray-700">Roles</legend>
      <label class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm"><span><strong class="block text-gray-800">Student</strong><span class="text-xs text-gray-500">Learn courses and track progress</span></span><input type="checkbox" checked disabled class="rounded border-gray-300 text-brand-600"></label>
      <label class="mt-2 flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm"><span><strong class="block text-gray-800">Course Creator</strong><span class="text-xs text-gray-500">Build and manage courses, chapters, and lessons</span></span><input name="roles[]" value="teacher" type="checkbox" checked class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"></label>
      <label class="mt-2 flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm"><span><strong class="block text-gray-800">Moderator</strong><span class="text-xs text-gray-500">Manage members, content, and settings</span></span><input name="roles[]" value="admin" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"></label>
      <div class="mt-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-400"><strong class="block">Evaluator</strong><span class="text-xs">Available when Skillspot has its grading workflow.</span></div>
    </fieldset>
    <div class="mt-6 flex justify-end gap-3"><button type="button" class="closeNewInstructorModal rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</button><button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Add</button></div>
  </form>
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
  const newCourseModal = document.getElementById('newCourseModal');
  const openNewCourseModal = document.getElementById('openNewCourseModal');
  const closeNewCourseModal = () => newCourseModal?.classList.add('hidden');
  openNewCourseModal?.addEventListener('click', () => {
    courseCreateMenuItems?.classList.add('hidden');
    courseCreateMenuButton?.setAttribute('aria-expanded', 'false');
    newCourseModal?.classList.remove('hidden');
  });
  if (new URLSearchParams(window.location.search).has('new')) {
    newCourseModal?.classList.remove('hidden');
  }
  document.querySelectorAll('.closeNewCourseModal').forEach((button) => button.addEventListener('click', closeNewCourseModal));
  newCourseModal?.addEventListener('click', (event) => {
    if (event.target === newCourseModal) closeNewCourseModal();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      if (!newInstructorModal?.classList.contains('hidden')) closeNewInstructorModal();
      else closeNewCourseModal();
    }
  });

  const categoryPicker = document.getElementById('categoryPicker');
  const categoryPickerButton = document.getElementById('categoryPickerButton');
  const categoryPickerMenu = document.getElementById('categoryPickerMenu');
  const categoryPickerLabel = document.getElementById('categoryPickerLabel');
  const selectedCategory = document.getElementById('selectedCategory');
  const newCategory = document.getElementById('newCategory');
  const chooseCategory = (name, isNew = false) => {
    selectedCategory.value = name;
    newCategory.value = isNew ? name : '';
    categoryPickerLabel.textContent = name;
    categoryPickerMenu.classList.add('hidden');
    categoryPickerButton.setAttribute('aria-expanded', 'false');
  };
  categoryPickerButton?.addEventListener('click', () => {
    categoryPickerMenu.classList.toggle('hidden');
    categoryPickerButton.setAttribute('aria-expanded', String(!categoryPickerMenu.classList.contains('hidden')));
  });
  document.querySelectorAll('.categoryOption').forEach((option) => option.addEventListener('click', () => chooseCategory(option.dataset.category)));
  document.getElementById('showNewCategory')?.addEventListener('click', () => {
    document.getElementById('newCategoryPanel').classList.remove('hidden');
    document.getElementById('newCategoryPanel').classList.add('flex');
    document.getElementById('newCategoryName').focus();
  });
  document.getElementById('createCategory')?.addEventListener('click', () => {
    const name = document.getElementById('newCategoryName').value.trim();
    if (name) chooseCategory(name, true);
  });

  const instructorPicker = document.getElementById('instructorPicker');
  const instructorPickerButton = document.getElementById('instructorPickerButton');
  const instructorPickerMenu = document.getElementById('instructorPickerMenu');
  const instructorPickerLabel = document.getElementById('instructorPickerLabel');
  const updateInstructorLabel = () => {
    const chosen = [...document.querySelectorAll('input[name="instructor_ids[]"]:checked')]
      .map((input) => input.closest('label').querySelector('span').textContent);
    instructorPickerLabel.textContent = chosen.length ? chosen.join(', ') : 'Select instructors';
  };
  instructorPickerButton?.addEventListener('click', () => {
    instructorPickerMenu.classList.toggle('hidden');
    instructorPickerButton.setAttribute('aria-expanded', String(!instructorPickerMenu.classList.contains('hidden')));
  });
  document.querySelectorAll('input[name="instructor_ids[]"]').forEach((input) => input.addEventListener('change', updateInstructorLabel));
  document.getElementById('clearInstructors')?.addEventListener('click', () => {
    document.querySelectorAll('input[name="instructor_ids[]"]:checked').forEach((input) => input.checked = false);
    updateInstructorLabel();
  });
  document.getElementById('instructorSearch')?.addEventListener('input', (event) => {
    const query = event.target.value.toLowerCase();
    document.querySelectorAll('.instructorOption').forEach((option) => option.classList.toggle('hidden', !option.textContent.toLowerCase().includes(query)));
  });

  document.addEventListener('click', (event) => {
    if (!categoryPicker?.contains(event.target)) categoryPickerMenu?.classList.add('hidden');
    if (!instructorPicker?.contains(event.target)) instructorPickerMenu?.classList.add('hidden');
  });

  const newInstructorModal = document.getElementById('newInstructorModal');
  const newInstructorForm = document.getElementById('newInstructorForm');
  const newInstructorError = document.getElementById('newInstructorError');
  const closeNewInstructorModal = () => newInstructorModal?.classList.add('hidden');
  document.getElementById('openNewInstructorModal')?.addEventListener('click', () => {
    instructorPickerMenu.classList.add('hidden');
    newInstructorError.classList.add('hidden');
    newInstructorModal.classList.remove('hidden');
    newInstructorForm.elements.email.focus();
  });
  document.querySelectorAll('.closeNewInstructorModal').forEach((button) => button.addEventListener('click', closeNewInstructorModal));
  newInstructorModal?.addEventListener('click', (event) => { if (event.target === newInstructorModal) closeNewInstructorModal(); });
  newInstructorForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    newInstructorError.classList.add('hidden');
    const response = await fetch('{{ route('admin.courses.instructors.store') }}', {
      method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': newInstructorForm.querySelector('input[name="_token"]')?.value || document.querySelector('#newCourseForm input[name="_token"]').value }, body: new FormData(newInstructorForm),
    });
    const payload = await response.json();
    if (!response.ok) {
      newInstructorError.textContent = Object.values(payload.errors || {}).flat().join(' ') || 'Unable to create the instructor.';
      newInstructorError.classList.remove('hidden');
      return;
    }
    const instructor = payload.instructor;
    const option = document.createElement('label');
    option.className = 'instructorOption flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm font-normal text-gray-700 hover:bg-gray-50';
    option.innerHTML = `<input type="checkbox" name="instructor_ids[]" value="${instructor.id}" checked class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"><span class="flex-1 truncate"></span><span class="truncate text-xs text-gray-400"></span>`;
    option.querySelectorAll('span')[0].textContent = instructor.name;
    option.querySelectorAll('span')[1].textContent = instructor.email;
    option.querySelector('input').addEventListener('change', updateInstructorLabel);
    document.getElementById('instructorOptions').append(option);
    updateInstructorLabel();
    newInstructorForm.reset();
    closeNewInstructorModal();
  });

  const thumbnailFile = document.getElementById('thumbnailFile');
  const thumbnailPreview = document.getElementById('thumbnailPreview');
  thumbnailFile?.addEventListener('change', () => {
    const file = thumbnailFile.files?.[0];
    if (!file) return;
    thumbnailPreview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Selected course thumbnail" class="h-full w-full object-cover">`;
  });

  const courseEditor = document.getElementById('courseEditor');
  const courseDescription = document.getElementById('courseDescription');
  const courseEditorColor = document.getElementById('courseEditorColor');
  const courseEditorImage = document.getElementById('courseEditorImage');
  const courseEditorVideo = document.getElementById('courseEditorVideo');
  const courseEditorUrlPopover = document.getElementById('courseEditorUrlPopover');
  const courseEditorUrl = document.getElementById('courseEditorUrl');
  const courseEditorUrlApply = document.getElementById('courseEditorUrlApply');
  let editorRange = null;
  let editorUrlAction = null;
  const saveEditorRange = () => {
    const selection = window.getSelection();
    if (selection.rangeCount && courseEditor.contains(selection.anchorNode)) editorRange = selection.getRangeAt(0).cloneRange();
  };
  const restoreEditorRange = () => {
    if (!editorRange) return;
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(editorRange);
  };
  const runEditorCommand = (command, value = null) => {
    restoreEditorRange();
    courseEditor.focus();
    document.execCommand(command, false, value);
    saveEditorRange();
  };
  const editorUrl = (value) => {
    if (!value) return null;
    try {
      const url = new URL(value, window.location.origin);
      return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
    } catch (_) {
      return null;
    }
  };
  const openEditorUrlPopover = (action, button) => {
    saveEditorRange();
    editorUrlAction = action;
    courseEditorUrlPopover.setAttribute('aria-label', action === 'link' ? 'Edit link' : 'Insert embed');
    courseEditorUrl.placeholder = action === 'link' ? 'https://example.com' : 'https://youtube.com/watch?v=…';
    courseEditorUrl.value = '';
    courseEditorUrlPopover.hidden = false;
    courseEditorUrlPopover.style.left = `${Math.min(button.offsetLeft, button.parentElement.clientWidth - 248)}px`;
    courseEditorUrlPopover.style.top = `${button.offsetTop + button.offsetHeight + 4}px`;
    courseEditorUrl.focus();
  };
  const closeEditorUrlPopover = () => {
    courseEditorUrlPopover.hidden = true;
    editorUrlAction = null;
  };
  const insertEditorFrame = (url) => {
    const parsed = new URL(url);
    if (['youtube.com', 'www.youtube.com', 'm.youtube.com'].includes(parsed.hostname) && parsed.pathname === '/watch' && parsed.searchParams.get('v')) parsed.href = `https://www.youtube.com/embed/${parsed.searchParams.get('v')}`;
    if (parsed.hostname === 'youtu.be' && parsed.pathname.length > 1) parsed.href = `https://www.youtube.com/embed${parsed.pathname}`;
    if (['vimeo.com', 'www.vimeo.com'].includes(parsed.hostname) && /^\/\d+$/.test(parsed.pathname)) parsed.href = `https://player.vimeo.com/video${parsed.pathname}`;
    const frame = document.createElement('iframe');
    frame.src = parsed.href;
    frame.title = 'Embedded course content';
    frame.loading = 'lazy';
    frame.allowFullscreen = true;
    runEditorCommand('insertHTML', frame.outerHTML);
  };
  const uploadEditorFiles = async (files, type) => {
    for (const file of files) {
      const data = new FormData();
      data.append('file', file);
      data.append('folder', 'course-descriptions');
      data.append('is_public', '1');
      const response = await fetch('{{ route('admin.media.upload') }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('#newCourseForm input[name="_token"]').value },
        body: data,
      });
      const payload = await response.json();
      if (!response.ok || !payload.url) throw new Error(payload.error || 'Upload failed.');
      if (type === 'image') {
        runEditorCommand('insertImage', payload.url);
        continue;
      }
      const video = document.createElement('video');
      video.src = payload.url;
      video.controls = true;
      video.preload = 'metadata';
      runEditorCommand('insertHTML', video.outerHTML);
    }
  };
  document.querySelectorAll('[data-editor-command], [data-editor-action]').forEach((button) => {
    button.addEventListener('mousedown', (event) => event.preventDefault());
    button.addEventListener('click', () => {
    const command = button.dataset.editorCommand;
    const action = button.dataset.editorAction;
    if (action === 'link' || action === 'embed') return openEditorUrlPopover(action, button);
    if (action === 'color') return courseEditorColor.click();
    if (action === 'image') return courseEditorImage.click();
    if (action === 'video') return courseEditorVideo.click();
    if (action === 'rule') return runEditorCommand('insertHTML', '<hr>');
    if (action === 'table') return runEditorCommand('insertHTML', '<table><tbody><tr><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table>');
    runEditorCommand(command, button.dataset.editorValue || null);
    });
  });
  document.addEventListener('selectionchange', saveEditorRange);
  const applyEditorUrl = () => {
    const url = editorUrl(courseEditorUrl.value.trim());
    if (!url) {
      courseEditorUrl.setCustomValidity('Enter a valid http or https URL.');
      courseEditorUrl.reportValidity();
      return;
    }
    courseEditorUrl.setCustomValidity('');
    if (editorUrlAction === 'link') runEditorCommand('createLink', url);
    else insertEditorFrame(url);
    closeEditorUrlPopover();
  };
  courseEditorUrlApply?.addEventListener('click', applyEditorUrl);
  courseEditorUrl?.addEventListener('input', () => courseEditorUrl.setCustomValidity(''));
  courseEditorUrl?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      applyEditorUrl();
      return;
    }
    if (event.key === 'Escape') {
      event.preventDefault();
      closeEditorUrlPopover();
      restoreEditorRange();
      courseEditor.focus();
    }
  });
  courseEditorColor?.addEventListener('input', () => runEditorCommand('foreColor', courseEditorColor.value));
  courseEditorImage?.addEventListener('change', async () => {
    try { await uploadEditorFiles([...courseEditorImage.files], 'image'); } catch (error) { window.alert(error.message); }
    courseEditorImage.value = '';
  });
  courseEditorVideo?.addEventListener('change', async () => {
    try { await uploadEditorFiles([...courseEditorVideo.files], 'video'); } catch (error) { window.alert(error.message); }
    courseEditorVideo.value = '';
  });
  document.getElementById('newCourseForm')?.addEventListener('submit', (event) => {
    courseDescription.value = courseEditor.innerHTML.trim();
    const hasInstructor = document.querySelector('input[name="instructor_ids[]"]:checked');
    if (!selectedCategory.value || !courseEditor.textContent.trim() || !hasInstructor) {
      event.preventDefault();
      if (!selectedCategory.value) categoryPickerButton.focus();
      else if (!hasInstructor) instructorPickerButton.focus();
      else courseEditor.focus();
    }
  });
</script>
@endpush
