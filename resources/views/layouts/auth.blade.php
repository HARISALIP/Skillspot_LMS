<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.text_direction') === 'auto' ? 'auto' : config('app.text_direction', 'ltr') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Skillspot.in LMS')</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
  @include('partials.turbo')
  <style>
    .gradient-bg { background: linear-gradient(135deg, #f8fafc 0%, #edf4ff 50%, #f5f3ff 100%); }
    .blob1 { position:fixed; width:400px; height:400px; background:#2563eb12; border-radius:50%; filter:blur(80px); top:-100px; right:-100px; pointer-events:none; }
    .blob2 { position:fixed; width:300px; height:300px; background:#7c3aed12; border-radius:50%; filter:blur(80px); bottom:-80px; left:-80px; pointer-events:none; }
  </style>
  @stack('styles')
</head>
<body class="font-sans gradient-bg min-h-screen flex flex-col antialiased text-slate-800">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to main content</a>
  <div class="blob1"></div>
  <div class="blob2"></div>

  <!-- Top bar -->
  <div class="relative z-10 flex items-center justify-between px-5 py-3 md:px-10">
    <a href="/" class="flex items-center gap-2.5">
      <div class="w-8 h-8 md:w-9 md:h-9 bg-gradient-to-br from-brand-600 to-accent-600 rounded-xl flex items-center justify-center text-white font-black text-base md:text-lg shadow-md shadow-brand-500/20">S</div>
      <span class="text-slate-900 font-bold text-lg md:text-xl">Skillspot.in <span class="text-brand-600 font-extrabold">LMS</span></span>
    </a>
    <div class="text-xs md:text-sm font-medium">@yield('top-link')</div>
  </div>

  <!-- Main -->
  <main id="main-content" tabindex="-1" class="relative z-10 flex-1 flex items-center justify-center px-4 py-2 md:py-4">
    <div class="w-full @yield('container-class', 'max-w-[420px]') bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-200/80 overflow-hidden transition-all duration-300">
      @yield('content')
    </div>
  </main>

  <!-- Footer -->
  <div class="relative z-10 text-center text-slate-400 text-xs py-2 md:py-3">
    © {{ date('Y') }} Skillspot.in LMS &nbsp;·&nbsp;
    <a href="/privacy" class="hover:text-brand-600 transition">Privacy</a> &nbsp;·&nbsp;
    <a href="/terms" class="hover:text-brand-600 transition">Terms</a>
  </div>

  @include('partials.ui-icon-normalizer')
  @stack('scripts')
</body>
</html>
