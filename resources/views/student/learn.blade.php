@extends('layouts.student')
@section('title', $course->title.' — Learn')
@section('page-title', $course->title)
@section('page-sub', $enrollment ? $enrollment->progress.'% complete' : 'Enrolled')

@push('styles')
<style>
  /* Prevent right-click + text selection on video */
  #videoPlayer { pointer-events: auto; }
  #videoPlayer::-webkit-media-controls-download-button { display: none !important; }
  video { -webkit-user-select: none; user-select: none; }
  .lesson-item.active { background: #eff6ff; border-color: #2563eb; }
  .lesson-item.done .lesson-icon { color: #16a34a; }
</style>
@endpush

@section('student-content')

{{-- Certificate Banner --}}
@if($certificate)
<div class="mb-5 bg-gradient-to-r from-yellow-400 to-amber-500 text-white rounded-2xl p-4 flex items-center gap-4">
  <span class="text-3xl">🏆</span>
  <div class="flex-1">
    <div class="font-black text-base">Course Completed! Certificate Earned!</div>
    <div class="text-yellow-100 text-xs">Certificate ID: {{ $certificate->certificate_number }}</div>
  </div>
  <a href="#" class="bg-white text-yellow-700 font-black text-xs px-4 py-2 rounded-xl hover:bg-yellow-50 transition">⬇️ Download</a>
</div>
@endif

<div class="flex flex-col xl:flex-row gap-5" style="min-height: calc(100vh - 200px)">

  {{-- ── Video / Content Player ──────────────────────────────────── --}}
  <div class="flex-1 min-w-0 space-y-4">

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

      {{-- Live class --}}
      <div id="liveWrap" class="hidden p-6 bg-white rounded-2xl min-h-[300px] text-center">
        <div class="text-5xl mb-4">📡</div>
        <div id="livePlatformName" class="font-black text-xl text-gray-900 mb-2"></div>
        <div id="liveSchedule" class="text-gray-500 text-sm mb-5"></div>
        <div id="liveMeetingInfo" class="hidden mb-5 bg-gray-50 rounded-xl p-4 text-left space-y-2 text-sm"></div>
        <a id="liveJoinBtn" href="#" target="_blank"
           class="inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-black px-8 py-3 rounded-2xl transition shadow-lg text-sm">
          📡 Join Live Class
        </a>
      </div>

      {{-- Loading --}}
      <div id="playerLoading" class="aspect-video flex items-center justify-center bg-gray-900">
        <div class="text-center text-white">
          <div class="text-4xl mb-3">📚</div>
          <div class="text-sm text-gray-400">Select a lesson to start learning</div>
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
        <button id="markDoneBtn" onclick="markLessonDone()"
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
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
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
        <div class="text-xs text-gray-400 mt-0.5">
          {{ $completedIds->count() }}/{{ $course->sections->sum(fn($s)=>$s->lessons->count()) }} lessons done
        </div>
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
              @else 📖 @endif
            </span>
            <div class="flex-1 min-w-0">
              <div class="text-xs font-semibold text-gray-800 line-clamp-2 {{ $done ? 'line-through text-gray-400' : '' }}">
                {{ $lesson->title }}
              </div>
              @if($lesson->duration)
              <div class="text-xs text-gray-400 mt-0.5">{{ $lesson->duration }}m</div>
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
<script>
const CSRF    = '{{ csrf_token() }}';
const COURSE  = {{ $course->id }};
const API_BASE= '/learn/{{ $course->id }}';
let currentLessonId = {{ $currentLesson?->id ?? 'null' }};
let saveTimer;

// Load a lesson
async function loadLesson(lessonId) {
  currentLessonId = lessonId;

  // Update active state in sidebar
  document.querySelectorAll('.lesson-item').forEach(el => {
    el.classList.toggle('active', parseInt(el.dataset.lessonId) === lessonId);
  });

  // Show loading
  hideAllPlayers();
  document.getElementById('playerLoading').classList.remove('hidden');
  document.getElementById('lessonInfo').classList.add('hidden');

  try {
    const res  = await fetch(`${API_BASE}/lesson/${lessonId}`, {
      headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
    });
    const data = await res.json();
    if (!res.ok) { alert(data.error || 'Failed to load lesson.'); return; }

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

    // Render based on type
    if (data.type === 'video' && data.video_url) {
      renderVideo(data.video_url, data.progress);
    } else if (data.type === 'live') {
      renderLive(data);
    } else if (data.type === 'text' || data.type === 'quiz') {
      renderContent(data.content || '<p class="text-gray-400">No content available.</p>');
    } else if (data.type === 'pdf' && data.content) {
      window.open(data.content, '_blank');
      renderContent('<p class="text-gray-500">PDF opened in new tab.</p>');
    } else {
      renderContent('<p class="text-gray-400">Content not available.</p>');
    }
  } catch(e) {
    document.getElementById('playerLoading').classList.add('hidden');
    alert('Error loading lesson: '+e.message);
  }
}

function hideAllPlayers() {
  ['videoWrap','iframeWrap','contentWrap','liveWrap','playerLoading'].forEach(id => {
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

function renderContent(html) {
  document.getElementById('lessonContent').innerHTML = html;
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
  document.getElementById('liveJoinBtn').href = data.live_url || '#';
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

async function saveLessonProgress(percent, seconds) {
  if (!currentLessonId) return;
  try {
    const res  = await fetch(`${API_BASE}/lesson/${currentLessonId}/progress`, {
      method : 'POST',
      headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
      body   : JSON.stringify({progress:percent, watched_seconds:seconds}),
    });
    const data = await res.json();
    if (!data.ok) return;

    // Update progress bar
    document.getElementById('progressBar').style.width  = data.course_progress + '%';
    document.getElementById('progressText').textContent = data.course_progress + '%';

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
