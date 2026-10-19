@extends('layouts.student')
@section('title', $course->title.' — Learn')
@section('page-title', $course->title)
@section('page-sub', !empty($isCreatorPreview) ? 'Learner preview · saved content' : ($enrollment ? $enrollment->progress.'% complete' : 'Enrolled'))

@push('styles')
<style>
  /* Prevent right-click + text selection on video */
  #videoPlayer { pointer-events: auto; }
  #videoPlayer::-webkit-media-controls-download-button { display: none !important; }
  video { -webkit-user-select: none; user-select: none; }
  .lesson-item.active { background: #eff6ff; border-color: #2563eb; }
  .lesson-item.done .lesson-icon { color: #16a34a; }
  .lesson-slide { min-height: 420px; background: radial-gradient(circle at 88% 18%, #d9f4f2 0, transparent 32%), linear-gradient(135deg, #f8fbff, #eef4ff); }
  .lesson-slide-body { font-size: clamp(1.15rem, 2vw, 1.5rem); line-height: 1.65; color: #172b4d; }
  .lesson-slide-body > :first-child { margin-top: 0; }
  .lesson-slide-body > :last-child { margin-bottom: 0; }
  .lesson-slide-body img { max-height: 55vh; width: auto; max-width: 100%; object-fit: contain; }
</style>
@endpush

@section('student-content')

@if(!empty($isCreatorPreview))
<div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4" role="status">
  <div><strong class="text-sm text-blue-950">Preview as learner</strong><p class="mt-1 text-xs text-blue-900">This shows saved course content. Progress, assignment submissions, quiz submissions and attendance are disabled.</p></div>
  <a href="{{ $previewEditorUrl }}" class="inline-flex min-h-11 items-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">Return to editor</a>
</div>
@endif

@if($course->announcements->isNotEmpty())
<section class="mb-5 rounded-xl border border-brand-100 bg-brand-50 p-4" aria-label="Course announcements"><h2 class="text-sm font-bold text-brand-900">Announcements</h2><div class="mt-2 space-y-3">@foreach($course->announcements->take(5) as $announcement)<article><h3 class="text-sm font-semibold text-gray-900">{{ $announcement->title }}</h3><p class="whitespace-pre-wrap text-sm text-gray-700">{{ $announcement->body }}</p><p class="text-xs text-gray-500">{{ $announcement->created_at->format('d M Y') }}</p></article>@endforeach</div></section>
@endif

{{-- Certificate Banner --}}
@if($certificate)
<div class="mb-5 bg-gradient-to-r from-yellow-400 to-amber-500 text-white rounded-2xl p-4 flex items-center gap-4">
  <span class="text-3xl">🏆</span>
  <div class="flex-1">
    <div class="font-black text-base">Course Completed! Certificate Earned!</div>
    <div class="text-yellow-100 text-xs">Certificate ID: {{ $certificate->certificate_number }}</div>
  </div>
  <a href="{{ route('student.certs.show', $certificate) }}" class="bg-white text-yellow-700 font-black text-xs px-4 py-2 rounded-xl hover:bg-yellow-50 transition">View / save certificate</a>
</div>
@endif

<div class="flex flex-col xl:flex-row gap-5" style="min-height: calc(100vh - 200px)">

  {{-- ── Video / Content Player ──────────────────────────────────── --}}
  <div class="min-w-0 space-y-4 xl:flex-1">

    {{-- Player area --}}
    <div class="bg-black rounded-2xl overflow-hidden relative" id="playerBox">

      {{-- Video player --}}
      <div id="videoWrap" class="hidden">
        <div class="relative" oncontextmenu="return false">
          <video id="videoPlayer" class="w-full aspect-video" controlsList="nodownload noremoteplayback"
                 disablePictureInPicture
                 oncontextmenu="return false">
          </video>
          {{-- Anti-download overlay --}}
          <div class="absolute inset-0 pointer-events-none" style="z-index:1"></div>
        </div>
      </div>

      {{-- YouTube/Vimeo embed --}}
      <div id="iframeWrap" class="hidden">
        <div class="aspect-video">
          <iframe id="lessonIframe" class="w-full h-full" frameborder="0"
                  allow="accelerometer; autoplay; encrypted-media; gyroscope"
                  allowfullscreen></iframe>
        </div>
      </div>

      {{-- Text/Content area --}}
      <div id="contentWrap" class="hidden p-6 bg-white rounded-2xl min-h-[300px]">
        <div id="lessonContent" class="prose prose-sm max-w-none text-gray-700"></div>
      </div>

      <div id="pdfWrap" class="hidden bg-white p-4">
        <div class="flex justify-between items-center gap-3 pb-3 text-sm"><strong>📄 Course handout</strong><div class="flex gap-3"><button id="pdfOpen" type="button" onclick="openMaterial(this)" class="text-brand-600 font-bold">View full screen ↗</button><a id="pdfDownload" class="text-brand-600 font-bold">Download ↓</a></div></div>
        <div id="pdfPages" class="w-full rounded-xl border border-gray-200 bg-gray-100 p-3 overflow-auto" style="height:70vh" aria-label="Course PDF handout"></div>
      </div>

      {{-- Live class --}}
      <div id="liveWrap" class="hidden p-6 bg-white rounded-2xl min-h-[300px] text-center">
        <div class="text-5xl mb-4">📡</div>
        <div id="livePlatformName" class="font-black text-xl text-gray-900 mb-2"></div>
        <div id="liveSchedule" class="text-gray-500 text-sm mb-5"></div>
        <div id="liveMeetingInfo" class="hidden mb-5 bg-gray-50 rounded-xl p-4 text-left space-y-2 text-sm"></div>
        <a id="liveJoinBtn" href="#" @if(!empty($isCreatorPreview)) hidden @endif
           class="inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-black px-8 py-3 rounded-2xl transition shadow-lg text-sm">
          📡 Join Live Class
        </a>
      </div>

      {{-- Loading --}}
      <div id="playerLoading" class="aspect-video flex items-center justify-center bg-gray-900" role="status" aria-live="polite">
        <div class="text-center text-white">
          <div class="text-4xl mb-3">📚</div>
          <div id="playerStatus" class="text-sm text-gray-400">Select a lesson to start learning</div>
        </div>
      </div>
    </div>

    {{-- Lesson info --}}
    <div id="lessonInfo" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hidden">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h2 id="lessonTitle" class="font-black text-gray-900 text-lg mb-1"></h2>
          <div id="lessonMeta" class="text-xs text-gray-400"></div>
        </div>
        <button id="markDoneBtn" onclick="markLessonDone()" @if(!empty($isCreatorPreview)) hidden @endif
                class="flex-shrink-0 flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition">
          ✅ Mark Complete
        </button>
      </div>
      {{-- Notes --}}
      <div id="lessonNotes" class="hidden mt-4 pt-4 border-t border-gray-100">
        <div class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">📝 Lesson Notes</div>
        <div id="lessonNotesContent" class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap"></div>
      </div>
    </div>

    {{-- Progress bar --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 {{ !empty($isCreatorPreview) ? 'hidden' : '' }}">
      <div class="flex items-center justify-between mb-2 text-xs text-gray-500">
        <span>Course Progress</span>
        <span id="progressText" class="font-bold text-brand-600">{{ $enrollment?->progress ?? 0 }}%</span>
      </div>
      <div class="bg-gray-100 rounded-full h-2.5 overflow-hidden">
        <div id="progressBar" class="bg-gradient-to-r from-brand-500 to-accent-500 h-full rounded-full transition-all duration-500"
             style="width:{{ $enrollment?->progress ?? 0 }}%"></div>
      </div>
    </div>
  </div>

  {{-- ── Curriculum Sidebar ───────────────────────────────────────── --}}
  <div class="xl:w-80 flex-shrink-0">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden xl:sticky xl:top-20">
      <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
        <div class="font-black text-gray-900 text-sm">📋 Course Content</div>
        @if(!empty($isCreatorPreview))
        <div class="text-xs text-gray-400 mt-0.5">Saved course content · read-only</div>
        @else
        <div id="lessonsDoneCount" class="text-xs text-gray-400 mt-0.5">
          {{ $completedIds->count() }}/{{ $course->sections->sum(fn($s)=>$s->lessons->count()) }} lessons done
        </div>
        @endif
      </div>
      <div class="overflow-y-auto max-h-[60vh] xl:max-h-[calc(100vh-250px)]">
        @foreach($course->sections as $section)
        <div>
          <div class="px-4 py-2.5 bg-gray-50 border-b border-t border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wide">
            {{ $section->title }}
          </div>
          @foreach($section->lessons as $lesson)
          @php
            $done    = $completedIds->contains($lesson->id);
            $isCur   = $currentLesson?->id === $lesson->id;
            $prog    = $lessonProgress[$lesson->id] ?? 0;
          @endphp
          <button onclick="loadLesson({{ $lesson->id }})"
                  data-lesson-id="{{ $lesson->id }}"
                  class="lesson-item w-full text-left flex items-center gap-3 px-4 py-3 border-b border-gray-50 hover:bg-brand-50 transition {{ $isCur ? 'active' : '' }} {{ $done ? 'done' : '' }}">
            <span class="lesson-icon text-base flex-shrink-0">
              @if($done) ✅
              @elseif($lesson->type==='video') 🎬
              @elseif($lesson->type==='quiz')  📝
              @elseif($lesson->type==='pdf')   📄
              @elseif($lesson->type==='live')  📡
              @elseif($lesson->type==='scorm') <i class="fa-solid fa-box-archive" aria-label="SCORM package"></i>
              @else 📖 @endif
            </span>
            <div class="flex-1 min-w-0">
              <div class="text-xs font-semibold text-gray-800 line-clamp-2 {{ $done ? 'line-through text-gray-400' : '' }}">
                {{ $lesson->title }}
              </div>
              @if($lesson->duration)
              <div class="text-xs text-gray-400 mt-0.5">{{ (int) ceil($lesson->duration / 60) }}m</div>
              @endif
              @if($prog > 0 && !$done)
              <div class="mt-1 bg-gray-200 rounded-full h-1 overflow-hidden">
                <div class="bg-brand-400 h-full rounded-full" style="width:{{ $prog }}%"></div>
              </div>
              @endif
            </div>
          </button>
          @endforeach
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

@if($courseMaterials || collect($materialsByLesson)->flatten(1)->isNotEmpty())
<section id="studyMaterials" class="mt-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
  <h2 class="font-black text-gray-900 text-lg">📚 Study materials</h2>
  <p class="text-sm text-gray-500 mt-1 mb-4">Preview slides, PDFs, images and audio here, or download a copy to study offline.</p>
  <div class="grid gap-3 md:grid-cols-2">
    @foreach($courseMaterials as $material)
      @include('student.material-card', ['source' => 'Course material'])
    @endforeach
    @foreach($course->sections as $section)
      @foreach($section->lessons as $materialLesson)
        @foreach($materialsByLesson[$materialLesson->id] ?? [] as $material)
          @continue(collect($courseMaterials)->contains(fn($courseMaterial) => $courseMaterial['title'] === $material['title'] && $courseMaterial['kind'] === $material['kind']))
          @include('student.material-card', ['source' => $materialLesson->title])
        @endforeach
      @endforeach
    @endforeach
  </div>
</section>
@endif

<dialog id="materialViewer" class="w-[96vw] max-w-6xl max-h-[96vh] rounded-2xl p-0 shadow-2xl backdrop:bg-black/70" aria-label="Material preview">
  <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-3 bg-white">
    <h2 id="materialViewerTitle" class="font-black text-gray-900 truncate flex-1"></h2>
    <a id="materialViewerOpen" class="text-sm font-bold text-brand-700">Download ↓</a>
    <button type="button" onclick="document.getElementById('materialViewer').close()" class="text-2xl text-gray-600 px-2" aria-label="Close preview">×</button>
  </div>
  <div id="materialViewerBody" class="bg-gray-100 overflow-auto" style="height:min(82vh,900px)"></div>
</dialog>

{{-- Certificate modal --}}
<div id="certModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-8 text-center">
    <div class="text-6xl mb-4">🏆</div>
    <h2 class="text-2xl font-black text-gray-900 mb-2">Congratulations!</h2>
    <p class="text-gray-500 text-sm mb-1">You've completed the course!</p>
    <p class="text-brand-600 font-bold mb-5">{{ $course->title }}</p>
    <div id="certNumber" class="text-xs font-mono text-gray-400 bg-gray-100 rounded-lg px-3 py-2 mb-5"></div>
    <div class="flex flex-col gap-3">
      <a href="{{ route('student.certs') }}"
         class="block py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-black rounded-2xl transition">
        📜 View My Certificate
      </a>
      <button onclick="document.getElementById('certModal').classList.add('hidden')"
              class="py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl transition text-sm">
        Continue Exploring
      </button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
<script>
if (window.pdfjsLib) pdfjsLib.GlobalWorkerOptions.workerSrc = '{{ asset('vendor/pdfjs/pdf.worker.min.js') }}';
const pdfRenderTokens = new WeakMap();
async function renderPdfPages(url, container) {
  const token = Symbol();
  pdfRenderTokens.set(container, token);
  container.textContent = 'Loading PDF preview…';
  try {
    if (!window.pdfjsLib) throw new Error('PDF viewer unavailable');
    const pdf = await pdfjsLib.getDocument(url).promise;
    if (pdfRenderTokens.get(container) !== token) return;
    container.replaceChildren();
    for (let number = 1; number <= pdf.numPages; number++) {
      if (pdfRenderTokens.get(container) !== token) return;
      const page = await pdf.getPage(number);
      const base = page.getViewport({scale: 1});
      const viewport = page.getViewport({scale: Math.min(2, Math.max(0.2, (container.clientWidth - 28) / base.width))});
      const scale = Math.min(window.devicePixelRatio || 1, 2);
      const canvas = document.createElement('canvas');
      canvas.width = Math.floor(viewport.width * scale);
      canvas.height = Math.floor(viewport.height * scale);
      canvas.style.width = viewport.width + 'px';
      canvas.style.height = viewport.height + 'px';
      canvas.className = 'block mx-auto mb-3 bg-white shadow-sm max-w-full';
      container.appendChild(canvas);
      await page.render({canvasContext: canvas.getContext('2d'), viewport, transform: scale === 1 ? null : [scale, 0, 0, scale, 0, 0]}).promise;
    }
  } catch (error) {
    if (pdfRenderTokens.get(container) === token) container.textContent = 'Preview unavailable here. Please download this file instead.';
  }
}
function openMaterial(button) {
  const viewer = document.getElementById('materialViewer');
  const body = document.getElementById('materialViewerBody');
  const {title, type, url, downloadUrl} = button.dataset;
  document.getElementById('materialViewerTitle').textContent = title;
  const download = document.getElementById('materialViewerOpen');
  download.classList.toggle('hidden', !downloadUrl);
  if (downloadUrl) download.href = downloadUrl;
  body.replaceChildren();
  viewer.showModal();
  if (type === 'pdf' || type === 'presentation') {
    renderPdfPages(url, body);
    return;
  }
  let content;
  if (type === 'image') {
    content = document.createElement('img');
    content.alt = title;
    content.className = 'w-full h-full object-contain';
  } else if (type === 'audio') {
    content = document.createElement('audio');
    content.controls = true;
    content.className = 'w-full p-6';
  } else {
    content = document.createElement('iframe');
    content.title = title;
    content.className = 'w-full h-full border-0';
  }
  content.src = url;
  body.appendChild(content);
}
document.getElementById('materialViewer').addEventListener('close', () => {
  const body = document.getElementById('materialViewerBody');
  pdfRenderTokens.delete(body);
  body.replaceChildren();
});
document.getElementById('materialViewer').addEventListener('click', event => {
  if (event.target.id === 'materialViewer') event.target.close();
});
const CSRF    = '{{ csrf_token() }}';
const COURSE  = {{ $course->id }};
const CREATOR_PREVIEW = @json(!empty($isCreatorPreview));
const API_BASE= CREATOR_PREVIEW ? @json(route('instructor.courses.preview', $course)) : '/learn/{{ $course->id }}';
let currentLessonId = {{ $currentLesson?->id ?? 'null' }};
let saveTimer;
let activeLessonRequest;
let scormState = {};
let scormCompleted = false;

function scormSetValue(key, value) {
  scormState[key] = String(value);
  scormCompleted = ['completed','passed'].includes(String(scormState['cmi.core.lesson_status'] || '').toLowerCase())
    || String(scormState['cmi.completion_status'] || '').toLowerCase() === 'completed';
  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => saveLessonProgress(scormCompleted ? 100 : 0, 0, JSON.stringify(scormState)), 500);
  return 'true';
}
window.API = {
  LMSInitialize: () => 'true', LMSFinish: () => { saveLessonProgress(scormCompleted ? 100 : 0, 0, JSON.stringify(scormState)); return 'true'; },
  LMSGetValue: key => scormState[key] || '', LMSSetValue: scormSetValue,
  LMSCommit: () => { saveLessonProgress(scormCompleted ? 100 : 0, 0, JSON.stringify(scormState)); return 'true'; },
  LMSGetLastError: () => '0', LMSGetErrorString: () => 'No error', LMSGetDiagnostic: () => '',
};
window.API_1484_11 = {
  Initialize: () => 'true', Terminate: () => { saveLessonProgress(scormCompleted ? 100 : 0, 0, JSON.stringify(scormState)); return 'true'; },
  GetValue: key => scormState[key] || '', SetValue: scormSetValue,
  Commit: () => { saveLessonProgress(scormCompleted ? 100 : 0, 0, JSON.stringify(scormState)); return 'true'; },
  GetLastError: () => '0', GetErrorString: () => 'No error', GetDiagnostic: () => '',
};

// Load a lesson
async function loadLesson(lessonId) {
  activeLessonRequest?.abort();
  activeLessonRequest = new AbortController();
  clearTimeout(saveTimer);
  document.getElementById('videoPlayer').pause();
  currentLessonId = lessonId;

  // Update active state in sidebar
  document.querySelectorAll('.lesson-item').forEach(el => {
    el.classList.toggle('active', parseInt(el.dataset.lessonId) === lessonId);
  });

  // Show loading
  hideAllPlayers();
  document.getElementById('playerStatus').textContent = 'Loading lesson…';
  document.getElementById('playerLoading').classList.remove('hidden');
  document.getElementById('lessonInfo').classList.add('hidden');

  try {
    const res  = await fetch(`${API_BASE}/lesson/${lessonId}`, {
      headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
      signal: activeLessonRequest.signal,
    });
    const data = await res.json();
    if (!res.ok) { document.getElementById('playerStatus').textContent = data.error || 'Could not load this lesson. Select it to retry.'; return; }

    document.getElementById('playerLoading').classList.add('hidden');
    document.getElementById('lessonInfo').classList.remove('hidden');
    document.getElementById('lessonTitle').textContent = data.title;
    document.getElementById('lessonMeta').textContent  =
      (data.type ? data.type.charAt(0).toUpperCase()+data.type.slice(1) : '') +
      (data.duration ? ` · ${data.duration} min` : '');

    if (data.notes) {
      document.getElementById('lessonNotes').classList.remove('hidden');
      document.getElementById('lessonNotesContent').textContent = data.notes;
    } else {
      document.getElementById('lessonNotes').classList.add('hidden');
    }

    // Mark done button
    const doneBtn = document.getElementById('markDoneBtn');
    if (data.completed) {
      doneBtn.textContent = '✅ Completed';
      doneBtn.disabled    = true;
      doneBtn.className   = 'flex-shrink-0 flex items-center gap-2 bg-green-100 text-green-700 font-bold px-5 py-2.5 rounded-xl text-sm cursor-not-allowed';
    } else {
      doneBtn.innerHTML  = '✅ Mark Complete';
      doneBtn.disabled   = false;
      doneBtn.className  = 'flex-shrink-0 flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition';
    }
    doneBtn.classList.toggle('hidden', CREATOR_PREVIEW || data.type === 'quiz' || data.type === 'assignment' || data.type === 'scorm');

    // Render based on type
    if (data.type === 'scorm' && data.scorm_launch_url) {
      scormState = {};
      try { scormState = data.scorm_data ? JSON.parse(data.scorm_data) : {}; } catch (_) {}
      scormCompleted = !!data.completed;
      const frame = document.getElementById('lessonIframe');
      frame.title = data.title;
      frame.src = data.scorm_launch_url;
      document.getElementById('iframeWrap').classList.remove('hidden');
    } else if (data.type === 'video' && data.video_url) {
      renderVideo(data.video_url, data.progress);
    } else if (data.type === 'video') {
      renderContent('<p class="text-gray-500">No video has been saved for this lesson yet.</p>', data.title);
    } else if (data.type === 'live') {
      renderLive(data);
    } else if (data.quiz) {
      renderQuiz(data.quiz, data.completed);
    } else if (data.type === 'assignment' && data.assignment) {
      renderAssignment(data.assignment);
    } else if (data.type === 'text') {
      renderContent(data.block_document ? renderBlockDocument(data.block_document) : (data.content || '<p class="text-gray-400">No content available.</p>'), data.title);
    } else if (data.type === 'pdf' && data.content) {
      renderPdf(data.content);
    } else {
      renderContent('<p class="text-gray-400">Content not available.</p>');
    }
  } catch(e) {
    if (e.name !== 'AbortError') document.getElementById('playerStatus').textContent = 'Could not load this lesson. Select it to retry.';
  }
}

function hideAllPlayers() {
  pdfRenderTokens.delete(document.getElementById('pdfPages'));
  ['videoWrap','iframeWrap','contentWrap','pdfWrap','liveWrap','playerLoading'].forEach(id => {
    document.getElementById(id).classList.add('hidden');
  });
}

function renderVideo(url, savedProgress) {
  const isExternal = url.includes('youtube.com') || url.includes('youtu.be') || url.includes('vimeo.com');
  if (isExternal) {
    let embedUrl = url;
    if (url.includes('youtu.be')) {
      const id = url.split('/').pop().split('?')[0];
      embedUrl = `https://www.youtube.com/embed/${id}?rel=0`;
    } else if (url.includes('youtube.com/watch')) {
      const id = new URL(url).searchParams.get('v');
      embedUrl = `https://www.youtube.com/embed/${id}?rel=0`;
    } else if (url.includes('vimeo.com')) {
      const id = url.split('/').pop();
      embedUrl = `https://player.vimeo.com/video/${id}`;
    }
    document.getElementById('lessonIframe').src = embedUrl;
    document.getElementById('iframeWrap').classList.remove('hidden');
  } else {
    const video = document.getElementById('videoPlayer');
    video.src   = url;
    // Restore progress
    if (savedProgress > 0 && video.duration) {
      video.currentTime = (savedProgress / 100) * video.duration;
    }
    video.onloadedmetadata = () => {
      if (savedProgress > 0) video.currentTime = (savedProgress/100)*video.duration;
    };
    video.ontimeupdate = () => throttleSaveProgress(video);
    document.getElementById('videoWrap').classList.remove('hidden');
  }
}

function renderBlockDocument(document) {
  const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
  const safeUrl = value => {
    try { const url = new URL(value, location.origin); return ['http:', 'https:'].includes(url.protocol) ? escape(url.href) : ''; }
    catch { return ''; }
  };
  return (document?.blocks || []).map(block => {
    const data = block.data || {};
    if (block.type === 'header') return '<h' + Math.min(Math.max(data.level || 2, 1), 6) + '>' + escape(data.text) + '</h' + Math.min(Math.max(data.level || 2, 1), 6) + '>';
    if (block.type === 'paragraph') return '<p>' + escape(data.text) + '</p>';
    if (block.type === 'list') return '<ul>' + (data.items || []).map(item => '<li>' + escape(typeof item === 'string' ? item : item.content) + '</li>').join('') + '</ul>';
    if (block.type === 'table') return '<table><tbody>' + (data.content || []).map(row => '<tr>' + row.map(cell => '<td>' + escape(cell) + '</td>').join('') + '</tr>').join('') + '</tbody></table>';
    if (block.type === 'image') { const url = safeUrl(data.url); return url ? '<img src="' + url + '" alt="' + escape(data.caption || '') + '">' : ''; }
    if (block.type === 'embed') {
      const url = safeUrl(data.embed || data.source);
      if (!url) return '';
      const host = new URL(data.embed || data.source, location.origin).hostname.toLowerCase();
      if (!['youtube.com','www.youtube.com','youtu.be','player.vimeo.com','vimeo.com','codepen.io','github.com','codesandbox.io'].some(allowed => host === allowed || host.endsWith('.' + allowed))) return '';
      return '<iframe src="' + url + '" title="Embedded lesson content" loading="lazy" allowfullscreen></iframe>';
    }
    if (block.type === 'upload') {
      const url = safeUrl(data.file_url);
      if (!url) return '';
      if (data.file_type === 'video') return '<video controls src="' + url + '"></video>';
      if (data.file_type === 'audio') return '<audio controls src="' + url + '"></audio>';
      if (data.file_type === 'pdf') return '<iframe src="' + url + '" title="' + escape(data.file_name || 'PDF document') + '"></iframe>';
      return '<img src="' + url + '" alt="' + escape(data.file_name || '') + '">';
    }
    if (block.type === 'codeBox') return '<pre class="my-4 overflow-x-auto rounded-lg bg-slate-900 p-4 text-sm text-slate-100"><code>' + escape(data.code || '') + '</code></pre>';
    if (['quiz','assignment','program'].includes(block.type)) return '<div class="rounded-xl border border-blue-100 bg-blue-50 p-4 my-4"><strong>' + escape(block.type.replace('_', ' ')) + '</strong><p>' + escape(data.text) + '</p></div>';
    if (block.type === 'instructorNote') return '';
    return '';
  }).join('') || '<p class="text-gray-400">No content available.</p>';
}

function renderContent(html, title = 'Lesson') {
  const source = document.createElement('div');
  source.innerHTML = html;
  const slides = [...source.childNodes].filter(node => node.textContent.trim() || (node.nodeType === 1 && (node.matches('img, video, iframe, audio, svg, canvas') || node.querySelector('img, video, iframe, audio, svg, canvas'))));
  if (!slides.length) slides.push(document.createTextNode('No content available.'));
  const container = document.getElementById('lessonContent');
  container.innerHTML = '<div class="lesson-slide rounded-2xl border border-blue-100 overflow-hidden flex flex-col shadow-sm"><div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between gap-4"><span class="text-xs font-black tracking-widest uppercase text-teal-300">Skillspot · Lesson</span><span id="slideCount" class="text-xs font-bold text-slate-300"></span></div><div class="flex-1 px-7 py-9 sm:px-12 sm:py-12"><h2 id="slideTitle" class="text-2xl sm:text-3xl font-black text-slate-900 mb-8"></h2><div id="slideBody" class="lesson-slide-body"></div></div><div class="flex items-center justify-between gap-4 border-t border-blue-100 px-6 py-4 bg-white"><button id="slidePrevious" type="button" class="font-bold text-brand-700 disabled:text-gray-400">← Previous</button><span id="slideDots" class="text-xs font-semibold text-gray-500" aria-live="polite"></span><button id="slideNext" type="button" class="font-bold text-brand-700 disabled:text-gray-400">Next →</button></div></div>';
  container.querySelector('#slideTitle').textContent = title;
  let index = 0;
  const showSlide = () => {
    const body = container.querySelector('#slideBody');
    body.replaceChildren(slides[index]);
    container.querySelector('#slideCount').textContent = `${index + 1} / ${slides.length}`;
    container.querySelector('#slideDots').textContent = `Slide ${index + 1} of ${slides.length}`;
    container.querySelector('#slidePrevious').disabled = index === 0;
    container.querySelector('#slideNext').disabled = index === slides.length - 1;
  };
  container.querySelector('#slidePrevious').addEventListener('click', () => { index--; showSlide(); });
  container.querySelector('#slideNext').addEventListener('click', () => { index++; showSlide(); });
  showSlide();
  document.getElementById('contentWrap').classList.remove('hidden');
}

function renderPdf(url) {
  try {
    const parsed = new URL(url, location.origin);
    if (!['http:', 'https:'].includes(parsed.protocol)) throw new Error('Unsupported PDF URL');
    document.getElementById('pdfWrap').classList.remove('hidden');
    renderPdfPages(parsed.href, document.getElementById('pdfPages'));
    const localMedia = parsed.origin === location.origin && /^\/media\/file\/[a-f0-9-]+$/.test(parsed.pathname);
    const open = document.getElementById('pdfOpen');
    open.dataset.title = document.getElementById('lessonTitle').textContent || 'Course handout';
    open.dataset.type = 'pdf';
    open.dataset.url = parsed.href;
    open.dataset.downloadUrl = localMedia ? parsed.pathname + '/download' : '';
    document.getElementById('pdfDownload').classList.toggle('hidden', !localMedia);
    if (localMedia) document.getElementById('pdfDownload').href = parsed.pathname + '/download';
  } catch (e) { renderContent('<p>PDF link is unavailable.</p>'); }
}

function updateProgressDisplay(progress) {
  document.getElementById('progressBar').style.width = progress + '%';
  document.getElementById('progressText').textContent = progress + '%';
  document.getElementById('pageSubtitle').textContent = progress + '% complete';
  document.getElementById('lessonsDoneCount').textContent =
    document.querySelectorAll('.lesson-item.done').length + '/' + document.querySelectorAll('.lesson-item').length + ' lessons done';
}

function renderAssignment(assignment) {
  const container = document.getElementById('lessonContent');
  container.replaceChildren();
  const title = document.createElement('h2'); title.textContent = 'Assignment';
  const instructions = document.createElement('p'); instructions.className = 'whitespace-pre-wrap'; instructions.textContent = assignment.instructions;
  container.append(title, instructions);
  if (assignment.due_at) {
    const due = document.createElement('p'); due.className = 'text-sm text-gray-600';
    due.textContent = 'Due ' + new Date(assignment.due_at).toLocaleString(); container.append(due);
  }
  if (assignment.submission) {
    const status = document.createElement('p'); status.className = 'rounded-lg bg-blue-50 p-3 text-sm text-blue-900';
    status.textContent = assignment.submission.status === 'graded'
      ? `Grade: ${assignment.submission.score}% · ${assignment.submission.feedback || 'No feedback yet'}`
      : 'Submitted privately · waiting for grading';
    container.append(status);
    if (assignment.submission.file_url) {
      const file = document.createElement('a'); file.href = assignment.submission.file_url;
      file.className = 'text-sm font-semibold text-brand-700 underline';
      file.textContent = 'Download your submission: ' + assignment.submission.file_name; container.append(file);
    }
  }
  const passed = assignment.submission?.status === 'graded' && assignment.submission.score >= assignment.pass_score;
  const closed = assignment.due_at && new Date(assignment.due_at) < new Date();
  if (CREATOR_PREVIEW || passed || closed) {
    const notice = document.createElement('p'); notice.className = 'mt-3 text-sm text-gray-600';
    notice.textContent = CREATOR_PREVIEW ? 'Preview only — submissions are disabled.' : (passed ? 'Assignment passed.' : 'Submissions are closed.');
    container.append(notice);
  } else {
    const form = document.createElement('form'); form.className = 'mt-4 space-y-3'; form.dataset.ajax = '';
    const answer = document.createElement('textarea'); answer.name = 'answer'; answer.rows = 6;
    answer.className = 'w-full rounded-xl border-gray-200'; answer.placeholder = 'Write your answer';
    answer.value = assignment.submission?.answer || '';
    const file = document.createElement('input'); file.type = 'file'; file.name = 'file';
    file.accept = '.pdf,.doc,.docx,.txt,.png,.jpg,.jpeg'; file.className = 'block w-full text-sm';
    const button = document.createElement('button'); button.type = 'submit';
    button.className = 'rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white'; button.textContent = 'Submit assignment';
    const feedback = document.createElement('p'); feedback.setAttribute('role', 'status'); feedback.className = 'text-sm';
    form.append(answer, file, button, feedback);
    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (!answer.value.trim() && !file.files.length) { feedback.textContent = 'Write an answer or attach a file.'; return; }
      button.disabled = true; button.textContent = 'Submitting…';
      try {
        const response = await fetch(`/learn/{{ $course->id }}/assignment/${assignment.id}`, {
          method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: new FormData(form),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Could not submit assignment.');
        feedback.className = 'text-sm text-green-700'; feedback.textContent = data.message;
        button.textContent = 'Submitted';
      } catch (error) { feedback.className = 'text-sm text-red-700'; feedback.textContent = error.message; button.disabled = false; button.textContent = 'Submit assignment'; }
    });
    container.append(form);
  }
  document.getElementById('contentWrap').classList.remove('hidden');
}

function renderQuiz(quiz, completed) {
  const container = document.getElementById('lessonContent');
  container.replaceChildren();
  const title = document.createElement('h2');
  title.textContent = quiz.title;
  container.appendChild(title);
  const subtitle = document.createElement('p');
  subtitle.textContent = `Answer all ${quiz.questions.length} questions. Pass score: ${quiz.pass_score}%.`;
  container.appendChild(subtitle);
  if (completed || quiz.pending) {
    const passed = document.createElement('p');
    passed.className = quiz.pending ? 'rounded-xl bg-blue-50 text-blue-900 p-4 font-bold' : 'rounded-xl bg-green-50 text-green-700 p-4 font-bold';
    passed.textContent = quiz.pending ? 'Open answers submitted — waiting for teacher grading.' : '✅ You passed this quiz.';
    container.appendChild(passed);
  } else {
    const form = document.createElement('form');
    form.dataset.ajax = '';
    form.className = 'space-y-5';
    quiz.questions.forEach((question, index) => {
      const fieldset = document.createElement('fieldset');
      fieldset.className = 'rounded-xl border border-gray-200 p-4';
      fieldset.disabled = CREATOR_PREVIEW;
      const legend = document.createElement('legend');
      legend.className = 'font-bold';
      legend.textContent = `${index + 1}. ${question.question}`;
      fieldset.appendChild(legend);
      if (question.type === 'open') {
        const input = document.createElement('textarea'); input.name = `q${question.id}`;
        input.required = true; input.rows = 5; input.maxLength = 10000;
        input.className = 'mt-2 w-full rounded-xl border-gray-200';
        fieldset.appendChild(input);
      } else if (question.type === 'user_input') {
        const input = document.createElement('input'); input.name = `q${question.id}`;
        input.required = true; input.maxLength = 2000;
        input.className = 'mt-2 w-full rounded-xl border-gray-200';
        fieldset.appendChild(input);
      } else {
        (question.options || []).forEach((option, choice) => {
          const label = document.createElement('label');
          label.className = 'flex items-center gap-3 p-2 rounded-lg hover:bg-brand-50 cursor-pointer';
          const input = document.createElement('input');
          input.type = question.type === 'multiple' ? 'checkbox' : 'radio';
          input.name = `q${question.id}`; input.value = choice;
          input.required = question.type !== 'multiple';
          label.append(input, document.createTextNode(option));
          fieldset.appendChild(label);
        });
      }
      form.appendChild(fieldset);
    });
    const button = document.createElement('button');
    button.type = 'submit'; button.className = 'rounded-xl bg-brand-600 text-white font-bold px-6 py-3';
    button.textContent = 'Submit quiz';
    const result = document.createElement('p');
    result.className = 'font-bold';
    if (CREATOR_PREVIEW) {
      const notice = document.createElement('p');
      notice.className = 'rounded-lg bg-blue-50 p-3 text-sm text-blue-900';
      notice.textContent = 'Preview only — answers cannot be submitted.';
      form.appendChild(notice);
    } else form.append(button, result);
    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (CREATOR_PREVIEW) return;
      button.disabled = true;
      const fields = new FormData(form);
      const answers = Object.fromEntries(quiz.questions.map(q => [q.id, ['open', 'user_input'].includes(q.type)
        ? fields.get(`q${q.id}`) : (q.type === 'multiple'
          ? fields.getAll(`q${q.id}`).map(Number) : Number(fields.get(`q${q.id}`)))]));
      try {
        const response = await fetch(`${API_BASE}/lesson/${currentLessonId}/quiz`, {
          method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
          body: JSON.stringify({answers}),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Could not submit quiz.');
        result.textContent = data.pending ? 'Submitted — open answers are waiting for manual grading.' : (data.passed ? `✅ Passed — ${data.score}%.` : `Try again — ${data.score}%. You need ${data.pass_score}%.`);
        result.className = `font-bold ${data.passed ? 'text-green-700' : 'text-amber-700'}`;
        if (data.passed) {
          const item = document.querySelector(`.lesson-item[data-lesson-id="${currentLessonId}"]`);
          if (item) { item.classList.add('done'); item.querySelector('.lesson-icon').textContent = '✅'; }
          updateProgressDisplay(data.course_progress);
          if (data.certificate) { document.getElementById('certNumber').textContent = 'Certificate ID: ' + data.certificate; document.getElementById('certModal').classList.remove('hidden'); }
        } else if (!data.pending) button.disabled = false;
      } catch (error) { result.textContent = error.message; button.disabled = false; }
    });
    container.appendChild(form);
  }
  document.getElementById('contentWrap').classList.remove('hidden');
}

function renderLive(data) {
  const names = {google_meet:'Google Meet',zoom:'Zoom',teams:'Microsoft Teams',other:'Live Session'};
  document.getElementById('livePlatformName').textContent = names[data.live_platform] || 'Live Session';

  if (data.live_scheduled_at) {
    const d = new Date(data.live_scheduled_at);
    document.getElementById('liveSchedule').textContent =
      d.toLocaleDateString('en-IN',{weekday:'long',day:'numeric',month:'long',year:'numeric'}) +
      ' at ' + d.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'});
  }

  const info = document.getElementById('liveMeetingInfo');
  if (data.live_meeting_id || data.live_password) {
    info.innerHTML = '';
    if (data.live_meeting_id) info.innerHTML += `<div><span class="text-gray-400">Meeting ID:</span> <strong class="font-mono">${data.live_meeting_id}</strong></div>`;
    if (data.live_password)   info.innerHTML += `<div><span class="text-gray-400">Password:</span> <strong class="font-mono">${data.live_password}</strong></div>`;
    info.classList.remove('hidden');
  }
  document.getElementById('liveJoinBtn').href = data.live_url || '';
  document.getElementById('liveJoinBtn').classList.toggle('hidden', CREATOR_PREVIEW || !data.live_url);
  document.getElementById('liveWrap').classList.remove('hidden');
}

// Save progress with throttle
let lastSavedPct = 0;
function throttleSaveProgress(video) {
  if (!video.duration) return;
  const pct = Math.round((video.currentTime / video.duration) * 100);
  if (Math.abs(pct - lastSavedPct) < 5) return; // save every 5%
  lastSavedPct = pct;
  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => saveLessonProgress(pct, Math.round(video.currentTime)), 1000);
}

async function markLessonDone() {
  await saveLessonProgress(100, 0);
}

async function saveLessonProgress(percent, seconds, scormData = null) {
  if (CREATOR_PREVIEW || !currentLessonId) return;
  try {
    const res  = await fetch(`${API_BASE}/lesson/${currentLessonId}/progress`, {
      method : 'POST',
      headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
      body   : JSON.stringify({progress:percent, watched_seconds:seconds, scorm_data:scormData}),
    });
    const data = await res.json();
    if (!data.ok) return;

    // Mark lesson done in sidebar
    if (data.lesson_done) {
      const btn = document.querySelector(`.lesson-item[data-lesson-id="${currentLessonId}"]`);
      if (btn) {
        btn.classList.add('done');
        btn.querySelector('.lesson-icon').textContent = '✅';
      }
      // Update mark done button
      const doneBtn = document.getElementById('markDoneBtn');
      doneBtn.textContent = '✅ Completed';
      doneBtn.disabled    = true;
    }
    updateProgressDisplay(data.course_progress);

    // Certificate earned!
    if (data.course_completed && data.certificate) {
      document.getElementById('certNumber').textContent = 'Certificate ID: '+data.certificate.number;
      document.getElementById('certModal').classList.remove('hidden');
      // Also update banner
      const banner = document.createElement('div');
      banner.className = 'mb-5 bg-gradient-to-r from-yellow-400 to-amber-500 text-white rounded-2xl p-4 flex items-center gap-4';
      banner.innerHTML = '<span class="text-3xl">🏆</span><div><div class="font-black">Course Complete! Certificate Earned!</div><div class="text-yellow-100 text-xs">'+data.certificate.number+'</div></div>';
      document.querySelector('.flex.flex-col').prepend(banner);
    }
  } catch(e) { /* silent */ }
}

// Auto-load first/current lesson
window.addEventListener('DOMContentLoaded', () => {
  if (currentLessonId) loadLesson(currentLessonId);
});

// Keyboard shortcut blocker (prevent save video)
document.addEventListener('keydown', e => {
  if ((e.ctrlKey||e.metaKey) && (e.key==='s'||e.key==='u')) e.preventDefault();
});
</script>
@endpush
