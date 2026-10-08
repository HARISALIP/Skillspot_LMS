@extends('layouts.vendor')
@section('title', $liveClass->title . ' — Manage')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-5">

  {{-- ── Header ── --}}
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <a href="{{ route('vendor.live_classes.index') }}" class="text-sm text-gray-400 hover:text-gray-600 transition">← Live Classes</a>
      <h1 class="text-2xl font-black text-gray-900 mt-1">{{ $liveClass->title }}</h1>
      <div class="flex flex-wrap gap-2 mt-2">
        @if($liveClass->status === 'live')
          <span class="bg-red-100 text-red-600 text-xs font-bold px-3 py-1 rounded-full animate-pulse">🔴 LIVE NOW</span>
        @elseif($liveClass->status === 'completed')
          <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">✅ Completed</span>
        @elseif($liveClass->status === 'cancelled')
          <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-3 py-1 rounded-full">Cancelled</span>
        @else
          <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full">Active</span>
        @endif
        @if($liveClass->schedule_type === 'hours_based')
          <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full">⏱ Hours Based</span>
        @else
          <span class="bg-amber-100 text-amber-700 text-xs font-semibold px-3 py-1 rounded-full">📅 Batch</span>
        @endif
        <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-3 py-1 rounded-full">🖥 {{ ucfirst(str_replace('_',' ',$liveClass->platform)) }}</span>
      </div>
    </div>

    {{-- Start / Stop --}}
    @if(!in_array($liveClass->status, ['completed','cancelled']))
      @if($liveClass->status === 'live')
        <form method="POST" action="{{ route('vendor.live_classes.stop', $liveClass->id) }}">
          @csrf
          <button onclick="return confirm('Stop this session?')"
                  class="bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm">
            ⏹ Stop Session
          </button>
        </form>
      @else
        <div x-data="{open:false}">
          <button @click="open=true" class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm">
            ▶ Start Session
          </button>
          {{-- Start modal --}}
          <div x-show="open" x-transition
               class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
               @keydown.escape.window="open=false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4" @click.stop>
              <h3 class="font-black text-gray-900 text-lg">▶ Start Live Session</h3>
              <form method="POST" action="{{ route('vendor.live_classes.start', $liveClass->id) }}" class="space-y-4">
                @csrf
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-1.5">Module Name <span class="text-gray-400 font-normal">(optional)</span></label>
                  <input type="text" name="module_name" placeholder="e.g. Module 1 — Networking Basics"
                         class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-1.5">Session Name <span class="text-gray-400 font-normal">(optional)</span></label>
                  <input type="text" name="session_name" placeholder="e.g. Session 1 — OSI Model"
                         class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                  <p class="text-xs text-gray-400 mt-1">Auto-fills upload title when session ends.</p>
                </div>
                <div class="flex gap-3 pt-1">
                  <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl transition">
                    Go Live
                  </button>
                  <button type="button" @click="open=false" class="px-5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition">
                    Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      @endif
    @endif
  </div>

  {{-- Flash messages --}}
  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
  @endif

  {{-- ── Stats Row ── --}}
  @php
    $completedH = (float) $liveClass->completed_hours;
    $totalH     = (float) $liveClass->total_hours;
    $remainH    = (float) $liveClass->remainingHours();
    $pct        = $liveClass->progressPercent();
  @endphp
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    @if($liveClass->schedule_type === 'hours_based')
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
      <div class="text-2xl font-black text-blue-600">{{ rtrim(rtrim(number_format($completedH,2),'0'),'.') }}h</div>
      <div class="text-xs text-gray-500 mt-0.5">Completed</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
      <div class="text-2xl font-black text-gray-700">{{ rtrim(rtrim(number_format($totalH,2),'0'),'.') }}h</div>
      <div class="text-xs text-gray-500 mt-0.5">Total Target</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
      <div class="text-2xl font-black {{ $remainH > 0 ? 'text-amber-500' : 'text-green-600' }}">{{ rtrim(rtrim(number_format($remainH,2),'0'),'.') }}h</div>
      <div class="text-xs text-gray-500 mt-0.5">Remaining</div>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center col-span-2">
      <div class="text-sm font-bold text-gray-700">{{ $liveClass->batch_name ?? 'No batch name' }}</div>
      <div class="text-xs text-gray-400 mt-0.5">
        {{ $liveClass->batch_start_date?->format('d M Y') ?? '—' }} → {{ $liveClass->batch_end_date?->format('d M Y') ?? 'TBD' }}
      </div>
    </div>
    @endif
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
      <div class="text-2xl font-black text-gray-700">{{ $liveClass->sessions->count() }}</div>
      <div class="text-xs text-gray-500 mt-0.5">Sessions</div>
    </div>
  </div>

  {{-- Hours progress bar --}}
  @if($liveClass->schedule_type === 'hours_based' && $totalH > 0)
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4">
    <div class="flex justify-between text-sm mb-2">
      <span class="font-semibold text-gray-700">Overall Progress</span>
      <span class="text-gray-500 font-semibold">{{ $pct }}%</span>
    </div>
    <div class="w-full bg-gray-100 rounded-full h-3">
      <div class="h-3 rounded-full bg-blue-500 transition-all" style="width:{{ $pct }}%"></div>
    </div>
    <div class="flex justify-between text-xs text-gray-400 mt-1.5">
      <span>{{ rtrim(rtrim(number_format($completedH,2),'0'),'.') }}h done</span>
      <span>{{ rtrim(rtrim(number_format($remainH,2),'0'),'.') }}h remaining</span>
    </div>
  </div>
  @endif

  {{-- ── Share Link Card (active classes only) ── --}}
  @if(!in_array($liveClass->status, ["completed","cancelled"]))
  @php
    $shareUrl  = route('vendor.portal.live_class.detail', [$vendor->slug, $liveClass->slug]);
    $portalUrl = route('vendor.portal.home', $vendor->slug);
    $waText    = urlencode('Join my live class: ' . $liveClass->title . ' — ' . $shareUrl);
  @endphp
  <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-5">
    <h3 class="font-bold text-gray-800 mb-3 text-sm">🔗 Share with Students</h3>
    <div class="space-y-2 mb-4">
      <div class="flex items-center gap-2">
        <input type="text" id="shareLiveUrl" value="{{ $shareUrl }}" readonly
               class="flex-1 bg-white border border-blue-200 rounded-xl px-3 py-2 text-xs text-gray-600 font-mono focus:outline-none">
        <button onclick="copyLink('shareLiveUrl',this)"
                class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2 rounded-xl transition">Copy</button>
      </div>
      <div class="flex items-center gap-2">
        <input type="text" id="sharePortalUrl" value="{{ $portalUrl }}" readonly
               class="flex-1 bg-white border border-blue-200 rounded-xl px-3 py-2 text-xs text-gray-600 font-mono focus:outline-none">
        <button onclick="copyLink('sharePortalUrl',this)"
                class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2 rounded-xl transition">Copy</button>
      </div>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="https://wa.me/?text={{ $waText }}" target="_blank"
         class="inline-flex items-center gap-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        WhatsApp
      </a>
      <button onclick="copyAllDetails()"
              class="inline-flex items-center gap-1.5 bg-gray-700 hover:bg-gray-800 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
        📋 Copy All Details
      </button>
      @if($liveClass->meeting_url)
      <a href="{{ $liveClass->meeting_url }}" target="_blank"
         class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
        🔗 Open Meeting
      </a>
      @endif
    </div>
  </div>

  @endif

  {{-- ── Main 2-col grid ── --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Left: Sessions --}}
    <div class="lg:col-span-2 space-y-4">

      {{-- Meeting details (active classes only) --}}
      @if(!in_array($liveClass->status, ['completed','cancelled']) && ($liveClass->meeting_url || $liveClass->meeting_id || $liveClass->meeting_password))
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <h3 class="font-bold text-gray-800 text-sm mb-3">🔗 Meeting Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
          <div class="bg-gray-50 rounded-xl px-3 py-2">
            <div class="text-gray-400 mb-0.5">Platform</div>
            <div class="font-semibold text-gray-800">{{ ucfirst(str_replace('_',' ',$liveClass->platform)) }}</div>
          </div>
          @if($liveClass->meeting_id)
          <div class="bg-gray-50 rounded-xl px-3 py-2">
            <div class="text-gray-400 mb-0.5">Meeting ID</div>
            <div class="font-mono font-bold text-gray-800 select-all">{{ $liveClass->meeting_id }}</div>
          </div>
          @endif
          @if($liveClass->meeting_password)
          <div class="bg-gray-50 rounded-xl px-3 py-2">
            <div class="text-gray-400 mb-0.5">Password</div>
            <div class="font-mono font-bold text-gray-800 select-all">{{ $liveClass->meeting_password }}</div>
          </div>
          @endif
        </div>
        @if($liveClass->meeting_url)
        <div class="mt-2 text-xs text-blue-600 truncate">
          <a href="{{ $liveClass->meeting_url }}" target="_blank" class="hover:underline">{{ $liveClass->meeting_url }}</a>
        </div>
        @endif
      </div>
      @endif

      {{-- Session History --}}
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-gray-800">🕐 Session History</h3>
          @if($liveClass->schedule_type === 'date_based' && $liveClass->batch_start_date)
          <span class="text-xs bg-amber-100 text-amber-700 font-semibold px-3 py-1 rounded-full">
            📅 {{ $liveClass->batch_start_date->format('d M Y') }} → {{ $liveClass->batch_end_date?->format('d M Y') ?? 'Ongoing' }}
          </span>
          @elseif($liveClass->schedule_type === 'hours_based')
          <span class="text-xs bg-purple-100 text-purple-700 font-semibold px-3 py-1 rounded-full">
            ⏱ {{ rtrim(rtrim(number_format((float)$liveClass->completed_hours,2),'0'),'.') }}h / {{ rtrim(rtrim(number_format((float)$liveClass->total_hours,2),'0'),'.') }}h
          </span>
          @endif
        </div>
        @forelse($liveClass->sessions->load('files')->sortByDesc('started_at') as $session)
        <div class="py-4 border-b border-gray-50 last:border-0 space-y-3">

          {{-- Session header --}}
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              {{-- Module / Session name badges --}}
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
                {{ $session->started_at?->format('d M Y, h:i A') }}
                @if($session->status === 'live')
                  <span class="text-red-500 text-xs font-bold ml-1 animate-pulse">● LIVE</span>
                @endif
              </div>
              <div class="text-xs text-gray-400 mt-0.5">
                By {{ $session->teacher->name ?? 'Unknown' }}
                @if($session->stopped_at) &middot; Ended {{ $session->stopped_at->format('h:i A') }} @endif
              </div>
            </div>
            <div class="text-right flex-shrink-0">
              @if($session->duration_hours)
                <div class="text-base font-black text-blue-600">{{ rtrim(rtrim(number_format((float)$session->duration_hours,2),'0'),'.') }}h</div>
                <div class="text-xs text-gray-400">{{ $session->duration_minutes }} min</div>
              @elseif($session->status === 'live')
                <div class="text-xs text-red-500 font-semibold">In progress...</div>
              @else
                <div class="text-xs text-gray-400">0 min</div>
              @endif
            </div>
          </div>

          {{-- Uploaded files --}}
          @if($session->files->count())
          <div class="bg-gray-50 rounded-xl p-3 space-y-2">
            @foreach($session->files as $file)
            <div class="flex items-center justify-between gap-2 text-xs">
              <div class="flex items-center gap-2 min-w-0">
                <span class="text-base">{{ $file->file_type === 'recording' ? '🎬' : ($file->file_type === 'notes' ? '📄' : '📦') }}</span>
                <div class="min-w-0">
                  <div class="font-semibold text-gray-800 truncate">{{ $file->title }}</div>
                  <div class="text-gray-400">{{ $file->humanSize() }}
                    @if(!$file->is_visible) &middot; <span class="text-red-500 font-semibold">Hidden</span>@endif
                  </div>
                </div>
              </div>
              <div class="flex gap-1 flex-shrink-0">
                <a href="{{ route('vendor.live_classes.file.view', [$liveClass->id, $file->id]) }}" target="_blank"
                   class="bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold px-2 py-1 rounded-lg">View</a>
                <form method="POST" action="{{ route('vendor.live_classes.file.toggle', [$liveClass->id, $file->id]) }}" class="inline">
                  @csrf @method('PATCH')
                  <button class="{{ $file->is_visible ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }} font-semibold px-2 py-1 rounded-lg">
                    {{ $file->is_visible ? 'Hide' : 'Show' }}
                  </button>
                </form>
                <form method="POST" action="{{ route('vendor.live_classes.file.delete', [$liveClass->id, $file->id]) }}" class="inline">
                  @csrf @method('DELETE')
                  <button onclick="return confirm('Delete this file?')" class="bg-red-100 text-red-600 font-semibold px-2 py-1 rounded-lg">✕</button>
                </form>
              </div>
            </div>
            @endforeach
          </div>
          @endif

          {{-- Upload toggle (ended sessions only) --}}
          @if($session->status === 'ended')
          @php
            $autoTitle = trim(($session->module_name ? $session->module_name . ' - ' : '') . ($session->session_name ?: ''));
            $maxMb = (int) App\Models\Setting::get('max_upload_mb', 2048);
          @endphp
          <div x-data="{open:false}">
            <button @click="open=!open"
                    class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
              <span x-text="open ? '▲ Hide upload' : '+ Upload Recording / Notes'">+ Upload Recording / Notes</span>
            </button>
            <div x-show="open" x-transition class="mt-2 bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Type *</label>
                  <select id="fileType_{{ $session->id }}"
                          class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="recording">🎬 Recording</option>
                    <option value="notes">📄 Notes / PDF</option>
                    <option value="resource">📦 Resource</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Title <span class="text-gray-400 font-normal">(auto-filled)</span></label>
                  <input type="text" id="fileTitle_{{ $session->id }}" value="{{ $autoTitle }}"
                         placeholder="e.g. Session 1 Recording"
                         class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">File * <span class="text-gray-400 font-normal">(mp4, mkv, pdf, pptx, docx, zip — max {{ $maxMb }}MB)</span></label>
                <input type="file" id="fileInput_{{ $session->id }}"
                       accept=".mp4,.mkv,.webm,.avi,.mov,.pdf,.ppt,.pptx,.doc,.docx,.xls,.xlsx,.txt,.zip"
                       class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none">
              </div>

              {{-- Progress UI --}}
              <div id="progressWrap_{{ $session->id }}" class="hidden space-y-1.5">
                <div class="flex justify-between text-xs font-semibold text-gray-600">
                  <span id="progressLabel_{{ $session->id }}">Uploading...</span>
                  <span id="progressPct_{{ $session->id }}">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                  <div id="progressBar_{{ $session->id }}" class="h-3 rounded-full bg-blue-500 transition-all duration-200" style="width:0%"></div>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span id="progressSpeed_{{ $session->id }}" class="text-gray-400"></span>
                  <div class="flex gap-2">
                    <button onclick="pauseResumeUpload('{{ $session->id }}')"
                            class="bg-amber-100 hover:bg-amber-200 text-amber-700 font-semibold px-2.5 py-1 rounded-lg transition">⏸ Pause</button>
                    <button onclick="cancelUpload('{{ $session->id }}')"
                            class="bg-red-100 hover:bg-red-200 text-red-600 font-semibold px-2.5 py-1 rounded-lg transition">✕ Cancel</button>
                  </div>
                </div>
              </div>

              <div id="uploadSuccess_{{ $session->id }}" class="hidden bg-green-50 border border-green-200 text-green-800 rounded-lg px-3 py-2 text-xs font-semibold"></div>
              <div id="uploadError_{{ $session->id }}"   class="hidden bg-red-50 border border-red-200 text-red-700 rounded-lg px-3 py-2 text-xs"></div>

              <button id="uploadBtn_{{ $session->id }}"
                      onclick="startUpload('{{ $session->id }}','{{ route('vendor.live_classes.file.upload', [$liveClass->id, $session->id]) }}','{{ csrf_token() }}')"
                      class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-5 py-2.5 rounded-lg transition">
                Upload to Cloud
              </button>
            </div>
          </div>
          @endif

        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
          <div class="text-4xl mb-2">🎬</div>
          <p class="text-sm">No sessions yet. Hit Start Session to begin.</p>
        </div>
        @endforelse
      </div>
    </div>

    {{-- Right: Students --}}
    <div class="space-y-4">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-bold text-gray-800 mb-4">
          👥 Students
          <span class="text-gray-400 font-normal text-sm ml-1">
            {{ count($enrolledIds) }}{{ $liveClass->max_students ? ' / '.$liveClass->max_students : '' }}
          </span>
        </h3>

        {{-- Enroll form --}}
        @if(!in_array($liveClass->status, ['completed','cancelled']))
        <form method="POST" action="{{ route('vendor.live_classes.enroll', $liveClass->id) }}" class="mb-4 space-y-2">
          @csrf
          <select name="user_id" required
                  class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">— Select student —</option>
            @foreach($vendorStudents as $s)
              @if(!in_array($s->id, $enrolledIds))
              <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endif
            @endforeach
          </select>
          <button class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 rounded-xl transition">
            + Enroll Student
          </button>
        </form>
        @endif

        {{-- Enrolled list --}}
        <div class="space-y-1 max-h-72 overflow-y-auto">
          @forelse($liveClass->enrollments as $enrollment)
          <div class="flex items-center justify-between gap-2 px-2 py-2 rounded-xl hover:bg-gray-50 transition">
            <div class="min-w-0">
              <div class="text-sm font-semibold text-gray-800 truncate">{{ $enrollment->user->name }}</div>
              <div class="text-xs text-gray-400 truncate">{{ $enrollment->user->email }}</div>
            </div>
            <form method="POST" action="{{ route('vendor.live_classes.unenroll', [$liveClass->id, $enrollment->user_id]) }}">
              @csrf @method('DELETE')
              <button onclick="return confirm('Remove this student?')"
                      class="text-xs text-red-400 hover:text-red-600 hover:bg-red-50 px-2 py-1 rounded-lg transition">✕</button>
            </form>
          </div>
          @empty
          <div class="text-center py-6 text-gray-400">
            <div class="text-2xl mb-1">👤</div>
            <p class="text-xs">No students enrolled yet.</p>
          </div>
          @endforelse
        </div>
      </div>
    </div>

  </div>
