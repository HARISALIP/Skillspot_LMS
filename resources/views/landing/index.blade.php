@extends('layouts.landing')
@section('title', 'Skillspot.in — Learn. Build. Grow.')
@section('content')

<!-- HERO -->
<section class="gradient-hero min-h-screen flex flex-col items-center justify-center px-4 pt-20 text-center relative overflow-hidden">
  <!-- Blobs -->
  <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-brand-600 opacity-20 rounded-full blur-3xl pointer-events-none"></div>
  <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-accent-600 opacity-20 rounded-full blur-3xl pointer-events-none"></div>

  <div class="relative z-10 max-w-4xl mx-auto">
    <!-- Badge -->
    <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-1.5 text-sm text-blue-200 mb-8">
      <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
      Official Skillspot.in — India
    </div>

    <h1 class="text-4xl sm:text-5xl md:text-7xl font-black text-white leading-tight mb-6">
      Learn Tech.<br>
      <span class="gradient-text">Build Your Future.</span>
    </h1>

    <p class="text-lg md:text-xl text-gray-300 max-w-2xl mx-auto mb-10 leading-relaxed">
      Skillspot.in offers professional technology courses in programming, web development, cybersecurity, and more — taught by industry experts.
    </p>

    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/register" class="bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white px-8 py-4 rounded-2xl text-lg font-bold transition shadow-xl shadow-brand-900/50">
        Start Learning Free →
      </a>
      <a href="#courses" class="glass text-white px-8 py-4 rounded-2xl text-lg font-medium hover:bg-white/10 active:scale-[0.98] transition">
        Browse Courses
      </a>
    </div>

    <!-- Stats -->
    <div class="mt-16 grid grid-cols-3 gap-4 max-w-md mx-auto">
      <div class="bg-white/10 rounded-2xl py-4 px-2 text-center">
        <div class="text-3xl md:text-4xl font-black text-white">{{ $stats['courses'] > 0 ? $stats['courses'].'+ ' : '50+' }}</div>
        <div class="text-gray-400 text-xs mt-1">Courses</div>
      </div>
      <div class="bg-white/10 rounded-2xl py-4 px-2 text-center">
        <div class="text-3xl md:text-4xl font-black text-white">{{ $stats['students'] > 0 ? $stats['students'].'+ ' : '2K+' }}</div>
        <div class="text-gray-400 text-xs mt-1">Students</div>
      </div>
      <div class="bg-white/10 rounded-2xl py-4 px-2 text-center">
        <div class="text-3xl md:text-4xl font-black text-white">100%</div>
        <div class="text-gray-400 text-xs mt-1">Certified</div>
      </div>
    </div>
  </div>

  <!-- Wave -->
  <div class="absolute bottom-0 left-0 right-0">
    <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0 80L1440 80L1440 40C1200 80 720 0 0 40Z" fill="white"/>
    </svg>
  </div>
</section>

