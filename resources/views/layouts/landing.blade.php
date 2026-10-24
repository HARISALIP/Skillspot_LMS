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
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to main content</a>

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

      <div class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
        <a href="/#courses" class="hover:text-brand-600 transition">Courses</a>
        <a href="https://skillspot.in/internship" target="_blank" class="hover:text-indigo-600 transition flex items-center gap-1.5"><i class="fa-solid fa-graduation-cap text-indigo-600"></i> Web Dev Internships <span class="text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-bold">100+</span></a>
        <a href="https://skillspot.in/business" target="_blank" class="hover:text-emerald-600 transition flex items-center gap-1.5"><i class="fa-solid fa-briefcase text-emerald-600"></i> WFH Jobs <span class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded font-bold">100+</span></a>
      </div>

      <div class="hidden md:flex items-center gap-3">
        @auth
          <a href="{{ auth()->user()->hasRole('student') ? route('student.dashboard') : (auth()->user()->hasRole('teacher') ? route('vendor.teacher.dashboard') : route('admin.dashboard')) }}" class="text-sm bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-lg shadow-brand-600/20 flex items-center gap-2">
            <i class="fa-solid fa-gauge-high text-xs"></i> <span>My Dashboard</span>
          </a>
        @else
          <a href="/login" class="text-sm font-semibold text-slate-700 hover:text-brand-600 transition px-4 py-2">Login</a>
          <a href="/register" class="text-sm bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-lg shadow-brand-600/20">
            Join Free
          </a>
        @endauth
      </div>

      <button id="hamburger" class="md:hidden text-slate-700 hover:text-slate-900 p-2 rounded-xl focus:outline-none" aria-label="Open menu">
        <svg id="icon-open" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <svg id="icon-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
  </div>

  <div id="mobile-menu" class="md:hidden bg-white/98 border-t border-slate-200/80 shadow-xl">
    <div class="px-4 py-4 flex flex-col gap-1 text-sm font-medium">
      <a href="/#courses" class="text-slate-700 hover:text-brand-600 px-3 py-2.5 rounded-xl hover:bg-slate-50 transition">📚 Courses</a>
      <a href="https://skillspot.in/internship" target="_blank" class="text-slate-700 hover:text-indigo-600 px-3 py-2.5 rounded-xl hover:bg-indigo-50 transition flex items-center justify-between"><span>🎓 Web Dev Internships</span> <span class="text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-bold">100+</span></a>
      <a href="https://skillspot.in/business" target="_blank" class="text-slate-700 hover:text-emerald-600 px-3 py-2.5 rounded-xl hover:bg-emerald-50 transition flex items-center justify-between"><span>💼 WFH &amp; Business Jobs</span> <span class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded font-bold">100+</span></a>
      <div class="border-t border-slate-100 mt-2 pt-3 flex flex-col gap-2">
        @auth
          <a href="{{ auth()->user()->hasRole('student') ? route('student.dashboard') : (auth()->user()->hasRole('teacher') ? route('vendor.teacher.dashboard') : route('admin.dashboard')) }}" class="text-center bg-brand-600 hover:bg-brand-700 text-white px-4 py-3 rounded-xl font-bold transition shadow-md shadow-brand-600/20">
            Go to Dashboard →
          </a>
        @else
          <a href="/login" class="text-center text-slate-700 border border-slate-200 px-4 py-3 rounded-xl font-semibold transition hover:bg-slate-50">Login</a>
          <a href="/register" class="text-center bg-brand-600 hover:bg-brand-700 text-white px-4 py-3 rounded-xl font-bold transition shadow-md shadow-brand-600/20">Start Learning →</a>
        @endauth
      </div>
    </div>
  </div>
</nav>

<main id="main-content" tabindex="-1" class="flex-1">
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
          <li><a href="{{ route('marketplace.browse') }}" class="text-slate-300 hover:text-brand-400 transition">Browse the course catalog</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-bold mb-4 text-sm">Student Hub</h4>
        <ul class="space-y-2.5 text-sm">
          <li><a href="/register" class="text-slate-300 hover:text-brand-400 transition">Register Free</a></li>
          <li><a href="/login" class="text-slate-300 hover:text-brand-400 transition">Student Login</a></li>
          <li><a href="/#how-it-works" class="text-slate-300 hover:text-brand-400 transition">How it Works</a></li>
          <li><a href="/#courses" class="text-slate-300 hover:text-brand-400 transition">Browse Courses</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-bold mb-4 text-sm">Company</h4>
        <ul class="space-y-2.5 text-sm">
          <li><a href="https://skillspot.in/internship" target="_blank" class="text-slate-300 hover:text-indigo-400 transition flex items-center gap-1.5">Web Dev Internships <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-1.5 py-0.5 rounded font-bold">100+</span></a></li>
          <li><a href="https://skillspot.in/business" target="_blank" class="text-slate-300 hover:text-emerald-400 transition flex items-center gap-1.5">WFH &amp; Business <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.5 rounded font-bold">100+</span></a></li>
          <li><a href="https://skillspot.in/careers" target="_blank" class="text-slate-300 hover:text-brand-400 transition">Careers Hub</a></li>
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
        <a href="mailto:info@skillspot.in" class="hover:text-brand-400 transition">info@skillspot.in</a>
      </div>
    </div>
  </div>
</footer>

@include('partials.ui-icon-normalizer')
@stack('scripts')
</body>
</html>
