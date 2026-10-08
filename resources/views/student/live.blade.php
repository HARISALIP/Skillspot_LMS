@extends('layouts.student')
@section('title','Live Classes — Skillspot.in')
@section('page-title','Live Classes')
@section('page-sub','Your upcoming & past live sessions')

@section('student-content')

@php
  use App\Models\Lesson;
  use App\Models\Enrollment;
  use Illuminate\Support\Facades\DB;

  $user = auth()->user();

  // Get all courses the student is enrolled in
  $enrolledCourseIds = Enrollment::where('user_id',$user->id)
    ->whereIn('status',['active','completed'])
    ->pluck('course_id');

  // All live lessons from enrolled courses
  $allLive = Lesson::where('type','live')
    ->whereNotNull('live_scheduled_at')
    ->whereHas('section', fn($q) => $q->whereIn('course_id',$enrolledCourseIds))
    ->with('section.course')
    ->orderBy('live_scheduled_at','asc')
    ->get();

  $upcoming = $allLive->filter(fn($l) => $l->live_scheduled_at && $l->live_scheduled_at->isFuture());
  $past     = $allLive->filter(fn($l) => $l->live_scheduled_at && $l->live_scheduled_at->isPast())
                      ->sortByDesc('live_scheduled_at');

  $platformIcons = [
    'google_meet' => '🟢',
    'zoom'        => '🔵',
    'teams'       => '🟣',
    'other'       => '📹',
  ];
  $platformNames = [
    'google_meet' => 'Google Meet',
    'zoom'        => 'Zoom',
    'teams'       => 'Microsoft Teams',
    'other'       => 'Live Session',
  ];
@endphp

{{-- No enrollments at all --}}
@if($enrolledCourseIds->isEmpty())
<div class="text-center py-20 bg-white rounded-2xl border border-gray-100 shadow-sm">
  <div class="text-6xl mb-4">📡</div>
  <h3 class="text-xl font-black text-gray-900 mb-2">No Live Classes Yet</h3>
  <p class="text-gray-500 mb-6">Enroll in a course to access live sessions.</p>
  <a href="{{ route('student.browse') }}" class="inline-block bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3 rounded-2xl transition">Browse Courses →</a>
</div>

