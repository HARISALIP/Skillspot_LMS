<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $lesson->title }} — {{ $vendor->brand_name }}</title>
  @if($vendor->favicon)<link rel="icon" href="{{ Storage::disk('public')->url($vendor->favicon) }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>:root { --primary: {{ $vendor->primary_color ?? '#2563eb' }}; } .btn-primary { background: var(--primary); }</style>
</head>
<body class="font-sans bg-gray-900 text-white antialiased min-h-screen flex flex-col items-center justify-center p-6">
  <div class="text-center max-w-lg w-full">
    <div class="mb-6">
      <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">{{ $vendor->brand_name }}</div>
      <h1 class="text-2xl font-black">{{ $lesson->title }}</h1>
      <p class="text-gray-400 mt-1">{{ $lesson->section->course->title ?? '' }} · {{ ucfirst($lesson->live_platform) }}</p>
    </div>

    @php $now = now(); $start = $lesson->live_scheduled_at; $end = $start?->copy()->addMinutes($lesson->live_duration_min ?? 60); $isLive = $now->between($start, $end); @endphp

    @if($isLive)
      <div class="flex items-center justify-center gap-2 mb-6">
        <span class="w-3 h-3 rounded-full bg-red-500 animate-ping"></span>
        <span class="text-red-400 font-bold">LIVE NOW</span>
      </div>
      @if($lesson->live_share_link)
        <a href="{{ $lesson->live_share_link }}" target="_blank" class="btn-primary text-white text-lg font-bold px-8 py-4 rounded-2xl inline-block hover:opacity-90 transition mb-4">
          Join Live Class →
        </a>
      @else
        <a href="{{ $lesson->live_url }}" target="_blank" class="btn-primary text-white text-lg font-bold px-8 py-4 rounded-2xl inline-block hover:opacity-90 transition mb-4">
          Join Live Class →
        </a>
      @endif
      @if($lesson->live_meeting_id)
        <p class="text-gray-400 text-sm mt-3">Meeting ID: <span class="font-mono text-white">{{ $lesson->live_meeting_id }}</span></p>
      @endif
      @if($lesson->live_password)
        <p class="text-gray-400 text-sm">Password: <span class="font-mono text-white">{{ $lesson->live_password }}</span></p>
      @endif
    @elseif($now->lt($start))
      <div class="bg-gray-800 rounded-2xl p-6 mb-6">
        <div class="text-gray-400 text-sm mb-1">Class starts at</div>
        <div class="text-2xl font-black text-white">{{ $start->format('d M Y, h:i A') }}</div>
        <div class="text-gray-400 text-sm mt-1">Duration: {{ $lesson->live_duration_min }} minutes</div>
      </div>
      <p class="text-gray-400 text-sm">This page will show a join button when the class starts. Refresh to check.</p>
    @else
      <div class="bg-gray-800 rounded-2xl p-6">
        <p class="text-gray-400">This live session has ended.</p>
        @if($lesson->recording_shared && $lesson->recording_url)
          <a href="{{ route('vendor.portal.recording', [$vendor->slug, $lesson->id]) }}" class="btn-primary text-white font-semibold px-6 py-3 rounded-xl mt-4 inline-block transition">Watch Recording</a>
        @endif
      </div>
    @endif

    <div class="mt-8">
      <a href="{{ route('vendor.portal.dashboard', $vendor->slug) }}" class="text-gray-400 hover:text-white text-sm transition">← Back to Dashboard</a>
    </div>
  </div>
</body>
</html>
