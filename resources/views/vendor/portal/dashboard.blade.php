<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Dashboard — {{ $vendor->brand_name }}</title>
  @if($vendor->favicon)<link rel="icon" href="{{ Storage::disk('public')->url($vendor->favicon) }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --primary: {{ $vendor->primary_color ?? '#2563eb' }}; --accent: {{ $vendor->accent_color ?? '#7c3aed' }}; }
    .hero-bg { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); }
    .btn-primary { background: var(--primary); }
    .btn-primary:hover { opacity: 0.9; }
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 antialiased">

<nav class="bg-white border-b border-gray-100 sticky top-0 z-20">
  <div class="max-w-5xl mx-auto px-6 h-16 flex items-center justify-between">
    <div class="flex items-center gap-3">
      @if($vendor->logo)
        <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-9 h-9 rounded-xl object-cover">
      @else
        <div class="w-9 h-9 hero-bg rounded-xl flex items-center justify-center text-white font-black">{{ strtoupper(substr($vendor->brand_name,0,1)) }}</div>
      @endif
      <span class="font-bold text-gray-900">{{ $vendor->brand_name }}</span>
    </div>
    <div class="flex items-center gap-3 text-sm">
      <span class="text-gray-600 hidden sm:block">{{ auth()->user()->name }}</span>
      <form method="POST" action="{{ route('vendor.portal.logout', $vendor->slug) }}">@csrf
        <button class="text-gray-400 hover:text-red-600 transition text-sm">Sign out</button>
      </form>
    </div>
  </div>
