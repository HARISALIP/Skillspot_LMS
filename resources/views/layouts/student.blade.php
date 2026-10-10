<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'My Learning — Skillspot.in LMS')</title>
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
    .progress-bar { background: linear-gradient(90deg, #2563eb, #7c3aed); border-radius: 9999px; }
    .active-nav { color: #2563eb; font-weight: 600; background: rgba(37,99,235,0.08); }
  </style>
  @stack('styles')
</head>
<body class="font-sans bg-gray-50 antialiased">

<!-- Mobile nav bottom bar -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40 lg:hidden flex items-center justify-around h-16 px-2 safe-area-bottom">
  @php $mobileNav = [
    ['icon'=>'🏠','label'=>'Home',    'href'=>'/dashboard',          'route'=>'student.dashboard'],
    ['icon'=>'📚','label'=>'Courses', 'href'=>'/dashboard/courses',  'route'=>'student.courses'],
    ['icon'=>'📡','label'=>'Live',    'href'=>'/dashboard/live',     'route'=>'student.live'],
    ['icon'=>'🔖','label'=>'Saved',   'href'=>'/dashboard/saved',    'route'=>'student.saved'],
    ['icon'=>'📜','label'=>'Certs',   'href'=>'/dashboard/certs',    'route'=>'student.certs'],
    ['icon'=>'👤','label'=>'Profile', 'href'=>'/dashboard/profile',  'route'=>'student.profile'],
  ]; @endphp
  @foreach($mobileNav as $item)
  <a href="{{ $item['href'] }}" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl transition {{ request()->routeIs($item['route']) ? 'text-brand-600' : 'text-gray-500' }}">
    <span class="text-xl leading-none">{{ $item['icon'] }}</span>
    <span class="text-xs font-medium">{{ $item['label'] }}</span>
  </a>
  @endforeach
</nav>

<div class="flex h-screen overflow-hidden pb-16 lg:pb-0">

  <!-- Desktop Sidebar -->
  <aside class="hidden lg:flex w-64 bg-white border-r border-gray-100 flex-col h-full fixed lg:relative shadow-sm z-20">
    <!-- Logo -->
    <div class="flex items-center gap-2.5 px-5 h-16 border-b border-gray-100 flex-shrink-0">
      <a href="/" class="flex items-center gap-2.5">
        <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-accent-500 rounded-xl flex items-center justify-center text-white font-black text-base">I</div>
        <span class="font-bold text-gray-900 text-base">Skillspot.in <span class="text-brand-600">LMS</span></span>
      </a>
    </div>

    <!-- User card -->
    <div class="px-4 py-4 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-accent-500 rounded-2xl flex items-center justify-center text-white font-black text-lg flex-shrink-0">
          {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
        </div>
        <div class="min-w-0">
          <div class="text-sm font-bold text-gray-900 truncate">{{ auth()->user()->name ?? 'Student' }}</div>
          <div class="text-xs text-gray-400 truncate">{{ auth()->user()->email ?? '' }}</div>
        </div>
      </div>
      <!-- XP progress -->
      <div class="mt-3">
        <div class="flex items-center justify-between text-xs mb-1">
          <span class="text-gray-500 font-medium">⚡ Learning streak</span>
          <span class="text-brand-600 font-bold">Lv.3</span>
        </div>
        <div class="bg-gray-100 rounded-full h-1.5 overflow-hidden">
          <div class="progress-bar h-full" style="width:62%"></div>
        </div>
      </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 overflow-y-auto">
      @php $navItems = [
        ['icon'=>'🏠','label'=>'Dashboard',   'route'=>'student.dashboard','href'=>'/dashboard'],
        ['icon'=>'📚','label'=>'My Courses',  'route'=>'student.courses',  'href'=>'/dashboard/courses'],
        ['icon'=>'📡','label'=>'Live Classes','route'=>'student.live',    'href'=>'/dashboard/live'],
        ['icon'=>'🔍','label'=>'Browse',      'route'=>'student.browse',   'href'=>'/dashboard/browse'],
        ['icon'=>'🔖','label'=>'Saved',       'route'=>'student.saved',    'href'=>'/dashboard/saved'],
        ['icon'=>'📜','label'=>'Certificates','route'=>'student.certs',    'href'=>'/dashboard/certs'],
        ['icon'=>'📊','label'=>'Progress',    'route'=>'student.progress', 'href'=>'/dashboard/progress'],
        ['icon'=>'👤','label'=>'Profile',     'route'=>'student.profile',  'href'=>'/dashboard/profile'],
        ['icon'=>'⚙️','label'=>'Settings',    'route'=>'student.settings', 'href'=>'/dashboard/settings'],
      ]; @endphp

      @foreach($navItems as $item)
      <a href="{{ $item['href'] }}"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl mb-0.5 transition text-sm {{ request()->routeIs($item['route']) ? 'active-nav' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
        <span class="text-base">{{ $item['icon'] }}</span>
        <span class="font-medium">{{ $item['label'] }}</span>
      </a>
      @endforeach
    </nav>

    <!-- Logout -->
    <div class="px-3 py-4 border-t border-gray-100">
      <form method="POST" action="/logout">
        @csrf
        <button type="submit" class="flex items-center gap-3 px-3 py-2.5 rounded-xl w-full text-left text-gray-500 hover:bg-red-50 hover:text-red-600 transition text-sm">
          <span class="text-base">🚪</span>
          <span class="font-medium">Logout</span>
        </button>
      </form>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Top bar -->
    <header class="h-14 lg:h-16 bg-white border-b border-gray-100 flex items-center justify-between px-4 md:px-6 flex-shrink-0 z-10">
      <div>
        <h1 class="text-base font-bold text-gray-900">@yield('page-title', 'My Learning')</h1>
        <p class="text-xs text-gray-400 hidden sm:block">@yield('page-sub', 'Keep it up! 🔥')</p>
      </div>
      <div class="flex items-center gap-2">
        <a href="/dashboard/browse" class="hidden sm:flex items-center gap-1.5 text-xs bg-brand-50 text-brand-700 hover:bg-brand-100 font-semibold px-3 py-2 rounded-xl transition">
          🔍 Browse Courses
        </a>
        <button class="p-2 rounded-xl hover:bg-gray-100 text-gray-400 transition relative">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-brand-500 rounded-full"></span>
        </button>
      </div>
    </header>

    <!-- Page content -->
    <main class="flex-1 overflow-y-auto p-4 md:p-6">
      @yield('student-content')
    </main>
  </div>
</div>

@stack('scripts')
</body>
</html>
