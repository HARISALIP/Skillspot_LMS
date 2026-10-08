<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $liveClass->title }} — {{ $vendor->brand_name }}</title>
  @if($vendor->favicon)<link rel="icon" href="{{ Storage::disk('public')->url($vendor->favicon) }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --primary: {{ $vendor->primary_color ?? '#2563eb' }}; --accent: {{ $vendor->accent_color ?? '#7c3aed' }}; }
    .hero-bg { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); }
    .btn-primary { background: var(--primary); }
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 antialiased">

{{-- Nav --}}
<nav class="bg-white border-b border-gray-100 sticky top-0 z-20">
  <div class="max-w-4xl mx-auto px-6 h-16 flex items-center justify-between">
    <div class="flex items-center gap-3">
      @if($vendor->logo)
        <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-8 h-8 rounded-xl object-cover">
      @else
        <div class="w-8 h-8 hero-bg rounded-xl flex items-center justify-center text-white font-black text-sm">{{ strtoupper(substr($vendor->brand_name,0,1)) }}</div>
      @endif
      <a href="{{ route('vendor.portal.dashboard', $vendor->slug) }}" class="text-sm text-gray-500 hover:text-gray-700">← Dashboard</a>
    </div>
    <span class="text-sm text-gray-600 hidden sm:block">{{ auth()->user()->name }}</span>
  </div>
