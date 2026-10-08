<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $vendor->brand_name }}</title>
  @if($vendor->favicon)<link rel="icon" href="{{ Storage::disk('public')->url($vendor->favicon) }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --primary: {{ $vendor->primary_color ?? '#2563eb' }}; --accent: {{ $vendor->accent_color ?? '#7c3aed' }}; }
    .btn-primary { background: var(--primary); }
    .btn-primary:hover { opacity: 0.9; }
    .hero-bg { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); }
    .tag-bg { background: color-mix(in srgb, var(--primary) 10%, white); color: var(--primary); }
  </style>
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased">

<!-- Nav -->
<nav class="bg-white border-b border-gray-100 sticky top-0 z-20">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <div class="flex items-center gap-3">
      @if($vendor->logo)
        <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-9 h-9 rounded-xl object-cover">
      @else
        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black" style="background: {{ $vendor->primary_color }}">{{ strtoupper(substr($vendor->brand_name,0,1)) }}</div>
      @endif
      <span class="font-bold text-gray-900 text-base">{{ $vendor->brand_name }}</span>
    </div>
    <div class="flex items-center gap-3">
      @auth
        <a href="{{ route('vendor.portal.dashboard', $vendor->slug) }}" class="btn-primary text-white text-sm font-semibold px-4 py-2 rounded-xl transition">My Dashboard</a>
        <form method="POST" action="{{ route('vendor.portal.logout', $vendor->slug) }}" class="inline">
          @csrf
          <button class="text-sm text-gray-500 hover:text-red-600 transition ml-1">Sign out</button>
        </form>
      @else
        <a href="{{ route('vendor.portal.login', $vendor->slug) }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Login</a>
        <a href="{{ route('vendor.portal.register.general', $vendor->slug) }}" class="btn-primary text-white text-sm font-semibold px-4 py-2 rounded-xl transition ml-1">Register</a>
      @endauth
    </div>
  </div>
</nav>

<!-- Hero -->
<div class="hero-bg text-white">
  @if($vendor->banner_image)
    <div class="relative">
      <img src="{{ Storage::disk('public')->url($vendor->banner_image) }}" class="w-full h-64 object-cover opacity-30">
      <div class="absolute inset-0 flex items-center justify-center text-center px-6">
        <div>
          <h1 class="text-4xl font-black">{{ $vendor->brand_name }}</h1>
          @if($vendor->tagline)<p class="text-lg text-white/80 mt-2">{{ $vendor->tagline }}</p>@endif
        </div>
      </div>
    </div>
  @else
    <div class="max-w-6xl mx-auto px-6 py-16 text-center">
      <h1 class="text-4xl font-black">{{ $vendor->brand_name }}</h1>
      @if($vendor->tagline)<p class="text-lg text-white/80 mt-3">{{ $vendor->tagline }}</p>@endif
      @if($vendor->description)<p class="text-white/70 mt-4 max-w-2xl mx-auto">{{ $vendor->description }}</p>@endif
    </div>
  @endif
</div>

<!-- Courses -->
<div class="max-w-6xl mx-auto px-6 py-12">
  <h2 class="text-2xl font-black text-gray-900 mb-6">Available Courses</h2>
  @if($courses->count())
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($courses as $course)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition">
      @if($course->thumbnail)
        <img src="{{ $course->thumbnail }}" class="w-full h-40 object-cover">
      @else
        <div class="w-full h-40 hero-bg flex items-center justify-center text-white text-4xl font-black">{{ strtoupper(substr($course->title,0,1)) }}</div>
      @endif
      <div class="p-5">
        <div class="flex items-center gap-2 mb-2">
          <span class="tag-bg text-xs font-semibold px-2 py-0.5 rounded-full">{{ ucfirst(str_replace('_',' ',$course->course_type)) }}</span>
          @if($course->batch_name)<span class="bg-purple-50 text-purple-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $course->batch_name }}</span>@endif
        </div>
        <h3 class="font-bold text-gray-900 text-base leading-snug">{{ $course->title }}</h3>
        @if($course->description)<p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $course->description }}</p>@endif
        <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
          <span>{{ $course->enrollments_count }} enrolled</span>
          @if($course->max_students)<span>{{ max(0, $course->max_students - $course->enrollments_count) }} seats left</span>@endif
        </div>
        @if($course->batch_start_date)
          <div class="text-xs text-gray-400 mt-1">📅 {{ $course->batch_start_date->format('d M Y') }} → {{ $course->batch_end_date?->format('d M Y') ?? 'TBD' }}</div>
        @endif
        <div class="mt-4">
          <a href="{{ route('vendor.portal.register', [$vendor->slug, $course->id]) }}" class="block w-full text-center btn-primary text-white py-2.5 rounded-xl font-semibold text-sm transition">
            Register Now
          </a>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @else
  <div class="text-center py-16 text-gray-400">No courses available yet. Check back soon!</div>
  @endif
</div>

<!-- Footer -->
<footer class="bg-white border-t border-gray-100 mt-8 py-6">
  <div class="max-w-6xl mx-auto px-6 flex items-center justify-between text-sm text-gray-400">
    <span>© {{ date('Y') }} {{ $vendor->brand_name }}</span>
    <div class="flex items-center gap-4">
      @if($vendor->email)<a href="mailto:{{ $vendor->email }}" class="hover:text-gray-600">{{ $vendor->email }}</a>@endif
      @if($vendor->phone)<span>{{ $vendor->phone }}</span>@endif
    </div>
  </div>
</footer>
</body>
</html>
