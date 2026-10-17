<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', $vendor->brand_name)</title>
  @if($vendor->favicon)<link rel="icon" href="{{ Storage::disk('public')->url($vendor->favicon) }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
  @include('partials.turbo')
  <style>
    :root { --primary: {{ $vendor->primary_color ?? '#2563eb' }}; --accent: {{ $vendor->accent_color ?? '#7c3aed' }}; }
    .hero-bg { background: linear-gradient(135deg, var(--primary), var(--accent)); }
    body { font-family: 'Inter', sans-serif; }
  </style>
  @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to main content</a>
<nav aria-label="Portal navigation" class="sticky top-0 z-20 border-b border-gray-100 bg-white">
  <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
    <a href="{{ route('vendor.portal.dashboard', $vendor->slug) }}" class="flex items-center gap-3 font-bold text-gray-900">
      <span aria-hidden="true" class="hero-bg flex h-9 w-9 items-center justify-center rounded-xl font-black text-white">{{ strtoupper(substr($vendor->brand_name, 0, 1)) }}</span>
      <span>{{ $vendor->brand_name }}</span>
    </a>
    <div class="flex items-center gap-3 text-sm">
      <span class="hidden text-gray-600 sm:block">{{ auth()->user()->name }}</span>
      <form method="POST" action="{{ route('vendor.portal.logout', $vendor->slug) }}">@csrf
        <button class="text-gray-500 transition hover:text-red-600">Sign out</button>
      </form>
    </div>
  </div>
</nav>
<main id="main-content" tabindex="-1">@yield('content')</main>
@stack('scripts')
</body>
</html>
