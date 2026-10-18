<div id="newCourseModal" class="fixed inset-0 z-50 {{ request()->has('new') || old('modal_create') ? '' : 'hidden' }} overflow-y-auto bg-black/35 p-4" role="dialog" aria-modal="true" aria-labelledby="newCourseModalTitle">
  <form id="newCourseForm" method="POST" action="{{ $courseCreateAction ?? route('admin.courses.store') }}" enctype="multipart/form-data" class="mx-auto my-6 w-full max-w-3xl rounded-xl bg-white p-5 shadow-xl md:my-8 md:p-6">
    @csrf
    <input type="hidden" name="modal_create" value="1">
    <input type="hidden" name="category" id="selectedCategory" value="{{ old('category') }}">
    <input type="hidden" name="new_category" id="newCategory" value="{{ old('new_category') }}">
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

    @if(old('modal_create') && $errors->any())<div role="alert" class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <div class="grid gap-x-5 gap-y-5 pt-6 md:grid-cols-2">
      <label class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span>
        <input name="title" type="text" required maxlength="255" value="{{ old('title') }}" autocomplete="off" class="mt-2 h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100" aria-label="Course title">
      </label>
      <div class="relative text-sm font-medium text-gray-700" id="categoryPicker">Category
        <button type="button" id="categoryPickerButton" aria-expanded="false" class="mt-2 flex h-10 w-full items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm font-normal text-gray-500 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"><span id="categoryPickerLabel">{{ old('category', old('new_category', 'Select option')) ?: 'Select option' }}</span><i class="fa-solid fa-chevron-down text-xs"></i></button>
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
              <input type="checkbox" name="instructor_ids[]" value="{{ $instructor->id }}" @checked(in_array($instructor->id, old('instructor_ids', []))) class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
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
        <div class="mt-2 flex flex-wrap items-center gap-3">
          <label id="thumbnailPreview" for="thumbnailFile" class="flex h-24 flex-1 cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-dashed border-gray-200 bg-gray-50 text-gray-400"><i class="fa-regular fa-image text-xl"></i></label>
          <label for="thumbnailFile" class="cursor-pointer rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Upload</label>
          <input id="thumbnailFile" name="thumbnail_file" type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="sr-only">
          <button id="removeThumbnail" type="button" class="hidden text-xs font-medium text-red-600 hover:underline">Remove</button>
        </div>
        <p class="mt-1 text-xs font-normal text-gray-500">Upload a 750×422 image (.jpg, .jpeg, .gif, or .png). Shown on the catalog card and lesson hero.</p>
      </div>
    </div>

    <div class="mt-6 border-t border-gray-100 pt-5">
      <label class="block text-sm font-medium text-gray-700">Short introduction <span class="text-red-500">*</span>
        <textarea name="short_introduction" required maxlength="1000" rows="3" class="mt-2 w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">{{ old('short_introduction') }}</textarea>
      </label>
      <div class="mt-5 block text-sm font-medium text-gray-700">
        <span id="courseDescriptionLabel">Course description <span class="text-red-500">*</span></span>
        <div class="frappe-rich-editor mt-2" data-rich-editor>
          <div class="frappe-rich-editor__toolbar" role="toolbar" aria-label="Course description formatting">
            <button type="button" class="frappe-rich-editor__tool frappe-rich-editor__heading" data-editor-command="formatBlock" data-editor-value="p" title="Heading">Heading</button>
            <button type="button" class="frappe-rich-editor__tool" data-editor-command="formatBlock" data-editor-value="h2" title="Heading 2">H₂</button><button type="button" class="frappe-rich-editor__tool" data-editor-command="formatBlock" data-editor-value="h3" title="Heading 3">H₃</button><button type="button" class="frappe-rich-editor__tool" data-editor-command="formatBlock" data-editor-value="h4" title="Heading 4">H₄</button>
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-command="bold" title="Bold" aria-pressed="false"><b>B</b></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="italic" title="Italic" aria-pressed="false"><i>I</i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="strikeThrough" title="Strike" aria-pressed="false"><s>S</s></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="link" title="Link"><i class="fa-solid fa-link"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="color" title="Text and background color"><i class="fa-solid fa-a"></i></button>
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-command="insertUnorderedList" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="insertOrderedList" title="Ordered List"><i class="fa-solid fa-list-ol"></i></button>
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-command="justifyLeft" title="Align Left"><i class="fa-solid fa-align-left"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="justifyCenter" title="Align Center"><i class="fa-solid fa-align-center"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="justifyRight" title="Align Right"><i class="fa-solid fa-align-right"></i></button>
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-action="image" title="Image / Gallery"><i class="fa-regular fa-image"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="video" title="Video"><i class="fa-solid fa-video"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="formatBlock" data-editor-value="blockquote" title="Blockquote"><i class="fa-solid fa-quote-right"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="code" title="Inline Code"><i class="fa-solid fa-code"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="embed" title="Embed"><i class="fa-solid fa-window-maximize"></i></button>
            <span class="frappe-rich-editor__divider"></span>
            <button type="button" class="frappe-rich-editor__tool" data-editor-action="rule" title="Horizontal Rule"><i class="fa-solid fa-minus"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-action="table" title="Table"><i class="fa-solid fa-table"></i></button>
            <div class="frappe-rich-editor__history"><button type="button" class="frappe-rich-editor__tool" data-editor-command="undo" title="Undo"><i class="fa-solid fa-rotate-left"></i></button><button type="button" class="frappe-rich-editor__tool" data-editor-command="redo" title="Redo"><i class="fa-solid fa-rotate-right"></i></button></div>
            <div id="courseEditorUrlPopover" class="frappe-rich-editor__popover" role="dialog" aria-label="Edit link" hidden>
              <input id="courseEditorUrl" type="url" inputmode="url" autocomplete="off" placeholder="https://example.com" aria-label="URL">
              <button id="courseEditorUrlApply" type="button" class="frappe-rich-editor__tool" aria-label="Apply URL"><i class="fa-solid fa-check"></i></button>
            </div>
            <div id="courseEditorColorPopover" class="frappe-rich-editor__popover frappe-rich-editor__palette" role="dialog" aria-label="Text and background color" hidden></div>
            <div id="courseEditorTablePopover" class="frappe-rich-editor__popover frappe-rich-editor__table-picker" role="dialog" aria-label="Table size, 3 rows by 3 columns" hidden>
              <div id="courseEditorTableGrid" class="frappe-rich-editor__table-grid" role="grid"></div>
              <div id="courseEditorTableSize" class="frappe-rich-editor__table-size">3 × 3</div>
            </div>
          </div>
          <div id="courseEditor" contenteditable="true" class="frappe-rich-editor__content" data-placeholder="Write a detailed description of your course" aria-labelledby="courseDescriptionLabel"></div>
          <input id="courseEditorImage" type="file" accept="image/*" multiple class="sr-only">
          <input id="courseEditorVideo" type="file" accept="video/*" class="sr-only">
        </div>
      </div>
    </div>

    <div class="mt-6 flex justify-end">
      <button type="submit" class="rounded-md bg-[#171717] px-3 py-1.5 text-sm font-semibold text-white hover:bg-[#333]">Save</button>
    </div>
  </form>