</nav>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6">

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
  @endif

  {{-- Hero card --}}
  <div class="rounded-2xl overflow-hidden shadow-sm">
    <div class="hero-bg p-6 text-white">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <div class="text-xs font-semibold uppercase tracking-widest opacity-70 mb-1">Live Class</div>
          <h1 class="text-2xl font-black mb-1">{{ $liveClass->title }}</h1>
          @if($liveClass->description)<p class="text-white/70 text-sm mt-1">{{ $liveClass->description }}</p>@endif
          <div class="flex flex-wrap gap-3 mt-3 text-sm">
            @if($liveClass->status === 'live')
              <span class="bg-red-500 px-3 py-1 rounded-full font-bold animate-pulse">🔴 LIVE NOW</span>
            @elseif($liveClass->status === 'completed')
              <span class="bg-white/20 px-3 py-1 rounded-full">✅ Completed</span>
            @else
              <span class="bg-white/20 px-3 py-1 rounded-full">Active</span>
            @endif
            @if($liveClass->schedule_type === 'hours_based')
              <span class="bg-white/20 px-3 py-1 rounded-full">⏱ {{ $liveClass->completed_hours }}h / {{ $liveClass->total_hours }}h</span>
            @else
              @if($liveClass->batch_name)<span class="bg-white/20 px-3 py-1 rounded-full">📅 {{ $liveClass->batch_name }}</span>@endif
              @if($liveClass->batch_start_date)<span class="bg-white/20 px-3 py-1 rounded-full">{{ $liveClass->batch_start_date->format('d M') }} → {{ $liveClass->batch_end_date?->format('d M Y') ?? 'TBD' }}</span>@endif
            @endif
            <span class="bg-white/20 px-3 py-1 rounded-full">🖥 {{ ucfirst(str_replace('_',' ',$liveClass->platform)) }}</span>
          </div>
        </div>
        {{-- Join button if LIVE --}}
        @if($liveClass->status === 'live' && $liveClass->meeting_url)
        <a href="{{ $liveClass->meeting_url }}" target="_blank"
           class="flex-shrink-0 bg-white font-black px-6 py-3 rounded-xl transition hover:bg-gray-50 text-sm"
           style="color: var(--primary)">
          🔴 Join Class Now →
        </a>
        @endif
      </div>

      {{-- Hours progress --}}
      @if($liveClass->schedule_type === 'hours_based' && $liveClass->total_hours > 0)
      <div class="mt-4">
        <div class="flex justify-between text-xs text-white/70 mb-1">
          <span>Overall Progress</span><span>{{ $liveClass->progressPercent() }}%</span>
        </div>
        <div class="w-full bg-white/20 rounded-full h-2">
          <div class="h-2 rounded-full bg-white transition-all" style="width:{{ $liveClass->progressPercent() }}%"></div>
        </div>
      </div>
      @endif
    </div>
  </div>

  {{-- Meeting info --}}
  @if($liveClass->meeting_id || $liveClass->meeting_password || $liveClass->meeting_url)
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-bold text-gray-800 mb-3">🔗 Meeting Details</h3>
    <div class="space-y-2 text-sm">
      @if($liveClass->meeting_url)
      <div class="flex items-center gap-3">
        <span class="text-gray-500 w-28 flex-shrink-0">Join Link:</span>
        <a href="{{ $liveClass->meeting_url }}" target="_blank" class="text-blue-600 hover:underline truncate">{{ $liveClass->meeting_url }}</a>
      </div>
      @endif
      @if($liveClass->meeting_id)
      <div class="flex items-center gap-3">
        <span class="text-gray-500 w-28 flex-shrink-0">Meeting ID:</span>
        <span class="font-mono font-bold text-gray-800 select-all">{{ $liveClass->meeting_id }}</span>
      </div>
      @endif
      @if($liveClass->meeting_password)
      <div class="flex items-center gap-3">
        <span class="text-gray-500 w-28 flex-shrink-0">Password:</span>
        <span class="font-mono font-bold text-gray-800 select-all">{{ $liveClass->meeting_password }}</span>
      </div>
      @endif
    </div>
  </div>
  @endif

  {{-- Stats row --}}
  <div class="grid grid-cols-3 gap-3">
    <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
      <div class="text-2xl font-black" style="color:var(--primary)">{{ $attendedSessions }}</div>
      <div class="text-xs text-gray-500 mt-0.5">Sessions Attended</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
      <div class="text-2xl font-black text-gray-700">{{ $totalSessions }}</div>
      <div class="text-xs text-gray-500 mt-0.5">Total Sessions</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
      <div class="text-2xl font-black {{ $attendPct >= 75 ? 'text-green-600' : ($attendPct >= 50 ? 'text-amber-500' : 'text-red-500') }}">{{ $attendPct }}%</div>
      <div class="text-xs text-gray-500 mt-0.5">Attendance</div>
    </div>
  </div>

  {{-- Active session banner --}}
  @if($activeSession)
  <div class="bg-red-600 rounded-2xl p-4 flex items-center justify-between gap-4">
    <div>
      <div class="text-white font-bold text-sm animate-pulse">🔴 Live Session In Progress</div>
      <div class="text-red-200 text-xs mt-0.5">Started {{ $activeSession->started_at->format('h:i A') }}
        @if($activeSession->session_name) &middot; {{ $activeSession->session_name }} @endif
      </div>
    </div>
    @if($liveClass->meeting_url)
    <a href="{{ $liveClass->meeting_url }}" target="_blank"
       class="flex-shrink-0 bg-white font-black text-xs px-5 py-2.5 rounded-xl transition hover:bg-red-50"
       style="color: var(--primary)">
      Join Now →
    </a>
    @endif
  </div>
  @endif

  {{-- Session History --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h3 class="font-bold text-gray-800">📋 Session History & Resources</h3>
      <p class="text-xs text-gray-400 mt-0.5">Attendance, recordings and notes for each session</p>
    </div>

    @forelse($liveClass->sessions->where('status','ended')->sortByDesc('started_at') as $session)
    @php $myAttend = $attendance->where('live_class_session_id', $session->id)->first(); @endphp
    <div class="px-5 py-4 border-b border-gray-50 last:border-0 space-y-3">

      {{-- Session info row --}}
      <div class="flex items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
          {{-- Module / Session badges --}}
          @if($session->module_name || $session->session_name)
          <div class="flex flex-wrap gap-1.5 mb-1.5">
            @if($session->module_name)
              <span class="text-xs bg-purple-100 text-purple-700 font-semibold px-2.5 py-0.5 rounded-full">{{ $session->module_name }}</span>
            @endif
            @if($session->session_name)
              <span class="text-xs bg-blue-100 text-blue-700 font-semibold px-2.5 py-0.5 rounded-full">{{ $session->session_name }}</span>
            @endif
          </div>
          @endif

          <div class="text-sm font-semibold text-gray-800">
            {{ $session->started_at?->format('l, d M Y') }}
          </div>
          <div class="text-xs text-gray-400 mt-0.5">
            {{ $session->started_at?->format('h:i A') }}
            @if($session->stopped_at) → {{ $session->stopped_at->format('h:i A') }} @endif
            @if($session->duration_hours)
              &middot; {{ rtrim(rtrim(number_format((float)$session->duration_hours,2),'0'),'.') }}h
            @endif
          </div>
        </div>

        {{-- Attendance badge --}}
        @if($myAttend)
          <span class="flex-shrink-0 bg-green-100 text-green-700 text-xs font-bold px-3 py-1.5 rounded-full">✅ Present</span>
        @else
          <span class="flex-shrink-0 bg-red-50 text-red-400 text-xs font-semibold px-3 py-1.5 rounded-full">✗ Absent</span>
        @endif
      </div>

      {{-- Files section --}}
      @php $visibleFiles = $session->files->where('is_visible', true); @endphp
      @if($visibleFiles->count())
      <div class="space-y-2">
        @foreach($visibleFiles as $file)
        @php
          $typeIcon  = $file->file_type === 'recording' ? '🎬' : ($file->file_type === 'notes' ? '📄' : '📦');
          $typeLabel = ucfirst($file->file_type);
          $typeBg    = $file->file_type === 'recording'
            ? 'bg-purple-50 border-purple-200 hover:bg-purple-100'
            : ($file->file_type === 'notes'
              ? 'bg-blue-50 border-blue-200 hover:bg-blue-100'
              : 'bg-gray-50 border-gray-200 hover:bg-gray-100');
          $typeText  = $file->file_type === 'recording'
            ? 'text-purple-700'
            : ($file->file_type === 'notes' ? 'text-blue-700' : 'text-gray-700');
        @endphp
        <a href="{{ route('vendor.portal.live_class.file.view', [$vendor->slug, $liveClass->slug, $file->id]) }}"
           target="_blank"
           class="flex items-center justify-between gap-3 px-4 py-3 border rounded-xl transition {{ $typeBg }}">
          <div class="flex items-center gap-3 min-w-0">
            <span class="text-2xl flex-shrink-0">{{ $typeIcon }}</span>
            <div class="min-w-0">
              <div class="text-sm font-semibold {{ $typeText }} truncate">{{ $file->title }}</div>
              <div class="text-xs text-gray-400">{{ $typeLabel }} &middot; {{ $file->humanSize() }}</div>
            </div>
          </div>
          <div class="flex-shrink-0 {{ $typeText }} font-bold text-xs">View →</div>
        </a>
        @endforeach
      </div>
      @endif

    </div>
    @empty
    <div class="text-center py-10 text-gray-400">
      <div class="text-4xl mb-2">📋</div>
      <p class="text-sm">No sessions completed yet. First class coming up!</p>
    </div>
    @endforelse
  </div>

</div>

{{-- Auto-refresh when live --}}
@if($liveClass->status === 'live')
<script>setTimeout(() => location.reload(), 30000);</script>
@endif

</body>
</html>
