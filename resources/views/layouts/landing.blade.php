<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Skillspot.in — Professional IT & Programming courses. Learn. Build. Grow.">
  <title>@yield('title', 'Skillspot.in')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter','sans-serif'] },
          colors: {
            brand:  { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
            accent: { 50:'#f5f3ff',100:'#ede9fe',200:'#ddd6fe',400:'#a78bfa',500:'#8b5cf6',600:'#7c3aed',700:'#6d28d9',900:'#4c1d95' }
          }
        }
      }
    }
  </script>
  <style>
    .gradient-hero  { background: linear-gradient(135deg, #f8fafc 0%, #edf4ff 50%, #f5f3ff 100%); }
    .gradient-text  { background: linear-gradient(90deg, #2563eb, #7c3aed); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    .card-hover     { transition: transform .3s ease, box-shadow .3s ease; }
    .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(37,99,235,.08); }
    .glass          { backdrop-filter: blur(16px); background: rgba(255,255,255,.85); border: 1px solid rgba(226,232,240,.8); }
    .nav-solid      { background: rgba(255,255,255,.96) !important; border-bottom: 1px solid rgba(226,232,240,.9); box-shadow: 0 4px 20px -2px rgba(0,0,0,.05); }
    #mobile-menu    { display: none; }
    #mobile-menu.open { display: block; }
    html { scroll-behavior: smooth; }
  </style>
  @stack('styles')
</head>
<body class="font-sans bg-slate-50 text-slate-800 antialiased">

<!-- ── NAVBAR ──────────────────────────────────────────────────────────── -->
<nav id="navbar" class="fixed top-0 w-full z-50 glass transition-all duration-300">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      <!-- Logo -->
      <a href="/" class="flex items-center gap-2.5 flex-shrink-0">
        <div class="w-9 h-9 bg-gradient-to-br from-brand-600 to-accent-600 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-md shadow-brand-500/20">S</div>
        <div class="leading-tight">
          <span class="text-slate-900 font-black text-base block leading-none tracking-tight">Skillspot.in</span>
          <span class="text-brand-600 text-xs font-bold tracking-wider">TECH ACADEMY</span>
        </div>
      </a>

      <!-- Desktop links -->
      <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
        <a href="#courses"      class="hover:text-brand-600 transition">Courses</a>
        <a href="#how-it-works" class="hover:text-brand-600 transition">How it Works</a>
        <a href="#about"        class="hover:text-brand-600 transition">Why Skillspot.in</a>
      </div>

      <!-- Desktop CTA -->
      <div class="hidden md:flex items-center gap-3">
        <a href="/login"    class="text-sm font-semibold text-slate-700 hover:text-brand-600 transition px-4 py-2">Login</a>
        <a href="/register" class="text-sm bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-lg shadow-brand-600/20">
          Get Started Free
        </a>
      </div>

      <!-- Mobile hamburger -->
      <button id="hamburger" class="md:hidden text-slate-700 hover:text-slate-900 p-2 rounded-xl focus:outline-none" aria-label="Open menu">
        <svg id="icon-open"  class="w-6 h-6"         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <svg id="icon-close" class="w-6 h-6 hidden"  fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
  </div>

  <!-- Mobile menu -->
  <div id="mobile-menu" class="md:hidden bg-white/98 border-t border-slate-200/80 shadow-xl">
    <div class="px-4 py-4 flex flex-col gap-1 text-sm font-medium">
      <a href="#courses"      class="text-slate-700 hover:text-brand-600 px-3 py-2.5 rounded-xl hover:bg-slate-50 transition">📚 Courses</a>
      <a href="#how-it-works" class="text-slate-700 hover:text-brand-600 px-3 py-2.5 rounded-xl hover:bg-slate-50 transition">🚀 How it Works</a>
      <a href="#about"        class="text-slate-700 hover:text-brand-600 px-3 py-2.5 rounded-xl hover:bg-slate-50 transition">🏆 Why Skillspot.in</a>
      <div class="border-t border-slate-100 mt-2 pt-3 flex flex-col gap-2">
        <a href="/login"    class="text-center text-slate-700 border border-slate-200 px-4 py-3 rounded-xl font-semibold transition hover:bg-slate-50">Login</a>
        <a href="/register" class="text-center bg-brand-600 hover:bg-brand-700 text-white px-4 py-3 rounded-xl font-bold transition shadow-md shadow-brand-600/20">Get Started Free →</a>
      </div>
    </div>
  </div>
</nav>

@yield('content')

<!-- ── FOOTER ──────────────────────────────────────────────────────────── -->
<footer class="bg-white border-t border-slate-200 text-slate-600 py-14">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-10 mb-12">
      <div>
        <div class="flex items-center gap-2.5 mb-4">
          <div class="w-8 h-8 bg-gradient-to-br from-brand-600 to-accent-600 rounded-xl flex items-center justify-center text-white font-black text-sm shadow">S</div>
          <div>
            <span class="text-slate-900 font-black text-sm block leading-none">Skillspot.in</span>
            <span class="text-brand-600 text-xs font-bold tracking-wider">TECH ACADEMY</span>
          </div>
        </div>
        <p class="text-sm text-slate-500 leading-relaxed">Professional IT education for students and professionals. Learn real skills, earn real certificates.</p>
      </div>
      <div>
        <h4 class="text-slate-900 font-bold mb-4 text-sm">Courses</h4>
        <ul class="space-y-2.5 text-sm">
          <li><a href="#courses" class="hover:text-brand-600 transition">Web Development</a></li>
          <li><a href="#courses" class="hover:text-brand-600 transition">Cybersecurity</a></li>
          <li><a href="#courses" class="hover:text-brand-600 transition">Python & AI</a></li>
          <li><a href="#courses" class="hover:text-brand-600 transition">Mobile Development</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-slate-900 font-bold mb-4 text-sm">Students</h4>
        <ul class="space-y-2.5 text-sm">
          <li><a href="/register" class="hover:text-brand-600 transition">Register Free</a></li>
          <li><a href="/login"    class="hover:text-brand-600 transition">Student Login</a></li>
          <li><a href="#how-it-works" class="hover:text-brand-600 transition">How it Works</a></li>
          <li><a href="/login" class="hover:text-brand-600 transition">Certificate Verify</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-slate-900 font-bold mb-4 text-sm">Academy</h4>
        <ul class="space-y-2.5 text-sm">
          <li><a href="/about" class="hover:text-brand-600 transition">About Skillspot.in</a></li>
          <li><a href="/contact" class="hover:text-brand-600 transition">Contact Us</a></li>
          <li><a href="/privacy" class="hover:text-brand-600 transition">Privacy Policy</a></li>
          <li><a href="/terms" class="hover:text-brand-600 transition">Terms of Service</a></li>
          <li><a href="/pricing" class="hover:text-brand-600 transition">Our Pricing</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-slate-100 pt-8 text-center text-sm text-slate-400">
      <p>© {{ date('Y') }} Skillspot.in. All rights reserved.</p>
    </div>
  </div>
</footer>

<script>
  // Navbar scroll solid effect
  const navbar = document.getElementById('navbar');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 40) {
      navbar.classList.add('nav-solid');
      navbar.classList.remove('glass');
    } else {
      navbar.classList.remove('nav-solid');
      navbar.classList.add('glass');
    }
  });

  // Mobile menu toggle
  const hamburger  = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobile-menu');
  const iconOpen   = document.getElementById('icon-open');
  const iconClose  = document.getElementById('icon-close');
  hamburger.addEventListener('click', () => {
    const open = mobileMenu.classList.toggle('open');
    iconOpen.classList.toggle('hidden', open);
    iconClose.classList.toggle('hidden', !open);
    document.body.style.overflow = open ? 'hidden' : '';
  });
  mobileMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      mobileMenu.classList.remove('open');
      iconOpen.classList.remove('hidden');
      iconClose.classList.add('hidden');
      document.body.style.overflow = '';
    });
  });
</script>
@stack('scripts')
</body>
</html>