</div>

<div id="courseEditorEmbedModal" class="frappe-embed-modal fixed inset-0 z-[80] flex items-center justify-center bg-black/70 p-4" role="dialog" aria-modal="true" aria-labelledby="courseEditorEmbedTitle" hidden>
  <div class="w-full max-w-md rounded-2xl border border-gray-700 bg-[#202020] p-6 text-gray-100 shadow-2xl">
    <div class="flex items-center justify-between">
      <h3 id="courseEditorEmbedTitle" class="text-lg font-semibold">Insert Embed</h3>
      <button id="courseEditorEmbedClose" type="button" class="rounded p-1 text-gray-300 hover:bg-white/10" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <label class="mt-6 block text-sm text-gray-400">URL or Embed Code
      <textarea id="courseEditorEmbedInput" rows="3" class="mt-2 w-full resize-y rounded-lg border border-gray-600 bg-[#171717] px-3 py-2 text-sm text-gray-100 outline-none focus:border-gray-400" placeholder="https://youtube.com/watch?v=… or <iframe src=…>"></textarea>
    </label>
    <p id="courseEditorEmbedError" class="mt-2 hidden text-xs text-red-400">Please enter a supported URL or iframe embed code</p>
    <div class="mt-6 flex justify-end gap-2">
      <button id="courseEditorEmbedCancel" type="button" class="rounded-lg bg-gray-700 px-3 py-2 text-sm">Cancel</button>
      <button id="courseEditorEmbedApply" type="button" disabled class="rounded-lg bg-gray-700 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:text-gray-500 enabled:bg-white enabled:text-gray-900">Insert Embed</button>
    </div>
  </div>
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
