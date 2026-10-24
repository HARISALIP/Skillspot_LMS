<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.text_direction') === 'auto' ? 'auto' : config('app.text_direction', 'ltr') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@hasSection('title')@yield('title')@elseif(View::hasSection('page-title'))@yield('page-title') — Skillspot.in LMS @else Admin — Skillspot.in LMS @endif</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
  @include('partials.turbo')
  <style>
    html,body{height:100%;overflow:hidden}
    .frappe-sidebar{width:224px;min-width:224px;transition:transform .2s ease,width .2s ease,min-width .2s ease}.frappe-sidebar.is-collapsed{width:60px;min-width:60px}.frappe-sidebar.is-collapsed .sidebar-copy,.frappe-sidebar.is-collapsed .sidebar-subtitle,.frappe-sidebar.is-collapsed .nav-copy,.frappe-sidebar.is-collapsed .nav-meta,.frappe-sidebar.is-collapsed .onboarding-card,.frappe-sidebar.is-collapsed .collapse-copy{display:none}.frappe-sidebar.is-collapsed .app-menu-button,.frappe-sidebar.is-collapsed .nav-item,.frappe-sidebar.is-collapsed .footer-action{justify-content:center}.frappe-sidebar.is-collapsed .app-menu-button{padding-left:.5rem;padding-right:.5rem}.frappe-sidebar.is-collapsed .nav-item{padding-left:.625rem;padding-right:.625rem}.frappe-sidebar.is-collapsed .nav-icon{margin-right:0}
    @media(max-width:1023px){.frappe-sidebar{transform:translateX(-100%)}.frappe-sidebar.is-mobile-open{transform:translateX(0)}.frappe-sidebar.is-collapsed{width:224px;min-width:224px}.frappe-sidebar.is-collapsed .sidebar-copy,.frappe-sidebar.is-collapsed .sidebar-subtitle,.frappe-sidebar.is-collapsed .nav-copy,.frappe-sidebar.is-collapsed .nav-meta,.frappe-sidebar.is-collapsed .onboarding-card,.frappe-sidebar.is-collapsed .collapse-copy{display:initial}#sidebarToggle{display:none}.frappe-sidebar .nav-item,.frappe-sidebar .footer-action,.frappe-sidebar .app-menu-button,.frappe-sidebar .app-menu a,.frappe-sidebar .app-menu button{min-height:44px}}
    @media(prefers-reduced-motion:reduce){.frappe-sidebar{transition:none!important}}
    :where(a,button,input,select,textarea,summary):focus-visible{outline:2px solid #2563eb;outline-offset:2px}
    .frappe-sidebar[data-theme="dark"]{background:#202020;border-color:#303030;color:#d4d4d4}.frappe-sidebar[data-theme="dark"] .sidebar-border{border-color:#303030}.frappe-sidebar[data-theme="dark"] .muted{color:#a3a3a3}.frappe-sidebar[data-theme="dark"] .nav-item{color:#c3c3c3}.frappe-sidebar[data-theme="dark"] .nav-item:hover{background:#2c2c2c;color:#fff}.frappe-sidebar[data-theme="dark"] .nav-item.is-active{background:#404040;color:#fff;border-color:#515151}.frappe-sidebar[data-theme="dark"] .app-menu-button:hover,.frappe-sidebar[data-theme="dark"] .footer-action:hover{background:#2c2c2c}.frappe-sidebar[data-theme="dark"] .app-menu{background:#292929;border-color:#3b3b3b;color:#ddd}.frappe-sidebar[data-theme="dark"] .app-menu a,.frappe-sidebar[data-theme="dark"] .app-menu button{color:#d4d4d4}.frappe-sidebar[data-theme="dark"] .app-menu a:hover,.frappe-sidebar[data-theme="dark"] .app-menu button:hover{background:#393939}.frappe-sidebar[data-theme="dark"] .getting-started{background:#262626;border-color:#373737}.frappe-sidebar[data-theme="dark"] .theme-menu{background:#292929;border-color:#3b3b3b}
    body[data-theme="dark"]{background:#171717!important;color:#e5e5e5}body[data-theme="dark"] .bg-white,body[data-theme="dark"] .bg-gray-50,body[data-theme="dark"] .bg-slate-50{background-color:#1f1f1f!important}body[data-theme="dark"] .bg-gray-100,body[data-theme="dark"] .bg-slate-100{background-color:#292929!important}body[data-theme="dark"] .bg-gray-200,body[data-theme="dark"] .bg-slate-200{background-color:#353535!important}body[data-theme="dark"] .text-gray-900,body[data-theme="dark"] .text-slate-900,body[data-theme="dark"] .text-black{color:#f5f5f5!important}body[data-theme="dark"] .text-gray-800,body[data-theme="dark"] .text-slate-800{color:#e5e5e5!important}body[data-theme="dark"] .text-gray-700,body[data-theme="dark"] .text-slate-700{color:#d4d4d4!important}body[data-theme="dark"] .text-gray-600,body[data-theme="dark"] .text-slate-600{color:#bdbdbd!important}body[data-theme="dark"] .text-gray-500,body[data-theme="dark"] .text-slate-500,body[data-theme="dark"] .text-gray-400,body[data-theme="dark"] .text-slate-400{color:#9e9e9e!important}body[data-theme="dark"] .border-gray-50,body[data-theme="dark"] .border-gray-100,body[data-theme="dark"] .border-gray-200,body[data-theme="dark"] .border-slate-100,body[data-theme="dark"] .border-slate-200{border-color:#3b3b3b!important}body[data-theme="dark"] input,body[data-theme="dark"] select,body[data-theme="dark"] textarea{background-color:#292929!important;border-color:#454545!important;color:#f5f5f5!important}body[data-theme="dark"] input::placeholder,body[data-theme="dark"] textarea::placeholder{color:#8f8f8f!important}body[data-theme="dark"] option{background:#292929;color:#f5f5f5}body[data-theme="dark"] table tbody tr:hover{background-color:#292929!important}body[data-theme="dark"] .notification-panel,body[data-theme="dark"] .command-box{background:#292929!important;border-color:#3b3b3b!important}body[data-theme="dark"] .command-item{color:#e5e5e5!important}body[data-theme="dark"] .command-item:hover{background:#363636!important}
    .app-menu,.theme-menu,.notification-panel,.command-palette{display:none}.app-menu.is-open,.theme-menu.is-open,.notification-panel.is-open,.command-palette.is-open{display:block}.command-palette{position:fixed;inset:0;z-index:60;background:rgba(0,0,0,.45);padding-top:12vh}.command-box{width:min(34rem,calc(100vw - 2rem));margin:auto}
    .settings-surface[data-theme="dark"]{background:#202020!important}.settings-surface[data-theme="light"]{background:#fff!important}
    html.settings-open,body.settings-open,body.settings-open main{overflow:hidden!important}
  </style>
  @stack('styles')
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-gray-900">Skip to main content</a>
@php
  $user = auth()->user();
  $organizationContext = request()->routeIs('vendor.*') ? ($vendor ?? \App\Models\Vendor::find(session('active_vendor_id'))) : null;
  $isTeacherOnly = $user->hasRole('teacher') && !$user->hasRole(['admin', 'super-admin']);
  $navItems = [
    ['icon'=>'fa-house','label'=>'Home','route'=>'admin.dashboard','href'=>route('admin.dashboard'),'teacher'=>true],
    ['icon'=>'fa-book-open','label'=>'Courses','route'=>'admin.courses*','href'=>route('admin.courses'),'teacher'=>true],
    ['icon'=>'fa-layer-group','label'=>'Batches','route'=>'admin.batches*','href'=>route('admin.batches'),'teacher'=>true],
    ['icon'=>'fa-certificate','label'=>'Certifications','route'=>'admin.certs*','href'=>route('admin.certs'),'teacher'=>true],
    ['icon'=>'fa-user-graduate','label'=>'Enrollments','route'=>'admin.enrollments*','href'=>route('admin.enrollments'),'teacher'=>true],
    ['icon'=>'fa-video','label'=>'Live Classes','route'=>'admin.live*','href'=>route('admin.live'),'teacher'=>true],
    ['icon'=>'fa-photo-film','label'=>'Media','route'=>'admin.media*','href'=>route('admin.media'),'teacher'=>true],
    ['icon'=>'fa-chart-line','label'=>'Instructor Earnings','route'=>'marketplace.dashboard','href'=>route('marketplace.dashboard'),'teacher'=>true],
    ['icon'=>'fa-store','label'=>'Organizations','route'=>'admin.vendors*','href'=>route('admin.vendors'),'teacher'=>false],
    ['icon'=>'fa-user-check','label'=>'Instructor Applications','route'=>'admin.marketplace.instructors*','href'=>route('admin.marketplace.instructors'),'teacher'=>false],
    ['icon'=>'fa-clipboard-check','label'=>'Course Reviews','route'=>'admin.marketplace.reviews*','href'=>route('admin.marketplace.reviews'),'teacher'=>false],
    ['icon'=>'fa-percent','label'=>'Commissions','route'=>'admin.marketplace.commissions*','href'=>route('admin.marketplace.commissions'),'teacher'=>false],
    ['icon'=>'fa-money-bill-transfer','label'=>'Payouts','route'=>'admin.marketplace.payouts*','href'=>route('admin.marketplace.payouts'),'teacher'=>false],
    ['icon'=>'fa-users','label'=>'Users','route'=>'admin.users*','href'=>route('admin.users'),'teacher'=>false],
  ];
  if ($organizationContext) {
    $navItems = [
      ['icon'=>'fa-house','label'=>'Overview','route'=>'vendor.dashboard','href'=>route('vendor.dashboard'),'teacher'=>true],
      ['icon'=>'fa-book-open','label'=>'Courses','route'=>'vendor.courses*','href'=>route('vendor.courses'),'teacher'=>true],
      ['icon'=>'fa-video','label'=>'Live Classes','route'=>'vendor.live_classes*','href'=>route('vendor.live_classes.index'),'teacher'=>true],
      ['icon'=>'fa-user-graduate','label'=>'Students & Grants','route'=>'vendor.students*','href'=>route('vendor.students'),'teacher'=>false],
      ['icon'=>'fa-users','label'=>'Staff & Classes','route'=>'vendor.organization.*','href'=>route('vendor.organization.index'),'teacher'=>false],
      ['icon'=>'fa-layer-group','label'=>'Batches','route'=>'vendor.batches*','href'=>route('vendor.batches'),'teacher'=>true],
      ['icon'=>'fa-photo-film','label'=>'Media','route'=>'vendor.media','href'=>route('vendor.media'),'teacher'=>false],
      ['icon'=>'fa-certificate','label'=>'Certificates & Reports','route'=>'vendor.reports','href'=>route('vendor.reports'),'teacher'=>false],
      ['icon'=>'fa-chart-line','label'=>'Organization Sales','route'=>'vendor.earnings','href'=>route('vendor.earnings'),'teacher'=>false],
      ['icon'=>'fa-palette','label'=>'Branding','route'=>'vendor.profile*','href'=>route('vendor.profile'),'teacher'=>false],
      ['icon'=>'fa-chalkboard-user','label'=>'Creator Studio','route'=>'studio.dashboard','href'=>route('studio.dashboard', ['organization'=>$organizationContext->id]),'teacher'=>true],
    ];
  }
  $primaryNavItems = array_slice($navItems, 0, $organizationContext ? 6 : 7);
  $platformNavItems = array_slice($navItems, $organizationContext ? 6 : 7);
  $hasCourses = $organizationContext
    ? \App\Models\Course::where('vendor_id', $organizationContext->id)->exists()
    : \App\Models\Course::exists();
@endphp
<div class="flex h-screen overflow-hidden">
  <aside id="sidebar" data-navigation-sidebar="admin" class="frappe-sidebar bg-white border-r border-gray-200 flex flex-col h-full z-30 fixed lg:relative" data-theme="light">
    <div class="relative z-40 p-2 sidebar-border border-b">
      <button id="appMenuButton" type="button" aria-expanded="false" class="app-menu-button w-full flex items-center gap-2 rounded-lg px-2 py-1.5 text-left hover:bg-black/5">
        <span class="w-7 h-7 rounded-lg bg-emerald-700 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-book-open text-xs"></i></span>
        <span class="sidebar-copy min-w-0 flex-1"><span class="block text-sm font-semibold leading-4 truncate">{{ $organizationContext?->brand_name ?? 'Learning' }}</span><span class="sidebar-subtitle muted block text-xs leading-4">{{ $organizationContext ? 'Organization' : 'Administrator' }}</span></span><i class="sidebar-copy fa-solid fa-chevron-down muted text-xs"></i>
      </button>
      <div id="appMenu" class="app-menu absolute left-2 right-2 top-[calc(100%-2px)] z-50 rounded-xl border border-gray-200 bg-white text-gray-700 shadow-xl p-1.5">
        <a href="{{ $organizationContext ? route('vendor.my-profile') : route('admin.profile') }}" class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm"><i class="fa-regular fa-user w-4 muted"></i>My Profile</a>
        @if($organizationContext && $user->hasRole(['admin','super-admin']))<a href="{{ route('admin.vendors') }}" class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm"><i class="fa-solid fa-arrow-left w-4 muted"></i>Leave organization</a>@endif
        @if($organizationContext)<a href="{{ route('vendor.pick') }}" class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm"><i class="fa-solid fa-arrow-right-arrow-left w-4 muted"></i>Switch organization</a>@endif
        <div class="relative"><button id="themeMenuButton" type="button" class="w-full flex items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm"><i class="fa-solid fa-sun w-4 muted"></i><span class="flex-1">Theme</span><i class="fa-solid fa-chevron-right text-xs muted"></i></button><div id="themeMenu" class="theme-menu absolute left-[calc(100%+4px)] top-0 z-50 w-36 rounded-xl border border-gray-200 bg-white text-gray-700 shadow-xl p-1.5"><button type="button" data-sidebar-theme="light" class="w-full rounded-lg px-2.5 py-2 text-left text-sm hover:bg-black/5">Light</button><button type="button" data-sidebar-theme="dark" class="w-full rounded-lg px-2.5 py-2 text-left text-sm hover:bg-black/5">Dark</button><button type="button" data-sidebar-theme="system" class="w-full rounded-lg px-2.5 py-2 text-left text-sm hover:bg-black/5">System</button></div></div>
        @if(!$isTeacherOnly && !$organizationContext)<button id="settingsModalButton" type="button" class="w-full flex items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm"><i class="fa-solid fa-gear w-4 muted"></i>Settings</button><a href="{{ route('admin.roles') }}" class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm"><i class="fa-solid fa-wrench w-4 muted"></i>Configuration</a>@endif
        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t sidebar-border pt-1">@csrf<button type="submit" class="w-full flex items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm"><i class="fa-solid fa-right-from-bracket w-4 muted"></i>Log out</button></form>
      </div>
    </div>
    <nav class="relative z-0 flex-1 overflow-y-auto px-2 py-3" aria-label="Main navigation">
      @php($homeItem = $navItems[0])
      <a href="{{ $homeItem['href'] }}" title="{{ $homeItem['label'] }}" class="nav-item {{ request()->routeIs($homeItem['route']) ? 'is-active border' : '' }} mb-0.5 flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm"><i class="nav-icon fa-solid {{ $homeItem['icon'] }} w-4 text-center"></i><span class="nav-copy">{{ $homeItem['label'] }}</span></a>
      <button id="searchButton" type="button" class="nav-item w-full flex items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm muted hover:bg-black/5" title="Search (Ctrl+K)"><i class="nav-icon fa-solid fa-magnifying-glass w-4 text-center"></i><span class="nav-copy flex-1">Search</span><kbd class="nav-meta rounded bg-black/10 px-1.5 py-0.5 text-[10px]">Ctrl K</kbd></button>
      <div class="relative"><button id="notificationsButton" type="button" class="nav-item w-full flex items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm muted hover:bg-black/5"><i class="nav-icon fa-regular fa-bell w-4 text-center"></i><span class="nav-copy">Notifications</span></button><div id="notificationPanel" class="notification-panel absolute left-1 right-1 top-full z-40 rounded-xl border border-gray-200 bg-white p-4 text-center text-sm text-gray-500 shadow-xl">No notifications yet.</div></div>
      <div class="my-3 border-t sidebar-border"></div>
      @foreach($primaryNavItems as $item)
        @if($loop->first) @continue @endif
        @if($isTeacherOnly && !$item['teacher']) @continue @endif
        <a href="{{ $item['href'] }}" title="{{ $item['label'] }}" class="nav-item {{ request()->routeIs($item['route']) ? 'is-active border' : '' }} mb-0.5 flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm"><i class="nav-icon fa-solid {{ $item['icon'] }} w-4 text-center"></i><span class="nav-copy">{{ $item['label'] }}</span></a>
      @endforeach
      <details class="mt-2" @if(collect($platformNavItems)->contains(fn($item) => request()->routeIs($item['route']))) open @endif>
        <summary title="More tools" class="nav-item flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-sm"><i class="nav-icon fa-solid fa-ellipsis w-4 text-center" aria-hidden="true"></i><span class="nav-copy">More tools</span></summary>
        @foreach($platformNavItems as $item)
          @if($isTeacherOnly && !$item['teacher']) @continue @endif
          <a href="{{ $item['href'] }}" title="{{ $item['label'] }}" class="nav-item {{ request()->routeIs($item['route']) ? 'is-active border' : '' }} mb-0.5 flex items-center gap-2 rounded-lg px-2.5 py-2 pl-7 text-sm"><i class="nav-icon fa-solid {{ $item['icon'] }} w-4 text-center" aria-hidden="true"></i><span class="nav-copy">{{ $item['label'] }}</span></a>
        @endforeach
      </details>
    </nav>
    <div class="sidebar-border border-t p-2">
      @unless($hasCourses)<div class="getting-started onboarding-card mb-2 rounded-xl border border-gray-200 p-3"><div class="flex items-center gap-2 text-sm font-semibold"><i class="fa-solid fa-diagram-project text-xs" aria-hidden="true"></i>Getting started</div><p class="sidebar-copy muted mt-1 text-xs">Create your first course.</p><a href="{{ $organizationContext ? route('instructor.courses.index', ['vendor_id'=>$organizationContext->id]) : route('admin.courses', ['new'=>1]) }}" class="sidebar-copy mt-2 flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-2 py-1.5 text-sm font-medium text-white hover:bg-blue-600">Create course</a></div>@endunless
      <div class="flex gap-2 px-1 pb-1"><a href="{{ $organizationContext ? route('vendor.my-profile') : route('admin.profile') }}" class="footer-action flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm muted hover:bg-black/5" title="My Profile"><i class="fa-regular fa-user"></i><span class="nav-copy">My Profile</span></a>@if($user->hasRole('teacher'))<a href="{{ route('studio.dashboard') }}" class="footer-action flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm muted hover:bg-black/5" title="Creator Studio"><i class="fa-solid fa-bolt"></i><span class="nav-copy">Creator Studio</span></a>@endif</div>
      <button id="sidebarToggle" type="button" class="footer-action w-full flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm muted hover:bg-black/5" title="Collapse sidebar"><i class="fa-solid fa-right-from-bracket rotate-180"></i><span class="collapse-copy">Collapse</span></button>
    </div>
  </aside>
  <div id="mobileOverlay" class="fixed inset-0 z-20 hidden bg-black/40 lg:hidden"></div>
  <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
    <header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-4 md:px-6 flex-shrink-0 z-10"><div class="flex items-center gap-3"><button id="mobileMenuBtn" type="button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false" class="lg:hidden flex h-11 w-11 items-center justify-center rounded-xl hover:bg-gray-100 text-gray-500"><i class="fa-solid fa-bars" aria-hidden="true"></i></button><div><h1 class="text-base md:text-lg font-bold text-gray-900">@yield('page-title', 'Dashboard')</h1><p class="max-w-[220px] truncate text-xs text-gray-500 sm:max-w-none">@yield('page-sub', $organizationContext?->brand_name ?? 'Skillspot.in LMS Admin')</p></div></div><div class="flex items-center gap-3">@yield('page-actions')@yield('header-actions')<a href="{{ $organizationContext ? route('vendor.my-profile') : route('admin.profile') }}" title="My Profile" class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-accent-500 text-sm font-black text-white">{{ strtoupper(substr($user->name ?? 'A', 0, 1)) }}</a></div></header>
    <main id="main-content" tabindex="-1" data-navigation-main class="flex-1 overflow-y-auto">@if(session('impersonating_as'))<div class="bg-purple-600 text-white px-4 py-2.5 flex items-center justify-between text-sm"><span class="font-semibold"><i class="fa-solid fa-user-shield mr-1.5" aria-hidden="true"></i>Impersonating: <strong>{{ $user->name }}</strong> ({{ $user->roles->first()?->name ?? 'user' }})</span><a href="{{ route('admin.users.impersonate.stop') }}" class="bg-white text-purple-600 font-bold px-4 py-1.5 rounded-lg text-xs">Stop Impersonating <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i></a></div>@endif<div class="p-4 md:p-6">@yield('admin-content')</div></main>
  </div>
</div>
<div id="commandPalette" class="command-palette" role="dialog" aria-modal="true" aria-label="Search Skillspot"><div class="command-box overflow-hidden rounded-2xl bg-white shadow-2xl"><div class="flex items-center border-b border-gray-100 px-4"><i class="fa-solid fa-magnifying-glass text-gray-400"></i><input id="commandInput" type="search" autocomplete="off" placeholder="Search pages…" class="w-full border-0 px-3 py-4 text-sm outline-none"><button id="commandClose" type="button" class="text-gray-400 hover:text-gray-700">Esc</button></div><div id="commandItems" class="max-h-80 overflow-y-auto p-2">@foreach($navItems as $item)@if($isTeacherOnly && !$item['teacher']) @continue @endif<a href="{{ $item['href'] }}" data-command-item="{{ strtolower($item['label']) }}" class="command-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100"><i class="fa-solid {{ $item['icon'] }} w-4 text-gray-400"></i>{{ $item['label'] }}</a>@endforeach<a href="{{ $organizationContext ? route('vendor.my-profile') : route('admin.profile') }}" data-command-item="profile" class="command-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100"><i class="fa-regular fa-user w-4 text-gray-400"></i>My Profile</a>@if(!$isTeacherOnly && !$organizationContext)<a href="{{ route('admin.settings') }}" data-command-item="settings" class="command-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100"><i class="fa-solid fa-gear w-4 text-gray-400"></i>Settings</a>@endif</div></div></div>
@unless($organizationContext)<div id="settingsModal" class="fixed inset-0 z-[70] hidden flex items-center justify-center bg-black/65 p-0 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Settings"><div id="settingsSurface" aria-busy="true" class="settings-surface relative h-[100dvh] w-full max-w-5xl overflow-hidden bg-white shadow-2xl sm:h-[min(860px,calc(100dvh-4rem))] sm:rounded-2xl"><div id="settingsLoading" role="status" class="absolute inset-0 z-10 bg-white p-8 text-sm font-medium text-gray-500"><span class="sr-only">Loading settings</span><div class="mx-auto max-w-3xl"><div class="skillspot-route-loading__line"></div><div class="skillspot-route-loading__line"></div><div class="skillspot-route-loading__line"></div><div class="skillspot-route-loading__line"></div></div></div><iframe id="settingsFrame" data-settings-url="{{ route('admin.settings') }}" title="Settings" class="h-full w-full border-0 opacity-0 transition-opacity duration-150"></iframe></div></div>@endunless
@include('partials.ui-icon-normalizer')
@stack('scripts')
</body>
</html>