</div>
@push('scripts')
<script>
function copyLink(inputId, btn) {
  const el = document.getElementById(inputId);
  el.select();
  navigator.clipboard.writeText(el.value).then(() => {
    const orig = btn.textContent;
    btn.textContent = 'Copied ✓';
    btn.classList.replace('bg-blue-600','bg-green-600');
    setTimeout(() => { btn.textContent = orig; btn.classList.replace('bg-green-600','bg-blue-600'); }, 2000);
  });
}
function copyAllDetails() {
  const liveUrl   = document.getElementById('shareLiveUrl').value;
  const portalUrl = document.getElementById('sharePortalUrl').value;
  const title     = {{ json_encode($liveClass->title) }};
  const mid       = {{ json_encode($liveClass->meeting_id ?? '') }};
  const mpw       = {{ json_encode($liveClass->meeting_password ?? '') }};
  let text = `📡 Live Class: ${title}\n\n🔗 Join Link: ${liveUrl}\n🌐 Portal: ${portalUrl}`;
  if (mid)  text += `\n📋 Meeting ID: ${mid}`;
  if (mpw)  text += `\n🔑 Password: ${mpw}`;
  navigator.clipboard.writeText(text).then(() => alert('Details copied to clipboard ✓'));
}
</script>
@endpush
@push('scripts')
<script>
// Upload state per session
const uploadState = {};

