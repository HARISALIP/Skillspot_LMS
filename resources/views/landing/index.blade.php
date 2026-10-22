@extends('layouts.landing')
@section('title', 'Skillspot.in — Learn. Build. Grow.')
@section('content')

<!-- ── 1. COURSES & MARKETPLACE CATALOG (FIRST AT THE TOP OF HOMEPAGE) ───────────────────────── -->
<section id="courses" class="pt-24 pb-14 bg-gradient-to-b from-slate-100/90 via-slate-50 to-white border-b border-slate-200/70">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    {{-- Header Banner --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-7 gap-4">
      <div>
        <div class="inline-flex items-center gap-1.5 bg-brand-50 text-brand-700 text-xs font-black border border-brand-200/80 px-3.5 py-1 rounded-full mb-2.5">
          <i class="fa-solid fa-graduation-cap text-brand-600"></i> Instant Access Courses
        </div>
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
          Learn In-Demand Tech Skills. <span class="gradient-text">Start Today.</span>
        </h1>
        <p class="text-slate-600 text-sm mt-1.5 max-w-2xl">
          Hands-on developer courses, AI workflows, and verified certificates. Pick a course to start learning or buying immediately.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ route('marketplace.browse') }}" class="text-xs bg-white hover:bg-slate-50 text-slate-700 font-bold px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm transition flex items-center gap-2">
          <span>All Courses ({{ $stats['courses'] }})</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
      </div>
    </div>

    {{-- Live Search & Category Filters Bar --}}
    <div class="bg-white rounded-2xl p-3 border border-slate-200/90 shadow-sm mb-7 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
      {{-- Fast live search --}}
      <div class="relative flex-1">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
        <input type="text" id="courseSearchInput" oninput="searchCourses(this.value)" placeholder="Quick search courses (e.g. Web Development, Python, Robotics, Coding)..."
               class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200/80 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 text-slate-800 placeholder-slate-400">
      </div>

      {{-- Fast Category pills --}}
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none text-xs" id="courseFilters">
        <button type="button" onclick="filterCourses('all', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-bold bg-brand-600 text-white shadow-sm transition whitespace-nowrap">All</button>
        <button type="button" onclick="filterCourses('web-development', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap">Web Dev</button>
        <button type="button" onclick="filterCourses('ai-machine-learning', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap">AI & Python</button>
        <button type="button" onclick="filterCourses('robotics-iot', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap">Robotics & IoT</button>
        <button type="button" onclick="filterCourses('computer-science-coding', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap">Coding & CS</button>
      </div>
    </div>

    @php
      $catIcons = [
        'Web Development'          => 'fa-solid fa-laptop-code',
        'AI & Machine Learning'    => 'fa-solid fa-robot',
        'Robotics & IoT'           => 'fa-solid fa-microchip',
        'Computer Science & Coding'=> 'fa-solid fa-code',
        'Cybersecurity'            => 'fa-solid fa-shield-halved',
        'Python & AI'              => 'fa-solid fa-robot',
        'Mobile Development'       => 'fa-solid fa-mobile-screen',
        'Cloud & DevOps'           => 'fa-solid fa-cloud',
        'Database & SQL'           => 'fa-solid fa-database',
      ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5" id="coursesGrid">
      @forelse($featuredCourses as $course)
      @php
        $icon     = $catIcons[$course->category] ?? 'fa-solid fa-book-open';
        $levelColor = match($course->level) {
          'intermediate' => 'bg-amber-50 text-amber-700 border border-amber-200/60',
          'advanced'     => 'bg-rose-50 text-rose-700 border border-rose-200/60',
          default        => 'bg-blue-50 text-blue-700 border border-blue-200/60',
        };
        $totalLessons = $course->sections->sum(fn($s) => $s->lessons->count());
        $catSlug = \Illuminate\Support\Str::slug($course->category);
      @endphp
      <div class="course-card-item bg-white rounded-2xl border border-slate-200/90 shadow-sm card-hover overflow-hidden group hover:border-brand-300 flex flex-col transition-all duration-300"
           data-category="{{ $catSlug }}">
        {{-- Badge --}}
        @if($course->is_free)
        <div class="bg-gradient-to-r from-emerald-500 to-green-600 text-white text-[10px] font-bold text-center py-1 tracking-wider uppercase"><i class="fa-solid fa-gift mr-1"></i> Free Course</div>
        @elseif($course->sale_price)
        <div class="bg-gradient-to-r from-orange-500 to-rose-600 text-white text-[10px] font-bold text-center py-1 tracking-wider uppercase"><i class="fa-solid fa-bolt mr-1"></i> Special Offer</div>
        @elseif($course->is_featured)
        <div class="bg-gradient-to-r from-brand-600 to-indigo-600 text-white text-[10px] font-bold text-center py-1 tracking-wider uppercase"><i class="fa-solid fa-star mr-1"></i> Featured</div>
        @endif

        {{-- Thumbnail Link --}}
        <a href="{{ route('student.course-detail', $course->slug) }}" class="relative h-40 bg-gradient-to-br from-brand-50 via-slate-50 to-indigo-50 flex items-center justify-center overflow-hidden border-b border-slate-100 block">
          @if($course->thumbnail)
            <img src="{{ $course->thumbnail }}" alt="{{ $course->title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy"
                 onerror="this.parentElement.innerHTML='<i class=\'{{ $icon }} text-4xl text-brand-600\'></i>'">
          @else
            <i class="{{ $icon }} text-4xl text-brand-600 group-hover:scale-110 transition-transform duration-300"></i>
          @endif
          {{-- Category pill --}}
          <span class="absolute top-2 left-2 bg-slate-900/80 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
            {{ $course->category }}
          </span>
          @if($course->enrollments_count > 0)
          <div class="absolute bottom-2 right-2 bg-slate-900/80 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1">
            <i class="fa-solid fa-users text-[10px]"></i> {{ $course->enrollments_count }}
          </div>
          @endif
        </a>

        <div class="p-4 flex flex-col flex-1">
          {{-- Title --}}
          <h3 class="text-sm font-bold text-slate-900 mb-1.5 group-hover:text-brand-600 transition leading-snug line-clamp-2">
            <a href="{{ route('student.course-detail', $course->slug) }}">{{ $course->title }}</a>
          </h3>

          {{-- Description --}}
          <p class="text-slate-500 text-[11px] leading-relaxed mb-3 line-clamp-2 flex-1">{{ $course->description }}</p>

          {{-- Meta tags --}}
          <div class="flex items-center gap-1.5 flex-wrap mb-4">
            <span class="text-[10px] {{ $levelColor }} font-semibold px-2 py-0.5 rounded-md capitalize">{{ $course->level }}</span>
            @if($totalLessons > 0)
            <span class="text-[10px] bg-slate-100 text-slate-600 font-medium px-2 py-0.5 rounded-md border border-slate-200/60 flex items-center gap-1">
              <i class="fa-solid fa-play-circle text-slate-400"></i> {{ $totalLessons }} lessons
            </span>
            @endif
            @if($course->enable_certification)
            <span class="text-[10px] bg-amber-50 text-amber-700 font-semibold px-2 py-0.5 rounded-md border border-amber-200/60 flex items-center gap-1">
              <i class="fa-solid fa-award"></i> Certificate
            </span>
            @endif
          </div>

          {{-- Price + Quick Buy/Enroll Button --}}
          <div class="flex items-center justify-between pt-3 border-t border-slate-100 mt-auto">
            <div>
              @if($course->is_free)
                <span class="text-base font-black text-emerald-600">Free</span>
              @else
                <div class="flex items-baseline gap-1">
                  <span class="text-base font-black text-slate-900">₹{{ number_format($course->sale_price ?? $course->price) }}</span>
                  @if($course->sale_price && $course->price > $course->sale_price)
                  <span class="text-[11px] text-slate-400 line-through">₹{{ number_format($course->price) }}</span>
                  @endif
                </div>
              @endif
            </div>

            @if($course->is_free)
            <a href="{{ route('student.course-detail', $course->slug) }}"
               class="text-xs bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold px-3.5 py-1.5 rounded-lg transition shadow-sm flex items-center gap-1">
              <span>Enroll Free</span> <i class="fa-solid fa-arrow-right text-[9px]"></i>
            </a>
            @else
            <a href="{{ route('student.course-detail', $course->slug) }}"
               class="text-xs bg-brand-600 hover:bg-brand-700 active:scale-95 text-white font-bold px-3.5 py-1.5 rounded-lg transition shadow-sm flex items-center gap-1">
              <span>Buy Course</span> <i class="fa-solid fa-bolt text-[9px]"></i>
            </a>
            @endif
          </div>
        </div>
      </div>
      @empty
      <div class="col-span-full text-center py-12 bg-white rounded-2xl border border-slate-200/80 text-slate-500 text-sm">
        <p>Courses coming soon. <a href="/register" class="text-brand-600 font-bold hover:underline">Register to be notified →</a></p>
      </div>
      @endforelse
    </div>
  </div>
</section>

<!-- ── 2. LEARNING PLATFORM OVERVIEW & STATS ──────────────────────────────────── -->
<section class="gradient-hero px-4 py-16 relative overflow-hidden">
  <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[560px] h-[300px] bg-gradient-to-tr from-brand-200/40 to-accent-200/40 rounded-full blur-3xl pointer-events-none"></div>

  <div class="max-w-7xl mx-auto relative z-10 grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] items-center gap-10">
    <div class="text-center lg:text-left">
      <div class="inline-flex items-center gap-2 bg-white/80 border border-slate-200/90 px-3.5 py-1 rounded-full text-xs font-semibold text-slate-700 mb-4 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        Built for Real Tech Careers
      </div>
      <h2 class="text-balance text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 leading-[1.08] tracking-[-0.04em] mb-4">
        Master Real-World Workflows.<br>
        <span class="gradient-text">Build Your Future.</span>
      </h2>

      <p class="max-w-xl mx-auto lg:mx-0 text-sm sm:text-base text-slate-600 leading-relaxed mb-6">
        Skillspot LMS helps you master practical coding, digital skills, and industry-ready workflows through flexible, instructor-led learning and verified certificates.
      </p>

      <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
        <a href="/register" class="bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white px-6 py-3 rounded-xl text-sm font-bold transition shadow-lg shadow-brand-600/20 text-center">
          Start Learning Free →
        </a>
        <a href="#courses" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 shadow-sm px-6 py-3 rounded-xl text-sm font-semibold active:scale-[0.98] transition text-center">
          Browse Courses Above ↑
        </a>
      </div>
    </div>

    <div class="relative mt-4 lg:mt-0">
      <div class="absolute -inset-4 bg-gradient-to-r from-brand-500/15 via-accent-500/10 to-brand-500/15 blur-2xl rounded-[2rem]"></div>
      <div class="relative bg-slate-900 text-white rounded-[24px] p-5 sm:p-6 shadow-[0_25px_60px_rgba(15,23,42,0.2)] ring-1 ring-white/10">
        <div class="flex items-center justify-between pb-3.5 border-b border-slate-700/80">
          <div>
            <p class="text-[11px] uppercase tracking-[0.25em] text-slate-400">Student Dashboard</p>
            <h3 class="mt-1 text-lg font-bold">Platform Stats & Progress</h3>
          </div>
          <span class="bg-emerald-500/15 text-emerald-300 border border-emerald-400/30 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">Live</span>
        </div>

        <div class="mt-4 space-y-3.5">
          <div class="bg-white/5 border border-white/10 rounded-2xl p-3.5">
            <div class="flex items-center justify-between text-xs mb-2">
              <span class="font-medium text-slate-200">Full Stack Web Dev</span>
              <span class="text-brand-300 font-bold">72%</span>
            </div>
            <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
              <div class="h-full w-[72%] rounded-full bg-gradient-to-r from-brand-400 to-blue-500"></div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 mt-3">
            <div class="bg-gradient-to-br from-brand-500/20 to-blue-500/10 rounded-2xl p-3.5 border border-brand-400/20">
              <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Active Courses</p>
              <p class="mt-1 text-2xl font-black text-white">{{ $stats['courses'] }}</p>
            </div>
            <div class="bg-gradient-to-br from-emerald-500/20 to-green-500/10 rounded-2xl p-3.5 border border-emerald-400/20">
              <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Enrolled Students</p>
              <p class="mt-1 text-2xl font-black text-white">{{ $stats['students'] }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── 3. HOW IT WORKS ────────────────────────────────────────────────────────── -->
<section id="how-it-works" class="py-14 bg-white">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10">
      <div class="inline-block bg-brand-50 text-brand-700 text-xs font-semibold border border-brand-100 px-3.5 py-1 rounded-full mb-3">Simple & Fast</div>
      <h2 class="text-2xl md:text-3xl font-black text-slate-900 mb-2 tracking-tight">Start in 3 Steps</h2>
      <p class="text-slate-500 text-sm">No experience needed. Pick a course and learn at your own pace.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      @php $steps = [
        ['n'=>'01','icon'=>'fa-solid fa-user-plus','title'=>'Create Account','desc'=>'Register free in under 60 seconds. Just your name and email — no credit card required.','color'=>'from-blue-500 to-brand-600'],
        ['n'=>'02','icon'=>'fa-solid fa-book-open-reader','title'=>'Pick a Course','desc'=>'Browse our catalog of tech courses. Filter by topic, level, or duration. Enroll or buy instantly.','color'=>'from-purple-500 to-accent-600'],
        ['n'=>'03','icon'=>'fa-solid fa-graduation-cap','title'=>'Learn & Earn Certificate','desc'=>'Watch video lessons, complete quizzes, finish the course — get a verified certificate.','color'=>'from-emerald-500 to-green-600'],
      ]; @endphp
      @foreach($steps as $s)
      <div class="bg-slate-50/80 rounded-2xl p-5 text-center border border-slate-200/70 card-hover hover:bg-white transition-all duration-300">
        <div class="w-11 h-11 bg-gradient-to-br {{ $s['color'] }} rounded-xl flex items-center justify-center text-white font-black text-base mx-auto mb-3 shadow-md">{{ $s['n'] }}</div>
        <div class="text-2xl text-brand-600 mb-2"><i class="{{ $s['icon'] }}"></i></div>
        <h3 class="text-base font-bold text-slate-900 mb-1.5">{{ $s['title'] }}</h3>
        <p class="text-slate-500 text-xs leading-relaxed">{{ $s['desc'] }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

<script>
window.currentCategory = 'all';

function filterCourses(category, btn) {
  window.currentCategory = category;
  document.querySelectorAll('.course-filter-btn').forEach(function(b) {
    b.className = 'course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap';
  });
  btn.className = 'course-filter-btn px-3.5 py-2 rounded-xl font-bold bg-brand-600 text-white shadow-sm transition whitespace-nowrap';
  var query = document.getElementById('courseSearchInput') ? document.getElementById('courseSearchInput').value : '';
  searchCourses(query);
}

function searchCourses(query) {
  var q = (query || '').toLowerCase().trim();
  var activeCategory = window.currentCategory || 'all';
  var cards = document.querySelectorAll('.course-card-item');
  cards.forEach(function(card) {
    var title = (card.querySelector('h3')?.innerText || '').toLowerCase();
    var desc = (card.querySelector('p')?.innerText || '').toLowerCase();
    var cat = (card.getAttribute('data-category') || '').toLowerCase();
    var matchesCat = (activeCategory === 'all' || cat === activeCategory);
    var matchesQuery = (q === '' || title.includes(q) || desc.includes(q));
    card.style.display = (matchesCat && matchesQuery) ? 'flex' : 'none';
  });
}
</script>



<!-- WHY Skillspot.in -->
<section id="about" class="py-14 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
      <div>
        <div class="inline-block bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200/70 px-3.5 py-1 rounded-full mb-4">Why Skillspot.in?</div>
        <h2 class="text-2xl md:text-3xl font-black text-slate-900 mb-4 leading-tight tracking-tight">Built for<br>Real Tech Careers</h2>
        <p class="text-slate-500 text-sm mb-6 leading-relaxed">We teach what the industry actually uses. Hands-on projects, real code, live sessions, and career-ready skills.</p>
        <div class="space-y-3.5">
          @foreach([
            ['fa-solid fa-bullseye','Industry-Relevant Curriculum','Courses designed with real-world job requirements in mind.'],
            ['fa-solid fa-chalkboard-user','Expert Instructors','Learn from practicing developers and IT professionals.'],
            ['fa-solid fa-certificate','Verified Certificates','Downloadable certificates for every completed course.'],
            ['fa-solid fa-comments','Community Support','Student forums, doubt-clearing sessions, and peer learning.'],
            ['fa-solid fa-mobile-screen-button','Learn on Any Device','Fully mobile-responsive. Learn on phone, tablet or desktop.'],
            ['fa-solid fa-arrows-rotate','Lifetime Access','Enroll once, access forever — including future updates.'],
          ] as [$icon,$title,$desc])
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 bg-brand-50 text-brand-600 rounded-lg flex items-center justify-center text-sm flex-shrink-0 border border-brand-100/80"><i class="{{ $icon }}"></i></div>
            <div>
              <div class="font-bold text-slate-900 text-sm">{{ $title }}</div>
              <div class="text-slate-500 text-xs mt-0.5">{{ $desc }}</div>
            </div>
          </div>
          @endforeach
        </div>
      </div>

      <!-- Visual card -->
      <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950 rounded-2xl p-5 text-white shadow-xl border border-slate-800">
        <div class="text-white text-xs font-bold mb-4 flex items-center gap-2">
          <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
          <i class="fa-solid fa-graduation-cap text-brand-400 text-sm"></i>
          Student Dashboard Preview
        </div>
        <!-- Progress cards -->
        <div class="space-y-2.5">
          @foreach([
            ['Full-Stack Web Dev','72%','from-blue-400 to-brand-500'],
            ['Python & AI','45%','from-purple-400 to-accent-500'],
            ['Cybersecurity','100%','from-emerald-400 to-green-500'],
          ] as [$name,$pct,$gradient])
          <div class="bg-white/10 rounded-xl p-3 border border-white/10">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-white text-xs font-semibold">{{ $name }}</span>
              <span class="text-blue-300 text-[11px] font-bold">{{ $pct }}</span>
            </div>
            <div class="bg-white/20 rounded-full h-1.5 overflow-hidden">
              <div class="bg-gradient-to-r {{ $gradient }} h-full rounded-full" style="width:{{ $pct }}"></div>
            </div>
          </div>
          @endforeach
        </div>
        <!-- Certificate -->
        <div class="mt-3.5 bg-gradient-to-r from-amber-400/20 to-yellow-400/20 border border-yellow-400/30 rounded-xl p-3 flex items-center gap-2.5">
          <i class="fa-solid fa-award text-amber-400 text-xl flex-shrink-0"></i>
          <div>
            <div class="text-white text-xs font-bold">Certificate Earned!</div>
            <div class="text-yellow-200 text-[11px]">Cybersecurity Fundamentals</div>
          </div>
        </div>
        <div class="text-center text-slate-400 text-[11px] mt-3">Sample — your dashboard after enrolling</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-14 bg-gradient-to-br from-brand-600 via-indigo-600 to-accent-700 text-center px-4 relative overflow-hidden">
  <div class="max-w-2xl mx-auto relative z-10">
    <h2 class="text-2xl md:text-4xl font-black text-white mb-3 tracking-tight">Ready to start your<br>tech journey?</h2>
    <p class="text-blue-100 text-sm mb-6">Explore courses and start learning at your pace.</p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a href="/register" class="bg-white text-brand-700 hover:bg-slate-50 px-7 py-3 rounded-xl text-sm md:text-base font-bold transition shadow-lg active:scale-[0.98]">
        Register Free →
      </a>
      <a href="/login" class="bg-white/10 hover:bg-white/20 text-white border border-white/25 px-7 py-3 rounded-xl text-sm md:text-base font-semibold backdrop-blur-sm transition">
        Already a Student? Login
      </a>
    </div>
  </div>
</section>

@endsection