<!-- HOW IT WORKS -->
<section id="how-it-works" class="py-20 bg-white">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <div class="inline-block bg-brand-100 text-brand-600 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Simple & Fast</div>
      <h2 class="text-3xl md:text-5xl font-black text-gray-900 mb-3">Start in 3 Steps</h2>
      <p class="text-gray-500 text-lg">No experience needed. Just show up ready to learn.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      @php $steps = [
        ['n'=>'01','icon'=>'📝','title'=>'Create Account','desc'=>'Register free in under 60 seconds. Just your name and email — no credit card required.','color'=>'from-blue-500 to-brand-600'],
        ['n'=>'02','icon'=>'📚','title'=>'Pick a Course','desc'=>'Browse our catalog of tech courses. Filter by topic, level, or duration. Enroll instantly.','color'=>'from-purple-500 to-accent-600'],
        ['n'=>'03','icon'=>'🏆','title'=>'Learn & Earn Certificate','desc'=>'Watch video lessons, complete quizzes, finish the course — get a verified certificate.','color'=>'from-green-500 to-emerald-600'],
      ]; @endphp
      @foreach($steps as $s)
      <div class="bg-gray-50 rounded-3xl p-7 text-center border border-gray-100 card-hover">
        <div class="w-14 h-14 bg-gradient-to-br {{ $s['color'] }} rounded-2xl flex items-center justify-center text-white font-black text-xl mx-auto mb-4 shadow-md">{{ $s['n'] }}</div>
        <div class="text-3xl mb-3">{{ $s['icon'] }}</div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $s['title'] }}</h3>
        <p class="text-gray-500 text-sm leading-relaxed">{{ $s['desc'] }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- COURSES -->
<section id="courses" class="py-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-12 gap-4">
      <div>
        <div class="inline-block bg-accent-100 text-accent-600 text-sm font-semibold px-4 py-1.5 rounded-full mb-3">What We Teach</div>
        <h2 class="text-3xl md:text-5xl font-black text-gray-900">Our Courses</h2>
      </div>
      <a href="/register" class="self-start sm:self-auto text-sm text-brand-600 font-semibold hover:text-brand-700 transition">View all {{ $stats['courses'] }} courses →</a>
    </div>

    @php
      $catIcons = [
        'Web Development'    => '💻',
        'Cybersecurity'      => '🔐',
        'Python & AI'        => '🤖',
        'Mobile Development'=> '📱',
        'Cloud & DevOps'     => '☁️',
        'Database & SQL'     => '🗄️',
        'Programming'        => '⌨️',
        'Networking'         => '🌐',
        'Graphic Design'     => '🎨',
        'Digital Marketing'  => '📈',
        'Soft Skills'        => '🤝',
      ];
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      @forelse($featuredCourses as $course)
      @php
        $icon     = $catIcons[$course->category] ?? '📚';
        $isFirst  = $loop->first;
        $levelColor = match($course->level) {
          'intermediate' => 'bg-yellow-50 text-yellow-700',
          'advanced'     => 'bg-red-50 text-red-700',
          default        => 'bg-blue-50 text-blue-700',
        };
        $totalLessons = $course->sections->sum(fn($s) => $s->lessons->count());
      @endphp
      <div class="bg-white rounded-3xl border border-gray-100 shadow-sm card-hover overflow-hidden group">
        {{-- Badge --}}
        @if($course->is_featured && $loop->first)
        <div class="bg-gradient-to-r from-brand-600 to-accent-600 text-white text-xs font-bold text-center py-1.5 tracking-wider">⭐ MOST POPULAR</div>
        @elseif($course->is_free)
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white text-xs font-bold text-center py-1.5 tracking-wider">🆓 FREE COURSE</div>
        @elseif($course->sale_price)
        <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white text-xs font-bold text-center py-1.5 tracking-wider">🔥 SALE</div>
        @endif

        {{-- Thumbnail --}}
        <div class="relative h-40 bg-gradient-to-br from-brand-100 to-accent-100 flex items-center justify-center overflow-hidden">
          @if($course->thumbnail)
            <img src="{{ $course->thumbnail }}" alt="{{ $course->title }}"
                 class="w-full h-full object-cover" loading="lazy"
                 onerror="this.parentElement.innerHTML='<span class=\'text-6xl\'>' + '{{ $icon }}' + '</span>'">
          @else
            <span class="text-6xl">{{ $icon }}</span>
          @endif
          {{-- Enrollments badge --}}
          @if($course->enrollments_count > 0)
          <div class="absolute bottom-2 right-2 bg-black/60 text-white text-xs font-bold px-2 py-1 rounded-lg">
            👥 {{ $course->enrollments_count }}
          </div>
          @endif
        </div>

        <div class="p-5">
          {{-- Category --}}
          <div class="text-xs text-brand-600 font-semibold mb-1">{{ $course->category }}</div>

          {{-- Title --}}
          <h3 class="text-base font-bold text-gray-900 mb-2 group-hover:text-brand-600 transition leading-snug line-clamp-2">
            {{ $course->title }}
          </h3>

          {{-- Description --}}
          <p class="text-gray-400 text-xs leading-relaxed mb-3 line-clamp-2">{{ $course->description }}</p>

          {{-- Meta --}}
          <div class="flex items-center gap-2 flex-wrap mb-4">
            <span class="text-xs {{ $levelColor }} font-semibold px-2.5 py-1 rounded-lg capitalize">{{ $course->level }}</span>
            @if($totalLessons > 0)
            <span class="text-xs bg-gray-100 text-gray-600 font-medium px-2.5 py-1 rounded-lg">📹 {{ $totalLessons }} lessons</span>
            @endif
            @if($course->language === 'hi')
            <span class="text-xs bg-orange-50 text-orange-600 font-medium px-2.5 py-1 rounded-lg">हिंदी</span>
            @elseif($course->language === 'ml')
            <span class="text-xs bg-green-50 text-green-600 font-medium px-2.5 py-1 rounded-lg">മലയാളം</span>
            @endif
          </div>

          {{-- Price + CTA --}}
          <div class="flex items-center justify-between">
            <div>
              @if($course->is_free)
                <span class="text-lg font-black text-green-600">Free</span>
              @else
                <span class="text-lg font-black text-gray-900">₹{{ number_format($course->sale_price ?? $course->price) }}</span>
                @if($course->sale_price && $course->price > $course->sale_price)
                <span class="text-xs text-gray-400 line-through ml-1">₹{{ number_format($course->price) }}</span>
                @endif
              @endif
            </div>
            <a href="/register"
               class="text-xs bg-brand-600 hover:bg-brand-700 text-white font-bold px-4 py-2 rounded-xl transition">
              Enroll →
            </a>
          </div>
        </div>
      </div>
      @empty
      {{-- Fallback if no courses yet --}}
      <div class="col-span-3 text-center py-10 text-gray-400">
        <p>Courses coming soon. <a href="/register" class="text-brand-600 font-semibold">Register to be notified →</a></p>
      </div>
      @endforelse
    </div>
  </div>
</section>

<!-- WHY Skillspot.in -->
<section id="about" class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
      <div>
        <div class="inline-block bg-green-100 text-green-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-5">Why Skillspot.in?</div>
        <h2 class="text-3xl md:text-5xl font-black text-gray-900 mb-6 leading-tight">Built for<br>Real Tech Careers</h2>
        <p class="text-gray-500 text-lg mb-8 leading-relaxed">We teach what the industry actually uses. Hands-on projects, real code, live sessions, and career-ready skills.</p>
        <div class="space-y-4">
          @foreach([
            ['🎯','Industry-Relevant Curriculum','Courses designed with real-world job requirements in mind.'],
            ['👨‍🏫','Expert Instructors','Learn from practicing developers and IT professionals.'],
            ['📜','Verified Certificates','Downloadable certificates for every completed course.'],
            ['💬','Community Support','Student forums, doubt-clearing sessions, and peer learning.'],
            ['📱','Learn on Any Device','Fully mobile-responsive. Learn on phone, tablet or desktop.'],
            ['🔄','Lifetime Access','Enroll once, access forever — including future updates.'],
          ] as [$icon,$title,$desc])
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-brand-50 rounded-xl flex items-center justify-center text-xl flex-shrink-0">{{ $icon }}</div>
            <div>
              <div class="font-semibold text-gray-900 text-sm">{{ $title }}</div>
              <div class="text-gray-400 text-sm mt-0.5">{{ $desc }}</div>
            </div>
          </div>
          @endforeach
        </div>
      </div>

      <!-- Visual card -->
      <div class="bg-gradient-to-br from-brand-900 to-accent-900 rounded-3xl p-7">
        <div class="text-white text-sm font-bold mb-5">🎓 Student Dashboard Preview</div>
        <!-- Progress cards -->
        <div class="space-y-3">
          @foreach([
            ['Full-Stack Web Dev','72%','from-blue-400 to-brand-500'],
            ['Python & AI','45%','from-purple-400 to-accent-500'],
            ['Cybersecurity','100%','from-green-400 to-emerald-500'],
          ] as [$name,$pct,$gradient])
          <div class="bg-white/10 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-white text-sm font-semibold">{{ $name }}</span>
              <span class="text-blue-200 text-xs font-bold">{{ $pct }}</span>
            </div>
            <div class="bg-white/20 rounded-full h-1.5 overflow-hidden">
              <div class="bg-gradient-to-r {{ $gradient }} h-full rounded-full" style="width:{{ $pct }}"></div>
            </div>
          </div>
          @endforeach
        </div>
        <!-- Certificate -->
        <div class="mt-4 bg-gradient-to-r from-yellow-400/20 to-amber-400/20 border border-yellow-400/30 rounded-2xl p-4 flex items-center gap-3">
          <span class="text-2xl">📜</span>
          <div>
            <div class="text-white text-sm font-bold">Certificate Earned!</div>
            <div class="text-yellow-200 text-xs">Cybersecurity Fundamentals</div>
          </div>
        </div>
        <div class="text-center text-gray-500 text-xs mt-4">Sample — your dashboard after enrolling</div>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="py-20 bg-gray-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-3">What Our Students Say</h2>
      <p class="text-gray-500">Real results from real students</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      @php $testimonials = [
        ['name'=>'Rahul Sharma','role'=>'Web Developer','text'=>'Skillspot.in completely changed my career. I went from zero coding knowledge to landing a full-stack developer job in 4 months!','stars'=>5,'avatar'=>'R'],
        ['name'=>'Priya Nair','role'=>'Python Developer','text'=>'The Python & AI course is outstanding. Very practical, well-structured, and the instructor explains everything clearly.','stars'=>5,'avatar'=>'P'],
        ['name'=>'Mohammed Irfan','role'=>'DevOps Engineer','text'=>'The Cloud & DevOps course got me AWS certified. The hands-on labs are exactly what you need for real job interviews.','stars'=>5,'avatar'=>'M'],
      ]; @endphp
      @foreach($testimonials as $t)
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm card-hover">
        <div class="flex items-center gap-1 mb-4">
          @for($i=0;$i<$t['stars'];$i++)<span class="text-yellow-400 text-sm">⭐</span>@endfor
        </div>
        <p class="text-gray-600 text-sm leading-relaxed mb-5">"{{ $t['text'] }}"</p>
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-gradient-to-br from-brand-400 to-accent-400 rounded-xl flex items-center justify-center text-white font-black text-sm flex-shrink-0">{{ $t['avatar'] }}</div>
          <div>
            <div class="font-bold text-gray-900 text-sm">{{ $t['name'] }}</div>
            <div class="text-gray-400 text-xs">{{ $t['role'] }}</div>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-20 gradient-hero text-center px-4">
  <div class="max-w-3xl mx-auto">
    <h2 class="text-3xl md:text-5xl font-black text-white mb-5">Ready to start your<br>tech journey?</h2>
    <p class="text-gray-300 text-lg mb-10">Join thousands of students already learning at Skillspot.in.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/register" class="bg-white text-brand-700 hover:bg-gray-100 px-10 py-4 rounded-2xl text-lg font-black transition shadow-xl active:scale-[0.98]">
        Register Free →
      </a>
      <a href="/login" class="glass text-white px-10 py-4 rounded-2xl text-lg font-medium hover:bg-white/10 transition">
        Already a Student? Login
      </a>
    </div>
  </div>
</section>

@endsection
