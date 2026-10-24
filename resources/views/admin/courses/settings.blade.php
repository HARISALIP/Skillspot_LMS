<section class="course-panel" data-course-panel="settings" hidden>
<form id="courseSettingsForm" method="POST" enctype="multipart/form-data" action="{{ route($workspaceRoutePrefix.'.update', $course) }}" class="settings-grid grid min-h-full grid-cols-[minmax(0,1fr)_30%]">
@csrf @method('PUT')
<div class="settings-main space-y-8 p-5">
  <section class="space-y-5">
    <h3 class="text-sm font-semibold">Course details</h3>
    <div class="grid gap-5 sm:grid-cols-2">
      <label><span class="cw-label">Title <b class="text-red-600">*</b></span><input class="cw-field" name="title" required value="{{ old('title', $course->title) }}"></label>
      <label><span class="cw-label">Category</span><select class="cw-field" name="category">@foreach($categories as $category)<option @selected(old('category', $course->category) === $category)>{{ $category }}</option>@endforeach</select></label>
      @if(($creatorWorkspace ?? false) && !($canManageOrganizationTeachers ?? false))
      <label><span class="cw-label">Instructors</span><div class="cw-field flex items-center" aria-label="Course instructors">{{ $course->teachers->pluck('name')->whenEmpty(fn () => collect([$course->primaryInstructor?->name]))->filter()->join(', ') }}</div></label>
@else
      <label><span class="cw-label">Instructors <b class="text-red-600">*</b></span><select class="cw-field" name="instructor_ids[]" multiple required>@foreach($instructors as $instructor)<option value="{{ $instructor->id }}" @selected(in_array($instructor->id, $selectedTeachers))>{{ $instructor->name }}</option>@endforeach</select></label>
