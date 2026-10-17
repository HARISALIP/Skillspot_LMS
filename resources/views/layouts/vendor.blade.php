<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.text_direction') === 'auto' ? 'auto' : config('app.text_direction', 'ltr') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Vendor — Skillspot.in LMS')</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
  @include('partials.turbo')
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
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to main content</a>
@if(session("impersonating_as"))
<div class="bg-purple-600 text-white px-4 py-2.5 flex items-center justify-between text-sm sticky top-0 z-50">
  <span class="font-semibold">&#128100; Impersonating: <strong>{{ auth()->user()->name }}</strong> &middot; {{ auth()->user()->roles->first()?->name ?? "user" }}</span>
  <a href="{{ route('admin.users.impersonate.stop') }}" class="bg-white text-purple-600 font-bold px-4 py-1.5 rounded-lg text-xs hover:bg-purple-50 transition">Stop &#8594;</a>
</div>
@endif

<div class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar bg-white border-r border-gray-100 flex flex-col h-full z-30 fixed lg:relative shadow-sm lg:shadow-none">

    <!-- Logo -->
    <div class="flex items-center justify-between px-5 h-16 border-b border-gray-100 flex-shrink-0">
      <a href="{{ route('vendor.dashboard') }}" class="flex items-center gap-2.5">
        <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-accent-500 rounded-xl flex items-center justify-center text-white font-black text-base flex-shrink-0">V</div>
        <span class="brand-text font-bold text-gray-900 text-base">Vendor <span class="text-brand-600">Panel</span></span>
      </a>
      <button id="sidebarToggle" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition hidden lg:flex">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>

    <!-- Vendor badge -->
    <div class="px-4 py-3 border-b border-gray-100">
      <div class="bg-gradient-to-r from-brand-50 to-accent-100 rounded-xl px-3 py-2 flex items-center gap-2">
        <div class="w-7 h-7 bg-gradient-to-br from-brand-500 to-accent-500 rounded-lg flex items-center justify-center text-white text-xs font-black flex-shrink-0">
          {{ strtoupper(substr(auth()->user()->name ?? 'V', 0, 1)) }}
        </div>
        <div class="nav-label overflow-hidden">
          <div class="text-xs font-semibold text-gray-900 truncate">{{ auth()->user()->name ?? 'Vendor' }}</div>
          @if(auth()->user()->hasRole('teacher'))
            <div class="text-xs text-purple-600 font-medium">Teacher</div>
          @elseif(auth()->user()->hasRole('vendor'))
            <div class="text-xs text-brand-600 font-medium">Vendor <span class="text-gray-400 font-normal">(view only)</span></div>
          @else
            <div class="text-xs text-brand-600 font-medium">Admin</div>
          @endif
        </div>
      </div>
    </div>

    <!-- Nav -->
    <nav data-navigation-sidebar="vendor" class="flex-1 px-3 py-4 overflow-y-auto">
      @php
      $isTeacherOrAdmin = auth()->user()->hasRole(['teacher','admin','super-admin']);
      $isVendorRole     = auth()->user()->hasRole('vendor') && !$isTeacherOrAdmin;

      // Vendor-only: just Dashboard, Live Classes, Students
      if ($isVendorRole) {
          $navItems = [
              ['icon'=>'📊','label'=>'Dashboard',   'route'=>'vendor.dashboard',  'href'=>route('vendor.dashboard')],
              ['icon'=>'📡','label'=>'Live Classes', 'route'=>'vendor.live_classes.index', 'href'=>route('vendor.live_classes.index')],
              ['icon'=>'🎓','label'=>'My Courses',  'route'=>'vendor.courses', 'href'=>route('vendor.courses')],
              ['icon'=>'👥','label'=>'Students',     'route'=>'vendor.students*',  'href'=>route('vendor.students')],
              ['icon'=>'💰','label'=>'Earnings',      'route'=>'vendor.earnings',   'href'=>route('vendor.earnings')],
          ];
      } else {
          // Teacher / Admin: full access
          $isAdminRole   = auth()->user()->hasRole(['admin','super-admin']);
          $isTeacherOnly = auth()->user()->hasRole('teacher') && !$isAdminRole;
          $navItems = [
              ['icon'=>'📊','label'=>'Dashboard',   'route'=>'vendor.dashboard',   'href'=>route('vendor.dashboard')],
              ['icon'=>'📡','label'=>'Live Classes', 'route'=>'vendor.live_classes.index', 'href'=>route('vendor.live_classes.index')],
              ['icon'=>'🎓','label'=>'My Courses',  'route'=>'vendor.courses', 'href'=>route('vendor.courses')],
              ['icon'=>'👥','label'=>'Students',     'route'=>'vendor.students*',   'href'=>route('vendor.students')],
              ...(!$isTeacherOnly ? [['icon'=>'💰','label'=>'Earnings','route'=>'vendor.earnings','href'=>route('vendor.earnings')]] : []),
              ...($isAdminRole ? [['icon'=>'👩‍🏫','label'=>'Teachers','route'=>'vendor.teachers','href'=>route('vendor.teachers')]] : []),
              ...(!$isTeacherOnly ? [['icon'=>'🎨','label'=>'Branding','route'=>'vendor.profile*','href'=>route('vendor.profile')]] : []),
              ['icon'=>'👤','label'=>'My Profile',   'route'=>'vendor.my-profile*', 'href'=>route('vendor.my-profile')],
          ];
      }
      @endphp

      @foreach($navItems as $item)
      <a href="{{ $item['href'] }}"
         @php
          $isActiveNav = match ($item['route']) {
              'vendor.live_classes.index' => request()->routeIs('vendor.live_classes.*'),
              'vendor.courses' => request()->routeIs('vendor.courses*'),
              default => request()->routeIs($item['route']),
          };
        @endphp
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl mb-0.5 transition group {{ $isActiveNav ? 'active-nav' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
        <span class="nav-icon text-lg flex-shrink-0">{{ $item['icon'] }}</span>
        <span class="nav-label text-sm font-medium">{{ $item['label'] }}</span>
      </a>
      @endforeach

    </nav>

    <!-- Vendor Switcher + Logout -->
    <div class="px-3 py-4 border-t border-gray-100 space-y-1">
      @php
        $u = auth()->user();
        $allMyVendors = $u->hasRole('teacher')
            ? \App\Models\Vendor::whereHas('teachers', fn($q) => $q->where('user_id', $u->id))->where('status','active')->get()
            : \App\Models\Vendor::whereHas('managers', fn($q) => $q->where('user_id', $u->id))->where('status','active')->get();
        $activeVid = session('active_vendor_id');
      @endphp
      @if($allMyVendors->count() > 1)
        <div class="px-1 mb-1">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Switch Vendor</p>
        </div>
        @foreach($allMyVendors as $mv)
        <form method="POST" action="{{ route('vendor.switch') }}">
          @csrf
          <input type="hidden" name="vendor_id" value="{{ $mv->id }}">
          <button type="submit" class="flex items-center gap-2 px-3 py-2 rounded-xl w-full text-left transition text-xs {{ $activeVid == $mv->id ? 'bg-brand-50 text-brand-700 font-bold' : 'text-gray-500 hover:bg-gray-100' }}">
            <div class="w-5 h-5 rounded flex items-center justify-center text-white text-xs font-black flex-shrink-0" style="background: {{ $mv->primary_color ?? '#2563eb' }}">{{ strtoupper(substr($mv->brand_name,0,1)) }}</div>
            <span class="nav-label truncate flex-1">{{ $mv->brand_name }}</span>
            @if($activeVid == $mv->id)<span class="nav-label text-brand-600">✓</span>@endif
          </button>
        </form>
        @endforeach
        <div class="border-t border-gray-100 pt-1 mt-1"></div>
      @endif
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex items-center gap-3 px-3 py-2.5 rounded-xl w-full text-left text-gray-600 hover:bg-red-50 hover:text-red-600 transition">
          <span class="text-lg flex-shrink-0">🚪</span>
          <span class="nav-label text-sm font-medium">Logout</span>
        </button>
      </form>
    </div>
  </aside>

  <!-- Mobile overlay -->
  <div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-20 hidden lg:hidden"></div>

  <!-- Main content -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

    <!-- Top bar -->
    <header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-4 md:px-6 flex-shrink-0 z-10">
      <div class="flex items-center gap-3">
        <!-- Mobile menu btn -->
        <button id="mobileMenuBtn" type="button" class="lg:hidden p-2 rounded-xl hover:bg-gray-100 text-gray-500 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div>
          <h1 class="text-base md:text-lg font-bold text-gray-900">@yield('header', 'Vendor Panel')</h1>
          <p class="text-xs text-gray-500 hidden sm:block">@yield('page-sub', 'Skillspot.in LMS')</p>
        </div>
      </div>
      <div class="flex items-center gap-2 md:gap-3">
        @yield('header-actions')
        <!-- Avatar -->
        <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-accent-500 rounded-xl flex items-center justify-center text-white font-black text-sm cursor-pointer">
          {{ strtoupper(substr(auth()->user()->name ?? 'V', 0, 1)) }}
        </div>
      </div>
    </header>

    <!-- Page content -->
    <main id="main-content" tabindex="-1" data-navigation-main class="flex-1 overflow-y-auto p-4 md:p-6">
      @if(session('success'))
        <div role="status" class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div role="alert" class="bg-red-50 text-red-800 border border-red-200 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('error') }}</div>
      @endif
      @yield('content')
    </main>
  </div>
</div>

@include('partials.ui-icon-normalizer')
@stack('scripts')
</body>
</html>
