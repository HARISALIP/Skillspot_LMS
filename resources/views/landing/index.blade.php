@extends('layouts.landing')
@section('title', 'Skillspot.in — Learn. Build. Grow.')
@section('content')

<!-- HERO -->
<section class="gradient-hero px-4 pt-28 pb-20 relative overflow-hidden">
  <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[560px] h-[300px] bg-gradient-to-tr from-brand-200/50 to-accent-200/50 rounded-full blur-3xl pointer-events-none"></div>

  <div class="max-w-7xl mx-auto relative z-10 grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] items-center gap-10">
    <div class="text-center lg:text-left">
      <div class="inline-flex items-center gap-2 bg-white/90 border border-brand-200/80 rounded-full px-3.5 py-1.5 text-xs text-brand-700 font-semibold mb-5 shadow-sm backdrop-blur-sm">
        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
        Trusted by learners across India
      </div>

      <h1 class="text-balance text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 leading-[1.05] tracking-[-0.05em] mb-5">
        Learn in-demand tech skills.<br>
        <span class="gradient-text">Build your future.</span>
      </h1>

      <p class="max-w-xl mx-auto lg:mx-0 text-base sm:text-lg text-slate-600 leading-relaxed mb-7">
        Skillspot LMS helps you master practical coding, digital skills, and industry-ready workflows through flexible, instructor-led learning.
      </p>

      <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
        <a href="/register" class="bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white px-6 py-3.5 rounded-xl text-sm md:text-base font-bold transition shadow-lg shadow-brand-600/20">
          Start Learning Free →
        </a>
        <a href="#courses" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 shadow-sm px-6 py-3.5 rounded-xl text-sm md:text-base font-semibold active:scale-[0.98] transition">
          Explore Courses
        </a>
      </div>

      <div class="mt-7 flex flex-wrap items-center justify-center lg:justify-start gap-3 text-xs text-slate-500">
        <span class="bg-white/80 border border-slate-200 rounded-full px-3 py-1.5">⭐ 4.9 learner rating</span>
        <span class="bg-white/80 border border-slate-200 rounded-full px-3 py-1.5">📘 Live classes</span>
        <span class="bg-white/80 border border-slate-200 rounded-full px-3 py-1.5">🎓 Verified certificates</span>
      </div>
    </div>

    <div class="relative mt-6 lg:mt-0">
      <div class="absolute -inset-5 bg-gradient-to-r from-brand-500/15 via-accent-500/10 to-brand-500/15 blur-2xl rounded-[2rem]"></div>
      <div class="relative bg-slate-900 text-white rounded-[28px] p-5 sm:p-6 shadow-[0_30px_80px_rgba(15,23,42,0.25)] ring-1 ring-white/10">
        <div class="flex items-center justify-between pb-4 border-b border-slate-700/80">
          <div>
            <p class="text-[11px] uppercase tracking-[0.25em] text-slate-400">Student dashboard</p>
            <h3 class="mt-2 text-xl font-bold">Learning progress</h3>
          </div>
          <span class="bg-emerald-500/15 text-emerald-300 border border-emerald-400/30 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">Live</span>
        </div>

        <div class="mt-5 space-y-4">
          <div class="bg-white/5 border border-white/10 rounded-2xl p-4">
            <div class="flex items-center justify-between text-sm mb-2">
              <span class="font-medium text-slate-200">Full Stack Web Dev</span>
              <span class="text-brand-300 font-bold">72%</span>
            </div>
            <div class="h-2.5 bg-slate-700 rounded-full overflow-hidden">
              <div class="h-full w-[72%] rounded-full bg-gradient-to-r from-brand-400 to-blue-500"></div>
            </div>
          </div>

          <div class="bg-white/5 border border-white/10 rounded-2xl p-4">
            <div class="flex items-center justify-between text-sm mb-2">
              <span class="font-medium text-slate-200">Python & AI</span>
              <span class="text-violet-300 font-bold">45%</span>
            </div>
            <div class="h-2.5 bg-slate-700 rounded-full overflow-hidden">
              <div class="h-full w-[45%] rounded-full bg-gradient-to-r from-violet-400 to-accent-500"></div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 mt-4">
            <div class="bg-gradient-to-br from-brand-500/20 to-blue-500/10 rounded-2xl p-4 border border-brand-400/20">
              <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Courses</p>
              <p class="mt-2 text-3xl font-black text-white">{{ $stats['courses'] > 0 ? $stats['courses'] : '50' }}+</p>
            </div>
            <div class="bg-gradient-to-br from-emerald-500/20 to-green-500/10 rounded-2xl p-4 border border-emerald-400/20">
              <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Certificates</p>
              <p class="mt-2 text-3xl font-black text-white">100%</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="absolute bottom-0 left-0 right-0">
    <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0 60L1440 60L1440 30C1200 60 720 0 0 30Z" fill="#ffffff"/>
    </svg>
  </div>
</section>


@endsection