function startUpload(sid, url, token) {
  const fileInput = document.getElementById('fileInput_' + sid);
  const file = fileInput?.files[0];
  if (!file) { alert('Please select a file first.'); return; }

  const type  = document.getElementById('fileType_' + sid)?.value;
  const title = document.getElementById('fileTitle_' + sid)?.value;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('file_type', type);
  formData.append('title', title || file.name);
  formData.append('_token', token);

  const xhr = new XMLHttpRequest();
  uploadState[sid] = { xhr, paused: false, startTime: Date.now(), loaded: 0 };

  // Show progress UI
  document.getElementById('progressWrap_' + sid).classList.remove('hidden');
  document.getElementById('uploadBtn_' + sid).disabled = true;
  document.getElementById('uploadBtn_' + sid).textContent = 'Uploading...';
  document.getElementById('uploadError_' + sid).classList.add('hidden');
  document.getElementById('uploadSuccess_' + sid).classList.add('hidden');

  xhr.upload.addEventListener('progress', e => {
    if (!e.lengthComputable) return;
    const pct  = Math.round((e.loaded / e.total) * 100);
    const elapsed = (Date.now() - uploadState[sid].startTime) / 1000;
    const speed = elapsed > 0 ? formatBytes(e.loaded / elapsed) + '/s' : '';
    const remain = speed && e.total > e.loaded
      ? ' · ' + formatSecs(((e.total - e.loaded) / (e.loaded / elapsed))) + ' left'
      : '';

    document.getElementById('progressBar_' + sid).style.width = pct + '%';
    document.getElementById('progressPct_' + sid).textContent = pct + '%';
    document.getElementById('progressLabel_' + sid).textContent = formatBytes(e.loaded) + ' / ' + formatBytes(e.total);
    document.getElementById('progressSpeed_' + sid).textContent = speed + remain;
  });

  xhr.addEventListener('load', () => {
    const wrap = document.getElementById('progressWrap_' + sid);
    if (xhr.status >= 200 && xhr.status < 300) {
      wrap.classList.add('hidden');
      const msg = document.getElementById('uploadSuccess_' + sid);
      msg.textContent = '✅ Uploaded successfully! Refreshing...';
      msg.classList.remove('hidden');
      setTimeout(() => location.reload(), 1500);
    } else {
      wrap.classList.add('hidden');
      const err = document.getElementById('uploadError_' + sid);
      err.textContent = 'Upload failed (HTTP ' + xhr.status + '). Please try again.';
      err.classList.remove('hidden');
      resetUploadBtn(sid);
    }
    delete uploadState[sid];
  });

  xhr.addEventListener('error', () => {
    document.getElementById('progressWrap_' + sid).classList.add('hidden');
    const err = document.getElementById('uploadError_' + sid);
    err.textContent = 'Network error. Please check connection and try again.';
    err.classList.remove('hidden');
    resetUploadBtn(sid);
    delete uploadState[sid];
  });

  xhr.open('POST', url);
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.send(formData);
}

