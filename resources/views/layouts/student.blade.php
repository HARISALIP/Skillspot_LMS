<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.text_direction') === 'auto' ? 'auto' : config('app.text_direction', 'ltr') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'My Learning — Skillspot.in LMS')</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
  @include('partials.turbo')
  <style>
    .progress-bar { background: linear-gradient(90deg, #2563eb, #7c3aed); border-radius: 9999px; }
    .active-nav { color: #2563eb; font-weight: 600; background: rgba(37,99,235,0.08); }
  </style>
  @stack('styles')
</head>
<body class="font-sans bg-gray-50 antialiased">
@php
  $organizationContext = $organizationContext ?? (request()->routeIs('organization.dashboard', 'vendor.portal.*') ? ($vendor ?? null) : null);
  $canUseMarketplace = auth()->user()?->portal_access !== 'vendor_only';
  $profileHref = route('student.profile', $organizationContext ? ['org' => $organizationContext->slug] : []);
  $myOrganizations = auth()->check() ? \App\Models\Vendor::where('status', 'active')
    ->whereHas('studentAccess', fn ($students) => $students->whereKey(auth()->id()))
    ->orderBy('brand_name')->get() : collect();
@endphp

<!-- Mobile nav bottom bar -->
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to main content</a>
<nav aria-label="Learning navigation" class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40 lg:hidden flex items-center justify-around h-16 px-2 safe-area-bottom">
  @php $mobileNav = $organizationContext ? [
    ['icon'=>'fa-house','label'=>'Home', 'href'=>route('organization.dashboard', $organizationContext->slug), 'route'=>'organization.dashboard'],
    ['icon'=>'fa-book-open','label'=>'Courses', 'href'=>route('organization.dashboard', ['slug'=>$organizationContext->slug,'tab'=>'courses']), 'route'=>'organization.dashboard'],
    ['icon'=>'fa-magnifying-glass','label'=>'Explore', 'href'=>route('organization.dashboard', ['slug'=>$organizationContext->slug,'tab'=>'catalog']), 'route'=>'organization.dashboard'],
    ['icon'=>'fa-video','label'=>'Live', 'href'=>route('organization.dashboard', ['slug'=>$organizationContext->slug,'tab'=>'live']), 'route'=>'organization.dashboard'],
    ['icon'=>'fa-user','label'=>'Profile', 'href'=>$profileHref, 'route'=>'student.profile'],
  ] : [
    ['icon'=>'fa-house','label'=>'Home',    'href'=>'/dashboard',          'route'=>'student.dashboard'],
    ['icon'=>'fa-book-open','label'=>'Courses', 'href'=>'/dashboard/courses',  'route'=>'student.courses'],
    ['icon'=>'fa-magnifying-glass','label'=>'Explore', 'href'=>'/dashboard/browse', 'route'=>'student.browse'],
    ['icon'=>'fa-video','label'=>'Live',    'href'=>'/dashboard/live',     'route'=>'student.live'],
    ['icon'=>'fa-user','label'=>'Profile', 'href'=>'/dashboard/profile',  'route'=>'student.profile'],
  ]; @endphp
  @foreach($mobileNav as $item)
  @php $isCurrent = $organizationContext && $item['route'] === 'organization.dashboard'
      ? url()->current() === $item['href'] : request()->routeIs($item['route']); @endphp
  <a href="{{ $item['href'] }}" @if($isCurrent) aria-current="page" @endif class="flex min-h-11 min-w-0 flex-1 flex-col items-center justify-center gap-0.5 rounded-xl px-1 py-1.5 transition {{ $isCurrent ? 'text-brand-700' : 'text-gray-600' }}">
    <i class="fa-solid {{ $item['icon'] }} text-lg" aria-hidden="true"></i>
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
    </div>

    <div class="border-b border-gray-100 px-4 py-3">
      <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Learning context</p>
      @if($canUseMarketplace)<a href="{{ route('student.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm {{ !$organizationContext ? 'bg-brand-50 font-bold text-brand-700' : 'text-gray-700 hover:bg-gray-100' }}">Skillspot marketplace</a>@endif
      @foreach($myOrganizations as $organization)
        <a href="{{ route('organization.dashboard', $organization->slug) }}" class="mt-1 block truncate rounded-lg px-3 py-2 text-sm {{ $organizationContext?->id === $organization->id ? 'bg-brand-50 font-bold text-brand-700' : 'text-gray-700 hover:bg-gray-100' }}">{{ $organization->brand_name }}</a>
      @endforeach
    </div>

    <!-- Nav -->
    <nav data-navigation-sidebar="student" class="flex-1 px-3 py-4 overflow-y-auto">
      @php $navItems = $organizationContext ? [
        ['icon'=>'🏠','label'=>'Dashboard','route'=>'organization.dashboard','href'=>route('organization.dashboard', $organizationContext->slug)],
        ['icon'=>'📚','label'=>'My Courses','route'=>'organization.dashboard','href'=>route('organization.dashboard', ['slug'=>$organizationContext->slug,'tab'=>'courses'])],
        ['icon'=>'📡','label'=>'Live Classes','route'=>'organization.dashboard','href'=>route('organization.dashboard', ['slug'=>$organizationContext->slug,'tab'=>'live'])],
        ['icon'=>'🔍','label'=>'Browse','route'=>'organization.dashboard','href'=>route('organization.dashboard', ['slug'=>$organizationContext->slug,'tab'=>'catalog'])],
        ['icon'=>'👤','label'=>'Profile','route'=>'student.profile','href'=>$profileHref],
      ] : [
        ['icon'=>'🏠','label'=>'Dashboard',   'route'=>'student.dashboard','href'=>'/dashboard'],
        ['icon'=>'📚','label'=>'My Courses',  'route'=>'student.courses',  'href'=>'/dashboard/courses'],
        ['icon'=>'📡','label'=>'Live Classes','route'=>'student.live',    'href'=>'/dashboard/live'],
        ['icon'=>'🔍','label'=>'Browse',      'route'=>'student.browse',   'href'=>'/dashboard/browse'],
        ['icon'=>'📜','label'=>'Certificates','route'=>'student.certs',    'href'=>'/dashboard/certs'],
        ['icon'=>'📊','label'=>'Progress',    'route'=>'student.progress', 'href'=>'/dashboard/progress'],
        ['icon'=>'👤','label'=>'Profile',     'route'=>'student.profile',  'href'=>'/dashboard/profile'],
        ['icon'=>'🎓','label'=>'Creator Studio', 'route'=>'studio.dashboard', 'href'=>route('studio.dashboard')],
      ]; @endphp

      @foreach($navItems as $item)
      @php $isCurrent = $organizationContext && $item['route'] === 'organization.dashboard'
          ? url()->current() === $item['href'] : request()->routeIs($item['route']); @endphp
      <a href="{{ $item['href'] }}" @if($isCurrent) aria-current="page" @endif
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl mb-0.5 transition text-sm {{ $isCurrent ? 'active-nav' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
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
    <header class="h-14 lg:h-16 bg-white border-b border-gray-100 flex items-center justify-between gap-2 px-4 md:px-6 flex-shrink-0 z-10">
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-base font-bold text-gray-900">@yield('page-title', 'My Learning')</h1>
        <p id="pageSubtitle" class="text-xs text-gray-400 hidden sm:block">@yield('page-sub', $organizationContext?->brand_name ?? 'Keep it up! 🔥')</p>
      </div>
      <div class="flex shrink-0 items-center gap-2">
        @if($myOrganizations->isNotEmpty())<details data-context-switcher class="relative"><summary class="flex min-h-11 max-w-[190px] cursor-pointer list-none items-center rounded-xl border border-gray-200 px-3 text-xs font-semibold text-gray-700 sm:max-w-none"><span class="min-w-0 truncate">{{ $organizationContext?->brand_name ?? 'Skillspot marketplace' }}</span><i class="fa-solid fa-chevron-down ml-1 shrink-0" aria-hidden="true"></i></summary><div class="absolute right-0 top-full z-30 mt-1 max-w-[calc(100vw-2rem)] min-w-48 rounded-xl border border-gray-200 bg-white p-2 shadow-lg">@if($canUseMarketplace)<a href="{{ route('student.dashboard') }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm text-gray-700 hover:bg-gray-100">Skillspot marketplace</a>@endif @foreach($myOrganizations as $organization)<a href="{{ route('organization.dashboard', $organization->slug) }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm text-gray-700 hover:bg-gray-100">{{ $organization->brand_name }}</a>@endforeach</div></details>@endif
        <a href="{{ $organizationContext ? route('organization.dashboard', ['slug'=>$organizationContext->slug,'tab'=>'catalog']) : route('student.browse') }}" class="hidden sm:flex items-center gap-1.5 text-xs bg-brand-50 text-brand-700 hover:bg-brand-100 font-semibold px-3 py-2 rounded-xl transition">
          🔍 Browse Courses
        </a>
      </div>
    </header>

    <!-- Page content -->
    <main id="main-content" tabindex="-1" data-navigation-main class="flex-1 overflow-y-auto p-4 md:p-6">
      @yield('student-content')
    </main>
  </div>
</div>

@include('partials.ui-icon-normalizer')
@stack('scripts')
</body>
</html>
