<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.text_direction') === 'auto' ? 'auto' : config('app.text_direction', 'ltr') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Skillspot.in — Professional IT & Programming courses. Learn. Build. Grow.">
  <title>@yield('title', 'Skillspot.in')</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
  @include('partials.turbo')
  <style>
    .gradient-hero  { background: linear-gradient(135deg, #f8fafc 0%, #edf4ff 50%, #f5f3ff 100%); }
    .gradient-text  { background: linear-gradient(90deg, #2563eb, #7c3aed); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    .card-hover     { transition: transform .3s ease, box-shadow .3s ease; }
    .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(37,99,235,.08); }
    .glass          { backdrop-filter: blur(16px); background: rgba(255,255,255,.85); border: 1px solid rgba(226,232,240,.8); }
    .nav-solid      { background: rgba(255,255,255,.96) !important; border-bottom: 1px solid rgba(226,232,240,.9); box-shadow: 0 4px 20px -2px rgba(0,0,0,.05); }
    .text-balance   { text-wrap: balance; }
    #mobile-menu    { display: none; }
    #mobile-menu.open { display: block; }
    html { scroll-behavior: smooth; }
  </style>
  @stack('styles')
</head>
<body class="font-sans bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

<!-- ── NAVBAR ──────────────────────────────────────────────────────────── -->
<nav id="navbar" class="fixed top-0 w-full z-50 glass transition-all duration-300">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      <a href="/" class="flex items-center gap-3 flex-shrink-0">
        <div class="w-10 h-10 bg-gradient-to-br from-brand-600 to-accent-600 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-lg shadow-brand-500/20">S</div>
        <div class="leading-tight">
          <span class="text-slate-900 font-black text-base block leading-none tracking-tight">Skillspot.in</span>
          <span class="text-brand-600 text-[10px] font-extrabold tracking-[0.2em] uppercase">LMS</span>
        </div>
      </a>

      <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
        <a href="#courses" class="hover:text-brand-600 transition">Courses</a>
      </div>

      <div class="hidden md:flex items-center gap-3">
        <a href="/login" class="text-sm font-semibold text-slate-700 hover:text-brand-600 transition px-4 py-2">Login</a>
        <a href="/register" class="text-sm bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-lg shadow-brand-600/20">
          Join Free
        </a>
      </div>

      <button id="hamburger" class="md:hidden text-slate-700 hover:text-slate-900 p-2 rounded-xl focus:outline-none" aria-label="Open menu">
        <svg id="icon-open" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <svg id="icon-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
  </div>

  <div id="mobile-menu" class="md:hidden bg-white/98 border-t border-slate-200/80 shadow-xl">
    <div class="px-4 py-4 flex flex-col gap-1 text-sm font-medium">
      <a href="#courses" class="text-slate-700 hover:text-brand-600 px-3 py-2.5 rounded-xl hover:bg-slate-50 transition">📚 Courses</a>
      <div class="border-t border-slate-100 mt-2 pt-3 flex flex-col gap-2">
        <a href="/login" class="text-center text-slate-700 border border-slate-200 px-4 py-3 rounded-xl font-semibold transition hover:bg-slate-50">Login</a>
        <a href="/register" class="text-center bg-brand-600 hover:bg-brand-700 text-white px-4 py-3 rounded-xl font-bold transition shadow-md shadow-brand-600/20">Start Learning →</a>
      </div>
    </div>
  </div>
</nav>

<main class="flex-1">
  @yield('content')
</main>

<!-- ── FOOTER ──────────────────────────────────────────────────────────── -->
<footer class="bg-slate-950 text-slate-300 pt-12 pb-8 mt-auto">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-10 pb-8 border-b border-slate-800">
      <div class="lg:col-span-2">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 bg-gradient-to-br from-brand-600 to-accent-600 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-lg shadow-brand-500/20">S</div>
          <div>
            <span class="text-white font-black text-base block leading-none">Skillspot.in</span>
            <span class="text-brand-400 text-[10px] font-extrabold tracking-[0.2em] uppercase">LMS</span>
          </div>
        </div>
        <p class="text-sm text-slate-400 leading-relaxed max-w-md">Professional learning for the next generation of developers, analysts, and digital professionals. Learn practical skills and build a career you can be proud of.</p>
      </div>

      <div>
        <h4 class="text-white font-bold mb-4 text-sm">Programs</h4>
        <ul class="space-y-2.5 text-sm">
          <li><a href="#courses" class="text-slate-300 hover:text-brand-400 transition">Web Development</a></li>
          <li><a href="#courses" class="text-slate-300 hover:text-brand-400 transition">Cybersecurity</a></li>
          <li><a href="#courses" class="text-slate-300 hover:text-brand-400 transition">Python & AI</a></li>
          <li><a href="#courses" class="text-slate-300 hover:text-brand-400 transition">Cloud & DevOps</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-bold mb-4 text-sm">Student Hub</h4>
        <ul class="space-y-2.5 text-sm">
          <li><a href="/register" class="text-slate-300 hover:text-brand-400 transition">Register Free</a></li>
          <li><a href="/login" class="text-slate-300 hover:text-brand-400 transition">Login</a></li>
          <li><a href="#how-it-works" class="text-slate-300 hover:text-brand-400 transition">How it Works</a></li>
          <li><a href="#about" class="text-slate-300 hover:text-brand-400 transition">Why Skillspot</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-bold mb-4 text-sm">Company</h4>
        <ul class="space-y-2.5 text-sm">
          <li><a href="/about" class="text-slate-300 hover:text-brand-400 transition">About</a></li>
          <li><a href="/contact" class="text-slate-300 hover:text-brand-400 transition">Contact</a></li>
          <li><a href="/privacy" class="text-slate-300 hover:text-brand-400 transition">Privacy</a></li>
          <li><a href="/terms" class="text-slate-300 hover:text-brand-400 transition">Terms</a></li>
        </ul>
      </div>
    </div>

    <div class="pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-slate-400">
      <p>© {{ date('Y') }} Skillspot.in. All rights reserved.</p>
      <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4 text-center">
        <a href="mailto:hello@skillspot.in" class="hover:text-brand-400 transition">hello@skillspot.in</a>
        <span class="hidden sm:inline">•</span>
        <a href="tel:+919999999999" class="hover:text-brand-400 transition">+91 99999 99999</a>
      </div>
    </div>
  </div>
</footer>

@include('partials.ui-icon-normalizer')
@stack('scripts')
</body>
</html>
