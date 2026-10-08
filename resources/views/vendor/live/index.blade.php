@extends('layouts.vendor')
@section('title','Live Classes — Vendor Panel')
@section('header','Live Classes')
@section('content')
<div class="space-y-6">
  {{-- ── NEW: Hours-Based & Batch Live Classes ─────────────── --}}
  <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-5 text-white flex items-center justify-between gap-4">
    <div>
      <div class="font-bold text-lg">⏱ Hours-Based & Batch Live Classes</div>
      <div class="text-white/80 text-sm mt-0.5">Create classes with total hour targets or fixed batch dates. Teacher manually starts & stops — hours auto-tracked.</div>
    </div>
    <a href="{{ route('vendor.live_classes.index') }}"
       class="flex-shrink-0 bg-white text-blue-700 font-bold text-sm px-5 py-2.5 rounded-xl hover:bg-blue-50 transition whitespace-nowrap">
      Open →
    </a>
  </div>



  @php $isTeacher = auth()->user()->hasRole(['teacher','admin','super-admin']); @endphp

  {{-- ── TEACHER: Schedule Form ── --}}
  @if($isTeacher)
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
    <h2 class="font-semibold text-gray-900 mb-4">📅 Schedule Live Class</h2>
    @if($errors->any())
      <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-2 mb-4 text-sm text-red-800">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif
    <form method="POST" action="{{ route('vendor.live.store') }}" class="grid grid-cols-2 gap-4" id="liveForm">
      @csrf

      {{-- Title --}}
      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Class Title *</label>
        <input type="text" name="title" required placeholder="e.g. Networking Basics"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>

      {{-- Session Type --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Session Type *</label>
        <select name="session_type" id="sessionType" required onchange="toggleRecurring(this.value)"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
          <option value="one_time">One Time</option>
          <option value="recurring">Recurring</option>
        </select>
      </div>

      {{-- Platform --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Platform *</label>
        <select name="live_platform" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
          <option value="google_meet">Google Meet</option>
          <option value="zoom">Zoom</option>
          <option value="teams">Microsoft Teams</option>
          <option value="other">Other</option>
        </select>
      </div>

      {{-- ONE-TIME: single datetime --}}
      <div id="oneTimeFields" class="contents">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled At *</label>
          <input type="datetime-local" name="live_scheduled_at" id="scheduledAt"
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
      </div>

      {{-- RECURRING: days + time + date range --}}
      <div id="recurringFields" class="col-span-2 hidden">
        <div class="bg-purple-50 border border-purple-100 rounded-2xl p-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Repeat Days *</label>
            <div class="flex flex-wrap gap-2" id="daysSelect">
              @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
              <label class="cursor-pointer">
                <input type="checkbox" name="recurring_days[]" value="{{ $day }}"
                       class="hidden peer" id="day{{ $day }}">
                <span class="peer-checked:bg-brand-600 peer-checked:text-white peer-checked:border-brand-600
                             border border-gray-300 text-gray-600 text-xs font-semibold px-3 py-1.5 rounded-xl
                             transition select-none block">{{ $day }}</span>
              </label>
              @endforeach
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Class Time *</label>
              <input type="time" name="recurring_time" id="recurringTime"
                     class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
              <input type="date" name="recurring_start" id="recurringStart"
                     class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
              <input type="date" name="recurring_end" id="recurringEnd"
                     class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
          </div>
        </div>
      </div>

      {{-- Duration --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Duration (min) *</label>
        <input type="number" name="live_duration_min" value="60" min="15" required
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>

      {{-- Enroll Limit --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Enroll Limit <span class="text-gray-400 font-normal">(0 = unlimited)</span></label>
        <input type="number" name="enroll_limit" value="0" min="0"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>

      {{-- URLs --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Join URL (host) *</label>
        <input type="text" name="live_url" required placeholder="https://meet.google.com/..."
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Student Share Link</label>
        <input type="text" name="live_share_link" placeholder="URL shared with enrolled students"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Meeting ID</label>
        <input type="text" name="live_meeting_id"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="text" name="live_password"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>

      <div class="col-span-2 pt-2">
        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition">Schedule Class</button>
      </div>
    </form>
  </div>
  @endif

  {{-- ── Live Now ── --}}
  @if($liveNow->count())
  <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
    <h2 class="font-semibold text-red-800 mb-3 flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-red-500 animate-ping inline-block"></span> Live Now
    </h2>
    @foreach($liveNow as $lesson)
    <div class="bg-white rounded-xl p-4 flex items-center justify-between">
      <div>
        <div class="font-semibold text-gray-900">{{ $lesson->title }}</div>
        <div class="text-xs text-gray-400 mt-0.5">{{ $lesson->section->course->title ?? '' }} · {{ ucfirst($lesson->live_platform) }}</div>
      </div>
      <a href="{{ $lesson->live_url }}" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-700 transition">Join Now</a>
    </div>
    @endforeach
  </div>
  @endif

  {{-- ── Upcoming ── --}}
  @if($upcoming->count())
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-900">Upcoming</h2></div>
    <div class="divide-y divide-gray-50">
      @foreach($upcoming as $lesson)
      <div class="px-5 py-4 flex items-center justify-between">
        <div>
          <div class="font-medium text-gray-900">{{ $lesson->title }}</div>
          <div class="text-xs text-gray-400 mt-0.5">
            {{ ucfirst(str_replace('_',' ',$lesson->live_platform)) }} · {{ $lesson->live_scheduled_at->format('d M Y, h:i A') }}
            @if($lesson->content) · <span class="{{ $lesson->content == 'recurring' ? 'text-purple-600' : 'text-blue-600' }} font-semibold">{{ ucfirst(str_replace('_',' ',$lesson->content)) }}</span> @endif
            @if($lesson->notes) · <span class="text-gray-500">👥 Limit: {{ $lesson->notes == '0' ? '∞' : $lesson->notes }}</span> @endif
          </div>
          @if($lesson->live_share_link)
            <div class="text-xs text-brand-600 mt-0.5">Share link: <span class="font-mono">{{ $lesson->live_share_link }}</span></div>
          @endif
        </div>
        {{-- Cancel button — teacher only --}}
        @if($isTeacher)
        <form method="POST" action="{{ route('vendor.live.destroy', $lesson) }}">
          @csrf @method('DELETE')
          <button class="text-xs text-red-600 hover:text-red-800 font-medium" onclick="return confirm('Cancel this session?')">Cancel</button>
        </form>
        @endif
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ── Past + Recordings ── --}}
  @if($past->count())
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-900">🎬 Past Sessions & Recordings</h2></div>
    <div class="divide-y divide-gray-50">
      @foreach($past as $lesson)
      <div class="px-5 py-4">
        <div class="flex items-center justify-between mb-2">
          <div>
            <div class="font-medium text-gray-900">{{ $lesson->title }}</div>
            <div class="text-xs text-gray-400">{{ $lesson->section->course->title ?? '' }} · {{ $lesson->live_scheduled_at->format('d M Y') }}</div>
          </div>
          <span class="{{ $lesson->recording_shared ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} px-2 py-0.5 rounded-full text-xs font-medium">
            {{ $lesson->recording_shared ? 'Recording Shared' : 'Recording Hidden' }}
          </span>
        </div>
        {{-- Recording form — teacher only --}}
        @if($isTeacher)
        <form method="POST" action="{{ route('vendor.live.recording', $lesson) }}" class="flex items-center gap-3 mt-2">
          @csrf @method('PUT')
          <input type="url" name="recording_url" value="{{ $lesson->recording_url }}" placeholder="Recording URL (YouTube, Drive, etc.)" class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-brand-500">
          <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
            <input type="checkbox" name="recording_shared" value="1" {{ $lesson->recording_shared ? 'checked' : '' }} class="rounded">
            Share with students
          </label>
          <button type="submit" class="bg-brand-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-brand-700 transition whitespace-nowrap">Save</button>
        </form>
        @elseif($lesson->recording_url)
        <div class="mt-2">
          <a href="{{ $lesson->recording_url }}" target="_blank" class="text-xs bg-brand-50 text-brand-700 px-3 py-1.5 rounded-lg font-medium hover:bg-brand-100 transition">▶ View Recording</a>
        </div>
        @endif
      </div>
      @endforeach
    </div>
  </div>
  @endif

  @if(!$liveNow->count() && !$upcoming->count() && !$past->count())
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
    No live classes scheduled yet.
  </div>
  @endif

</div>

@push('scripts')
<script>
function toggleRecurring(val) {
  const rf = document.getElementById('recurringFields');
  const sa = document.getElementById('scheduledAt');
  if (val === 'recurring') {
    rf.classList.remove('hidden');
    sa.removeAttribute('required');
    document.getElementById('recurringTime').setAttribute('required','');
    document.getElementById('recurringStart').setAttribute('required','');
    document.getElementById('recurringEnd').setAttribute('required','');
  } else {
    rf.classList.add('hidden');
    sa.setAttribute('required','');
    document.getElementById('recurringTime').removeAttribute('required');
    document.getElementById('recurringStart').removeAttribute('required');
    document.getElementById('recurringEnd').removeAttribute('required');
  }
}
</script>
@endpush
@endsection