function pauseResumeUpload(sid) {
  // XHR doesn't support true pause — abort and show message
  const state = uploadState[sid];
  if (!state) return;
  const btn = document.getElementById('pauseBtn_' + sid);
  state.xhr.abort();
  document.getElementById('progressWrap_' + sid).classList.add('hidden');
  const err = document.getElementById('uploadError_' + sid);
  err.textContent = 'Upload paused/stopped. Select the file again to restart.';
  err.classList.remove('hidden');
  resetUploadBtn(sid);
  delete uploadState[sid];
}

function cancelUpload(sid) {
  const state = uploadState[sid];
  if (state) { state.xhr.abort(); delete uploadState[sid]; }
  document.getElementById('progressWrap_' + sid).classList.add('hidden');
  document.getElementById('uploadError_' + sid).classList.add('hidden');
  document.getElementById('uploadSuccess_' + sid).classList.add('hidden');
  resetUploadBtn(sid);
  const fi = document.getElementById('fileInput_' + sid);
  if (fi) fi.value = '';
}

function resetUploadBtn(sid) {
  const btn = document.getElementById('uploadBtn_' + sid);
  if (btn) { btn.disabled = false; btn.textContent = '☁️ Upload to Cloud'; }
}

function formatBytes(b) {
  if (b >= 1073741824) return (b/1073741824).toFixed(1)+' GB';
  if (b >= 1048576)    return (b/1048576).toFixed(1)+' MB';
  if (b >= 1024)       return (b/1024).toFixed(1)+' KB';
  return b+' B';
}

function formatSecs(s) {
  if (s >= 3600) return Math.floor(s/3600)+'h '+Math.floor((s%3600)/60)+'m';
  if (s >= 60)   return Math.floor(s/60)+'m '+Math.floor(s%60)+'s';
  return Math.floor(s)+'s';
}
</script>
@endpush
@endsection
