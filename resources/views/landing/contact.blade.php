@extends('layouts.landing')
@section('title', 'Contact Us — Skillspot.in')
@section('content')

@php
  $siteName    = \App\Models\Setting::get('site_name',    'Skillspot.in');
  $siteEmail   = \App\Models\Setting::get('site_email',   '');
  $sitePhone   = \App\Models\Setting::get('site_phone',   '');
  $siteWA      = \App\Models\Setting::get('site_whatsapp','');
  $siteAddress = \App\Models\Setting::get('site_address', '');
  $siteUrl     = \App\Models\Setting::get('app_url',      'https://Skillspot.in');
  $mapsUrl     = \App\Models\Setting::get('site_maps_url','');
@endphp

<!-- Hero -->
<section class="gradient-hero pt-32 pb-20 px-4 text-center relative overflow-hidden">
  <div class="absolute top-1/3 right-1/4 w-72 h-72 bg-accent-600 opacity-20 rounded-full blur-3xl pointer-events-none"></div>
  <div class="relative z-10 max-w-2xl mx-auto">
    <h1 class="text-4xl md:text-6xl font-black text-white mb-4">Get in <span class="gradient-text">Touch</span></h1>
    <p class="text-xl text-gray-300">We'd love to hear from you. Our team typically replies within 24 hours.</p>
  </div>
</section>

