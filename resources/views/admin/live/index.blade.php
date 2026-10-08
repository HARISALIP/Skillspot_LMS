@extends('layouts.admin')
@section('title','Live Classes — Skillspot.in Admin')
@section('page-title','Live Classes')
@section('page-sub','Schedule and manage live sessions across all courses')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ✅ {{ session('success') }} <button onclick="this.parentElement.remove()" class="ml-auto text-xl">×</button>
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ❌ {{ session('error') }} <button onclick="this.parentElement.remove()" class="ml-auto text-xl">×</button>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- ── Schedule New Live Class ──────────────────────────────────── --}}
  <div class="lg:col-span-1">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden sticky top-6">
      <div class="bg-gradient-to-r from-brand-600 to-accent-600 px-5 py-4">
        <h3 class="font-black text-white text-sm">📡 Schedule Live Class</h3>
        <p class="text-blue-200 text-xs mt-0.5">Add a live session to any course section</p>
      </div>
      <form method="POST" action="{{ route('admin.live.store') }}" class="p-5 space-y-4">
        @csrf

        {{-- Course --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Course <span class="text-red-400">*</span></label>
          <select name="course_id" id="courseSelect" required onchange="loadSections(this.value)"
                  class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            <option value="">Select course…</option>
            @foreach($courses as $c)
            <option value="{{ $c->id }}">{{ $c->title }}</option>
            @endforeach
          </select>
        </div>

        {{-- Section --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Section <span class="text-red-400">*</span></label>
          <select name="section_id" id="sectionSelect" required
                  class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            <option value="">Select course first…</option>
          </select>
        </div>

        {{-- Title --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Session Title <span class="text-red-400">*</span></label>
          <input type="text" name="title" required placeholder="e.g. Live Q&A — Week 3"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
        </div>

        {{-- Platform --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Platform <span class="text-red-400">*</span></label>
          <div class="grid grid-cols-2 gap-2">
            @foreach(['google_meet'=>['🟢','Google Meet'],'zoom'=>['🔵','Zoom'],'teams'=>['🟣','Teams'],'other'=>['⚪','Other']] as $val=>[$dot,$lbl])
            <label class="cursor-pointer">
              <input type="radio" name="live_platform" value="{{ $val }}" class="sr-only peer"
                     {{ $val==='google_meet'?'checked':'' }}>
              <div class="border-2 border-gray-200 rounded-xl p-2.5 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 transition flex items-center gap-2">
                <span>{{ $dot }}</span>
                <span class="text-xs font-bold text-gray-700">{{ $lbl }}</span>
              </div>
            </label>
            @endforeach
          </div>
        </div>

        {{-- Meeting link --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Meeting Link <span class="text-red-400">*</span></label>
          <input type="text" name="live_url" required placeholder="https://meet.google.com/xxx-xxxx-xxx"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition font-mono">
        </div>

        {{-- Date & Time --}}
        <div class="grid grid-cols-1 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Date & Time <span class="text-red-400">*</span></label>
            <input type="datetime-local" name="live_scheduled_at" required
                   min="{{ now()->format('Y-m-d\TH:i') }}"
                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Duration (minutes)</label>
            <select name="live_duration_min"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
              @foreach([30,45,60,90,120,180] as $d)
              <option value="{{ $d }}" {{ $d===60?'selected':'' }}>{{ $d }} minutes</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Meeting ID + Password (optional) --}}
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Meeting ID</label>
            <input type="text" name="live_meeting_id" placeholder="Optional"
                   class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Password</label>
            <input type="text" name="live_password" placeholder="Optional"
                   class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
        </div>

        {{-- Description --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Description</label>
          <textarea name="description" rows="2" placeholder="Brief description for students…"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition resize-none"></textarea>
        </div>

        {{-- Free Preview --}}
        <label class="flex items-center gap-3 cursor-pointer p-3 bg-green-50 border border-green-200 rounded-xl hover:bg-green-100 transition">
          <input type="checkbox" name="is_preview" value="1"
                 class="w-4 h-4 rounded text-green-600 focus:ring-green-500">
          <div>
            <div class="text-xs font-bold text-green-800">Free Preview</div>
            <div class="text-xs text-green-600">Non-enrolled students can join</div>
          </div>
        </label>

        <button type="submit"
                class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-700 hover:to-accent-700 text-white font-black py-3 rounded-2xl transition shadow-lg text-sm">
          📡 Schedule Live Class
        </button>
      </form>
    </div>
  </div>

  {{-- ── All Live Classes ─────────────────────────────────────────── --}}
  <div class="lg:col-span-2 space-y-5">

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
      @foreach([
        ['📡','Upcoming', $stats['upcoming'], 'bg-blue-50','text-blue-700'],
        ['🔴','Live Now',  $stats['live_now'], 'bg-red-50','text-red-700'],
        ['✅','Past',      $stats['past'],     'bg-gray-50','text-gray-700'],
      ] as [$icon,$label,$val,$bg,$text])
      <div class="{{ $bg }} rounded-2xl p-4 border border-transparent text-center">
        <div class="text-2xl mb-1">{{ $icon }}</div>
        <div class="text-2xl font-black {{ $text }}">{{ $val }}</div>
        <div class="text-xs font-semibold text-gray-500">{{ $label }}</div>
      </div>
      @endforeach
    </div>

    {{-- Upcoming --}}
    @if($upcoming->count())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100 bg-blue-50">
        <span class="text-lg">📡</span>
        <h3 class="font-black text-gray-900 text-sm">Upcoming Sessions</h3>
        <span class="ml-auto text-xs bg-brand-600 text-white font-bold px-2.5 py-1 rounded-full">{{ $upcoming->count() }}</span>
      </div>
      <div class="divide-y divide-gray-50">
        @foreach($upcoming as $lesson)
        @php
          $sched   = $lesson->live_scheduled_at;
          $endTime = $sched->copy()->addMinutes($lesson->live_duration_min ?? 60);
          $isLive  = now()->between($sched, $endTime);
          $icons   = ['google_meet'=>'🟢','zoom'=>'🔵','teams'=>'🟣','other'=>'⚪'];
          $course  = $lesson->section->course;
        @endphp
        <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 transition">
          <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
            {{ $icons[$lesson->live_platform ?? 'other'] ?? '📹' }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-gray-900 text-sm flex items-center gap-2 flex-wrap">
              {{ $lesson->title }}
              @if($isLive)
              <span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full font-bold animate-pulse">🔴 LIVE</span>
              @endif
            </div>
            <div class="text-xs text-brand-600 font-medium mt-0.5">{{ $course->title }} → {{ $lesson->section->title }}</div>
            <div class="flex flex-wrap gap-3 mt-1.5 text-xs text-gray-400">
              <span>📅 {{ $sched->format('D, d M Y') }}</span>
              <span>🕐 {{ $sched->format('h:i A') }}</span>
              <span>⏱ {{ $lesson->live_duration_min ?? 60 }}min</span>
              @if($lesson->live_meeting_id)
              <span>🔢 {{ $lesson->live_meeting_id }}</span>
              @endif
            </div>
            @if($lesson->live_url)
            <div class="mt-1.5">
              <a href="{{ $lesson->live_url }}" target="_blank"
                 class="text-xs text-brand-600 hover:text-brand-700 font-mono hover:underline">
                {{ Str::limit($lesson->live_url, 50) }}
              </a>
            </div>
            @endif
          </div>
          <div class="flex flex-col gap-1.5 flex-shrink-0">
            <a href="{{ route('admin.courses.lessons.edit', [$course->id, $lesson->section->id, $lesson->id]) }}"
               class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 font-bold px-3 py-1.5 rounded-lg transition">
              ✏️ Edit
            </a>
            <a href="{{ route('admin.live.attendance', $lesson->id) }}" class="w-full text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 font-bold px-3 py-1.5 rounded-lg transition block text-center">📋 Attendance</a>
            <form method="POST" action="{{ route('admin.live.destroy', $lesson->id) }}"
                  onsubmit="return confirm('Cancel this session?')">
              @csrf @method('DELETE')
              <button class="w-full text-xs bg-red-50 hover:bg-red-100 text-red-600 font-bold px-3 py-1.5 rounded-lg transition">
                🗑 Cancel
              </button>
            </form>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Past --}}
    @if($past->count())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100 bg-gray-50">
        <span class="text-lg">✅</span>
        <h3 class="font-black text-gray-900 text-sm">Past Sessions</h3>
        <span class="ml-auto text-xs bg-gray-500 text-white font-bold px-2.5 py-1 rounded-full">{{ $past->count() }}</span>
      </div>
      <div class="divide-y divide-gray-50">
        @foreach($past->take(10) as $lesson)
        @php $course2 = $lesson->section->course; @endphp
        <div class="flex items-center gap-3 px-5 py-3 opacity-70">
          <span class="text-xl flex-shrink-0">{{ ['google_meet'=>'🟢','zoom'=>'🔵','teams'=>'🟣','other'=>'⚪'][$lesson->live_platform ?? 'other'] ?? '📹' }}</span>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-gray-700 text-sm truncate">{{ $lesson->title }}</div>
            <div class="text-xs text-gray-400">{{ $course2->title }} · {{ $lesson->live_scheduled_at?->format('d M Y h:i A') }}</div>
          </div>
          <span class="text-xs bg-gray-100 text-gray-500 font-semibold px-2.5 py-1 rounded-full flex-shrink-0">Ended</span>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    @if($upcoming->isEmpty() && $past->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
      <div class="text-5xl mb-3">📡</div>
      <h3 class="font-black text-gray-900 text-lg mb-2">No Live Sessions Yet</h3>
      <p class="text-gray-500 text-sm">Use the form on the left to schedule your first live class.</p>
    </div>
    @endif

  </div>
</div>

@endsection

@push('scripts')
<script>
const sectionsData = @json($sections);

function loadSections(courseId) {
  const sel = document.getElementById('sectionSelect');
  sel.innerHTML = '<option value="">Select section…</option>';
  if (!courseId) return;
  const courseSections = sectionsData[courseId] || [];
  if (!courseSections.length) {
    sel.innerHTML = '<option value="">No sections found</option>';
    return;
  }
  courseSections.forEach(s => {
    const opt = document.createElement('option');
    opt.value = s.id;
    opt.textContent = s.title;
    sel.appendChild(opt);
  });
}
</script>
@endpush
