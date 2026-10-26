<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.text_direction') === 'auto' ? 'auto' : config('app.text_direction', 'ltr') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Skillspot.in LMS — Master In-Demand Tech Skills')</title>
  <meta name="description" content="@yield('meta_description', 'Practical, mentor-crafted online courses in web development, programming, AI, and digital skills. Learn at your own pace with hands-on projects and verified certificates.')">
  <meta name="keywords" content="@yield('meta_keywords', 'Skillspot, LMS, online courses, web development, programming, AI, python, robotics, certificates, tech skills')">
  <meta name="author" content="Skillspot.in">
  <link rel="canonical" href="@yield('canonical_url', url()->current())">

  {{-- Open Graph / Facebook / WhatsApp / Instagram --}}
  <meta property="og:type" content="@yield('og_type', 'website')">
  <meta property="og:site_name" content="Skillspot LMS">
  <meta property="og:url" content="@yield('canonical_url', url()->current())">
  <meta property="og:title" content="@yield('title', 'Skillspot.in LMS — Master In-Demand Tech Skills')">
  <meta property="og:description" content="@yield('meta_description', 'Practical, mentor-crafted online courses in web development, programming, AI, and digital skills. Learn at your own pace with hands-on projects and verified certificates.')">
  <meta property="og:image" content="@yield('og_image', asset('images/og-share-banner.jpg'))">
  <meta property="og:image:secure_url" content="@yield('og_image', asset('images/og-share-banner.jpg'))">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="675">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:alt" content="Skillspot.in LMS — Master In-Demand Tech Skills">

  {{-- Twitter / X Card --}}
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@skillspot">
  <meta name="twitter:title" content="@yield('title', 'Skillspot.in LMS — Master In-Demand Tech Skills')">
  <meta name="twitter:description" content="@yield('meta_description', 'Practical, mentor-crafted online courses in web development, programming, AI, and digital skills. Learn at your own pace with hands-on projects and verified certificates.')">
  <meta name="twitter:image" content="@yield('og_image', asset('images/og-share-banner.jpg'))">

  {{-- Favicon & Mobile App Icons --}}
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
  <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192.png') }}">
  <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('favicon-512.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <meta name="theme-color" content="#0f172a">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
  @include('partials.turbo')
  <style>
    .gradient-hero  { background: radial-gradient(circle at 50% 0%, #f1f5f9 0%, #f8fafc 50%, #ffffff 100%); }
    .card-hover     { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
    .card-hover:hover { transform: translateY(-4px); box-shadow: 0 16px 32px -8px rgba(15,23,42,.08); }
    .glass          { backdrop-filter: blur(16px); background: rgba(255,255,255,.92); border-bottom: 1px solid rgba(226,232,240,.9); }
    .text-balance   { text-wrap: balance; }
    #mobile-menu    { display: none; }
    #mobile-menu.open { display: block; }
    html { scroll-behavior: smooth; }
  </style>
  @stack('styles')
</head>
<body class="font-sans bg-slate-50/50 text-slate-800 antialiased min-h-screen flex flex-col selection:bg-blue-100 selection:text-blue-900">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to main content</a>

<!-- ── NAVBAR ──────────────────────────────────────────────────────────── -->
<nav id="navbar" class="fixed top-0 w-full z-50 glass transition-all duration-300">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      <a href="/" class="flex items-center gap-3 flex-shrink-0 group">
        <div class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center font-bold text-lg shadow-sm group-hover:bg-blue-600 transition">
          S
        </div>
        <div class="leading-tight">
          <span class="text-slate-900 font-extrabold text-base block leading-none tracking-tight">Skillspot.in</span>
          <span class="text-slate-500 text-[10px] font-semibold tracking-wider uppercase">Academy LMS</span>
        </div>
      </a>

      <div class="hidden md:flex items-center text-sm font-medium text-slate-600" style="gap: 2rem;">
        <a href="/#courses" class="hover:text-slate-900 transition py-1">Courses</a>
        <a href="https://skillspot.in/internship" target="_blank" class="hover:text-indigo-600 transition flex items-center gap-1.5 py-1">
          <i class="fa-solid fa-graduation-cap text-indigo-500 text-xs"></i>
          <span>Web Dev Internships</span>
          <span class="text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-200/70 px-1.5 py-0.5 rounded font-bold" style="margin-left: 4px;">100+</span>
        </a>
        <a href="https://skillspot.in/business" target="_blank" class="hover:text-emerald-600 transition flex items-center gap-1.5 py-1">
          <i class="fa-solid fa-briefcase text-emerald-500 text-xs"></i>
          <span>WFH Jobs</span>
          <span class="text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200/70 px-1.5 py-0.5 rounded font-bold" style="margin-left: 4px;">100+</span>
        </a>
      </div>

      <div class="hidden md:flex items-center" style="gap: 0.75rem;">
        @auth
          <a href="{{ auth()->user()->hasRole('student') ? route('student.dashboard') : (auth()->user()->hasRole('teacher') ? route('vendor.teacher.dashboard') : route('admin.dashboard')) }}" class="text-sm bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-xs flex items-center gap-2">
            <i class="fa-solid fa-gauge-high text-xs"></i> <span>Dashboard</span>
          </a>
        @else
          <a href="/login" class="text-sm font-semibold text-slate-700 hover:text-slate-900 transition px-3 py-2">Log in</a>
          <a href="/register" class="text-sm bg-slate-900 hover:bg-slate-800 active:scale-[0.98] text-white px-5 py-2.5 rounded-xl font-bold transition shadow-xs">
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

  {{-- Overhauled Mobile Menu (Clean, Modern & Functional) --}}
  <div id="mobile-menu" class="md:hidden bg-white/98 backdrop-blur-xl border-b border-slate-200/90 shadow-xl transition-all duration-200">
    <div class="px-5 py-4 flex flex-col" style="gap: 0.5rem;">
      <a href="/#courses" onclick="closeMobileMenu()" class="flex items-center gap-3.5 text-slate-800 hover:text-blue-600 px-3.5 py-2.5 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-200/60">
        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm shadow-2xs">
          <i class="fa-solid fa-book-open"></i>
        </div>
        <div class="flex-1">
          <span class="font-bold text-sm block text-slate-800">Browse Courses</span>
          <span class="text-[11px] text-slate-500 font-normal">Practical modules &amp; certificates</span>
        </div>
        <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
      </a>

      <a href="https://skillspot.in/internship" target="_blank" class="flex items-center justify-between text-slate-800 hover:text-indigo-600 px-3.5 py-2.5 rounded-xl hover:bg-indigo-50/40 transition border border-transparent hover:border-indigo-100">
        <div class="flex items-center gap-3.5">
          <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm shadow-2xs">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <div>
            <span class="font-bold text-sm block text-slate-800">Web Dev Internships</span>
            <span class="text-[11px] text-slate-500 font-normal">Hands-on project experience</span>
          </div>
        </div>
        <span class="text-[10px] bg-indigo-100 text-indigo-800 border border-indigo-200/60 px-2.5 py-0.5 rounded-full font-bold">100+</span>
      </a>

      <a href="https://skillspot.in/business" target="_blank" class="flex items-center justify-between text-slate-800 hover:text-emerald-600 px-3.5 py-2.5 rounded-xl hover:bg-emerald-50/40 transition border border-transparent hover:border-emerald-100">
        <div class="flex items-center gap-3.5">
          <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shadow-2xs">
            <i class="fa-solid fa-briefcase"></i>
          </div>
          <div>
            <span class="font-bold text-sm block text-slate-800">WFH Jobs</span>
            <span class="text-[11px] text-slate-500 font-normal">Remote roles &amp; tech listings</span>
          </div>
        </div>
        <span class="text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-200/60 px-2.5 py-0.5 rounded-full font-bold">100+</span>
      </a>

      <div class="border-t border-slate-200/80 mt-2 pt-3 flex flex-col" style="gap: 0.65rem;">
        @auth
          <a href="{{ auth()->user()->hasRole('student') ? route('student.dashboard') : (auth()->user()->hasRole('teacher') ? route('vendor.teacher.dashboard') : route('admin.dashboard')) }}" class="text-center bg-slate-900 hover:bg-slate-800 text-white px-4 py-3 rounded-xl font-bold transition shadow-xs text-sm">
            Go to Dashboard →
          </a>
        @else
          <div class="grid grid-cols-2" style="gap: 0.75rem;">
            <a href="/login" class="text-center text-slate-800 border border-slate-300 bg-white hover:bg-slate-50 px-4 py-2.5 rounded-xl font-semibold transition text-sm">Log in</a>
            <a href="/register" class="text-center bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl font-bold transition shadow-xs text-sm">Join Free</a>
          </div>
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
<script>
  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobile-menu');
  const iconOpen = document.getElementById('icon-open');
  const iconClose = document.getElementById('icon-close');
  
  function closeMobileMenu() {
    if (mobileMenu && mobileMenu.classList.contains('open')) {
      mobileMenu.classList.remove('open');
      iconOpen?.classList.remove('hidden');
      iconClose?.classList.add('hidden');
    }
  }
  window.closeMobileMenu = closeMobileMenu;

  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', () => {
      const isOpen = mobileMenu.classList.toggle('open');
      iconOpen?.classList.toggle('hidden', isOpen);
      iconClose?.classList.toggle('hidden', !isOpen);
    });
  }
</script>
@stack('scripts')
</body>
</html>
