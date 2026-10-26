@extends('layouts.landing')
@section('title', 'Skillspot.in — Learn In-Demand Tech Skills')
@section('content')

<!-- ── 1. HERO SECTION (CLEAN, HUMBLE & PROFESSIONAL) ────────────────────────── -->
<section class="gradient-hero pt-28 sm:pt-36 pb-16 sm:pb-20 border-b border-slate-200/70">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    
    {{-- Humble Badge --}}
    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-semibold bg-white text-slate-700 border border-slate-200 shadow-2xs mb-6">
      <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
      <span>Empowering Practical Skills &amp; Tech Careers</span>
    </div>

    {{-- Hero Headline --}}
    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight max-w-3xl mx-auto text-balance">
      Learn In-Demand Skills.<br class="hidden sm:inline"> Build Real Confidence.
    </h1>

    {{-- Subtitle --}}
    <p class="mt-4 text-sm sm:text-base text-slate-600 max-w-xl mx-auto leading-relaxed font-normal">
      Practical, mentor-crafted courses in web development, design, and programming. Self-paced learning with hands-on exercises and verified certificates.
    </p>

    {{-- Hero Action Buttons (Separated with guaranteed gap & clean sizing) --}}
    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-5" style="gap: 1.25rem;">
      <a href="#courses" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 active:scale-[0.98] text-white font-semibold text-sm px-6 py-3 rounded-xl shadow-xs transition">
        <span>Browse Courses</span> <i class="fa-solid fa-arrow-down text-xs text-slate-300"></i>
      </a>
      <a href="/register" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 active:scale-[0.98] text-slate-800 font-semibold text-sm px-6 py-3 rounded-xl border border-slate-300 shadow-2xs transition">
        <span>Create Free Account</span> <i class="fa-solid fa-arrow-right text-xs text-slate-400"></i>
      </a>
    </div>

    {{-- Key Value Pillars --}}
    <div class="mt-12 pt-8 border-t border-slate-200/70 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-medium text-slate-600">
      <div class="flex items-center justify-center gap-2 py-1">
        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
        <span>Self-Paced Learning</span>
      </div>
      <div class="flex items-center justify-center gap-2 py-1">
        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
        <span>Practical Projects</span>
      </div>
      <div class="flex items-center justify-center gap-2 py-1">
        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
        <span>Verified Certificate</span>
      </div>
      <div class="flex items-center justify-center gap-2 py-1">
        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
        <span>Lifetime Access</span>
      </div>
    </div>

  </div>
</section>

