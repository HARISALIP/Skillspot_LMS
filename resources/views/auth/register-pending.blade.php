@extends('layouts.auth')
@section('title', 'Registration Received — Skillspot.in LMS')
@section('container-class', 'max-w-xl')

@section('top-link')
  <a href="/login" class="text-slate-600 hover:text-brand-600 transition text-sm">
    Already approved? <span class="text-brand-600 font-bold">Sign in →</span>
  </a>
@endsection

@section('content')
<div class="p-6 sm:p-8 text-center">

  {{-- Hero icon --}}
  <div class="relative inline-flex items-center justify-center mb-5">
    <div class="w-20 h-20 bg-gradient-to-tr from-amber-400 via-orange-500 to-amber-500 rounded-3xl flex items-center justify-center text-white text-3xl shadow-lg shadow-amber-500/30">
      <i class="fa-solid fa-hourglass-half animate-pulse"></i>
    </div>
    <span class="absolute -bottom-1 -right-1 w-7 h-7 bg-emerald-500 text-white rounded-full flex items-center justify-center text-xs font-black shadow-md border-2 border-white">
      ✓
    </span>
  </div>

  {{-- Title & subtitle --}}
  <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-2">
    Thank You for Registering! 🎉
  </h1>
  <p class="text-slate-600 text-sm sm:text-base max-w-md mx-auto leading-relaxed mb-6">
    Welcome to <span class="font-bold text-slate-900">Skillspot.in LMS</span>. Your registration details have been received successfully.
  </p>

  {{-- Account status card --}}
  <div class="bg-amber-50 border border-amber-200/80 rounded-2xl p-5 mb-6 text-left">
    <div class="flex items-start gap-3">
      <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center text-amber-700 flex-shrink-0 mt-0.5">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-amber-200 text-amber-900 uppercase tracking-wide">
            <span class="w-2 h-2 rounded-full bg-amber-600 animate-ping"></span>
            Pending Admin Approval
          </span>
        </div>
        <p class="text-xs sm:text-sm text-amber-900 leading-relaxed font-medium">
          To ensure platform security and course quality, all new student accounts are activated after review by our admin team. Once approved, you can immediately log in to access your dashboard and courses.
        </p>
      </div>
    </div>
  </div>

  {{-- WhatsApp Quick Action --}}
  <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200/80 rounded-2xl p-6 mb-6 text-left">
    <div class="flex items-center gap-2 text-emerald-800 text-xs font-bold uppercase tracking-wider mb-2">
      <i class="fa-brands fa-whatsapp text-emerald-600 text-base"></i> Fast-Track Approval & Assistance
    </div>
    <h3 class="text-base font-bold text-slate-900 mb-1">
      Our team will contact you shortly!
    </h3>
    <p class="text-xs sm:text-sm text-slate-600 mb-4 leading-relaxed">
      You will be contacted by our team soon. If you would like immediate account activation, have course questions, or need priority access, please send us a direct message on WhatsApp:
    </p>

    @php
      $userName = session('registered_name', '');
      $userEmail = session('registered_email', '');
      $waText = "Hello Skillspot Team! I have registered on Skillspot LMS"
              . ($userName ? " as {$userName}" : "")
              . ($userEmail ? " ({$userEmail})" : "")
              . " and am waiting for account approval. Please approve my account.";
      $waUrl = "https://wa.me/919074854599?text=" . urlencode($waText);
    @endphp

    <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
       class="w-full inline-flex items-center justify-center gap-3 bg-[#25D366] hover:bg-[#20bd5a] text-white font-extrabold px-6 py-3.5 rounded-xl transition duration-200 shadow-md shadow-emerald-600/20 text-sm active:scale-[0.99]">
      <i class="fa-brands fa-whatsapp text-xl"></i>
      <span>Message Us on WhatsApp (+91 9074854599)</span>
      <i class="fa-solid fa-arrow-up-right-from-square text-xs opacity-75"></i>
    </a>
  </div>

  {{-- Secondary actions --}}
  <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
    <a href="/login"
       class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold transition shadow-sm">
      Go to Sign In
    </a>
    <a href="/"
       class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm font-semibold transition">
      Back to Home
    </a>
  </div>

</div>
@endsection
