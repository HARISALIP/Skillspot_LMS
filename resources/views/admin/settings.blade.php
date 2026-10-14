@extends(request()->boolean('embed') ? 'layouts.admin-embed' : 'layouts.admin')
@section('title', 'Settings — Skillspot.in Admin')
@section('page-title', 'Settings')
@section('page-sub', 'Configure your learning platform')

@push('styles')
<style>
  html,body,main { height:100%; background:#fff!important; }
  main { padding:0!important; overflow:hidden!important; }
  .settings-workspace { height:100vh; min-height:0; background:#fff; color:#1f2937; }
  .settings-sidebar,.settings-content { scrollbar-width:none; -ms-overflow-style:none; }
  .settings-sidebar::-webkit-scrollbar,.settings-content::-webkit-scrollbar { display:none; }
  .settings-sidebar { height:100%; min-height:0; overflow-y:auto; }
  .settings-content { min-height:0; overflow-y:auto; }
  .settings-workspace[data-theme="dark"] { background:#202020; color:#d0d0d0; }
  .settings-workspace[data-theme="dark"] .settings-sidebar { background:#242424; border-color:#383838; }
  .settings-workspace[data-theme="dark"] .text-gray-900 { color:#e4e4e4!important; }
  .settings-workspace[data-theme="dark"] .text-gray-800,.settings-workspace[data-theme="dark"] .text-gray-700 { color:#d0d0d0!important; }
  .settings-workspace[data-theme="dark"] .text-gray-600,.settings-workspace[data-theme="dark"] .text-gray-500,.settings-workspace[data-theme="dark"] .text-gray-400 { color:#a3a3a3!important; }
  .settings-workspace[data-theme="dark"] .border-gray-100,.settings-workspace[data-theme="dark"] .border-gray-200 { border-color:#383838!important; }
  .settings-workspace[data-theme="dark"] .bg-white,.settings-workspace[data-theme="dark"] .bg-gray-50,.settings-workspace[data-theme="dark"] .bg-gray-100 { background-color:#292929!important; }
  .settings-workspace[data-theme="dark"] input,.settings-workspace[data-theme="dark"] select,.settings-workspace[data-theme="dark"] textarea { background:#2a2a2a!important; border-color:#3e3e3e!important; color:#e4e4e4!important; }
  .settings-workspace[data-theme="dark"] option { background:#2a2a2a; color:#e4e4e4; }
  .settings-workspace[data-theme="dark"] .settings-nav-item { color:#d0d0d0; }
  .settings-workspace[data-theme="dark"] .settings-nav-item:hover { background:#303030; color:#fff; }
  .settings-workspace[data-theme="dark"] .settings-nav-item.is-active { background:#414141; color:#fff; }
  .settings-workspace[data-theme="dark"] .settings-panel-head,.settings-workspace[data-theme="dark"] .settings-save-bar { background:#202020!important; border-color:#383838!important; }
  .settings-workspace[data-theme="dark"] dialog { background:#242424; color:#e4e4e4; }
</style>
@endpush

@section('admin-content')

@php
$embedded = request()->boolean('embed');
$settingsQuery = $embedded ? ['embed' => 1] : [];
$tabs = [
  'general' => ['group'=>'System Configuration','fa'=>'fa-solid fa-sliders','label'=>'General','description'=>'Platform preferences'],
  'signup' => ['group'=>'System Configuration','fa'=>'fa-solid fa-right-to-bracket','label'=>'Signup','description'=>'Registration and consent'],
  'course-progress' => ['group'=>'Course Configuration','fa'=>'fa-solid fa-chart-line','label'=>'Progress','description'=>'Course completion rules'],
  'certificates' => ['group'=>'Course Configuration','fa'=>'fa-solid fa-certificate','label'=>'Certificates','description'=>'Automatic certificate design and delivery'],
  'badge-assignments' => ['group'=>'Course Configuration','fa'=>'fa-solid fa-certificate','label'=>'Badge Assignments','description'=>'Badge assignment settings'],
  'categories' => ['group'=>'Course Configuration','fa'=>'fa-solid fa-network-wired','label'=>'Categories','description'=>'Create and manage course categories'],
  'communication' => ['group'=>'Communication','fa'=>'fa-solid fa-sliders','label'=>'General','description'=>'Contact and notification preferences'],
  'email-template' => ['group'=>'Communication','fa'=>'fa-solid fa-envelope-open-text','label'=>'Email Template','description'=>'Email template management'],
  'mail' => ['group'=>'Communication','fa'=>'fa-solid fa-envelope','label'=>'Email Accounts','description'=>'SMTP and invitation delivery'],
  'members' => ['group'=>'User Management','fa'=>'fa-solid fa-user','label'=>'Users','description'=>'Member roles and access'],
  'payment' => ['group'=>'Payment','fa'=>'fa-solid fa-credit-card','label'=>'Configuration','description'=>'Course pricing and payment settings'],
  'payment-gateways' => ['group'=>'Payment','fa'=>'fa-solid fa-dollar-sign','label'=>'Gateways','description'=>'Payment gateway management'],
  'transactions' => ['group'=>'Payment','fa'=>'fa-solid fa-building-columns','label'=>'Transactions','description'=>'Payment transaction history'],
  'coupons' => ['group'=>'Payment','fa'=>'fa-solid fa-ticket','label'=>'Coupons','description'=>'Course discount codes'],
  'storage' => ['group'=>'Integrations','fa'=>'fa-solid fa-key','label'=>'Services','description'=>'Storage and service credentials'],
  'google-calendar' => ['group'=>'Integrations','fa'=>'fa-solid fa-calendar','label'=>'Google Calendar','description'=>'Calendar integration'],
  'google-meet' => ['group'=>'Integrations','fa'=>'fa-solid fa-person-chalkboard','label'=>'Google Meet','description'=>'Meeting integration'],
  'zoom' => ['group'=>'Integrations','fa'=>'fa-solid fa-video','label'=>'Zoom','description'=>'Zoom integration'],
  'raven' => ['group'=>'Integrations','fa'=>'fa-solid fa-comments','label'=>'Raven','description'=>'Messaging integration'],
  'branding' => ['group'=>'Customization','fa'=>'fa-solid fa-palette','label'=>'Branding','description'=>'Academy identity and visual settings'],
  'sidebar' => ['group'=>'Customization','fa'=>'fa-solid fa-table-columns','label'=>'Sidebar','description'=>'Navigation appearance'],
  'seo' => ['group'=>'Customization','fa'=>'fa-solid fa-magnifying-glass','label'=>'SEO','description'=>'Search and social metadata'],
];
@endphp

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold shadow-sm">
  <i class="fa-solid fa-circle-check text-green-600 text-lg flex-shrink-0"></i> {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600 text-lg leading-none">×</button>
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-3.5 text-sm font-semibold shadow-sm">
  <i class="fa-solid fa-circle-xmark text-red-600 text-lg flex-shrink-0"></i> {{ session('error') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-600 text-lg leading-none">×</button>
</div>
@endif

<div class="settings-workspace settings-shell flex flex-col overflow-hidden md:flex-row" data-theme="{{ request('theme') === 'light' ? 'light' : 'dark' }}">

  {{-- Sidebar tabs --}}
  <div data-navigation-sidebar="settings" class="settings-sidebar shrink-0 border-b border-gray-200 bg-gray-50 md:w-[220px] md:border-b-0 md:border-r overflow-y-auto">
    <div class="md:hidden flex gap-2 overflow-x-auto pb-2 snap-x -mx-1 px-1">
      @foreach($tabs as $key => $tab)
      <a href="{{ route('admin.settings', array_merge(['tab' => $key], $settingsQuery)) }}" data-settings-tab="{{ $key }}"
         class="snap-start flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition
                {{ $activeTab === $key ? 'bg-brand-600 text-white shadow-lg shadow-brand-200' : 'bg-white text-gray-600 border border-gray-200 hover:border-brand-300 hover:text-brand-600' }}">
        <i class="{{ $tab['fa'] }}"></i> {{ $tab['label'] }}
      </a>
      @endforeach
    </div>
    <div class="hidden md:block">
      <nav class="space-y-4 p-2">
        @php $lastGroup = null; @endphp
        @foreach($tabs as $key => $tab)
        @if($lastGroup !== $tab['group'])
          <p class="px-2 text-xs font-medium text-gray-400">{{ $tab['group'] }}</p>
          @php $lastGroup = $tab['group']; @endphp
        @endif
        <a href="{{ route('admin.settings', array_merge(['tab' => $key], $settingsQuery)) }}" data-settings-tab="{{ $key }}"
           class="settings-nav-item {{ $activeTab === $key ? 'is-active' : '' }} flex h-7 items-center gap-2 rounded-md px-2 text-[14px] transition font-medium">
          <span class="w-4 text-center text-gray-400">
            <i class="{{ $tab['fa'] }} text-xs"></i>
          </span>
          <span>{{ $tab['label'] }}</span>
          @if($activeTab === $key)
          <svg class="w-4 h-4 ml-auto opacity-60" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
          @endif
        </a>
        @endforeach
      </nav>
    </div>
  </div>

  {{-- Main panel --}}
  <div class="settings-content relative flex-1 min-w-0">
    <div class="min-h-full overflow-hidden">

      <div class="absolute right-1 top-1 z-10">
        @if(request()->boolean('embed'))
          <button type="button" onclick="window.parent.postMessage('skillspot:close-settings', window.location.origin)" aria-label="Close settings" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-700 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        @else
          <a href="{{ route('admin.dashboard') }}" aria-label="Close settings" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-700 hover:text-white"><i class="fa-solid fa-xmark"></i></a>
        @endif
      </div>

      @php $managedPanel = $activeTab === 'categories' || !array_key_exists($activeTab, $schema); @endphp
      @if($managedPanel)
      <div id="settingsForm">
      @else
      <form method="POST" action="{{ route('admin.settings.save', array_merge(['group' => $activeTab], $settingsQuery)) }}" id="settingsForm">
        @csrf
      @endif
        <div class="relative space-y-6 p-8">

          {{-- LINKED MANAGEMENT AREA --}}
          @if($activeTab === 'categories')
          <section class="category-settings space-y-5 pr-10">
            <div class="flex items-center justify-between gap-3">
              <h2 class="text-lg font-bold text-gray-900">Categories</h2>
              <button type="button" onclick="document.getElementById('newCategoryDialog').showModal()" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-50">
                <i class="fa-solid fa-plus"></i> New
              </button>
            </div>
            <div class="relative w-full max-w-xs">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
                <input id="categorySearch" type="search" placeholder="Search" class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
            </div>

            <div class="overflow-hidden">
              <div class="category-head grid grid-cols-[1fr_9rem_2rem] gap-3 border-b border-gray-100 px-1 py-3 text-xs font-medium text-gray-400">
                <span>Category</span><span>Created</span><span></span>
              </div>
              <div id="categoryRows">
                @forelse($categories as $category)
                <div data-category-row data-category-name="{{ strtolower($category->name) }}" class="grid grid-cols-[1fr_9rem_2rem] items-center gap-3 border-b border-gray-100 px-1 py-4 text-sm">
                  <button type="button" onclick="openCategoryEditor({{ $category->id }}, @js($category->name))" class="truncate text-left font-medium text-gray-800 hover:text-brand-600">{{ $category->name }}</button>
                  <span class="text-gray-500">{{ $category->created_at?->format('d M Y') }}</span>
                  <details class="relative justify-self-end">
                    <summary class="cursor-pointer list-none rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-800" aria-label="Category actions"><i class="fa-solid fa-ellipsis"></i></summary>
                    <div class="absolute right-0 z-10 mt-1 w-28 overflow-hidden rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-black/5">
                      <button type="button" onclick="openCategoryEditor({{ $category->id }}, @js($category->name))" class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50">Edit</button>
                      <form method="POST" action="{{ route('admin.settings.categories.destroy', $category) }}" onsubmit="return confirm('Delete {{ addslashes($category->name) }}?')">
                        @csrf @method('DELETE')
                        @if($embedded)<input type="hidden" name="embed" value="1">@endif
                        <button class="block w-full px-3 py-2 text-left text-red-600 hover:bg-red-50">Delete</button>
                      </form>
                    </div>
                  </details>
                </div>
                @empty
                <p id="emptyCategories" class="py-10 text-center text-sm text-gray-400">No categories yet.</p>
                @endforelse
              </div>
            </div>
          </section>

          {{-- CERTIFICATES --}}
          @elseif($activeTab === 'certificates')
          @php
            $certificateValue = fn ($key) => old($key, $settings[$key] ?? $schema['certificates'][$key]['default']);
            $previewTitle = $certificateValue('certificate_title');
            $previewIntro = $certificateValue('certificate_intro');
            $certificateToken = '{' . '{course_title}}';
            $previewCompletion = str_replace([$certificateToken, '{}'], 'Modern Web Development', $certificateValue('certificate_completion_text'));
          @endphp
          <section class="space-y-5 pr-10">
            <div>
              <h2 class="text-lg font-bold text-gray-900">Certificate design</h2>
              <p class="mt-1 text-sm text-gray-500">Certificates are issued automatically after a learner completes every lesson in a course.</p>
            </div>
            <label class="flex cursor-pointer items-center justify-between gap-4 border-y border-gray-200 py-3">
              <div><p class="text-sm font-semibold text-gray-900">Automatically issue certificates</p><p class="mt-0.5 text-xs text-gray-500">Create one verified certificate when course completion is reached.</p></div>
              <span class="relative shrink-0"><input type="checkbox" name="certificate_auto_issue" class="peer sr-only" {{ $certificateValue('certificate_auto_issue') === '1' ? 'checked' : '' }}><span class="block h-4 w-[26px] rounded-full border-2 border-transparent bg-gray-800 transition-colors duration-100 ease-in-out peer-checked:bg-brand-600"></span><span class="absolute left-0.5 top-0.5 h-3 w-3 rounded-full bg-white shadow transition duration-100 ease-in-out peer-checked:translate-x-2.5"></span></span>
            </label>
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(18rem,0.85fr)]">
              <div class="space-y-4">
                @foreach(['certificate_title', 'certificate_intro', 'certificate_completion_text', 'certificate_signatory', 'certificate_footer'] as $key)
                <label class="block text-xs font-semibold text-gray-700">{{ $schema['certificates'][$key]['label'] }}
                  <input name="{{ $key }}" value="{{ $certificateValue($key) }}" class="mt-1.5 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                </label>
                @endforeach
                <p class="text-xs text-gray-400">Use <code class="rounded bg-gray-100 px-1">&#123;&#123;course_title&#125;&#125;</code> to insert the completed course name.</p>
              </div>
              <div class="rounded-xl border-4 border-amber-500/70 bg-amber-50 p-5 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-700">Skillspot LMS</p>
                <h3 class="mt-4 font-serif text-2xl text-gray-900">{{ $previewTitle }}</h3>
                <p class="mt-4 text-xs text-gray-600">{{ $previewIntro }}</p>
                <p class="mt-2 font-serif text-xl italic text-brand-700">Jane Learner</p>
                <p class="mt-3 text-sm text-gray-700">{{ $previewCompletion }}</p>
                <div class="mt-7 flex items-end justify-between gap-3 border-t border-amber-200 pt-3 text-[10px] text-gray-500"><span>Certificate ID: ITF-PREVIEW-2026</span><span class="border-t border-amber-400 pt-1">{{ $certificateValue('certificate_signatory') }}</span></div>
                <p class="mt-3 text-[10px] text-gray-500">{{ $certificateValue('certificate_footer') }}</p>
              </div>
            </div>
          </section>

          {{-- GENERAL --}}
          @elseif($activeTab === 'general')
          <section class="space-y-2 pr-10">
            <h2 class="text-sm font-semibold text-gray-900">Preferences</h2>
            <div class="divide-y divide-gray-200">
              @foreach(['system_language', 'system_timezone', 'text_direction'] as $key)
              <div class="grid gap-3 py-2 md:grid-cols-[1fr_12rem] md:items-center">
                <div>
                  <p class="text-xs font-semibold text-gray-900">{{ $schema['general'][$key]['label'] }}</p>
                  <p class="mt-0.5 text-[11px] leading-tight text-gray-500">{{ match($key) {'system_language' => 'The language this site falls back to.', 'system_timezone' => 'The timezone new courses and batches start from.', default => 'Auto follows the language of the site.'} }}</p>
                </div>
                <select name="{{ $key }}" class="w-48 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                  @foreach($schema['general'][$key]['options'] as $optionValue => $optionLabel)
                  <option value="{{ $optionValue }}" @selected(old($key, $settings[$key] ?? $schema['general'][$key]['default']) === $optionValue)>{{ $optionLabel }}</option>
                  @endforeach
                </select>
              </div>
              @endforeach
            </div>
          </section>

          <section class="space-y-2 pt-4">
            <h2 class="text-sm font-semibold text-gray-900">Access &amp; Availability</h2>
            <div class="divide-y divide-gray-200">
              @foreach(['allow_guest_access' => 'If enabled, visitors can access the course and batch lists without logging in.', 'disable_pwa' => 'If checked, users will not be able to install the application as a Progressive Web App.', 'allow_job_posting' => 'If enabled, users can post job openings on the job board. Else only admins can post jobs.'] as $key => $description)
              <label class="flex cursor-pointer items-center justify-between gap-3 py-2">
                <div><p class="text-xs font-semibold text-gray-900">{{ $schema['general'][$key]['label'] }}</p><p class="mt-0.5 text-[11px] leading-tight text-gray-500">{{ $description }}</p></div>
                <span class="relative shrink-0"><input type="checkbox" name="{{ $key }}" class="peer sr-only" {{ ($settings[$key] ?? $schema['general'][$key]['default']) === '1' ? 'checked' : '' }}><span class="block h-4 w-[26px] rounded-full border-2 border-transparent bg-gray-800 transition-colors duration-100 ease-in-out peer-checked:bg-brand-600"></span><span class="absolute left-0.5 top-0.5 h-3 w-3 rounded-full bg-white shadow transition duration-100 ease-in-out peer-checked:translate-x-2.5"></span></span>
              </label>
              @endforeach
            </div>
          </section>

          <section class="space-y-2 pt-4"><h2 class="text-sm font-semibold text-gray-900">Evaluations</h2><div class="divide-y divide-gray-200"><label class="flex cursor-pointer items-center justify-between gap-3 py-2"><div><p class="text-xs font-semibold text-gray-900">Send calendar invite for evaluations</p><p class="mt-0.5 text-[11px] leading-tight text-gray-500">If enabled, sends a calendar invite to the student for evaluations.</p></div><span class="relative shrink-0"><input type="checkbox" name="send_evaluation_invites" class="peer sr-only" {{ ($settings['send_evaluation_invites'] ?? '0') === '1' ? 'checked' : '' }}><span class="block h-4 w-[26px] rounded-full border-2 border-transparent bg-gray-800 transition-colors duration-100 ease-in-out peer-checked:bg-brand-600"></span><span class="absolute left-0.5 top-0.5 h-3 w-3 rounded-full bg-white shadow transition duration-100 ease-in-out peer-checked:translate-x-2.5"></span></span></label></div></section>

          <section class="space-y-2 pt-4"><h2 class="text-sm font-semibold text-gray-900">Notifications</h2><div class="divide-y divide-gray-200">@foreach(['published_course_notification' => 'Notify members when a new course is published.', 'published_batch_notification' => 'Notify members when a new batch is published.'] as $key => $description)<div class="grid gap-3 py-2 md:grid-cols-[1fr_12rem] md:items-center"><div><p class="text-xs font-semibold text-gray-900">{{ $schema['general'][$key]['label'] }}</p><p class="mt-0.5 text-[11px] leading-tight text-gray-500">{{ $description }}</p></div><select name="{{ $key }}" class="w-48 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500">@foreach($schema['general'][$key]['options'] as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected(old($key, $settings[$key] ?? $schema['general'][$key]['default']) === $optionValue)>{{ $optionLabel }}</option>@endforeach</select></div>@endforeach</div></section>

          {{-- COMMUNICATION --}}
          @elseif($activeTab === 'communication')
          <section class="space-y-4">
            <h2 class="text-lg font-bold text-gray-900">Contact Information</h2>
            <p class="text-sm text-gray-500">These details are used wherever learners need to contact Skillspot.</p>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
              @foreach($schema['communication'] as $key => $meta)
                <x-settings-field :field="$key" :meta="$meta" :value="$settings[$key] ?? $meta['default']"/>
              @endforeach
            </div>
          </section>

          {{-- BRANDING --}}
          @elseif($activeTab === 'branding')
          <div class="bg-gradient-to-r from-brand-50 to-accent-50 border border-brand-200 rounded-2xl p-5">
            <div class="flex items-start gap-3">
              <span class="text-2xl flex-shrink-0">🌐</span>
              <div class="flex-1">
                <div class="font-bold text-gray-900 text-sm mb-1">App Domain / URL</div>
                <p class="text-xs text-gray-500 mb-3">Updates <code class="bg-white px-1.5 py-0.5 rounded border border-gray-200 font-mono text-xs">APP_URL</code> in .env and all generated links.</p>
                <input type="text" name="app_url" value="{{ old('app_url', $settings['app_url'] ?? 'https://Skillspot.in') }}"
                       class="w-full px-4 py-3 rounded-xl border border-brand-200 bg-white text-gray-900 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 transition"
                       placeholder="https://yourdomain.com">
                <div class="flex items-center gap-2 mt-2">
                  <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                  <span class="text-xs text-gray-500">Currently: <strong>{{ $settings['app_url'] ?? config('app.url') }}</strong></span>
                </div>
              </div>
            </div>
          </div>
          {{-- Logo & Favicon upload cards --}}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Logo --}}
            <div class="border border-gray-200 rounded-2xl p-4 bg-gray-50">
              <div class="flex items-center gap-2 mb-3">
                <span class="text-lg">🏷️</span>
                <div>
                  <div class="text-sm font-bold text-gray-900">Academy Logo</div>
                  <div class="text-xs text-gray-400">Recommended: <strong>200×60 px</strong> · PNG/SVG with transparent background</div>
                </div>
              </div>
              {{-- Logo preview --}}
              @if(!empty($settings['logo_url']))
              <div class="mb-3 p-3 bg-white rounded-xl border border-gray-200 flex items-center justify-center min-h-[60px]">
                <img src="{{ $settings['logo_url'] }}" alt="Logo" class="max-h-14 max-w-full object-contain"
                     onerror="this.parentElement.innerHTML='<span class=\'text-gray-400 text-xs\'>Preview unavailable</span>'">
              </div>
              @endif
              <x-media-picker
                name="logo_url"
                :value="$settings['logo_url'] ?? ''"
                label=""
                type="image"
                folder="settings"
                cropRatio="200:60"
              />
              <p class="text-xs text-gray-400 mt-2">💡 Use SVG or PNG with transparent bg for best results</p>
            </div>

            {{-- Favicon --}}
            <div class="border border-gray-200 rounded-2xl p-4 bg-gray-50">
              <div class="flex items-center gap-2 mb-3">
                <span class="text-lg">🌐</span>
                <div>
                  <div class="text-sm font-bold text-gray-900">Favicon</div>
                  <div class="text-xs text-gray-400">Required: <strong>32×32 px</strong> or <strong>64×64 px</strong> · ICO/PNG/SVG</div>
                </div>
              </div>
              {{-- Favicon preview --}}
              @if(!empty($settings['favicon_url']))
              <div class="mb-3 p-3 bg-white rounded-xl border border-gray-200 flex items-center justify-center">
                <img src="{{ $settings['favicon_url'] }}" alt="Favicon" class="w-10 h-10 object-contain"
                     onerror="this.parentElement.innerHTML='<span class=\'text-gray-400 text-xs\'>Preview unavailable</span>'">
              </div>
              @endif
              <x-media-picker
                name="favicon_url"
                :value="$settings['favicon_url'] ?? ''"
                label=""
                type="image"
                folder="settings"
                cropRatio="1:1"
              />
              <p class="text-xs text-gray-400 mt-2">💡 32×32 or 64×64 px square image · Shown in browser tab</p>
            </div>
          </div>

          {{-- Rest of branding fields --}}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($schema['branding'] as $key => $meta)
              @if(in_array($key, ['app_url','logo_url','favicon_url'])) @continue @endif
              <div class="{{ $meta['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
                <x-settings-field :field="$key" :meta="$meta" :value="$settings[$key] ?? $meta['default']"/>
              </div>
            @endforeach
          </div>

          {{-- MAIL --}}
          @elseif($activeTab === 'mail')
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($schema['mail'] as $key => $meta)
            <div><x-settings-field :field="$key" :meta="$meta" :value="$settings[$key] ?? $meta['default']"/></div>
            @endforeach
          </div>
          <div class="pt-5 border-t border-dashed border-gray-200">
            <p class="text-sm font-bold text-gray-700 mb-3">🧪 Test SMTP Connection</p>
            <div class="flex flex-col sm:flex-row gap-3">
              <input type="email" name="test_email_addr" value="{{ auth()->user()->email }}"
                     class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50">
              <button type="submit" formaction="{{ route('admin.settings.mail.test', $settingsQuery) }}"
                      class="flex-shrink-0 px-5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-sm font-bold transition">
                📨 Send Test Email
              </button>
            </div>
          </div>

          {{-- STORAGE --}}
          @elseif($activeTab === 'storage')
          <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex items-start gap-3">
            <span class="text-2xl flex-shrink-0">☁️</span>
            <div class="text-sm text-blue-700">
              <strong>Cloudflare R2</strong> — credentials loaded dynamically at runtime. No restart needed.
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($schema['storage'] as $key => $meta)
            <div class="{{ in_array($key, ['r2_endpoint','r2_public_url']) ? 'md:col-span-2' : '' }}">
              <x-settings-field :field="$key" :meta="$meta" :value="$settings[$key] ?? $meta['default']"/>
            </div>
            @endforeach
          </div>

          {{-- PAYMENT --}}
          @elseif($activeTab === 'payment')
          @php $mode = $settings['razorpay_mode'] ?? 'live'; @endphp
          <div class="flex items-center gap-3 p-4 rounded-2xl border-2 {{ $mode === 'live' ? 'border-green-200 bg-green-50' : 'border-yellow-200 bg-yellow-50' }}">
            <span class="text-2xl">{{ $mode === 'live' ? '🟢' : '🟡' }}</span>
            <div>
              <div class="font-black text-gray-900">Mode: <span class="{{ $mode === 'live' ? 'text-green-600' : 'text-yellow-600' }}">{{ strtoupper($mode) }}</span></div>
              <div class="text-xs text-gray-500">Set to <strong>test</strong> while testing, <strong>live</strong> for production.</div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div><x-settings-field field="razorpay_mode"    :meta="$schema['payment']['razorpay_mode']"    :value="$settings['razorpay_mode'] ?? 'live'"/></div>
            <div><x-settings-field field="currency"         :meta="$schema['payment']['currency']"         :value="$settings['currency'] ?? 'INR'"/></div>
            <div><x-settings-field field="currency_symbol"  :meta="$schema['payment']['currency_symbol']"  :value="$settings['currency_symbol'] ?? '₹'"/></div>
          </div>
          <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-4"><span class="text-lg">🟢</span><span class="font-bold text-green-800 text-sm">Live Keys (Production)</span></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div><x-settings-field field="razorpay_key_id"         :meta="$schema['payment']['razorpay_key_id']"         :value="$settings['razorpay_key_id'] ?? ''"/></div>
              <div><x-settings-field field="razorpay_key_secret"     :meta="$schema['payment']['razorpay_key_secret']"     :value="$settings['razorpay_key_secret'] ?? ''"/></div>
              <div class="md:col-span-2"><x-settings-field field="razorpay_webhook_secret" :meta="$schema['payment']['razorpay_webhook_secret']" :value="$settings['razorpay_webhook_secret'] ?? ''"/></div>
            </div>
          </div>
          <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-4"><span class="text-lg">🟡</span><span class="font-bold text-yellow-800 text-sm">Test Keys (Development)</span></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div><x-settings-field field="razorpay_test_key_id"     :meta="$schema['payment']['razorpay_test_key_id']"     :value="$settings['razorpay_test_key_id'] ?? ''"/></div>
              <div><x-settings-field field="razorpay_test_key_secret" :meta="$schema['payment']['razorpay_test_key_secret']" :value="$settings['razorpay_test_key_secret'] ?? ''"/></div>
            </div>
          </div>
          <div class="bg-brand-50 border border-brand-200 rounded-2xl p-4">
            <div class="flex items-start gap-3">
              <span class="text-2xl flex-shrink-0">🔗</span>
              <div class="flex-1 min-w-0">
                <div class="font-bold text-brand-800 text-sm mb-1">Webhook URL</div>
                <div class="text-xs text-gray-500 mb-2">Add in Razorpay Dashboard → Settings → Webhooks. Events: <strong>payment.captured, payment.failed, refund.created</strong></div>
                <div class="flex items-center gap-2">
                  <code id="webhookUrl" class="flex-1 text-xs bg-white border border-brand-200 rounded-xl px-3 py-2 font-mono text-brand-700 break-all">{{ rtrim(config('app.url'),'/') }}/webhook/razorpay</code>
                  <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhookUrl').innerText).then(()=>{this.textContent='✅';setTimeout(()=>this.textContent='📋',2000)})"
                          class="flex-shrink-0 px-3 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold transition">📋</button>
                </div>
              </div>
            </div>
          </div>
          <div><x-settings-field field="enable_free_courses" :meta="$schema['payment']['enable_free_courses']" :value="$settings['enable_free_courses'] ?? '1'"/></div>

          {{-- LICENSE --}}
          @elseif($activeTab === 'license')
          @php
            $lstatus = $settings['license_status'] ?? 'active';
            $lplan   = strtoupper($settings['license_plan'] ?? 'PRO');
            $lexpiry = $settings['license_expires'] ?? '';
            $expired = $lexpiry && \Carbon\Carbon::parse($lexpiry)->isPast();
            $isOk    = $lstatus === 'active' && !$expired;
          @endphp
          <div class="rounded-2xl border-2 p-5 {{ $isOk ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
            <div class="flex flex-wrap items-center gap-4">
              <div class="text-4xl">{{ $isOk ? '✅' : '❌' }}</div>
              <div class="flex-1 min-w-0">
                <div class="font-black text-gray-900 text-lg">License: <span class="{{ $isOk ? 'text-green-600' : 'text-red-600' }}">{{ strtoupper($lstatus) }}</span></div>
                <div class="flex flex-wrap gap-2 mt-1.5">
                  <span class="text-xs bg-white border border-gray-200 rounded-lg px-3 py-1 font-semibold">Plan: {{ $lplan }}</span>
                  <span class="text-xs bg-white border border-gray-200 rounded-lg px-3 py-1 font-semibold {{ $expired ? 'text-red-600' : 'text-green-600' }}">{{ $lexpiry ? ($expired ? '⚠️ Expired: ' : '📅 Expires: ').$lexpiry : '♾️ Lifetime' }}</span>
                  <span class="text-xs bg-white border border-gray-200 rounded-lg px-3 py-1 font-semibold text-blue-600">Students: {{ ($settings['max_students'] ?? 0) == 0 ? '∞' : $settings['max_students'] }}</span>
                  <span class="text-xs bg-white border border-gray-200 rounded-lg px-3 py-1 font-semibold text-purple-600">Courses: {{ ($settings['max_courses'] ?? 0) == 0 ? '∞' : $settings['max_courses'] }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($schema['license'] as $key => $meta)
            <div class="{{ $key === 'license_key' ? 'md:col-span-2' : '' }}">
              <x-settings-field :field="$key" :meta="$meta" :value="$settings[$key] ?? $meta['default']"/>
            </div>
            @endforeach
          </div>

          {{-- SECURITY & OTP --}}
          @elseif($activeTab === 'security')
          @php
            $smtpReady = !empty($settings['mail_host'] ?? '') && !empty($settings['mail_username'] ?? '');
            $smsReady  = !empty($settings['fast2sms_api_key'] ?? '');
            $emailOtp  = ($settings['otp_email_enabled'] ?? '0') === '1';
            $phoneOtp  = ($settings['otp_phone_enabled'] ?? '0') === '1';
          @endphp

          {{-- Human Verification --}}
          @php
            $hvLogin    = ($settings['human_verify_login']    ?? '1') === '1';
            $hvRegister = ($settings['human_verify_register'] ?? '1') === '1';
          @endphp
          <div class="rounded-2xl border-2 border-blue-200 bg-blue-50 p-5 mb-3">
            <div class="flex items-start gap-3 mb-4">
              <span class="text-2xl flex-shrink-0">🧮</span>
              <div>
                <div class="font-bold text-gray-900 text-sm">Human Verification (Math CAPTCHA)</div>
                <div class="text-xs text-gray-500 mt-0.5">Prevents bots on login &amp; registration forms. Simple math question shown to users.</div>
              </div>
            </div>
            <div class="space-y-3">

              {{-- Login CAPTCHA toggle --}}
              <div class="flex items-center justify-between gap-4 p-4 bg-white rounded-xl border border-blue-100">
                <div>
                  <div class="text-sm font-semibold text-gray-800">Login CAPTCHA</div>
                  <div class="text-xs text-gray-400 mt-0.5">Show math question on all login forms (including vendor portals)</div>
                  @if($hvLogin)
                    <span class="inline-flex items-center gap-1 mt-1 text-xs font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">✅ Enabled</span>
                  @else
                    <span class="inline-flex items-center gap-1 mt-1 text-xs font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded-full">🚫 Disabled</span>
                  @endif
                </div>
                <button type="button" onclick="settingToggle('human_verify_login', this)"
                    class="px-5 py-2 rounded-xl text-sm font-bold transition {{ $hvLogin ? 'bg-red-100 hover:bg-red-200 text-red-700 border border-red-200' : 'bg-green-100 hover:bg-green-200 text-green-700 border border-green-200' }}">
                    {{ $hvLogin ? '🚫 Turn OFF' : '✅ Turn ON' }}
                </button>
              </div>

              {{-- Register CAPTCHA toggle --}}
              <div class="flex items-center justify-between gap-4 p-4 bg-white rounded-xl border border-blue-100">
                <div>
                  <div class="text-sm font-semibold text-gray-800">Register CAPTCHA</div>
                  <div class="text-xs text-gray-400 mt-0.5">Show math question on all registration forms</div>
                  @if($hvRegister)
                    <span class="inline-flex items-center gap-1 mt-1 text-xs font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">✅ Enabled</span>
                  @else
                    <span class="inline-flex items-center gap-1 mt-1 text-xs font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded-full">🚫 Disabled</span>
                  @endif
                </div>
                <button type="button" onclick="settingToggle('human_verify_register', this)"
                    class="px-5 py-2 rounded-xl text-sm font-bold transition {{ $hvRegister ? 'bg-red-100 hover:bg-red-200 text-red-700 border border-red-200' : 'bg-green-100 hover:bg-green-200 text-green-700 border border-green-200' }}">
                    {{ $hvRegister ? '🚫 Turn OFF' : '✅ Turn ON' }}
                </button>
              </div>

            </div>
          </div>


          {{-- Public Registration --}}
          @php $regOn = ($settings['allow_registration'] ?? '1') === '1'; @endphp
          <div class="rounded-2xl border-2 p-5 mb-3 {{ $regOn ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
            <div class="flex items-start justify-between gap-4 flex-wrap">
              <div class="flex items-start gap-3 flex-1 min-w-0">
                <span class="text-2xl flex-shrink-0">{{ $regOn ? '✅' : '🚫' }}</span>
                <div>
                  <div class="font-bold text-gray-900 text-sm">Public Registration</div>
                  <div class="text-xs mt-1 leading-relaxed">
                    @if($regOn)
                      <span class="text-green-700 font-semibold">Open — New students can register at /register</span>
                    @else
                      <span class="text-red-700 font-semibold">Closed — /register shows a "Registration Closed" page. Existing users can still login.</span>
                    @endif
                  </div>
                </div>
              </div>
              <div class="flex-shrink-0">
                <x-settings-field field="allow_registration" :meta="$schema['security']['allow_registration']" :value="$settings['allow_registration'] ?? '1'"/>
              </div>
            </div>
          </div>

          {{-- Maintenance Mode --}}
          @php $maintOn = ($settings['maintenance_mode'] ?? '0') === '1'; @endphp
          <div class="rounded-2xl border-2 p-5 mb-3 {{ $maintOn ? 'border-orange-200 bg-orange-50' : 'border-gray-200 bg-gray-50' }}">
            <div class="flex items-start justify-between gap-4 flex-wrap">
              <div class="flex items-start gap-3 flex-1 min-w-0">
                <span class="text-2xl flex-shrink-0">{{ $maintOn ? '🔧' : '🟢' }}</span>
                <div>
                  <div class="font-bold text-gray-900 text-sm">Maintenance Mode</div>
                  <div class="text-xs mt-1 leading-relaxed">
                    @if($maintOn)
                      <span class="text-orange-700 font-semibold">⚠️ Active — Login & Register show a maintenance notice to visitors.</span>
                    @else
                      <span class="text-gray-500">Off — Platform running normally. Enable during updates or downtime.</span>
                    @endif
                  </div>
                </div>
              </div>
              <div class="flex-shrink-0">
                <x-settings-field field="maintenance_mode" :meta="$schema['security']['maintenance_mode']" :value="$settings['maintenance_mode'] ?? '0'"/>
              </div>
            </div>
          </div>

          {{-- Session & Login attempts --}}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><x-settings-field field="session_lifetime_min" :meta="$schema['security']['session_lifetime_min']" :value="$settings['session_lifetime_min'] ?? '120'"/></div>
            <div><x-settings-field field="max_login_attempts"   :meta="$schema['security']['max_login_attempts']"   :value="$settings['max_login_attempts'] ?? '5'"/></div>
          </div>

          {{-- SMS / Fast2SMS --}}
          <div class="border-t border-dashed border-gray-200 pt-5 mt-2">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-xl">📱</span>
              <div>
                <div class="font-black text-gray-900 text-sm">SMS Gateway — Fast2SMS</div>
                <div class="text-xs text-gray-400">Used for phone OTP verification (Quick SMS — no DLT needed)</div>
              </div>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
              <x-settings-field field="fast2sms_api_key" :meta="$schema['security']['fast2sms_api_key']" :value="$settings['fast2sms_api_key'] ?? ''"/>
              <div class="mt-2.5 flex items-center gap-2">
                @if($smsReady)
                  <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                  <span class="text-xs text-green-600 font-semibold">Fast2SMS connected ✅ — Quick SMS (no DLT) ready</span>
                @else
                  <span class="w-2 h-2 bg-gray-300 rounded-full"></span>
                  <span class="text-xs text-gray-400">Add your API key from <a href="https://www.fast2sms.com" target="_blank" class="text-brand-600 font-semibold hover:underline">fast2sms.com</a> → Dev API → Copy API Key</span>
                @endif
              </div>
            </div>
          </div>

          {{-- OTP section --}}
          <div class="border-t border-dashed border-gray-200 pt-5 mt-2">
            <div class="flex items-center gap-2 mb-4">
              <span class="text-xl">🔐</span>
              <div>
                <div class="font-black text-gray-900 text-sm">OTP Verification</div>
                <div class="text-xs text-gray-400">Control verification required during student registration</div>
              </div>
            </div>

            {{-- Email OTP --}}
            @php $emailBorder = $emailOtp && $smtpReady ? 'border-green-200 bg-green-50' : ($emailOtp && !$smtpReady ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200 bg-gray-50'); @endphp
            <div class="rounded-2xl border-2 p-5 mb-3 {{ $emailBorder }}">
              <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                  <span class="text-2xl flex-shrink-0">📧</span>
                  <div>
                    <div class="font-bold text-gray-900 text-sm">Email OTP on Register</div>
                    <div class="text-xs mt-1 leading-relaxed">
                      @if(!$smtpReady)
                        <span class="text-yellow-700">⚠️ SMTP not configured — OTP skipped even if enabled. Go to <a href="{{ route('admin.settings', ['tab'=>'mail']) }}" class="font-bold underline">Email / SMTP settings</a> first.</span>
                      @elseif($emailOtp)
                        <span class="text-green-700">✅ Active — students must verify email with 6-digit OTP at registration.</span>
                      @else
                        <span class="text-gray-500">SMTP ready. Enable to require email OTP at registration.</span>
                      @endif
                    </div>
                  </div>
                </div>
                <div class="flex-shrink-0">
                  <x-settings-field field="otp_email_enabled" :meta="$schema['security']['otp_email_enabled']" :value="$settings['otp_email_enabled'] ?? '0'"/>
                </div>
              </div>
            </div>

            {{-- Phone OTP --}}
            @php $phoneBorder = $phoneOtp && $smsReady ? 'border-green-200 bg-green-50' : ($phoneOtp && !$smsReady ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200 bg-gray-50'); @endphp
            <div class="rounded-2xl border-2 p-5 {{ $phoneBorder }}">
              <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                  <span class="text-2xl flex-shrink-0">📱</span>
                  <div>
                    <div class="font-bold text-gray-900 text-sm">Phone OTP on Register</div>
                    <div class="text-xs mt-1 leading-relaxed">
                      @if(!$smsReady)
                        <span class="text-red-600 font-semibold">⚠️ Fast2SMS API key not set — OTP will be skipped. Add key above first.</span>
                      @elseif($phoneOtp)
                        <span class="text-green-700 font-semibold">✅ Active — students must verify phone via SMS OTP at registration.</span>
                      @else
                        <span class="text-gray-500">Fast2SMS ready. Enable to require phone OTP at registration.</span>
                      @endif
                    </div>
                  </div>
                </div>
                <div class="flex-shrink-0">
                  <x-settings-field field="otp_phone_enabled" :meta="$schema['security']['otp_phone_enabled']" :value="$settings['otp_phone_enabled'] ?? '0'"/>
                </div>
              </div>
            </div>
          </div>

          {{-- Cache & Config Tools --}}
          <div class="border-t border-dashed border-red-100 pt-5 mt-2">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-lg">🛠️</span>
              <div>
                <div class="font-bold text-red-600 text-sm">Cache & Config Tools</div>
                <div class="text-xs text-gray-400">Clear cached data and reload platform configuration</div>
              </div>
            </div>
            <div class="flex flex-wrap gap-3">
              <button type="button" onclick="clearCache(this)"
                      class="flex items-center gap-2 px-5 py-2.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 border border-yellow-200 rounded-xl text-sm font-bold transition">
                🗑️ Clear All Cache
              </button>
              <button type="button" onclick="clearCache(this)"
                      class="flex items-center gap-2 px-5 py-2.5 bg-orange-50 hover:bg-orange-100 text-orange-700 border border-orange-200 rounded-xl text-sm font-bold transition">
                🔄 Reload Config
              </button>
            </div>
          </div>
          @elseif($activeTab === 'course-progress')
          <section class="space-y-4 pt-2 pr-10">
            <h2 class="text-sm font-semibold text-gray-900">Completion Time</h2>
            <div class="grid gap-3 border-b border-gray-200 pb-3 md:grid-cols-[1fr_8rem] md:items-center"><div><p class="text-xs font-semibold text-gray-900">Lesson Completion Time (seconds)</p><p class="mt-0.5 text-[11px] leading-tight text-gray-500">Seconds a learner must stay on a lesson before it auto-marks complete.</p></div><input type="number" min="1" name="lesson_dwell_time" value="{{ old('lesson_dwell_time', $settings['lesson_dwell_time'] ?? $schema['course-progress']['lesson_dwell_time']['default']) }}" class="w-32 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500"></div>
          </section>
          <section class="space-y-2 pt-4 pr-10"><h2 class="text-sm font-semibold text-gray-900">Enforcement</h2><div class="divide-y divide-gray-200">@foreach(['enforce_video_completion' => 'When enabled, lessons that contain a video can only be marked complete by playing the video to the end. If the video fails to load, the dwell timer is used as a fallback.', 'enforce_assignment_completion' => 'When enabled, lessons with an assignment cannot be marked complete until the assignment is submitted.', 'enforce_quiz_completion' => 'When enabled, lessons with a quiz cannot be marked complete until the quiz is submitted.', 'prevent_skipping_videos' => 'If enabled, users will not be able to move forward in a video.'] as $key => $description)<label class="flex cursor-pointer items-center justify-between gap-3 py-2"><div><p class="text-xs font-semibold text-gray-900">{{ $schema['course-progress'][$key]['label'] }}</p><p class="mt-0.5 text-[11px] leading-tight text-gray-500">{{ $description }}</p></div><span class="relative shrink-0"><input type="checkbox" name="{{ $key }}" class="peer sr-only" {{ ($settings[$key] ?? $schema['course-progress'][$key]['default']) === '1' ? 'checked' : '' }}><span class="block h-4 w-[26px] rounded-full border-2 border-transparent bg-gray-800 transition-colors duration-100 ease-in-out peer-checked:bg-brand-600"></span><span class="absolute left-0.5 top-0.5 h-3 w-3 rounded-full bg-white shadow transition duration-100 ease-in-out peer-checked:translate-x-2.5"></span></span></label>@endforeach</div></section>
          @elseif(in_array($activeTab, ['signup', 'seo']))
          <section class="space-y-5 pt-2">
            <h2 class="text-lg font-bold text-gray-900">{{ $tabs[$activeTab]['label'] }}</h2>
            <div class="divide-y divide-gray-200">
              @foreach($schema[$activeTab] as $key => $meta)
                @if($meta['type'] === 'boolean')
                <label class="flex cursor-pointer items-center justify-between gap-6 py-4"><p class="text-sm font-semibold text-gray-900">{{ $meta['label'] }}</p><span class="relative shrink-0"><input type="checkbox" name="{{ $key }}" class="peer sr-only" {{ ($settings[$key] ?? $meta['default']) === '1' ? 'checked' : '' }}><span class="block h-4 w-[26px] rounded-full border-2 border-transparent bg-gray-800 transition-colors duration-100 ease-in-out peer-checked:bg-brand-600"></span><span class="absolute left-0.5 top-0.5 h-3 w-3 rounded-full bg-white shadow transition duration-100 ease-in-out peer-checked:translate-x-2.5"></span></span></label>
                @else
                <div class="py-4"><x-settings-field :field="$key" :meta="$meta" :value="$settings[$key] ?? $meta['default']"/></div>
                @endif
              @endforeach
            </div>
          </section>
          @else
          <section class="min-h-[28rem] space-y-3 pt-2">
            <h2 class="text-lg font-bold text-gray-900">{{ $tabs[$activeTab]['label'] }}</h2>
            <p class="max-w-xl text-sm leading-6 text-gray-500">{{ $tabs[$activeTab]['description'] }}. Skillspot does not yet have the matching data model from Frappe LMS, so this panel stays inside Settings instead of sending you to an unrelated legacy page.</p>
          </section>
          @endif

        </div>

        {{-- Save bar --}}
        @unless($managedPanel)
        <div class="settings-save-bar px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <p class="text-xs text-gray-400">
            💡 Changes take effect <strong>immediately</strong> — no restart needed.
            @if($activeTab === 'branding') Domain & name also write to <code class="font-mono bg-gray-100 px-1 rounded">.env</code>. @endif
          </p>
          <button type="submit"
                  class="flex-shrink-0 flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 active:scale-[0.98] text-white font-black px-7 py-3 rounded-xl transition shadow-lg shadow-brand-200 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            Save {{ $tabs[$activeTab]['label'] }}
          </button>
        </div>
        @endunless
      @if($managedPanel)</div>@else</form>@endif
    </div>
  </div>
</div>

<script>
  (() => {
    const preference = localStorage.getItem('skillspot-theme');
    const theme = preference === 'light' || (preference === 'system' && !window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'light' : 'dark';
    document.body.dataset.theme = theme;
    document.querySelector('.settings-workspace')?.setAttribute('data-theme', theme);
  })();
</script>

@if($activeTab === 'categories')
<dialog id="newCategoryDialog" class="w-full max-w-md rounded-2xl bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/50">
  <form method="POST" action="{{ route('admin.settings.categories.store') }}" class="p-6">
    @csrf @if($embedded)<input type="hidden" name="embed" value="1">@endif
    <div class="flex items-center justify-between gap-4"><h3 class="text-lg font-bold">New Category</h3><button type="button" onclick="this.closest('dialog').close()" class="text-gray-400 hover:text-gray-700" aria-label="Close">×</button></div>
    <label class="mt-5 block text-sm font-medium">Name<input name="name" required maxlength="100" autofocus class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></label>
    <div class="mt-6 flex justify-end gap-3"><button type="button" onclick="this.closest('dialog').close()" class="px-4 py-2 text-sm text-gray-600">Cancel</button><button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Create</button></div>
  </form>
</dialog>

<dialog id="editCategoryDialog" class="w-full max-w-md rounded-2xl bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/50">
  <form method="POST" id="editCategoryForm" class="p-6">
    @csrf @method('PUT') @if($embedded)<input type="hidden" name="embed" value="1">@endif
    <div class="flex items-center justify-between gap-4"><h3 class="text-lg font-bold">Edit Category</h3><button type="button" onclick="this.closest('dialog').close()" class="text-gray-400 hover:text-gray-700" aria-label="Close">×</button></div>
    <label class="mt-5 block text-sm font-medium">Name<input id="editCategoryName" name="name" required maxlength="100" class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></label>
    <div class="mt-6 flex justify-end gap-3"><button type="button" onclick="this.closest('dialog').close()" class="px-4 py-2 text-sm text-gray-600">Cancel</button><button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Save</button></div>
  </form>
</dialog>
@endif

@push('scripts')
<script>
var _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

function openCategoryEditor(id, name) {
  const dialog = document.getElementById('editCategoryDialog');
  if (!dialog) return;
  document.getElementById('editCategoryForm').action = `{{ url('/admin/settings/categories') }}/${id}`;
  document.getElementById('editCategoryName').value = name;
  dialog.showModal();
}

document.getElementById('categorySearch')?.addEventListener('input', (event) => {
  const query = event.target.value.trim().toLowerCase();
  document.querySelectorAll('[data-category-row]').forEach((row) => {
    row.classList.toggle('hidden', !row.dataset.categoryName.includes(query));
  });
});

async function settingToggle(key, btn) {
  btn.disabled = true;
  const orig = btn.textContent;
  btn.textContent = '...';
  try {
    const res = await fetch(`{{ url('/admin/settings/toggle') }}/${key}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': _csrfToken, 'Accept': 'application/json' }
    });
    if (res.ok) { btn.textContent = orig; } else { alert('Toggle failed — please try again.'); btn.textContent = orig; }
  } catch(e) { alert('Network error.'); btn.textContent = orig; }
  btn.disabled = false;
}

async function clearCache(btn) {
  btn.disabled = true;
  const orig = btn.textContent;
  btn.textContent = '...';
  try {
    const res = await fetch(`{{ route('admin.settings.clear-cache') }}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': _csrfToken, 'Accept': 'application/json' }
    });
    if (res.ok) { btn.textContent = orig; } else { alert('Clear cache failed.'); btn.textContent = orig; }
  } catch(e) { alert('Network error.'); btn.textContent = orig; }
  btn.disabled = false;
}
</script>
@endpush
@endsection