<!-- ── 2. FEATURED COURSES CATALOG ───────────────────────────────────────────── -->
<section id="courses" class="py-16 sm:py-20 bg-slate-50/50 scroll-mt-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    {{-- Section Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 gap-4">
      <div>
        <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Course Catalog</span>
        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
          Featured Courses
        </h2>
        <p class="text-slate-600 text-sm mt-1 max-w-xl">
          Explore courses tailored to help you build practical, job-ready capabilities.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ route('marketplace.browse') }}" class="text-xs bg-white hover:bg-slate-50 text-slate-700 font-bold px-4 py-2.5 rounded-xl border border-slate-200 shadow-2xs transition flex items-center gap-2">
          <span>All Courses ({{ $stats['courses'] }})</span> <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
        </a>
      </div>
    </div>

    {{-- Search & Filter Bar --}}
    <div class="bg-white rounded-2xl p-2.5 border border-slate-200 shadow-xs mb-8 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-2.5">
      {{-- Fast search input --}}
      <div class="relative flex-1">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
        <input type="text" id="courseSearchInput" oninput="searchCourses(this.value)" placeholder="Search courses by topic, keyword, or title..."
               class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white text-slate-800 placeholder-slate-400 transition">
      </div>

      {{-- Fast Category pills --}}
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none text-xs" id="courseFilters">
        <button type="button" onclick="filterCourses('all', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-bold bg-slate-900 text-white shadow-xs transition whitespace-nowrap">All</button>
        <button type="button" onclick="filterCourses('web-development', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap">Web Dev</button>
        <button type="button" onclick="filterCourses('ai-machine-learning', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap">AI &amp; Python</button>
        <button type="button" onclick="filterCourses('robotics-iot', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap">Robotics &amp; IoT</button>
        <button type="button" onclick="filterCourses('computer-science-coding', this)" class="course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap">Coding &amp; CS</button>
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

    {{-- Courses Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="coursesGrid">
      @forelse($featuredCourses as $course)
      @php
        $icon         = $catIcons[$course->category] ?? 'fa-solid fa-book-open';
        $totalLessons = $course->sections->sum(fn($s) => $s->lessons->count());
        $catSlug      = \Illuminate\Support\Str::slug($course->category);
        $creator      = $course->vendor?->brand_name ?? $course->primaryInstructor?->name ?? 'Skillspot Academy';
        $cleanDesc    = \Illuminate\Support\Str::limit(strip_tags($course->short_introduction ?: $course->description), 110);
      @endphp
      <div class="course-card-item bg-white rounded-2xl border border-slate-200 shadow-xs card-hover overflow-hidden group hover:border-slate-300 flex flex-col transition-all duration-200"
           data-category="{{ $catSlug }}">

        {{-- Thumbnail Area --}}
        <a href="{{ route('student.course-detail', $course->slug) }}" class="relative aspect-video bg-slate-100 flex items-center justify-center overflow-hidden border-b border-slate-100 block">
          @if($course->thumbnail)
            <img src="{{ $course->thumbnail }}" alt="{{ $course->title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy"
                 onerror="this.parentElement.innerHTML='<i class=\'{{ $icon }} text-3xl text-slate-400\'></i>'">
          @else
            <i class="{{ $icon }} text-3xl text-slate-400 group-hover:scale-110 transition-transform duration-300"></i>
          @endif

          {{-- Subtle Category pill --}}
          <span class="absolute top-2.5 left-2.5 bg-slate-900/80 backdrop-blur-md text-white text-[10px] font-semibold px-2.5 py-0.5 rounded-md shadow-2xs">
            {{ $course->category ?: 'General' }}
          </span>

          {{-- Subtle status badge --}}
          @if($course->is_free)
            <span class="absolute top-2.5 right-2.5 bg-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-2xs">
              Free
            </span>
          @elseif($course->sale_price && $course->price > $course->sale_price)
            <span class="absolute top-2.5 right-2.5 bg-amber-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-2xs">
              Special Price
            </span>
          @endif
        </a>

        {{-- Card Content --}}
        <div class="p-4 flex flex-col flex-1">
          
          {{-- Creator info --}}
          <div class="text-[11px] font-medium text-slate-500 mb-1.5 flex items-center gap-1.5">
            <i class="fa-regular fa-user text-slate-400 text-[10px]"></i>
            <span class="truncate">{{ $creator }}</span>
          </div>

          {{-- Title --}}
          <h3 class="text-sm font-bold text-slate-900 mb-1.5 group-hover:text-blue-600 transition leading-snug line-clamp-2">
            <a href="{{ route('student.course-detail', $course->slug) }}">
              {{ ucwords(strtolower($course->title)) }}
            </a>
          </h3>

          {{-- Description (Guaranteed clean text, no raw HTML) --}}
          <p class="text-slate-500 text-xs leading-relaxed mb-3 line-clamp-2 flex-1">
            {{ $cleanDesc ?: 'Comprehensive self-paced lessons designed to build practical mastery.' }}
          </p>

          {{-- Meta tags --}}
          <div class="flex items-center gap-1.5 flex-wrap mb-4">
            <span class="text-[10px] bg-slate-100 text-slate-700 font-medium px-2 py-0.5 rounded-md border border-slate-200/60 capitalize">
              {{ $course->level ?: 'All levels' }}
            </span>
            @if($totalLessons > 0)
            <span class="text-[10px] bg-slate-100 text-slate-700 font-medium px-2 py-0.5 rounded-md border border-slate-200/60 flex items-center gap-1">
              <i class="fa-solid fa-play-circle text-slate-400"></i> {{ $totalLessons }} {{ $totalLessons === 1 ? 'lesson' : 'lessons' }}
            </span>
            @endif
            @if($course->enable_certification)
            <span class="text-[10px] bg-amber-50 text-amber-800 font-semibold px-2 py-0.5 rounded-md border border-amber-200/60 flex items-center gap-1">
              <i class="fa-solid fa-award text-amber-600"></i> Certificate
            </span>
            @endif
          </div>

          {{-- Price & CTA --}}
          <div class="flex items-center justify-between pt-3 border-t border-slate-100 mt-auto">
            <div>
              @if($course->is_free)
                <span class="text-base font-extrabold text-emerald-600">Free</span>
              @else
                <div class="flex items-baseline gap-1.5">
                  <span class="text-base font-black text-slate-900">₹{{ number_format($course->sale_price ?? $course->price) }}</span>
                  @if($course->sale_price && $course->price > $course->sale_price)
                  <span class="text-xs text-slate-400 line-through">₹{{ number_format($course->price) }}</span>
                  @endif
                </div>
              @endif
            </div>

            <a href="{{ route('student.course-detail', $course->slug) }}"
               class="text-xs bg-slate-900 hover:bg-blue-600 group-hover:bg-blue-600 active:scale-95 text-white font-bold px-3.5 py-1.5 rounded-lg transition shadow-2xs flex items-center gap-1.5">
              <span>View Course</span> <i class="fa-solid fa-arrow-right text-[9px]"></i>
            </a>
          </div>

        </div>
      </div>
      @empty
      <div class="col-span-full text-center py-12 bg-white rounded-2xl border border-slate-200 text-slate-500 text-sm">
        <p>No published courses found matching your criteria.</p>
        <a href="{{ route('marketplace.browse') }}" class="mt-2 inline-block text-blue-600 font-bold hover:underline">Browse all courses →</a>
      </div>
      @endforelse
    </div>

  </div>
</section>

<!-- ── 3. THE SKILLSPOT LEARNING ADVANTAGE (HUMBLE & INSPIRING) ──────────────── -->
<section class="py-16 sm:py-20 bg-white border-y border-slate-200/70">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="text-center max-w-2xl mx-auto mb-14">
      <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Why Skillspot</span>
      <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1 text-balance">
        A Practical, Career-Oriented Approach to Learning
      </h2>
      <p class="text-slate-600 text-sm mt-2 leading-relaxed">
        We prioritize hands-on capabilities over endless lectures. Here is how our learning experience helps you succeed.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      
      <div class="p-6 rounded-2xl border border-slate-200/80 bg-slate-50/60 hover:bg-white hover:border-slate-300 card-hover transition duration-200 flex flex-col">
        <div class="w-11 h-11 bg-blue-100 text-blue-700 rounded-xl flex items-center justify-center text-lg mb-4">
          <i class="fa-solid fa-laptop-code"></i>
        </div>
        <h3 class="font-bold text-slate-900 text-base mb-2">Curated Industry Topics</h3>
        <p class="text-slate-600 text-xs leading-relaxed">
          Courses are focused on modern tools, frameworks, and workflows actively utilized in contemporary tech jobs.
        </p>
      </div>

      <div class="p-6 rounded-2xl border border-slate-200/80 bg-slate-50/60 hover:bg-white hover:border-slate-300 card-hover transition duration-200 flex flex-col">
        <div class="w-11 h-11 bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center text-lg mb-4">
          <i class="fa-solid fa-code"></i>
        </div>
        <h3 class="font-bold text-slate-900 text-base mb-2">Learn by Building</h3>
        <p class="text-slate-600 text-xs leading-relaxed">
          Reinforce key concepts with step-by-step assignments, practical exercises, and portfolio-ready demo projects.
        </p>
      </div>

      <div class="p-6 rounded-2xl border border-slate-200/80 bg-slate-50/60 hover:bg-white hover:border-slate-300 card-hover transition duration-200 flex flex-col">
        <div class="w-11 h-11 bg-amber-100 text-amber-700 rounded-xl flex items-center justify-center text-lg mb-4">
          <i class="fa-solid fa-award"></i>
        </div>
        <h3 class="font-bold text-slate-900 text-base mb-2">Verified Certificates</h3>
        <p class="text-slate-600 text-xs leading-relaxed">
          Earn an authentic digital certificate upon completing all lessons and quizzes to validate your achievement.
        </p>
      </div>

      <div class="p-6 rounded-2xl border border-slate-200/80 bg-slate-50/60 hover:bg-white hover:border-slate-300 card-hover transition duration-200 flex flex-col">
        <div class="w-11 h-11 bg-purple-100 text-purple-700 rounded-xl flex items-center justify-center text-lg mb-4">
          <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <h3 class="font-bold text-slate-900 text-base mb-2">Lifetime Self-Paced Access</h3>
        <p class="text-slate-600 text-xs leading-relaxed">
          Learn whenever your schedule allows. Revisit lesson materials, notes, and resources anytime you need a refresher.
        </p>
      </div>

    </div>

  </div>
</section>

<!-- ── 4. HOW IT WORKS (3 SIMPLE STEPS) ──────────────────────────────────────── -->
<section id="how-it-works" class="py-16 sm:py-20 bg-slate-50/50">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="text-center max-w-xl mx-auto mb-12">
      <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Simple Process</span>
      <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
        Start in 3 Steps
      </h2>
      <p class="text-slate-600 text-sm mt-1">
        No prior experience required. Choose your path and start learning today.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs card-hover text-center">
        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-800 font-extrabold text-sm flex items-center justify-center mx-auto mb-4 border border-slate-200">
          01
        </div>
        <h3 class="text-base font-bold text-slate-900 mb-2">Pick a Course</h3>
        <p class="text-slate-500 text-xs leading-relaxed">
          Browse through our focused catalog across web development, design, and programming to find the right topic.
        </p>
      </div>

      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs card-hover text-center">
        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-800 font-extrabold text-sm flex items-center justify-center mx-auto mb-4 border border-slate-200">
          02
        </div>
        <h3 class="text-base font-bold text-slate-900 mb-2">Learn &amp; Build</h3>
        <p class="text-slate-500 text-xs leading-relaxed">
          Watch structured video lessons, review study notes, and complete practical quizzes at your own rhythm.
        </p>
      </div>

      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs card-hover text-center">
        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-800 font-extrabold text-sm flex items-center justify-center mx-auto mb-4 border border-slate-200">
          03
        </div>
        <h3 class="text-base font-bold text-slate-900 mb-2">Get Certified</h3>
        <p class="text-slate-500 text-xs leading-relaxed">
          Finish course requirements to receive your verifiable certificate to enhance your resume and professional profiles.
        </p>
      </div>

    </div>

  </div>
</section>

<!-- ── 5. CLEAN, SOPHISTICATED CALL TO ACTION ────────────────────────────────── -->
<section class="py-16 bg-slate-900 text-white text-center px-4">
  <div class="max-w-2xl mx-auto">
    <h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
      Ready to start your learning journey?
    </h2>
    <p class="text-slate-300 text-sm sm:text-base mt-3 max-w-xl mx-auto leading-relaxed">
      Create your free account today and start mastering skills that open new doors.
    </p>

    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-5" style="gap: 1.25rem;">
      <a href="/register" class="w-full sm:w-auto inline-flex items-center justify-center bg-white hover:bg-slate-100 active:scale-[0.98] text-slate-900 font-bold px-7 py-3 rounded-xl text-sm transition shadow-sm">
        Register Free Account →
      </a>
      <a href="#courses" class="w-full sm:w-auto inline-flex items-center justify-center bg-slate-800 hover:bg-slate-700 active:scale-[0.98] text-white border border-slate-700 px-7 py-3 rounded-xl text-sm font-semibold transition">
        Browse Courses Catalog
      </a>
    </div>
  </div>
</section>

<!-- ── JAVASCRIPT: FILTER & SEARCH ───────────────────────────────────────────── -->
<script>
window.currentCategory = 'all';

function filterCourses(category, btn) {
  window.currentCategory = category;
  document.querySelectorAll('.course-filter-btn').forEach(function(b) {
    b.className = 'course-filter-btn px-3.5 py-2 rounded-xl font-semibold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition whitespace-nowrap';
  });
  btn.className = 'course-filter-btn px-3.5 py-2 rounded-xl font-bold bg-slate-900 text-white shadow-xs transition whitespace-nowrap';
  var query = document.getElementById('courseSearchInput') ? document.getElementById('courseSearchInput').value : '';
  searchCourses(query);
}

function searchCourses(query) {
  var q = (query || '').toLowerCase().trim();
  var activeCategory = window.currentCategory || 'all';
  var cards = document.querySelectorAll('.course-card-item');
  var visibleCount = 0;

  cards.forEach(function(card) {
    var title = (card.querySelector('h3')?.innerText || '').toLowerCase();
    var desc = (card.querySelector('p')?.innerText || '').toLowerCase();
    var cat = (card.getAttribute('data-category') || '').toLowerCase();
    var matchesCat = (activeCategory === 'all' || cat === activeCategory);
    var matchesQuery = (q === '' || title.includes(q) || desc.includes(q));

    if (matchesCat && matchesQuery) {
      card.style.display = 'flex';
      visibleCount++;
    } else {
      card.style.display = 'none';
    }
  });
}
</script>

@endsection
