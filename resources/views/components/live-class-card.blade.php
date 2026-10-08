@props(['lesson'])

@php
  $now        = now();
  $scheduled  = $lesson->live_scheduled_at;
  $endTime    = $scheduled ? $scheduled->copy()->addMinutes($lesson->live_duration_min ?? 60) : null;
  $isLive     = $scheduled && $now->between($scheduled, $endTime);
  $isUpcoming = $scheduled && $now->lt($scheduled);
  $isPast     = $scheduled && $now->gt($endTime);
  $diffMin    = $scheduled ? (int)$now->diffInMinutes($scheduled, false) : null;

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
  $icon = $platformIcons[$lesson->live_platform ?? 'other'] ?? '📹';
  $name = $platformNames[$lesson->live_platform ?? 'other'] ?? 'Live Session';
@endphp

<div class="rounded-2xl border-2 p-5 {{ $isLive ? 'border-red-300 bg-red-50' : ($isUpcoming ? 'border-brand-200 bg-brand-50' : 'border-gray-200 bg-gray-50') }}">
  <div class="flex items-start justify-between gap-4 flex-wrap">
    <div class="flex items-start gap-3 flex-1 min-w-0">
      <span class="text-2xl flex-shrink-0">{{ $icon }}</span>
      <div class="min-w-0">
        <div class="font-bold text-gray-900 text-sm flex items-center gap-2 flex-wrap">
          {{ $lesson->title }}
          @if($isLive)
            <span class="inline-flex items-center gap-1 text-xs bg-red-500 text-white px-2 py-0.5 rounded-full font-bold animate-pulse">
              🔴 LIVE NOW
            </span>
          @elseif($isUpcoming && $diffMin !== null && $diffMin <= 30)
            <span class="inline-flex items-center gap-1 text-xs bg-orange-500 text-white px-2 py-0.5 rounded-full font-bold">
              ⏰ Starting soon
            </span>
          @endif
        </div>
        <div class="text-xs text-gray-500 mt-1">{{ $name }}</div>
        @if($scheduled)
        <div class="text-xs text-gray-600 mt-1 flex items-center gap-1.5">
          <span>📅</span>
          <span>{{ $scheduled->format('D, d M Y') }} at {{ $scheduled->format('h:i A') }}</span>
          <span class="text-gray-400">·</span>
          <span>{{ $lesson->live_duration_min ?? 60 }} min</span>
        </div>
        @endif
        @if($isUpcoming)
        <div class="text-xs font-semibold mt-1.5 text-brand-600" id="countdown-{{ $lesson->id }}"
             data-time="{{ $scheduled->timestamp }}">
          Calculating…
        </div>
        @endif
      </div>
    </div>

    {{-- Join button --}}
    <div class="flex-shrink-0">
      @if($isLive)
        <a href="{{ $lesson->live_url }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-black px-5 py-2.5 rounded-xl text-sm transition shadow-lg shadow-red-200 animate-pulse">
          🔴 Join Now
        </a>
      @elseif($isUpcoming)
        @if($diffMin !== null && $diffMin <= 15)
          <a href="{{ $lesson->live_url }}" target="_blank" rel="noopener"
             class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition shadow-lg">
            📡 Join Class
          </a>
        @else
          <button disabled
                  class="inline-flex items-center gap-2 bg-gray-200 text-gray-500 font-bold px-5 py-2.5 rounded-xl text-sm cursor-not-allowed">
            🔒 Opens 15min before
          </button>
        @endif
      @elseif($isPast)
        <span class="text-xs text-gray-400 font-medium">✅ Class ended</span>
      @else
        <a href="{{ $lesson->live_url }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 font-bold px-5 py-2.5 rounded-xl text-sm transition">
          📡 Join
        </a>
      @endif
    </div>
  </div>

  {{-- Meeting details for Zoom --}}
  @if($lesson->live_meeting_id || $lesson->live_password)
  <div class="mt-3 pt-3 border-t border-gray-200 flex flex-wrap gap-4 text-xs text-gray-600">
    @if($lesson->live_meeting_id)
    <div class="flex items-center gap-1.5">
      <span class="text-gray-400">#️⃣ Meeting ID:</span>
      <span class="font-mono font-bold">{{ $lesson->live_meeting_id }}</span>
      <button onclick="navigator.clipboard.writeText('{{ $lesson->live_meeting_id }}').then(()=>{this.textContent='✅';setTimeout(()=>this.textContent='📋',2000)})"
              class="text-brand-600 hover:text-brand-700">📋</button>
    </div>
    @endif
    @if($lesson->live_password)
    <div class="flex items-center gap-1.5">
      <span class="text-gray-400">🔑 Password:</span>
      <span class="font-mono font-bold">{{ $lesson->live_password }}</span>
      <button onclick="navigator.clipboard.writeText('{{ $lesson->live_password }}').then(()=>{this.textContent='✅';setTimeout(()=>this.textContent='📋',2000)})"
              class="text-brand-600 hover:text-brand-700">📋</button>
    </div>
    @endif
  </div>
  @endif
</div>
@once
@push('scripts')
<script>
// Countdown timers for upcoming live classes
function updateCountdowns() {
  document.querySelectorAll('[id^=countdown-]').forEach(el => {
    const target = parseInt(el.dataset.time) * 1000;
    const diff   = target - Date.now();
    if (diff <= 0) { el.textContent = '🔴 Starting now!'; el.style.color = '#ef4444'; return; }
    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    if (d > 0)      el.textContent = ;
    else if (h > 0) el.textContent = ;
    else            el.textContent = ;
  });
}
setInterval(updateCountdowns, 1000);
updateCountdowns();
</script>
@endpush
@endonce
