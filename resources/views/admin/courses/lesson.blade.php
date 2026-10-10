@extends('layouts.admin')
@section('title', ($isEdit ? 'Edit' : 'Add').' Lesson — Skillspot.in Admin')
@section('page-title', $isEdit ? 'Edit Lesson' : 'Add Lesson')
@section('page-sub', $course->title.' → '.$section->title)

@section('admin-content')

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-4 text-sm">
  @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- ── Main form ──────────────────────────────────────────────────── --}}
  <div class="lg:col-span-2">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-9 h-9 bg-green-100 rounded-xl flex items-center justify-center text-lg">🎬</div>
        <div>
          <h3 class="font-black text-gray-900">{{ $isEdit ? 'Edit Lesson' : 'New Lesson' }}</h3>
          <p class="text-xs text-gray-400">{{ $course->title }} → {{ $section->title }}</p>
        </div>
      </div>

      <form method="POST"
            action="{{ $isEdit
              ? route('admin.courses.lessons.update', [$course->id,$section->id,$lesson->id])
              : route('admin.courses.lessons.store',  [$course->id,$section->id]) }}"
            class="p-6 space-y-5">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Title --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Lesson Title <span class="text-red-400">*</span></label>
          <input type="text" name="title" value="{{ old('title',$lesson->title) }}" required
                 placeholder="e.g. Introduction to HTML"
                 class="w-full px-4 py-3 rounded-xl border {{ $errors->has('title') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
        </div>

        {{-- Description --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Short Description</label>
          <textarea name="description" rows="2" placeholder="Brief description of this lesson"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition resize-none">{{ old('description',$lesson->description) }}</textarea>
        </div>

        {{-- Lesson type --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Lesson Type <span class="text-red-400">*</span></label>
          <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
            @foreach([
              ['video','🎬','Video'],
              ['text', '📖','Text'],
              ['pdf',  '📄','PDF'],
              ['quiz', '📝','Quiz'],
              ['live', '📡','Live'],
            ] as [$val,$icon,$lbl])
            <label class="cursor-pointer">
              <input type="radio" name="type" value="{{ $val }}" class="sr-only peer"
                     {{ old('type',$lesson->type ?? 'video') === $val ? 'checked' : '' }}
                     onchange="onTypeChange('{{ $val }}')">
              <div class="border-2 border-gray-200 bg-white rounded-xl p-3 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 transition">
                <div class="text-2xl mb-1">{{ $icon }}</div>
                <div class="text-xs font-bold text-gray-700">{{ $lbl }}</div>
              </div>
            </label>
            @endforeach
          </div>
        </div>

        @include('shared.lesson-quiz-fields')

        {{-- ── VIDEO SECTION (secure) ── --}}
        <div id="fieldVideoUrl" class="{{ in_array(old('type',$lesson->type ?? 'video'),['video','live']) ? '' : 'hidden' }}">
          <input type="hidden" name="video_storage" id="videoStorageInput"
                 value="{{ old('video_storage', $lesson->video_storage ?? 'external') }}">
          <input type="hidden" name="video_r2_path" id="videoR2PathInput"
                 value="{{ old('video_r2_path', $lesson->video_r2_path) }}">

          {{-- Source tabs --}}
          <div class="flex bg-gray-100 rounded-2xl p-1 mb-4 gap-1">
            <button type="button" id="tab-r2"       onclick="switchVideoTab('r2')"
                    class="video-tab flex-1 py-2.5 rounded-xl text-sm font-semibold transition {{ old('video_storage',$lesson->video_storage ?? 'external') === 'r2' ? 'bg-white text-brand-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
              ☁️ Upload to R2 <span class="text-xs text-green-600 font-bold">(Secure)</span>
            </button>
            <button type="button" id="tab-external" onclick="switchVideoTab('external')"
                    class="video-tab flex-1 py-2.5 rounded-xl text-sm font-semibold transition {{ old('video_storage',$lesson->video_storage ?? 'external') !== 'r2' ? 'bg-white text-brand-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
              🔗 External URL
            </button>
          </div>

          {{-- R2 Upload tab --}}
          <div id="videoTabR2" class="{{ old('video_storage',$lesson->video_storage ?? 'external') === 'r2' ? '' : 'hidden' }}">

            {{-- Security badge --}}
            <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-3 flex items-start gap-2">
              <span class="text-lg flex-shrink-0">🔒</span>
              <div class="text-xs text-green-700">
                <strong>Secure Storage</strong> — Video stored privately on Cloudflare R2.
                Served via <strong>signed URLs</strong> (2hr expiry). Cannot be downloaded or shared outside LMS.
              </div>
            </div>

            {{-- Current video info --}}
            @if($isEdit && $lesson->video_r2_path)
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-3 flex items-center gap-3">
              <span class="text-2xl flex-shrink-0">✅</span>
              <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-blue-800">Video uploaded</div>
                <div class="text-xs text-blue-600 truncate font-mono">{{ $lesson->video_r2_path }}</div>
              </div>
            </div>
            @endif

            {{-- Upload area --}}
            <div id="r2DropZone"
                 class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center cursor-pointer hover:border-brand-400 hover:bg-brand-50 transition mb-3"
                 onclick="document.getElementById('r2VideoInput').click()"
                 ondragover="event.preventDefault();this.classList.add('border-brand-500','bg-brand-50')"
                 ondragleave="this.classList.remove('border-brand-500','bg-brand-50')"
                 ondrop="handleVideoDrop(event)">
              <div class="text-4xl mb-2">🎬</div>
              <p class="text-sm font-semibold text-gray-600">Drop video here or click to upload</p>
              <p class="text-xs text-gray-400 mt-1">MP4, WebM, MOV · Max 500MB</p>
            </div>
            <input type="file" id="r2VideoInput" class="hidden"
                   accept="video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo"
                   onchange="uploadVideo(this.files[0])">

            {{-- Or pick from media library --}}
            <button type="button"
                    onclick="openMediaPicker('videoPickerModal','videoPicker','video','course-videos')"
                    class="w-full flex items-center justify-center gap-2 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-600 hover:border-brand-400 hover:text-brand-600 hover:bg-brand-50 transition">
              📁 Pick from Media Library
            </button>

            {{-- Upload progress --}}
            <div id="videoUploadProgress" class="hidden mt-3">
              <div class="flex items-center justify-between text-xs mb-1">
                <span id="videoUploadName" class="text-gray-600 truncate"></span>
                <span id="videoUploadPct" class="font-bold text-brand-600">0%</span>
              </div>
              <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
                <div id="videoUploadBar" class="bg-brand-500 h-full rounded-full transition-all duration-300" style="width:0%"></div>
              </div>
              <div id="videoUploadStatus" class="text-xs text-gray-400 mt-1">Uploading to Cloudflare R2…</div>
            </div>
          </div>

          {{-- External URL tab --}}
          <div id="videoTabExternal" class="{{ old('video_storage',$lesson->video_storage ?? 'external') !== 'r2' ? '' : 'hidden' }}">
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-3 mb-3 flex items-start gap-2">
              <span class="text-lg flex-shrink-0">⚠️</span>
              <div class="text-xs text-yellow-700">
                <strong>External URLs are not secure</strong> — YouTube/Vimeo links can be shared outside the LMS.
                Use R2 upload for proprietary content.
              </div>
            </div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Video URL</label>
            <input type="text" name="video_url" id="videoUrlInput"
                   value="{{ old('video_url',$lesson->video_url) }}"
                   placeholder="https://youtube.com/watch?v=... or Vimeo URL"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
        </div>

        {{-- ── LIVE CLASS SECTION ── --}}
        <div id="fieldLive" class="{{ old('type',$lesson->type ?? 'video') === 'live' ? '' : 'hidden' }}">

          {{-- Platform selector --}}
          <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Platform <span class="text-red-400">*</span></label>
            @php
              $platforms = [
                ['google_meet','Google Meet','🟢','Free · No account needed for students'],
                ['zoom','Zoom','🔵','Free 40min · Paid for longer sessions'],
                ['teams','Microsoft Teams','🟣','Free tier available'],
                ['other','Other Platform','⚪','Any platform link'],
              ];
              $selPlatform = old('live_platform', $lesson->live_platform ?? 'google_meet');
            @endphp
            <div class="grid grid-cols-2 gap-3">
              @foreach($platforms as [$val,$name,$dot,$hint])
              <label class="cursor-pointer">
                <input type="radio" name="live_platform" value="{{ $val }}" class="sr-only peer"
                       {{ $selPlatform === $val ? 'checked' : '' }}>
                <div class="border-2 border-gray-200 bg-white rounded-xl p-3.5 peer-checked:border-brand-500 peer-checked:bg-brand-50 transition flex items-start gap-3">
                  <span class="text-xl flex-shrink-0">{{ $dot }}</span>
                  <div>
                    <div class="text-sm font-bold text-gray-900">{{ $name }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ $hint }}</div>
                  </div>
                </div>
              </label>
              @endforeach
            </div>
          </div>

          {{-- Meeting link --}}
          <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Meeting Link <span class="text-red-400">*</span></label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔗</span>
              <input type="text" name="live_url" value="{{ old('live_url',$lesson->live_url) }}"
                     placeholder="https://meet.google.com/xxx-xxxx-xxx"
                     class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            </div>
            <p class="text-xs text-gray-400 mt-1">Paste the full join link. Students see a "Join Live Class" button.</p>
          </div>

          {{-- Date + Time + Duration --}}
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Scheduled Date & Time</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📅</span>
                <input type="datetime-local" name="live_scheduled_at"
                       value="{{ old('live_scheduled_at', $lesson->live_scheduled_at ? \Carbon\Carbon::parse($lesson->live_scheduled_at)->format('Y-m-d\TH:i') : '') }}"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Duration (min)</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">⏱</span>
                <input type="number" name="live_duration_min" min="15" max="480" step="15"
                       value="{{ old('live_duration_min',$lesson->live_duration_min ?? 60) }}"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              </div>
            </div>
          </div>

          {{-- Meeting ID + Password --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Meeting ID <span class="text-gray-400 font-normal normal-case">(optional – for Zoom)</span></label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">#️⃣</span>
                <input type="text" name="live_meeting_id" value="{{ old('live_meeting_id',$lesson->live_meeting_id) }}"
                       placeholder="e.g. 123 456 7890"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Password <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔑</span>
                <input type="text" name="live_password" value="{{ old('live_password',$lesson->live_password) }}"
                       placeholder="Meeting password"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              </div>
            </div>
          </div>

          {{-- Platform guide --}}
          <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4">
            <div class="text-xs font-bold text-blue-800 mb-2">📋 How to get your meeting link:</div>
            <div class="space-y-2 text-xs text-blue-700">
              <div class="flex items-start gap-2">
                <span class="font-bold flex-shrink-0">🟢 Google Meet:</span>
                <span>Go to <strong>meet.google.com</strong> → New Meeting → Copy link → Paste above. Students join without a Google account.</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="font-bold flex-shrink-0">🔵 Zoom:</span>
                <span>Schedule a meeting in Zoom app → Copy <strong>Invite Link</strong> → Also add Meeting ID & Passcode for easy joining.</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="font-bold flex-shrink-0">🟣 Teams:</span>
                <span>Create meeting in Microsoft Teams → <strong>Copy join link</strong> → Paste above. Students click to join browser.</span>
              </div>
            </div>
          </div>
        </div>

        {{-- Content (text/pdf) --}}
        <div id="fieldContent" class="{{ in_array(old('type',$lesson->type ?? 'video'),['text','pdf']) ? '' : 'hidden' }}">
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">
            {{ old('type',$lesson->type) === 'pdf' ? 'PDF URL' : 'Text Content' }}
          </label>
          <div id="pdfFields" class="{{ old('type',$lesson->type ?? 'video') === 'pdf' ? '' : 'hidden' }}">
          {{-- PDF Upload Section --}}
          <input type="hidden" name="content" id="pdfContentInput" value="{{ old('content',$lesson->content) }}" {{ old('type',$lesson->type ?? 'video') === 'pdf' ? '' : 'disabled' }}>

          {{-- Current PDF info --}}
          @if($isEdit && $lesson->content && old('type',$lesson->type) === 'pdf')
          <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-3 flex items-center gap-3">
            <span class="text-2xl flex-shrink-0">📄</span>
            <div class="flex-1 min-w-0">
              <div class="text-xs font-semibold text-blue-800">Current PDF</div>
              <div class="text-xs text-blue-600 truncate font-mono">{{ basename($lesson->content) }}</div>
            </div>
            <a href="{{ $lesson->content }}" target="_blank"
               class="text-xs text-blue-700 font-bold hover:underline flex-shrink-0">View →</a>
          </div>
          @endif

          {{-- Drop zone --}}
          <div id="pdfDropZone"
               class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center cursor-pointer hover:border-brand-400 hover:bg-brand-50 transition mb-3"
               onclick="document.getElementById('pdfFileInput').click()"
               ondragover="event.preventDefault();this.classList.add('border-brand-500','bg-brand-50')"
               ondragleave="this.classList.remove('border-brand-500','bg-brand-50')"
               ondrop="handlePdfDrop(event)">
            <div class="text-4xl mb-2">📄</div>
            <p class="text-sm font-semibold text-gray-600">Drop PDF here or <span class="text-brand-600">click to browse</span></p>
            <p class="text-xs text-gray-400 mt-1">PDF files only · Max {{ \App\Models\Setting::get('max_upload_mb',500) }}MB</p>
          </div>
          <input type="file" id="pdfFileInput" class="hidden" accept="application/pdf"
                 onchange="uploadPdfFile(this.files[0])">

          {{-- Or enter URL manually --}}
          <div class="relative mb-3">
            <div class="flex items-center gap-2 mb-1.5">
              <div class="flex-1 h-px bg-gray-200"></div>
              <span class="text-xs text-gray-400 font-medium">OR paste URL</span>
              <div class="flex-1 h-px bg-gray-200"></div>
            </div>
            <input type="text" id="pdfUrlInput"
                   value="{{ old('content',$lesson->content) }}"
                   placeholder="https://… or R2 path"
                   oninput="document.getElementById('pdfContentInput').value=this.value;updatePdfPreview(this.value)"
                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>

          {{-- Upload progress --}}
          <div id="pdfUploadProgress" class="hidden mb-3">
            <div class="flex items-center justify-between text-xs mb-1">
              <span id="pdfUploadName" class="text-gray-600 truncate flex-1 mr-2"></span>
              <span id="pdfUploadPct" class="font-bold text-brand-600 flex-shrink-0">0%</span>
            </div>
            <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
              <div id="pdfUploadBar" class="bg-brand-500 h-full rounded-full transition-all duration-300" style="width:0%"></div>
            </div>
            <div id="pdfUploadStatus" class="text-xs text-gray-400 mt-1">Uploading to R2…</div>
          </div>

          {{-- Preview uploaded PDF --}}
          <div id="pdfPreview" class="hidden bg-green-50 border border-green-200 rounded-xl p-3 flex items-center gap-3">
            <span class="text-2xl flex-shrink-0">✅</span>
            <div class="flex-1 min-w-0">
              <div class="text-xs font-semibold text-green-800">PDF ready</div>
              <div id="pdfPreviewName" class="text-xs text-green-600 truncate font-mono"></div>
            </div>
            <a id="pdfPreviewLink" href="#" target="_blank"
               class="text-xs text-green-700 font-bold hover:underline flex-shrink-0">View PDF →</a>
          </div>

          </div>
          <div id="textFields" class="{{ old('type',$lesson->type ?? 'video') === 'text' ? '' : 'hidden' }}">
          {{-- Quill Rich Text Editor --}}
          <input type="hidden" name="content" id="contentInput" value="{{ old('content',$lesson->content) }}" {{ old('type',$lesson->type ?? 'video') === 'text' ? '' : 'disabled' }}>
          <div id="quillEditor" class="rounded-xl border border-gray-200 bg-white overflow-hidden" style="min-height:280px;">
            <div id="quillToolbar">
              <span class="ql-formats">
                <select class="ql-header">
                  <option selected></option>
                  <option value="1">Heading 1</option>
                  <option value="2">Heading 2</option>
                  <option value="3">Heading 3</option>
                </select>
              </span>
              <span class="ql-formats">
                <select class="ql-font">
                  <option selected>Default</option>
                  <option value="serif">Serif</option>
                  <option value="monospace">Monospace</option>
                </select>
              </span>
              <span class="ql-formats">
                <select class="ql-size">
                  <option value="small">Small</option>
                  <option selected>Normal</option>
                  <option value="large">Large</option>
                  <option value="huge">Huge</option>
                </select>
              </span>
              <span class="ql-formats">
                <button class="ql-bold" title="Bold"></button>
                <button class="ql-italic" title="Italic"></button>
                <button class="ql-underline" title="Underline"></button>
                <button class="ql-strike" title="Strikethrough"></button>
              </span>
              <span class="ql-formats">
                <select class="ql-color" title="Text Color"></select>
                <select class="ql-background" title="Background Color"></select>
              </span>
              <span class="ql-formats">
                <button class="ql-list" value="ordered" title="Ordered List"></button>
                <button class="ql-list" value="bullet" title="Bullet List"></button>
                <button class="ql-indent" value="-1" title="Decrease Indent"></button>
                <button class="ql-indent" value="+1" title="Increase Indent"></button>
              </span>
              <span class="ql-formats">
                <button class="ql-blockquote" title="Blockquote"></button>
                <button class="ql-code-block" title="Code Block"></button>
              </span>
              <span class="ql-formats">
                <button class="ql-link" title="Link"></button>
                <button class="ql-image" title="Image"></button>
              </span>
              <span class="ql-formats">
                <select class="ql-align" title="Align">
                  <option selected></option>
                  <option value="center"></option>
                  <option value="right"></option>
                  <option value="justify"></option>
                </select>
              </span>
              <span class="ql-formats">
                <button class="ql-clean" title="Clear Formatting"></button>
              </span>
            </div>
            <div id="quillContent" style="min-height:220px; font-size:14px; font-family:Inter,sans-serif;"></div>
          </div>
          <p class="text-xs text-gray-400 mt-1.5">Rich text editor — bold, italic, headings, colors, lists, links, images & more</p>
          </div>
        </div>

        {{-- Notes --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Lesson Notes <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
          <textarea name="notes" rows="3" placeholder="Additional notes, links, references for students…"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition resize-none">{{ old('notes',$lesson->notes) }}</textarea>
        </div>

        {{-- Duration + Preview --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Duration (minutes)</label>
            <input type="number" name="duration" value="{{ old('duration',$lesson->duration) }}" min="0"
                   placeholder="e.g. 15"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <div class="flex items-end pb-1">
            <label class="flex items-center gap-3 cursor-pointer p-4 bg-green-50 border border-green-200 rounded-xl w-full hover:bg-green-100 transition">
              <input type="checkbox" name="is_preview" value="1"
                     {{ old('is_preview',$lesson->is_preview ?? false) ? 'checked' : '' }}
                     class="w-4 h-4 rounded text-green-600 focus:ring-green-500">
              <div>
                <div class="text-sm font-bold text-green-800">Free Preview</div>
                <div class="text-xs text-green-600">Non-enrolled students can watch this</div>
              </div>
            </label>
          </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
          <a href="{{ route('admin.courses.edit', $course->id) }}" class="text-sm text-gray-500 hover:text-gray-700 transition">← Back to Course</a>
          <button type="submit" id="saveBtn"
                  class="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white font-black px-7 py-2.5 rounded-xl transition shadow-md text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            {{ $isEdit ? 'Update Lesson' : 'Add Lesson' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── Sidebar ─────────────────────────────────────────────────────── --}}
  <div class="space-y-5">
    {{-- Security info --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h4 class="font-bold text-gray-900 text-sm mb-3">🔒 Video Security</h4>
      <div class="space-y-3 text-xs text-gray-600">
        <div class="flex items-start gap-2 p-3 bg-green-50 rounded-xl border border-green-100">
          <span>✅</span>
          <div><strong class="text-green-800">R2 Secure</strong><br>Signed URLs expire in 2hrs. No direct access possible. Right-click blocked.</div>
        </div>
        <div class="flex items-start gap-2 p-3 bg-yellow-50 rounded-xl border border-yellow-100">
          <span>⚠️</span>
          <div><strong class="text-yellow-800">External URL</strong><br>YouTube/Vimeo links can be shared. Use only for free preview content.</div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h4 class="font-bold text-gray-900 text-sm mb-3">Lesson Types</h4>
      <div class="space-y-2 text-xs text-gray-600">
        <div class="flex items-start gap-2"><span>🎬</span><div><strong>Video</strong> — Upload to R2 (secure) or link YouTube/Vimeo</div></div>
        <div class="flex items-start gap-2"><span>📖</span><div><strong>Text</strong> — Written article or HTML content</div></div>
        <div class="flex items-start gap-2"><span>📄</span><div><strong>PDF</strong> — Upload PDF to R2 or link URL</div></div>
        <div class="flex items-start gap-2"><span>📝</span><div><strong>Quiz</strong> — Multiple choice assessment</div></div>
        <div class="flex items-start gap-2"><span>📡</span><div><strong>Live</strong> — Scheduled live session link</div></div>
      </div>
    </div>

    <div class="bg-brand-50 border border-brand-200 rounded-2xl p-5">
      <h4 class="font-bold text-brand-800 text-sm mb-2">💡 Tips</h4>
      <ul class="space-y-1.5 text-xs text-brand-700 leading-relaxed">
        <li>• Upload to R2 for full content protection</li>
        <li>• First 1-2 lessons → mark as Free Preview</li>
        <li>• Add notes for extra learning resources</li>
        <li>• Set accurate duration for student planning</li>
      </ul>
    </div>
  </div>
</div>

{{-- Media picker modal for video library --}}
<div id="videoPickerModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
      <h3 class="font-black text-gray-900">🎬 Video Library</h3>
      <div class="flex items-center gap-3">
        <input type="text" placeholder="Search videos…" id="videoPickerModal-search"
               class="px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 w-40"
               oninput="mpSearch('videoPickerModal')">
        <button onclick="document.getElementById('videoPickerModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl ml-2">×</button>
      </div>
    </div>
    <div class="flex-1 overflow-y-auto p-4">
      <div id="videoPickerModal-grid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
        <div class="col-span-full text-center py-10 text-gray-400">Loading…</div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
#quillEditor .ql-toolbar { border:none; border-bottom:1px solid #e5e7eb; background:#f9fafb; padding:8px 12px; }
#quillEditor .ql-container { border:none; }
#quillEditor .ql-editor { min-height:220px; font-size:14px; line-height:1.7; padding:16px; }
#quillEditor .ql-editor.ql-blank::before { color:#9ca3af; font-style:normal; }
.ql-toolbar .ql-formats { margin-right:8px; }
</style>
@endpush

@push('scripts')
<script>
const _uploadUrl = '{{ route('admin.media.upload') }}';
const _csrf      = '{{ csrf_token() }}';

function onTypeChange(type) {
  const videoFields   = ['video','live'];
  const contentFields = ['text','pdf'];
  document.getElementById('fieldVideoUrl').classList.toggle('hidden', !videoFields.includes(type));
  document.getElementById('fieldContent').classList.toggle('hidden',  !contentFields.includes(type));
}

function switchVideoTab(tab) {
  document.getElementById('videoTabR2').classList.toggle('hidden',       tab !== 'r2');
  document.getElementById('videoTabExternal').classList.toggle('hidden', tab === 'r2');
  document.getElementById('tab-r2').classList.toggle('bg-white',       tab === 'r2');
  document.getElementById('tab-r2').classList.toggle('text-brand-700', tab === 'r2');
  document.getElementById('tab-r2').classList.toggle('shadow-sm',      tab === 'r2');
  document.getElementById('tab-r2').classList.toggle('text-gray-500',  tab !== 'r2');
  document.getElementById('tab-external').classList.toggle('bg-white',       tab !== 'r2');
  document.getElementById('tab-external').classList.toggle('text-brand-700', tab !== 'r2');
  document.getElementById('tab-external').classList.toggle('shadow-sm',      tab !== 'r2');
  document.getElementById('tab-external').classList.toggle('text-gray-500',  tab === 'r2');
  document.getElementById('videoStorageInput').value = tab;
}

function handleVideoDrop(e) {
  e.preventDefault();
  document.getElementById('r2DropZone').classList.remove('border-brand-500','bg-brand-50');
  const file = e.dataTransfer.files[0];
  if (file) uploadVideo(file);
}

async function uploadVideo(file) {
  if (!file) return;
  const maxMB = 500;
  if (file.size > maxMB * 1024 * 1024) { alert(`File too large. Max ${maxMB}MB.`); return; }

  const prog   = document.getElementById('videoUploadProgress');
  const bar    = document.getElementById('videoUploadBar');
  const pct    = document.getElementById('videoUploadPct');
  const status = document.getElementById('videoUploadStatus');
  const name   = document.getElementById('videoUploadName');

  prog.classList.remove('hidden');
  name.textContent = file.name;
  status.textContent = 'Uploading to Cloudflare R2…';

  const formData = new FormData();
  formData.append('file',      file);
  formData.append('folder',    'course-videos');
  formData.append('is_public', '0');  // PRIVATE!
  formData.append('_token',    _csrf);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', _uploadUrl);
  xhr.upload.onprogress = (e) => {
    if (e.lengthComputable) {
      const p = Math.round(e.loaded / e.total * 100);
      bar.style.width = p + '%';
      pct.textContent = p + '%';
    }
  };
  xhr.onload = () => {
    const res = JSON.parse(xhr.responseText);
    if (res.success) {
      bar.style.width = '100%';
      bar.classList.replace('bg-brand-500','bg-green-500');
      pct.textContent = '100%';
      status.textContent = '✅ Uploaded securely to R2!';
      status.classList.add('text-green-600','font-semibold');
      // Store R2 path
      document.getElementById('videoR2PathInput').value = res.path;
      document.getElementById('videoStorageInput').value = 'r2';
      switchVideoTab('r2');
    } else {
      status.textContent = '❌ Upload failed: ' + (res.error || 'Unknown error');
      status.classList.add('text-red-600');
    }
  };
  xhr.onerror = () => { status.textContent = '❌ Network error'; };
  xhr.send(formData);
}

// Video picker from library — select sets R2 path
function mpPickerSelect(url, path) {
  document.getElementById('videoR2PathInput').value = path || '';
  document.getElementById('videoStorageInput').value = path ? 'r2' : 'external';
  document.getElementById('videoPickerModal').classList.add('hidden');
  if (path) {
    document.getElementById('videoUploadStatus').textContent = '✅ Video selected from library';
    document.getElementById('videoUploadProgress').classList.remove('hidden');
    switchVideoTab('r2');
  }
}

// Init on load
const currentType = document.querySelector('input[name="type"]:checked')?.value ?? 'video';
onTypeChange(currentType);

// Media picker for video gallery
const _mpApiUrl = '{{ route('admin.media.list') }}';
const _mpState  = {};

function openMediaPicker(modalId, pickerId, type, folder) {
  _mpState[modalId] = { page:1, type, folder, pickerId };
  document.getElementById(modalId).classList.remove('hidden');
  loadVideoPicker(modalId);
}

async function loadVideoPicker(modalId) {
  const grid   = document.getElementById(modalId+'-grid');
  const search = document.getElementById(modalId+'-search')?.value || '';
  grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-400">Loading…</div>';

  const res  = await fetch(_mpApiUrl + '?type=video&search=' + encodeURIComponent(search) + '&folder=course-videos');
  const data = await res.json();
  grid.innerHTML = '';

  if (!data.data.length) {
    grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-400 text-sm">No videos uploaded yet. Upload using the upload area.</div>';
    return;
  }
  data.data.forEach(f => {
    const div = document.createElement('div');
    div.className = 'cursor-pointer rounded-xl border-2 border-transparent hover:border-brand-400 overflow-hidden bg-gray-800 transition';
    div.onclick   = () => mpPickerSelect(f.url, f.path);
    div.innerHTML = `
      <div class="aspect-video bg-gray-800 flex flex-col items-center justify-center gap-1">
        <span class="text-3xl">🎬</span>
        <span class="text-xs text-gray-400">${f.extension?.toUpperCase()}</span>
      </div>
      <div class="p-2 bg-white">
        <p class="text-xs font-semibold text-gray-700 truncate">${f.name}</p>
        <p class="text-xs text-gray-400">${f.size}</p>
      </div>`;
    grid.appendChild(div);
  });
}

function mpSearch(modalId) { loadVideoPicker(modalId); }

document.getElementById('videoPickerModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.classList.add('hidden');
});
</script>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
// Initialize Quill rich text editor
let quill = null;

function initQuill() {
  if (quill) return;
  const editorEl = document.getElementById('quillContent');
  if (!editorEl) return;

  quill = new Quill('#quillContent', {
    modules: {
      toolbar: '#quillToolbar',
    },
    theme: 'snow',
    placeholder: 'Write your lesson content here — use the toolbar to format text, add headings, colors, lists, links and more…',
  });

  // Load existing content
  const existingContent = document.getElementById('contentInput').value;
  if (existingContent && existingContent.trim()) {
    if (existingContent.trim().startsWith('<')) {
      quill.clipboard.dangerouslyPasteHTML(existingContent);
    } else {
      quill.setText(existingContent);
    }
  }

  // Sync to hidden input on change
  quill.on('text-change', function() {
    const html = quill.root.innerHTML;
    document.getElementById('contentInput').value = html === '<p><br></p>' ? '' : html;
  });
}

// Init when type = text
function onTypeChange(type) {
  document.getElementById('fieldVideoUrl').classList.toggle('hidden', type !== 'video');
  document.getElementById('fieldLive').classList.toggle('hidden',     type !== 'live');
  document.getElementById('fieldContent').classList.toggle('hidden',  !['text','pdf'].includes(type));
  document.getElementById('fieldQuiz').classList.toggle('hidden', type !== 'quiz');
  document.getElementById('pdfFields').classList.toggle('hidden', type !== 'pdf');
  document.getElementById('textFields').classList.toggle('hidden', type !== 'text');
  document.getElementById('pdfContentInput').disabled = type !== 'pdf';
  document.getElementById('contentInput').disabled = type !== 'text';
  if (type === 'text') {
    setTimeout(initQuill, 100); // slight delay for DOM visibility
  }
}

// Auto-init if type is already text on load
const _initType = document.querySelector('input[name="type"]:checked')?.value ?? 'video';
if (_initType === 'text') setTimeout(initQuill, 200);

// Sync content before form submit
document.querySelector('form')?.addEventListener('submit', function() {
  if (quill) {
    const html = quill.root.innerHTML;
    document.getElementById('contentInput').value = html === '<p><br></p>' ? '' : html;
  }
});

// ── PDF Upload ────────────────────────────────────────────────────────
function handlePdfDrop(e) {
  e.preventDefault();
  document.getElementById('pdfDropZone').classList.remove('border-brand-500','bg-brand-50');
  const file = e.dataTransfer.files[0];
  if (file && file.type === 'application/pdf') {
    uploadPdfFile(file);
  } else {
    alert('Please drop a PDF file only.');
  }
}

async function uploadPdfFile(file) {
  if (!file) return;
  const maxMB = parseInt('{{ \App\Models\Setting::get("max_upload_mb",500) }}') || 500;
  if (file.size > maxMB * 1024 * 1024) {
    alert(`File too large. Max ${maxMB}MB.`);
    return;
  }

  const prog   = document.getElementById('pdfUploadProgress');
  const bar    = document.getElementById('pdfUploadBar');
  const pct    = document.getElementById('pdfUploadPct');
  const status = document.getElementById('pdfUploadStatus');
  const name   = document.getElementById('pdfUploadName');

  prog.classList.remove('hidden');
  document.getElementById('pdfPreview').classList.add('hidden');
  name.textContent = file.name;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('folder', 'course-pdfs');
  formData.append('is_public', '1');
  formData.append('_token', '{{ csrf_token() }}');

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '{{ route("admin.media.upload") }}');

  xhr.upload.onprogress = (e) => {
    if (e.lengthComputable) {
      const p = Math.round(e.loaded / e.total * 100);
      bar.style.width   = p + '%';
      pct.textContent   = p + '%';
    }
  };

  xhr.onload = () => {
    try {
      const res = JSON.parse(xhr.responseText);
      if (res.success) {
        bar.style.width           = '100%';
        bar.classList.replace('bg-brand-500','bg-green-500');
        pct.textContent           = '100%';
        status.textContent        = '✅ Uploaded to R2 successfully!';
        status.className          = 'text-xs text-green-600 font-semibold mt-1';

        // Set hidden input + URL input
        document.getElementById('pdfContentInput').value = res.url || res.path;
        document.getElementById('pdfUrlInput').value     = res.url || res.path;

        // Show preview
        updatePdfPreview(res.url || res.path, file.name);

        // Auto-hide progress after 2s
        setTimeout(() => prog.classList.add('hidden'), 2000);
      } else {
        status.textContent = '❌ Upload failed: ' + (res.error || 'Unknown error');
        status.className   = 'text-xs text-red-500 mt-1';
        bar.classList.replace('bg-brand-500','bg-red-500');
      }
    } catch(e) {
      status.textContent = '❌ Server error';
      bar.classList.replace('bg-brand-500','bg-red-500');
    }
  };

  xhr.onerror = () => {
    status.textContent = '❌ Network error';
    bar.classList.replace('bg-brand-500','bg-red-500');
  };

  xhr.send(formData);
}

function updatePdfPreview(url, filename) {
  if (!url) {
    document.getElementById('pdfPreview').classList.add('hidden');
    return;
  }
  const preview = document.getElementById('pdfPreview');
  const link    = document.getElementById('pdfPreviewLink');
  const nameEl  = document.getElementById('pdfPreviewName');
  nameEl.textContent = filename || url.split('/').pop();
  link.href          = url;
  preview.classList.remove('hidden');
}

// Init PDF preview if existing content
window.addEventListener('DOMContentLoaded', () => {
  const pdfInput = document.getElementById('pdfContentInput');
  if (pdfInput && !pdfInput.disabled && pdfInput.value) {
    updatePdfPreview(pdfInput.value);
  }
});
</script>
@endpush