@else

  {{-- ── Upcoming Live Classes ─────────────────────────────────────── --}}
  <div class="mb-8">
    <div class="flex items-center gap-3 mb-4">
      <h2 class="font-black text-gray-900 text-base">📡 Upcoming Sessions</h2>
      @if($upcoming->count())
      <span class="bg-red-100 text-red-600 text-xs font-bold px-2.5 py-1 rounded-full animate-pulse">
        {{ $upcoming->count() }} scheduled
      </span>
      @endif
    </div>

    @if($upcoming->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-400">
      <div class="text-4xl mb-2">📅</div>
      <p class="text-sm font-medium">No upcoming live sessions right now</p>
      <p class="text-xs mt-1">Check back later — sessions are added regularly</p>
    </div>
    @else
    <div class="space-y-4">
      @foreach($upcoming as $lesson)
      @php
        $now       = now();
        $sched     = $lesson->live_scheduled_at;
        $endTime   = $sched->copy()->addMinutes($lesson->live_duration_min ?? 60);
        $isLive    = $now->between($sched, $endTime);
        $diffMin   = (int)$now->diffInMinutes($sched, false);
        $canJoin   = $isLive || ($diffMin >= 0 && $diffMin <= 15);
        $icon      = $platformIcons[$lesson->live_platform ?? 'other'] ?? '📹';
        $platName  = $platformNames[$lesson->live_platform ?? 'other'] ?? 'Live Session';
        $course    = $lesson->section->course;
      @endphp
      <div class="bg-white rounded-2xl border-2 {{ $isLive ? 'border-red-300' : 'border-gray-100' }} shadow-sm overflow-hidden">
        {{-- Header band --}}
        @if($isLive)
        <div class="bg-red-500 text-white text-xs font-black text-center py-1.5 tracking-widest animate-pulse">
          🔴 LIVE NOW — CLASS IS IN PROGRESS
        </div>
        @elseif($diffMin >= 0 && $diffMin <= 30)
        <div class="bg-orange-500 text-white text-xs font-black text-center py-1.5 tracking-wider">
          ⏰ STARTING SOON
        </div>
        @endif

        <div class="p-5">
          <div class="flex items-start gap-4 flex-wrap">

            {{-- Platform icon --}}
            <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center text-3xl flex-shrink-0">
              {{ $icon }}
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-3 flex-wrap mb-2">
                <div>
                  <h3 class="font-black text-gray-900 text-base leading-tight">{{ $lesson->title }}</h3>
                  <div class="text-xs text-brand-600 font-semibold mt-0.5">{{ $course->title }}</div>
                </div>
                {{-- Join button --}}
                @if($isLive)
                <a href="{{ $lesson->live_url }}" target="_blank" rel="noopener"
                   class="flex-shrink-0 inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-black px-5 py-2.5 rounded-xl text-sm transition shadow-lg shadow-red-200 animate-pulse">
                  🔴 Join Now
                </a>
                @elseif($canJoin)
                <a href="{{ $lesson->live_url }}" target="_blank" rel="noopener"
                   class="flex-shrink-0 inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition shadow-md">
                  📡 Join Class
                </a>
                @else
                <div class="flex-shrink-0 flex flex-col items-end gap-1">
                  <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 font-semibold px-4 py-2 rounded-xl text-xs">
                    🔒 Opens 15 min before
                  </span>
                </div>
                @endif
              </div>

              {{-- Details --}}
              <div class="flex flex-wrap gap-3 text-xs text-gray-500 mb-3">
                <span class="flex items-center gap-1">
                  📅 {{ $sched->format('D, d M Y') }}
                </span>
                <span class="flex items-center gap-1">
                  🕐 {{ $sched->format('h:i A') }}
                </span>
                <span class="flex items-center gap-1">
                  ⏱ {{ $lesson->live_duration_min ?? 60 }} minutes
                </span>
                <span class="flex items-center gap-1">
                  {{ $icon }} {{ $platName }}
                </span>
              </div>

              {{-- Countdown --}}
              @if(!$isLive)
              <div class="inline-flex items-center gap-2 bg-brand-50 text-brand-700 text-xs font-bold px-3 py-1.5 rounded-xl mb-3">
                ⏳ <span id="countdown-{{ $lesson->id }}" data-time="{{ $sched->timestamp }}">Calculating…</span>
              </div>
              @endif

              {{-- Meeting details --}}
              @if($lesson->live_meeting_id || $lesson->live_password)
              <div class="flex flex-wrap gap-4 text-xs bg-gray-50 rounded-xl px-4 py-2.5 mb-3">
                @if($lesson->live_meeting_id)
                <div class="flex items-center gap-2">
                  <span class="text-gray-400">#️⃣ Meeting ID:</span>
                  <span class="font-mono font-bold text-gray-800">{{ $lesson->live_meeting_id }}</span>
                  <button onclick="navigator.clipboard.writeText('{{ $lesson->live_meeting_id }}').then(()=>{this.textContent='✅';setTimeout(()=>this.textContent='📋',2000)})"
                          class="text-brand-600 hover:text-brand-700 transition">📋</button>
                </div>
                @endif
                @if($lesson->live_password)
                <div class="flex items-center gap-2">
                  <span class="text-gray-400">🔑 Password:</span>
                  <span class="font-mono font-bold text-gray-800">{{ $lesson->live_password }}</span>
                  <button onclick="navigator.clipboard.writeText('{{ $lesson->live_password }}').then(()=>{this.textContent='✅';setTimeout(()=>this.textContent='📋',2000)})"
                          class="text-brand-600 hover:text-brand-700 transition">📋</button>
                </div>
                @endif
              </div>
              @endif

              @if($lesson->description)
              <p class="text-xs text-gray-500 leading-relaxed">{{ $lesson->description }}</p>
              @endif
            </div>
          </div>

          {{-- Add to calendar --}}
          <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-2">
            <a href="{{ $lesson->live_url }}" target="_blank"
               class="text-xs text-brand-600 hover:text-brand-700 font-semibold hover:underline">
              🔗 Copy join link
            </a>
            <span class="text-gray-300">|</span>
            <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text={{ urlencode($lesson->title) }}&dates={{ $sched->format('Ymd\THis') }}%2F{{ $endTime->format('Ymd\THis') }}&details={{ urlencode('Join: '.$lesson->live_url) }}"
               target="_blank"
               class="text-xs text-gray-500 hover:text-gray-700 font-semibold hover:underline">
              📅 Add to Google Calendar
            </a>
          </div>
        </div>
      </div>
      @endforeach
    </div>
    @endif
  </div>

  {{-- ── Past Sessions ──────────────────────────────────────────────── --}}
  @if($past->count())
  <div>
    <h2 class="font-black text-gray-900 text-base mb-4">🗓️ Past Sessions</h2>
    <div class="space-y-3">
      @foreach($past as $lesson)
      @php
        $icon2   = $platformIcons[$lesson->live_platform ?? 'other'] ?? '📹';
        $course2 = $lesson->section->course;
      @endphp
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4 opacity-70">
        <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-xl flex-shrink-0">{{ $icon2 }}</div>
        <div class="flex-1 min-w-0">
          <div class="font-semibold text-gray-700 text-sm truncate">{{ $lesson->title }}</div>
          <div class="text-xs text-gray-400">{{ $course2->title }} · {{ $lesson->live_scheduled_at->format('d M Y h:i A') }}</div>
        </div>
        <span class="text-xs bg-gray-100 text-gray-500 font-semibold px-3 py-1 rounded-full flex-shrink-0">✅ Ended</span>
      </div>
      @endforeach
    </div>
  </div>
  @endif

@endif

@endsection

@push('scripts')
<script>
function updateCountdowns() {
  document.querySelectorAll('[id^=countdown-]').forEach(el => {
    const target = parseInt(el.dataset.time) * 1000;
    const diff   = target - Date.now();
    if (diff <= 0) {
      el.textContent = '🔴 Starting now!';
      el.closest('.inline-flex')?.classList.replace('bg-brand-50','bg-red-50');
      el.closest('.inline-flex')?.classList.replace('text-brand-700','text-red-700');
      return;
    }
    const d = Math.floor(diff/86400000);
    const h = Math.floor((diff%86400000)/3600000);
    const m = Math.floor((diff%3600000)/60000);
    const s = Math.floor((diff%60000)/1000);
    if (d > 0)      el.textContent = `In ${d}d ${h}h ${m}m`;
    else if (h > 0) el.textContent = `In ${h}h ${m}m ${s}s`;
    else            el.textContent = `In ${m}m ${s}s`;
  });
}
setInterval(updateCountdowns, 1000);
updateCountdowns();
</script>
@endpush
