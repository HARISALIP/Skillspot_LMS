<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $lesson->title }} — Recording — {{ $vendor->brand_name }}</title>
  @if($vendor->favicon)<link rel="icon" href="{{ Storage::disk('public')->url($vendor->favicon) }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>:root { --primary: {{ $vendor->primary_color ?? '#2563eb' }}; }</style>
</head>
<body class="font-sans bg-gray-900 text-white antialiased min-h-screen flex flex-col items-center justify-center p-6">
  <div class="w-full max-w-3xl">
    <div class="mb-6 text-center">
      <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">{{ $vendor->brand_name }}</div>
      <h1 class="text-2xl font-black">{{ $lesson->title }}</h1>
      <p class="text-gray-400 mt-1">{{ $lesson->section->course->title ?? '' }} · Recorded {{ $lesson->live_scheduled_at?->format('d M Y') }}</p>
    </div>

    @php
      $url = $lesson->recording_url;
      $isYoutube = str_contains($url, 'youtube') || str_contains($url, 'youtu.be');
      $isDrive = str_contains($url, 'drive.google.com');
      if($isYoutube) {
        preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $m);
        $videoId = $m[1] ?? '';
        $embedUrl = "https://www.youtube.com/embed/{$videoId}";
      } elseif($isDrive) {
        $embedUrl = str_replace('/view', '/preview', $url);
      } else {
        $embedUrl = null;
      }
    @endphp

    @if($embedUrl)
      <div class="rounded-2xl overflow-hidden shadow-2xl bg-black aspect-video">
        <iframe src="{{ $embedUrl }}" class="w-full h-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
      </div>
    @else
      <div class="bg-gray-800 rounded-2xl p-8 text-center">
        <p class="text-gray-400 mb-4">Recording available at external link:</p>
        <a href="{{ $url }}" target="_blank" class="bg-white text-gray-900 font-bold px-6 py-3 rounded-xl hover:bg-gray-100 transition inline-block">Watch Recording →</a>
      </div>
    @endif

    <div class="mt-6 text-center">
      <a href="{{ route('vendor.portal.dashboard', $vendor->slug) }}" class="text-gray-400 hover:text-white text-sm transition">← Back to Dashboard</a>
    </div>
  </div>
</body>
</html>
