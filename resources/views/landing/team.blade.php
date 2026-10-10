@extends('layouts.landing')
@section('title', 'Our Team — Skillspot.in')
@section('content')

<!-- Hero -->
<section class="gradient-hero pt-32 pb-20 px-4 text-center relative overflow-hidden">
  <div class="absolute top-1/4 right-1/4 w-80 h-80 bg-accent-200/40 opacity-30 rounded-full blur-3xl pointer-events-none"></div>
  <div class="relative z-10 max-w-3xl mx-auto">
    <div class="inline-flex items-center gap-2 bg-white/90 border border-brand-200/80 rounded-full px-4 py-1.5 text-sm text-brand-700 font-semibold mb-6 shadow-sm">
      <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span> The People Behind Skillspot.in
    </div>
    <h1 class="text-4xl md:text-6xl font-black text-slate-900 mb-6 tracking-tight">Meet Our <span class="gradient-text">Team</span></h1>
    <p class="text-xl text-slate-600 font-normal">Educators, engineers, and dreamers — united by a love for teaching tech.</p>
  </div>
</section>

<!-- Leadership -->
<section class="py-20 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <div class="inline-block bg-brand-100 text-brand-600 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Leadership</div>
      <h2 class="text-3xl md:text-4xl font-black text-gray-900">Founders & Leaders</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      @php $leaders = [
        ['name'=>'Asif P',         'role'=>'Founder & CEO',          'bio'=>'Visionary behind Skillspot.in. Full-stack developer with 10+ years of industry experience. Passionate about making tech education accessible to everyone.', 'avatar'=>'A', 'color'=>'from-brand-500 to-accent-500', 'skills'=>['PHP','Laravel','Leadership']],
        ['name'=>'Ravi Kumar',     'role'=>'Head of Curriculum',     'bio'=>'Former senior engineer at top tech companies. Designs courses that mirror real industry requirements. Believes in learning by doing.', 'avatar'=>'R', 'color'=>'from-green-500 to-emerald-600', 'skills'=>['Python','AI/ML','Curriculum']],
        ['name'=>'Priya Menon',    'role'=>'Lead Instructor',        'bio'=>'Web development expert with a passion for teaching. Has mentored 500+ students from zero to their first developer job.', 'avatar'=>'P', 'color'=>'from-purple-500 to-accent-600', 'skills'=>['React','Node.js','Mentoring']],
      ]; @endphp
      @foreach($leaders as $m)
      <div class="bg-white rounded-3xl border border-gray-100 shadow-sm card-hover overflow-hidden">
        <div class="bg-gradient-to-br {{ $m['color'] }} p-8 flex flex-col items-center text-center">
          <div class="w-20 h-20 bg-white/20 rounded-3xl flex items-center justify-center text-white font-black text-4xl mb-4 border border-white/30">{{ $m['avatar'] }}</div>
          <h3 class="text-white font-black text-lg">{{ $m['name'] }}</h3>
          <p class="text-blue-200 text-sm mt-1">{{ $m['role'] }}</p>
        </div>
        <div class="p-6">
          <p class="text-gray-500 text-sm leading-relaxed mb-4">{{ $m['bio'] }}</p>
          <div class="flex flex-wrap gap-2">
            @foreach($m['skills'] as $skill)
            <span class="text-xs bg-brand-50 text-brand-700 px-2.5 py-1 rounded-lg font-semibold">{{ $skill }}</span>
            @endforeach
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- Instructors -->
<section class="py-20 bg-gray-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <div class="inline-block bg-accent-100 text-accent-600 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Instructors</div>
      <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-3">Our Expert Instructors</h2>
      <p class="text-gray-500">Industry professionals who bring real experience to the classroom</p>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
      @php $instructors = [
        ['name'=>'Mohammed Irfan', 'subject'=>'Cybersecurity',      'avatar'=>'M', 'color'=>'from-red-400 to-rose-500'],
        ['name'=>'Sneha Pillai',   'subject'=>'Mobile Dev',         'avatar'=>'S', 'color'=>'from-pink-400 to-rose-500'],
        ['name'=>'Arjun Nair',     'subject'=>'Cloud & DevOps',     'avatar'=>'A', 'color'=>'from-blue-400 to-cyan-500'],
        ['name'=>'Deepa Raj',      'subject'=>'Python & AI',        'avatar'=>'D', 'color'=>'from-yellow-400 to-orange-500'],
        ['name'=>'Kiran Thomas',   'subject'=>'Database & SQL',     'avatar'=>'K', 'color'=>'from-green-400 to-teal-500'],
        ['name'=>'Fathima Beevi',  'subject'=>'Web Design',         'avatar'=>'F', 'color'=>'from-purple-400 to-violet-500'],
        ['name'=>'Rahul Sharma',   'subject'=>'JavaScript & React', 'avatar'=>'R', 'color'=>'from-indigo-400 to-blue-500'],
        ['name'=>'Anjali George',  'subject'=>'Soft Skills & HR',   'avatar'=>'A', 'color'=>'from-amber-400 to-yellow-500'],
      ]; @endphp
      @foreach($instructors as $i)
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm card-hover p-5 text-center">
        <div class="w-14 h-14 bg-gradient-to-br {{ $i['color'] }} rounded-2xl flex items-center justify-center text-white font-black text-2xl mx-auto mb-3">{{ $i['avatar'] }}</div>
        <div class="font-bold text-gray-900 text-sm">{{ $i['name'] }}</div>
        <div class="text-xs text-gray-400 mt-0.5">{{ $i['subject'] }}</div>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- Join team CTA -->
<section class="py-20 bg-white">
  <div class="max-w-4xl mx-auto px-4 text-center">
    <div class="bg-gradient-to-r from-brand-50 to-accent-50 border border-brand-200 rounded-3xl p-10">
      <div class="text-4xl mb-4">🚀</div>
      <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-4">Want to Teach at Skillspot.in?</h2>
      <p class="text-gray-500 mb-8">We are always looking for passionate industry professionals to join our instructor community.</p>
      <a href="mailto:info@Skillspot.in" class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-2xl transition shadow-lg shadow-brand-200">
        📧 Apply as Instructor
      </a>
    </div>
  </div>
</section>
@endsection