</nav>

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-8">

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
  @endif

  <h1 class="text-2xl font-black text-gray-900">My Dashboard</h1>

  {{-- ══ LIVE CLASSES (hours/batch based) ═════════════════════════════ --}}
  @if($liveClassEnrollments->count())
  <div>
    <h2 class="font-bold text-gray-900 text-lg mb-3">📡 My Live Classes</h2>
    <div class="space-y-3">
      @foreach($liveClassEnrollments as $enrollment)
      @php
        $lc = $enrollment->liveClass;
        $isLive = $lc->status === 'live';
        $isDone = $lc->status === 'completed';
      @endphp
      <div class="bg-white rounded-2xl border {{ $isLive ? 'border-red-300' : 'border-gray-100' }} shadow-sm overflow-hidden">
        <div class="h-1.5 {{ $isLive ? 'bg-red-500 animate-pulse' : ($isDone ? 'bg-green-500' : 'bg-blue-500') }}"></div>
        <div class="p-5 flex items-center justify-between gap-4 flex-wrap">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-bold text-gray-900">{{ $lc->title }}</span>
              @if($isLive)
                <span class="inline-flex items-center gap-1 bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">🔴 LIVE NOW</span>
              @elseif($isDone)
                <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">✅ Completed</span>
              @else
                <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">Active</span>
              @endif
              @if($lc->schedule_type === 'hours_based')
                <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-2 py-0.5 rounded-full">⏱ {{ $lc->completed_hours }}h / {{ $lc->total_hours }}h</span>
              @else
                @if($lc->batch_name)<span class="bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">📅 {{ $lc->batch_name }}</span>@endif
              @endif
            </div>
            <div class="text-xs text-gray-400 mt-1.5 flex flex-wrap gap-3">
              <span>🖥 {{ ucfirst(str_replace('_',' ',$lc->platform)) }}</span>
              <span>📋 {{ $lc->sessions->count() }} sessions held</span>
              @if($lc->schedule_type === 'date_based' && $lc->batch_start_date)
                <span>📅 {{ $lc->batch_start_date->format('d M Y') }} → {{ $lc->batch_end_date?->format('d M Y') ?? 'Ongoing' }}</span>
              @endif
            </div>
            {{-- Progress bar for hours-based --}}
            @if($lc->schedule_type === 'hours_based' && $lc->total_hours > 0)
            <div class="mt-2.5 flex items-center gap-2">
              <div class="flex-1 bg-gray-200 rounded-full h-1.5 max-w-xs">
                <div class="h-1.5 rounded-full bg-blue-500" style="width:{{ $lc->progressPercent() }}%"></div>
              </div>
              <span class="text-xs text-gray-500">{{ $lc->progressPercent() }}%</span>
            </div>
            @endif
          </div>
          <div class="flex gap-2 flex-shrink-0">
            @if($isLive && $lc->meeting_url)
              <a href="{{ $lc->meeting_url }}" target="_blank"
                 class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-2 rounded-xl transition">
                🔴 Join Now
              </a>
            @endif
            <a href="{{ route('vendor.portal.live_class.detail', [$vendor->slug, $lc->slug]) }}"
               class="btn-primary text-white text-sm font-semibold px-4 py-2 rounded-xl transition">
              View →
            </a>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ══ COURSE ENROLLMENTS ═══════════════════════════════════════════ --}}
  @if($enrollments->count())
  <div>
    <h2 class="font-bold text-gray-900 text-lg mb-3">📚 My Courses</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      @foreach($enrollments as $enrollment)
      @php $course = $enrollment->course; @endphp
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="h-2 hero-bg"></div>
        <div class="p-5">
          <div class="flex items-start justify-between mb-2">
            <div>
              <div class="font-bold text-gray-900">{{ $course->title }}</div>
              @if($course->batch_name)<div class="text-xs font-semibold mt-0.5" style="color:var(--primary)">{{ $course->batch_name }}</div>@endif
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-green-100 text-green-700">Enrolled</span>
          </div>
          @if($course->batch_start_date)
            <div class="text-xs text-gray-400 mb-3">📅 {{ $course->batch_start_date->format('d M Y') }} → {{ $course->batch_end_date?->format('d M Y') ?? 'Ongoing' }}</div>
          @endif
          <div class="flex items-center gap-2 mb-4">
            <div class="flex-1 bg-gray-200 rounded-full h-1.5">
              <div class="h-1.5 rounded-full btn-primary" style="width:{{ $enrollment->progress }}%"></div>
            </div>
            <span class="text-xs font-semibold text-gray-500">{{ $enrollment->progress }}%</span>
          </div>
          <a href="{{ route('vendor.portal.batch', [$vendor->slug, $course->id]) }}"
             class="block w-full text-center btn-primary text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
            📚 View Batch Content
          </a>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ══ UPCOMING OLD-STYLE LIVE ══════════════════════════════════════ --}}
  @if($upcomingLive->count())
  <div>
    <h2 class="font-bold text-gray-900 text-lg mb-3">🗓 Scheduled Live Sessions</h2>
    <div class="space-y-3">
      @foreach($upcomingLive as $lesson)
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center justify-between gap-4">
        <div>
          <div class="font-semibold text-gray-900">{{ $lesson->title }}</div>
          <div class="text-sm text-gray-500 mt-0.5">{{ $lesson->section->course->title ?? '' }} · {{ ucfirst($lesson->live_platform ?? '') }}</div>
          <div class="text-xs text-gray-400 mt-0.5">📅 {{ $lesson->live_scheduled_at->format('d M Y, h:i A') }}</div>
        </div>
        @if($lesson->live_url)
          <a href="{{ $lesson->live_url }}" target="_blank" class="btn-primary text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex-shrink-0">Join →</a>
        @endif
      </div>
      @endforeach
    </div>
  </div>
  @endif

  @if(!$liveClassEnrollments->count() && !$enrollments->count())
  <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center text-gray-400">
    <div class="text-5xl mb-3">📚</div>
    <p class="font-semibold text-gray-600">No classes enrolled yet.</p>
    <a href="{{ route('vendor.portal.home', $vendor->slug) }}" class="btn-primary text-white text-sm font-semibold px-5 py-2.5 rounded-xl mt-4 inline-block transition">Browse Courses</a>
  </div>
  @endif

</div>
</body>
</html>
