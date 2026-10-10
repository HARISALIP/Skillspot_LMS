@extends('layouts.landing')
@section('title', 'About Us — Skillspot.in')
@section('content')

<!-- Hero -->
<section class="gradient-hero pt-32 pb-20 px-4 text-center relative overflow-hidden">
  <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-brand-200/40 opacity-30 rounded-full blur-3xl pointer-events-none"></div>
  <div class="absolute bottom-1/4 right-1/4 w-72 h-72 bg-accent-200/40 opacity-30 rounded-full blur-3xl pointer-events-none"></div>
  <div class="relative z-10 max-w-3xl mx-auto">
    <div class="inline-flex items-center gap-2 bg-white/90 border border-brand-200/80 rounded-full px-4 py-1.5 text-sm text-brand-700 font-semibold mb-6 shadow-sm">
      <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span> Our Story
    </div>
    <h1 class="text-4xl md:text-6xl font-black text-slate-900 mb-6 tracking-tight">About <span class="gradient-text">Skillspot.in</span></h1>
    <p class="text-xl text-slate-600 leading-relaxed font-normal">We are a passionate team of educators and technologists building the future of tech education in India.</p>
  </div>
</section>

<!-- Mission -->
<section class="py-20 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
      <div>
        <div class="inline-block bg-brand-100 text-brand-600 text-sm font-semibold px-4 py-1.5 rounded-full mb-5">Our Mission</div>
        <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-6 leading-tight">Making Quality Tech Education Accessible to Everyone</h2>
        <p class="text-gray-500 leading-relaxed mb-5">Skillspot.in was founded with a single vision — to bridge the gap between aspiring developers and the skills industry actually demands. We believe quality education should not be a privilege.</p>
        <p class="text-gray-500 leading-relaxed mb-5">Our courses are designed by practicing professionals, updated regularly, and taught with real-world projects so students graduate job-ready — not just certificate-ready.</p>
        <p class="text-gray-500 leading-relaxed">From our first batch of 12 students to thousands of learners across India and beyond, Skillspot.in remains committed to one thing: <strong class="text-gray-900">your success.</strong></p>
      </div>
      <div class="grid grid-cols-2 gap-4">
        @php $stats = [
          ['value'=>'50+',  'label'=>'Courses Available',    'icon'=>'📚','color'=>'bg-blue-50 text-blue-600'],
          ['value'=>'2K+',  'label'=>'Students Enrolled',    'icon'=>'🎓','color'=>'bg-purple-50 text-purple-600'],
          ['value'=>'98%',  'label'=>'Completion Rate',      'icon'=>'✅','color'=>'bg-green-50 text-green-600'],
          ['value'=>'4.9★', 'label'=>'Average Rating',       'icon'=>'⭐','color'=>'bg-yellow-50 text-yellow-600'],
          ['value'=>'15+',  'label'=>'Expert Instructors',   'icon'=>'👨‍🏫','color'=>'bg-red-50 text-red-600'],
          ['value'=>'3yrs', 'label'=>'Years of Excellence',  'icon'=>'🏆','color'=>'bg-indigo-50 text-indigo-600'],
        ]; @endphp
        @foreach($stats as $s)
        <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 card-hover text-center">
          <div class="text-2xl mb-2">{{ $s['icon'] }}</div>
          <div class="text-2xl font-black text-gray-900">{{ $s['value'] }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $s['label'] }}</div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

<!-- Values -->
<section class="py-20 bg-gray-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-3">What We Stand For</h2>
      <p class="text-gray-500 text-lg">The values that guide everything we do</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      @php $values = [
        ['icon'=>'🎯','title'=>'Practical First','desc'=>'Every lesson is built around real-world applications. We teach what companies actually use in production.'],
        ['icon'=>'❤️','title'=>'Student Success','desc'=>'Your achievement is our achievement. We measure ourselves by how well our students do after graduation.'],
        ['icon'=>'🔄','title'=>'Always Updated','desc'=>'Tech evolves fast. Our curriculum evolves with it. Enrolled students get lifetime access to all updates.'],
        ['icon'=>'🌍','title'=>'Inclusive Learning','desc'=>'Education should have no barriers. We offer multiple pricing options to make learning accessible to all.'],
        ['icon'=>'👥','title'=>'Community Driven','desc'=>'Learning is better together. Our student community, forums, and peer support make the journey enjoyable.'],
        ['icon'=>'🏅','title'=>'Verified Credentials','desc'=>'Our certificates carry real weight. Each one is digitally signed, verifiable, and recognised by employers.'],
      ]; @endphp
      @foreach($values as $v)
      <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm card-hover">
        <div class="text-3xl mb-4">{{ $v['icon'] }}</div>
        <h3 class="font-bold text-gray-900 mb-2">{{ $v['title'] }}</h3>
        <p class="text-gray-500 text-sm leading-relaxed">{{ $v['desc'] }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-20 gradient-hero text-center px-4">
  <div class="max-w-2xl mx-auto">
    <h2 class="text-3xl md:text-4xl font-black text-white mb-5">Join Our Growing Community</h2>
    <p class="text-gray-300 mb-8">Start your tech journey with Skillspot.in today — free to register.</p>
    <a href="/register" class="inline-block bg-white text-brand-700 hover:bg-gray-100 px-8 py-4 rounded-2xl font-black transition shadow-xl">Register Free →</a>
  </div>
</section>
@endsection
