@extends('layouts.admin')
@section('title', 'Settings — Skillspot.in Admin')
@section('page-title', 'Platform Settings')
@section('page-sub', 'Manage branding, domain, mail, storage, payments, license & security')

@section('admin-content')

@php
$tabs = [
  'branding' => ['fa'=>'fa-solid fa-palette',           'color'=>'text-purple-600', 'bg'=>'bg-purple-100', 'label'=>'Branding & Domain'],
  'mail'     => ['fa'=>'fa-solid fa-envelope-open-text', 'color'=>'text-blue-600',   'bg'=>'bg-blue-100',   'label'=>'Email / SMTP'],
  'storage'  => ['fa'=>'fa-solid fa-cloud',              'color'=>'text-cyan-600',   'bg'=>'bg-cyan-100',   'label'=>'Storage (R2)'],
  'payment'  => ['fa'=>'fa-solid fa-credit-card',        'color'=>'text-emerald-600','bg'=>'bg-emerald-100','label'=>'Payments'],
  'license'  => ['fa'=>'fa-solid fa-key',                'color'=>'text-amber-600',  'bg'=>'bg-amber-100',  'label'=>'License'],
  'security' => ['fa'=>'fa-solid fa-shield-halved',      'color'=>'text-rose-600',   'bg'=>'bg-rose-100',   'label'=>'Security & OTP'],
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

<div class="flex flex-col lg:flex-row gap-6">

  {{-- Sidebar tabs --}}
  <div class="lg:w-60 flex-shrink-0">
    <div class="lg:hidden flex gap-2 overflow-x-auto pb-2 snap-x -mx-1 px-1">
      @foreach($tabs as $key => $tab)
      <a href="{{ route('admin.settings', ['tab' => $key]) }}"
         class="snap-start flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition
                {{ $activeTab === $key ? 'bg-brand-600 text-white shadow-lg shadow-brand-200' : 'bg-white text-gray-600 border border-gray-200 hover:border-brand-300 hover:text-brand-600' }}">
        <i class="{{ $tab['fa'] }}"></i> {{ $tab['label'] }}
      </a>
      @endforeach
    </div>
    <div class="hidden lg:block bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden sticky top-6">
      <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
        <p class="text-xs font-black text-gray-400 uppercase tracking-wider">Settings</p>
      </div>
      <nav class="p-2 space-y-0.5">
        @foreach($tabs as $key => $tab)
        <a href="{{ route('admin.settings', ['tab' => $key]) }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition font-medium
                  {{ $activeTab === $key ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
          <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all {{ $activeTab === $key ? 'bg-white/20 text-white' : $tab['bg'] . ' ' . $tab['color'] }}">
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
  <div class="flex-1 min-w-0">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

      <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 bg-gradient-to-br from-brand-600 to-accent-600 rounded-xl flex items-center justify-center text-white text-lg shadow-md shadow-brand-500/20">
            <i class="{{ $tabs[$activeTab]['fa'] }}"></i>
          </div>
          <div>
            <h2 class="text-base font-black text-gray-900">{{ $tabs[$activeTab]['label'] }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">
              @switch($activeTab)
                @case('branding')  Domain URL, academy name, logo, colors @break
                @case('mail')      SMTP server config for sending emails @break
                @case('storage')   Cloudflare R2 bucket, keys & limits @break
                @case('payment')   Gateway, mode, live/test keys @break
                @case('license')   License key, plan, usage limits @break
                @case('security')  Registration, OTP, sessions, maintenance @break
              @endswitch
            </p>
          </div>
        </div>
      </div>

      <form method="POST" action="{{ route('admin.settings.save', $activeTab) }}" id="settingsForm">
        @csrf
        <div class="p-6 space-y-5">

          {{-- BRANDING --}}
          @if($activeTab === 'branding')
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
              <button type="submit" formaction="{{ route('admin.settings.mail.test') }}"
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
          @endif

        </div>

        {{-- Save bar --}}
        <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
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
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

async function settingToggle(key, btn) {
  btn.disabled = true;
  const orig = btn.textContent;
  btn.textContent = '...';
  try {
    const res = await fetch(`{{ url('/admin/settings/toggle') }}/${key}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': _csrfToken, 'Accept': 'application/json' }
    });
    if (res.ok) { location.reload(); } else { alert('Toggle failed — please try again.'); btn.textContent = orig; }
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
    if (res.ok) { location.reload(); } else { alert('Clear cache failed.'); btn.textContent = orig; }
  } catch(e) { alert('Network error.'); btn.textContent = orig; }
  btn.disabled = false;
}
</script>
@endpush
@endsection