<section class="py-20 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">

      <!-- ── Contact Info ─────────────────────────────────────────── -->
      <div class="lg:col-span-2 space-y-4">
        <div>
          <div class="inline-block bg-brand-100 text-brand-600 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Contact Info</div>
          <h2 class="text-2xl font-black text-gray-900 mb-2">We're here to help</h2>
          <p class="text-gray-500 text-sm leading-relaxed">Whether you have a question about courses, payments, certificates, or anything else — our team is ready.</p>
        </div>

        {{-- Email --}}
        @if($siteEmail)
        <a href="mailto:{{ $siteEmail }}"
           class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand-200 hover:bg-brand-50 transition group">
          <div class="w-11 h-11 bg-blue-50 group-hover:bg-blue-100 rounded-xl flex items-center justify-center text-xl flex-shrink-0 transition">📧</div>
          <div class="min-w-0">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Email Us</div>
            <div class="font-bold text-gray-900 text-sm mt-0.5 truncate">{{ $siteEmail }}</div>
            <div class="text-xs text-gray-400">For general enquiries</div>
          </div>
        </a>
        @endif

        {{-- Phone --}}
        @if($sitePhone)
        <a href="tel:{{ preg_replace('/\s+/','',$sitePhone) }}"
           class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-green-200 hover:bg-green-50 transition group">
          <div class="w-11 h-11 bg-green-50 group-hover:bg-green-100 rounded-xl flex items-center justify-center text-xl flex-shrink-0 transition">📞</div>
          <div>
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Call Us</div>
            <div class="font-bold text-gray-900 text-sm mt-0.5">{{ $sitePhone }}</div>
            <div class="text-xs text-gray-400">Mon–Fri, 9 AM – 6 PM</div>
          </div>
        </a>
        @endif

        {{-- WhatsApp --}}
        @if($siteWA)
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/','',$siteWA) }}" target="_blank"
           class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-green-300 hover:bg-green-50 transition group">
          <div class="w-11 h-11 bg-green-50 group-hover:bg-green-100 rounded-xl flex items-center justify-center text-xl flex-shrink-0 transition">💬</div>
          <div>
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">WhatsApp</div>
            <div class="font-bold text-gray-900 text-sm mt-0.5">{{ $siteWA }}</div>
            <div class="text-xs text-gray-400">Chat with us instantly</div>
          </div>
        </a>
        @endif

        {{-- Website --}}
        <a href="{{ $siteUrl }}"
           class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand-200 hover:bg-brand-50 transition group">
          <div class="w-11 h-11 bg-brand-50 group-hover:bg-brand-100 rounded-xl flex items-center justify-center text-xl flex-shrink-0 transition">🌐</div>
          <div class="min-w-0">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Website</div>
            <div class="font-bold text-gray-900 text-sm mt-0.5 truncate">{{ str_replace(['https://','http://'],'',$siteUrl) }}</div>
            <div class="text-xs text-gray-400">Browse our courses</div>
          </div>
        </a>

        {{-- Address --}}
        @if($siteAddress)
        <div class="{{ $mapsUrl ? 'cursor-pointer' : '' }} flex items-start gap-4 p-4 rounded-2xl border border-gray-100 {{ $mapsUrl ? 'hover:border-orange-200 hover:bg-orange-50' : '' }} transition group"
             @if($mapsUrl) onclick="window.open('{{ $mapsUrl }}','_blank')" @endif>
          <div class="w-11 h-11 bg-orange-50 rounded-xl flex items-center justify-center text-xl flex-shrink-0">📍</div>
          <div>
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Address</div>
            <div class="font-semibold text-gray-900 text-sm mt-0.5 leading-relaxed">{{ $siteAddress }}</div>
            @if($mapsUrl)
            <div class="text-xs text-brand-600 mt-1 font-semibold">View on Maps →</div>
            @endif
          </div>
        </div>
        @endif

        <!-- Support Hours -->
        <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-xl">🕐</span>
            <span class="font-bold text-gray-900 text-sm">Support Hours</span>
          </div>
          <div class="space-y-1.5 text-sm">
            <div class="flex justify-between text-gray-600"><span>Mon – Fri</span><span class="font-semibold">9:00 AM – 6:00 PM</span></div>
            <div class="flex justify-between text-gray-600"><span>Saturday</span><span class="font-semibold">10:00 AM – 4:00 PM</span></div>
            <div class="flex justify-between text-gray-400"><span>Sunday</span><span>Closed</span></div>
          </div>
          <div class="mt-3 flex items-center gap-1.5 text-xs text-green-600">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> Currently accepting enquiries
          </div>
        </div>
      </div>

      <!-- ── Contact Form ──────────────────────────────────────────── -->
      <div class="lg:col-span-3">
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-black text-gray-900">Send us a Message</h3>
            <p class="text-xs text-gray-400 mt-0.5">Fill in the form and we'll get back to you within 24 hours</p>
          </div>
          <div class="p-7">
            @if(session('contact_success'))
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-2xl p-4 mb-5 flex items-center gap-2 text-sm font-semibold">
              ✅ Message sent! We'll reply to <strong>{{ session('contact_email') }}</strong> within 24 hours.
            </div>
            @endif

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-5">
              @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('contact.send') }}" id="contactForm" class="space-y-4">
              @csrf
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Your Name</label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">👤</span>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Full name"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
                  </div>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Email Address</label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📧</span>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Phone (optional)</label>
                <div class="relative">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📱</span>
                  <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+91 9876543210"
                         class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Subject</label>
                <div class="relative">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📋</span>
                  <select name="subject" class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition appearance-none cursor-pointer">
                    <option value="general">General Enquiry</option>
                    <option value="courses">Course Information</option>
                    <option value="payment">Payment / Billing</option>
                    <option value="certificate">Certificate Issue</option>
                    <option value="technical">Technical Support</option>
                    <option value="instructor">Become an Instructor</option>
                    <option value="other">Other</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Message</label>
                <textarea name="message" rows="5" required placeholder="Tell us how we can help..."
                          class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition resize-none">{{ old('message') }}</textarea>
              </div>

              <button type="submit" id="contactBtn"
                      class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-700 hover:to-accent-700 active:scale-[0.98] text-white font-black py-3.5 rounded-2xl transition shadow-lg shadow-brand-200 text-sm flex items-center justify-center gap-2">
                <span id="contactBtnText">📨 Send Message</span>
                <span id="contactSpinner" class="hidden">
                  <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                </span>
              </button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
  document.getElementById('contactForm')?.addEventListener('submit', function() {
    document.getElementById('contactBtnText').textContent = 'Sending…';
    document.getElementById('contactSpinner').classList.remove('hidden');
    document.getElementById('contactBtn').disabled = true;
  });
</script>
@endpush