@endif
      <label><span class="cw-label">Tags</span><input class="cw-field" name="tags" value="{{ old('tags', $course->tags) }}" placeholder="Add tags, separated by commas"></label>
      <label class="sm:col-span-2"><span class="cw-label">Short description <b class="text-red-600">*</b></span><textarea class="cw-field" rows="3" name="short_introduction" required>{{ old('short_introduction', $course->short_introduction) }}</textarea></label>
    </div>
    <div class="settings-media-row">
      <div>
        <span class="cw-label">Course thumbnail</span>
        <div class="flex flex-wrap items-start gap-4">
          <div id="settingsThumbnailPreview" class="settings-media-preview flex shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-gray-100" style="background:{{ $course->thumbnail_color ?: '#aeb7bf' }}">@if($course->thumbnail)<img src="{{ $course->thumbnail }}" alt="" class="h-full w-full object-cover">@endif</div>
          <div class="min-w-0 flex-1 space-y-2">
            <p id="settingsThumbnailName" class="break-all text-xs" @if(!$course->thumbnail) hidden @endif>{{ $course->thumbnail ? basename(parse_url($course->thumbnail, PHP_URL_PATH) ?: '') : '' }}</p>
            <div id="settingsThumbnailColors" @if($course->thumbnail) hidden @endif><span class="cw-label">Color</span><div class="settings-color-swatches">
              @foreach(['Red'=>'#f37c8b','Blue'=>'#5da4e9','Green'=>'#6bcf9a','Amber'=>'#e8c173','Purple'=>'#b869e8','Cyan'=>'#52bceb','Orange'=>'#e6a77d','Violet'=>'#9f8bee','Pink'=>'#eb6bb2','Teal'=>'#60bec7','Gray'=>'#aeb7bf','Yellow'=>'#eaca49'] as $name=>$hex)
                <button type="button" class="settings-color-swatch" data-thumbnail-color="{{ $hex }}" style="background-color:{{ $hex }}" aria-label="{{ $name }}" aria-pressed="false" title="{{ $name }}"></button>
              @endforeach
            </div></div>
            <div class="flex flex-wrap items-center gap-2"><label class="settings-button cursor-pointer"><span id="settingsThumbnailUploadText">{{ $course->thumbnail ? 'Replace' : 'Upload image instead' }}</span><input id="settingsThumbnailFile" type="file" name="thumbnail_file" accept=".jpg,.jpeg,.png,.gif,.webp" class="sr-only"></label><button id="removeThumbnailButton" type="button" class="settings-button text-red-600" @if(!$course->thumbnail) hidden @endif>Remove</button></div>
            <input id="removeThumbnailInput" type="hidden" name="remove_thumbnail" value="0">
            <input id="settingsThumbnailValue" type="hidden" name="thumbnail" value="{{ $course->thumbnail }}">
            <p id="settingsThumbnailHelp" class="text-xs text-gray-500">{{ $course->thumbnail ? 'Remove the image to pick a color instead.' : 'Upload an image to replace the color.' }}</p>
            <input id="settingsThumbnailColorInput" type="hidden" name="thumbnail_color" value="{{ old('thumbnail_color', $course->thumbnail_color ?: '#aeb7bf') }}">
          </div>
        </div>
      </div>
      <div>
        <span class="cw-label">Preview video</span>
        <div class="flex flex-wrap items-start gap-4">
          <div id="settingsVideoPreview" class="settings-media-preview flex shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-gray-100"><i class="fa-solid fa-video text-gray-400"></i></div>
          <div class="min-w-0 flex-1 space-y-2">
            <input id="settingsVideoUrl" class="cw-field" name="intro_video" value="{{ old('intro_video', $course->intro_video) }}" placeholder="Paste a YouTube link" aria-label="YouTube link">
            <label class="settings-button inline-block cursor-pointer">Upload video<input id="settingsVideoFile" type="file" name="intro_video_file" accept="video/mp4,video/webm,video/ogg" class="sr-only"></label>
            <button id="removePreviewVideo" type="button" class="settings-button text-red-600">Remove</button>
            <p class="text-xs text-gray-500">Paste a YouTube link or upload MP4, WebM, or OGG (up to 50 MB). Learners see this on the course page.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="space-y-5 border-t pt-6">
    <h3 class="text-sm font-semibold">Course overview</h3>
    <div><span id="courseDescriptionLabel" class="cw-label">Course Description <b class="text-red-600">*</b></span>@include('admin.courses.rich-editor')</div>
    <div><span class="cw-label">Related Courses</span><input type="hidden" name="related_course_ids_present" value="1"><details id="settingsRelatedPicker" class="settings-related-picker"><summary><span id="settingsRelatedSummary">Select related courses</span><span aria-hidden="true">⌄</span></summary><div class="settings-related-menu"><input id="settingsRelatedSearch" type="search" class="cw-field" placeholder="Select related courses" aria-label="Search related courses"><div class="settings-related-options">@foreach($relatedCourses as $related)<label class="settings-related-option" data-related-search="{{ Str::lower($related->title.' '.$related->slug) }}"><input type="checkbox" name="related_course_ids[]" value="{{ $related->id }}" @checked(in_array($related->id, old('related_course_ids', $course->related_course_ids ?? [])))><span><span class="block">{{ $related->title }}@if(!$related->is_published) <span class="text-gray-500">(Unpublished)</span>@endif</span><small>{{ $related->slug }}</small></span></label>@endforeach<p id="settingsRelatedEmpty" class="p-3 text-xs text-gray-500" hidden>No other published courses available</p></div><button id="settingsRelatedClear" type="button" class="settings-related-clear">Clear</button></div></details></div>
  </section>
  <section class="space-y-4 border-t pt-6">
    <div><h3 class="text-sm font-semibold">Meta Tags</h3><p class="mt-1 text-xs text-gray-500">These tags help search engines describe and rank your course in results.</p></div>
    <label class="block"><span class="cw-label">Meta description</span><textarea class="cw-field" rows="3" name="meta_description" placeholder="A short summary of the course for search results.">{{ old('meta_description', $course->meta_description) }}</textarea></label>
    <label class="block"><span class="cw-label">Meta keywords</span><textarea class="cw-field" rows="3" name="meta_keywords" placeholder="Comma separated keywords for SEO">{{ old('meta_keywords', $course->meta_keywords) }}</textarea></label>
  </section>
  <details class="border-t pt-5"><summary class="cursor-pointer text-sm font-semibold">Additional Skillspot settings</summary><div class="mt-5 space-y-5">
    <div class="grid gap-4 sm:grid-cols-2">
    @unless($creatorWorkspace ?? false)<label><span class="cw-label">Organization</span><select class="cw-field" name="vendor_id"><option value="">Skillspot (No organization)</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected($course->vendor_id == $vendor->id)>{{ $vendor->brand_name }}</option>@endforeach</select></label>@endunless
      <label><span class="cw-label">Language</span><select class="cw-field" name="language">@foreach($languages as $code=>$language)<option value="{{ $code }}" @selected($course->language === $code)>{{ $language }}</option>@endforeach</select></label>
      <label><span class="cw-label">Level</span><select class="cw-field" name="level">@foreach($levels as $level)<option value="{{ $level }}" @selected($course->level === $level)>{{ ucfirst($level) }}</option>@endforeach</select></label>
      <label><span class="cw-label">Course type</span><select class="cw-field" name="course_type">@foreach(['open'=>'Open','one_time'=>'One time','batch'=>'Batch'] as $value=>$label)<option value="{{ $value }}" @selected($course->course_type === $value)>{{ $label }}</option>@endforeach</select></label>
      <label><span class="cw-label">Requirements</span><textarea class="cw-field" rows="5" name="requirements">{{ $req }}</textarea></label>
      <label><span class="cw-label">What learners will learn</span><textarea class="cw-field" rows="5" name="outcomes">{{ $out }}</textarea></label>
    </div>
    <div id="courseMaterials"><div class="mb-3 flex justify-between"><div><h3 class="text-sm font-semibold">Course materials</h3><p class="text-xs text-gray-500">Files shared across the course.</p></div><button type="button" onclick="addMaterialRow()" class="settings-button">+ Add material</button></div><div id="materialRows" class="space-y-3"></div></div>
  </div></details>
