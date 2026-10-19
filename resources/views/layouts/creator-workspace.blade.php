<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Course editor — Skillspot')</title>
  <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('styles')
</head>
<body class="creator-course-workspace bg-white font-sans text-gray-900 antialiased">
  <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to main content</a>
  <div class="flex h-screen min-w-0 flex-col overflow-hidden">
    <header class="flex h-12 shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5">
      <div class="min-w-0"><h1 class="truncate text-sm font-bold">@yield('page-title')</h1></div>
      <div class="flex shrink-0 items-center gap-2">@yield('page-actions')</div>
    </header>
    <main id="main-content" tabindex="-1" class="min-h-0 flex-1 overflow-hidden"><div>@yield('creator-content')</div></main>
  </div>
  @stack('scripts')
</body>
</html>
