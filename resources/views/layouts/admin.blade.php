<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Admin — Skillspot.in LMS')</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter','sans-serif'] },
          colors: {
            brand: { 50:'#eff6ff',100:'#dbeafe',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',900:'#1e3a8a' },
            accent: { 100:'#ede9fe',400:'#a78bfa',500:'#8b5cf6',600:'#7c3aed',900:'#4c1d95' }
          }
        }
      }
    }
  </script>
  <style>
    .sidebar { width: 260px; min-width: 260px; }
    .sidebar-collapsed { width: 72px; min-width: 72px; }
    #sidebar { transition: width 0.25s ease, min-width 0.25s ease; }
    .nav-label { transition: opacity 0.2s, width 0.2s; }
    .sidebar-collapsed .nav-label { opacity:0; width:0; overflow:hidden; white-space:nowrap; }
    .sidebar-collapsed .brand-text { display:none; }
    .active-nav { background: rgba(37,99,235,0.12); color: #2563eb; font-weight: 600; }
    .active-nav .nav-icon { color: #2563eb; }
  </style>
  @stack('styles')
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased">

<div class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar bg-white border-r border-gray-100 flex flex-col h-full z-30 fixed lg:relative shadow-sm lg:shadow-none">

    <!-- Logo -->
    <div class="flex items-center justify-between px-5 h-16 border-b border-gray-100 flex-shrink-0">
      <a href="/admin" class="flex items-center gap-2.5">
        <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-accent-500 rounded-xl flex items-center justify-center text-white font-black text-base flex-shrink-0">I</div>
        <span class="brand-text font-bold text-gray-900 text-base">Skillspot.in <span class="text-brand-600">LMS</span></span>
      </a>
      <button id="sidebarToggle" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition hidden lg:flex">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>

    <!-- Admin badge -->
    <div class="px-4 py-3 border-b border-gray-100">
      <div class="bg-gradient-to-r from-brand-50 to-accent-100 rounded-xl px-3 py-2 flex items-center gap-2">
        <div class="w-7 h-7 bg-gradient-to-br from-brand-500 to-accent-500 rounded-lg flex items-center justify-center text-white text-xs font-black flex-shrink-0">
          {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
        </div>
        <div class="nav-label overflow-hidden">
          <div class="text-xs font-semibold text-gray-900 truncate">{{ auth()->user()->name ?? 'Admin' }}</div>
          <div class="text-xs text-brand-600 font-medium">Super Admin</div>
        </div>
      </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 overflow-y-auto">
      @php
        $isTeacherOnly = auth()->user()->hasRole('teacher') && !auth()->user()->hasRole(['admin','super-admin']);
        $navItems = [
          ['fa'=>'fa-solid fa-chart-pie',         'color'=>'text-blue-500',    'label'=>'Dashboard',   'route'=>'admin.dashboard',   'href'=>route('admin.dashboard'),  'teacherOk'=>true],
          ['fa'=>'fa-solid fa-store',             'color'=>'text-purple-500',  'label'=>'Vendors',     'route'=>'admin.vendors',     'href'=>route('admin.vendors'),    'teacherOk'=>false],
          ['fa'=>'fa-solid fa-users',             'color'=>'text-emerald-500', 'label'=>'Users',       'route'=>'admin.users',       'href'=>route('admin.users'),      'teacherOk'=>false],
          ['fa'=>'fa-solid fa-book-open',         'color'=>'text-indigo-500',  'label'=>'Courses',     'route'=>'admin.courses',     'href'=>route('admin.courses'),    'teacherOk'=>true],
          ['fa'=>'fa-solid fa-user-graduate',     'color'=>'text-amber-500',   'label'=>'Enrollments', 'route'=>'admin.enrollments', 'href'=>route('admin.enrollments'),'teacherOk'=>true],
          ['fa'=>'fa-solid fa-video',             'color'=>'text-rose-500',    'label'=>'Live Classes','route'=>'admin.live',        'href'=>route('admin.live'),       'teacherOk'=>true],
          ['fa'=>'fa-solid fa-cubes',             'color'=>'text-cyan-500',    'label'=>'Batches',     'route'=>'admin.batches',     'href'=>route('admin.batches'),    'teacherOk'=>true],
          ['fa'=>'fa-solid fa-credit-card',       'color'=>'text-emerald-600', 'label'=>'Payments',    'route'=>'admin.payments',    'href'=>route('admin.payments'),   'teacherOk'=>false],
          ['fa'=>'fa-solid fa-certificate',       'color'=>'text-amber-600',   'label'=>'Certificates','route'=>'admin.certs',       'href'=>route('admin.certs'),      'teacherOk'=>true],
          ['fa'=>'fa-solid fa-photo-film',        'color'=>'text-teal-500',    'label'=>'Media',       'route'=>'admin.media',       'href'=>route('admin.media'),      'teacherOk'=>true],
          ['fa'=>'fa-solid fa-shield-halved',     'color'=>'text-violet-500',  'label'=>'Roles',       'route'=>'admin.roles',       'href'=>route('admin.roles'),      'teacherOk'=>false],
          ['fa'=>'fa-solid fa-sliders',           'color'=>'text-slate-500',   'label'=>'Settings',    'route'=>'admin.settings',    'href'=>route('admin.settings'),   'teacherOk'=>false],
          ['fa'=>'fa-solid fa-circle-user',       'color'=>'text-blue-600',    'label'=>'My Profile',  'route'=>'admin.profile',     'href'=>route('admin.profile'),    'teacherOk'=>true],
        ];
      @endphp

      @php $courseOpen = request()->routeIs('admin.courses*'); @endphp

      {{-- Simple nav items --}}
      @foreach($navItems as $item)
        @if($isTeacherOnly && !($item['teacherOk'] ?? false)) @continue @endif
        @if($item['label'] === 'Courses')
        {{-- Courses expandable group --}}
        <div class="mb-0.5">
          <button onclick="toggleCoursesMenu()"
                  class="flex items-center gap-3 px-3 py-2.5 rounded-xl w-full transition nav-label
                         {{ $courseOpen ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 bg-indigo-50 text-indigo-500">
              <i class="fa-solid fa-book-open text-sm"></i>
            </span>
            <span class="nav-label text-sm font-medium flex-1 text-left">Courses</span>
            <svg id="coursesArrow" class="nav-label w-4 h-4 transition-transform {{ $courseOpen ? 'rotate-180' : '' }}"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div id="coursesSubmenu" class="{{ $courseOpen ? '' : 'hidden' }} nav-label ml-4 mt-0.5 space-y-0.5 border-l-2 border-brand-100 pl-3">
            @foreach([
              ['fa-solid fa-list-check','All Courses',  route('admin.courses'),           'admin.courses'],
              ['fa-solid fa-plus','New Course',   route('admin.courses.create'),    'admin.courses.create'],
              ['fa-solid fa-tags','Categories',  route('admin.courses.categories'),'admin.courses.categories*'],
              ['fa-solid fa-signal','Levels',       route('admin.courses.levels'),    'admin.courses.levels*'],
              ['fa-solid fa-globe','Languages',    route('admin.courses.languages'), 'admin.courses.languages*'],
            ] as [$faIcon,$lbl,$href,$rt])
            <a href="{{ $href }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition
                      {{ request()->routeIs($rt) ? 'bg-brand-100 text-brand-700 font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
              <i class="{{ $faIcon }} text-xs opacity-75"></i> {{ $lbl }}
            </a>
            @endforeach
          </div>
        </div>
        @else
        <a href="{{ $item['href'] }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl mb-0.5 transition group {{ request()->routeIs($item['route']) ? 'active-nav' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
          <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all {{ request()->routeIs($item['route']) ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/30' : 'bg-slate-100/80 ' . $item['color'] . ' group-hover:bg-white group-hover:shadow-sm' }}">
            <i class="{{ $item['fa'] }} text-sm"></i>
          </span>
          <span class="nav-label text-sm font-medium">{{ $item['label'] }}</span>
        </a>
        @endif
      @endforeach
    </nav>

    <!-- Logout -->
    <div class="px-3 py-4 border-t border-gray-100 space-y-1">
      @if(auth()->user()->hasRole('teacher'))
      <a href="{{ route('vendor.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl w-full text-left text-gray-600 hover:bg-brand-50 hover:text-brand-600 transition">
        <span class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
          <i class="fa-solid fa-building text-sm"></i>
        </span>
        <span class="nav-label text-sm font-medium">Vendor Panel</span>
      </a>
      @endif
      <form method="POST" action="/logout">
        @csrf
        <button type="submit" class="flex items-center gap-3 px-3 py-2.5 rounded-xl w-full text-left text-gray-600 hover:bg-red-50 hover:text-red-600 transition">
          <span class="w-7 h-7 rounded-lg bg-red-50 text-red-500 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-right-from-bracket text-sm"></i>
          </span>
          <span class="nav-label text-sm font-medium">Logout</span>
        </button>
      </form>
    </div>
  </aside>

  <!-- Mobile overlay -->
  <div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-20 hidden lg:hidden" onclick="closeMobileSidebar()"></div>

  <!-- Main content -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

    <!-- Top bar -->
    <header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-4 md:px-6 flex-shrink-0 z-10">
      <div class="flex items-center gap-3">
        <!-- Mobile menu btn -->
        <button id="mobileMenuBtn" onclick="openMobileSidebar()" class="lg:hidden p-2 rounded-xl hover:bg-gray-100 text-gray-500 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div>
          <h1 class="text-base md:text-lg font-bold text-gray-900">@yield('page-title', 'Dashboard')</h1>
          <p class="text-xs text-gray-500 hidden sm:block">@yield('page-sub', 'Skillspot.in LMS Admin')</p>
        </div>
      </div>
      <div class="flex items-center gap-2 md:gap-3">
        <!-- Notifications -->
        <button class="relative p-2 rounded-xl hover:bg-gray-100 text-gray-500 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
        </button>
        <!-- Avatar -->
        <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-accent-500 rounded-xl flex items-center justify-center text-white font-black text-sm cursor-pointer">
          {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
        </div>
      </div>
    </header>

    <!-- Page content -->
    <main class="flex-1 overflow-y-auto">
      @if(session("impersonating_as"))
      <div class="bg-purple-600 text-white px-4 py-2.5 flex items-center justify-between text-sm">
        <span class="font-semibold">👤 Impersonating: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->roles->first()?->name ?? "user" }})</span>
        <a href="{{ route("admin.users.impersonate.stop") }}"
           class="bg-white text-purple-600 font-bold px-4 py-1.5 rounded-lg text-xs hover:bg-purple-50 transition">
          Stop Impersonating →
        </a>
      </div>
      @endif
      <div class="p-4 md:p-6">
      @yield('admin-content')
      </div>
    </main>
  </div>
</div>

<script>
  // Sidebar collapse (desktop)
  const sidebar = document.getElementById('sidebar');
  document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    sidebar.classList.toggle('sidebar-collapsed');
    sidebar.classList.toggle('sidebar');
  });

  // Mobile sidebar
  function openMobileSidebar() {
    sidebar.style.transform = 'translateX(0)';
    document.getElementById('mobileOverlay').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
  function closeMobileSidebar() {
    document.getElementById('mobileOverlay').classList.add('hidden');
    document.body.style.overflow = '';
  }

  // Mobile: hide sidebar off-screen by default on small screens
  if (window.innerWidth < 1024) {
    sidebar.style.transform = 'translateX(-100%)';
    sidebar.style.transition = 'transform 0.25s ease';
    function openMobileSidebar() {
      sidebar.style.transform = 'translateX(0)';
      document.getElementById('mobileOverlay').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
    function closeMobileSidebar() {
      sidebar.style.transform = 'translateX(-100%)';
      document.getElementById('mobileOverlay').classList.add('hidden');
      document.body.style.overflow = '';
    }
  }
</script>
@stack('scripts')

<script>
function toggleCoursesMenu() {
  const menu  = document.getElementById('coursesSubmenu');
  const arrow = document.getElementById('coursesArrow');
  if (!menu) return;
  menu.classList.toggle('hidden');
  arrow.style.transform = menu.classList.contains('hidden') ? '' : 'rotate(180deg)';
}
</script>
</body>
</html>