</div>
<aside class="settings-side border-l">
  <details open class="settings-side-section"><summary>Visibility</summary><div class="space-y-5 p-5 pt-3">
    @foreach([['upcoming','Upcoming','Not yet open for enrollment.'],['is_featured','Featured','Highlight on the homepage.'],['registration_open','Self enrollment','Let users enroll themselves.'],['enforce_lesson_completion','Enforce Lesson Completion','Students must complete each lesson before the next one opens.']] as [$name,$label,$help])
      <label class="flex items-start gap-3"><span class="flex-1"><b class="block text-sm font-normal">{{ $label }}</b><span class="text-xs text-gray-500">{{ $help }}</span></span><input class="cw-toggle" type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $course->$name))></label>
    @endforeach
    @unless($creatorWorkspace ?? false)
    <label class="block"><span class="cw-label">Access</span><select class="cw-field" name="visibility">@foreach(['draft'=>'Draft','public'=>'Public','restricted'=>'Restricted'] as $value=>$label)<option value="{{ $value }}" @selected($course->visibility === $value)>{{ $label }}</option>@endforeach</select></label>
@endunless
    @if($course->vendor_id)
    <div class="space-y-3 border-t pt-4"><p class="text-sm font-semibold">Organization access</p><label class="flex items-start gap-3"><span class="flex-1"><b class="block text-sm font-normal">Publish in this organization</b><span class="text-xs text-gray-500">Keep marketplace review separate.</span></span><input class="cw-toggle" type="checkbox" name="organization_published" value="1" @checked(old('organization_published', $course->organization_published))></label><label><span class="cw-label">Audience</span><select class="cw-field" name="organization_audience"><option value="all" @selected(old('organization_audience', $course->organization_audience) === 'all')>All organization students</option><option value="classes" @selected(old('organization_audience', $course->organization_audience) === 'classes')>Selected classes</option></select></label><fieldset><legend class="cw-label">Classes</legend><div class="space-y-2">@forelse($course->vendor->organizationClasses as $class)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, old('class_ids', $course->organizationClasses->pluck('id')->all())))> {{ $class->name }}</label>@empty<p class="text-xs text-gray-500">Create a class in Organization before selecting this audience.</p>@endforelse</div></fieldset></div>
    @endif
  </div></details>
  <details open class="settings-side-section"><summary>Pricing and certification</summary><div class="space-y-5 p-5 pt-3">
    <label class="flex items-start gap-3"><span class="flex-1"><b class="block text-sm font-normal">Paid course</b><span class="text-xs text-gray-500">Charge learners to enroll in this course.</span></span><input id="paidToggle" class="cw-toggle" type="checkbox" @checked(!$course->is_free)></label>
    <input id="freeInput" type="hidden" name="is_free" value="{{ $course->is_free ? 1 : 0 }}">
    <div id="priceFields" class="grid grid-cols-2 gap-2"><label><span class="cw-label">Course price (₹)</span><input class="cw-field" type="number" name="price" min="0" step=".01" value="{{ old('price', $course->price) }}" required></label><label><span class="cw-label">Sale price</span><input class="cw-field" type="number" name="sale_price" min="0" step=".01" value="{{ old('sale_price', $course->sale_price) }}"></label></div>
    <label class="flex items-start gap-3 border-t pt-4"><span class="flex-1"><b class="block text-sm font-normal">Completion certificate</b><span class="text-xs text-gray-500">Issue a free certificate when learners complete the course.</span></span><input class="cw-toggle" type="checkbox" name="enable_certification" value="1" @checked(old('enable_certification', $course->enable_certification ?? true))></label>
  </div></details>
  <details class="settings-side-section"><summary>Marketplace review</summary><div class="space-y-3 p-5 pt-3 text-xs"><p>Status: <b>{{ ucfirst(str_replace('_', ' ', $course->marketplace_status ?? 'draft')) }}</b></p>@if($course->review_feedback)<p class="rounded bg-amber-50 p-2 text-amber-800">{{ $course->review_feedback }}</p>@endif @if(in_array($course->marketplace_status, ['draft','needs_changes','rejected']))<button form="reviewForm" class="settings-button">Submit for review</button>@endif</div></details>
</aside>
</form>
<form id="reviewForm" method="POST" action="{{ route(($creatorWorkspace ?? false) ? 'studio.courses.submit-review' : 'admin.courses.submit-review', $course) }}">@csrf</form>
</section>
