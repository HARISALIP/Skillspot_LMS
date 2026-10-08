@extends('layouts.landing')
@section('title', 'Pricing — Skillspot.in')
@section('content')

<!-- Hero -->
<section class="gradient-hero pt-32 pb-20 px-4 text-center relative overflow-hidden">
  <div class="absolute bottom-1/4 left-1/4 w-80 h-80 bg-brand-600 opacity-20 rounded-full blur-3xl pointer-events-none"></div>
  <div class="relative z-10 max-w-3xl mx-auto">
    <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-1.5 text-sm text-blue-200 mb-6">
      <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span> Simple & Transparent
    </div>
    <h1 class="text-4xl md:text-6xl font-black text-white mb-6">Pay Per <span class="gradient-text">Course</span></h1>
    <p class="text-xl text-gray-300 max-w-xl mx-auto">No subscriptions. No packages. Just pay for the course you want — once.</p>
  </div>
</section>

<!-- How it works -->
<section class="py-20 bg-white">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <div class="inline-block bg-brand-100 text-brand-600 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">How It Works</div>
      <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-3">Simple Course-Based Pricing</h2>
      <p class="text-gray-500 text-lg">Each course is priced individually. Pay once, learn at your own pace.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
      @php $steps = [
        ['n'=>'01','icon'=>'🔍','title'=>'Browse Courses',  'desc'=>'Explore our catalog. Each course shows its price, duration, and access type upfront — no surprises.'],
        ['n'=>'02','icon'=>'💳','title'=>'Pay Once',        'desc'=>'Pay a one-time fee per course via UPI, card, or net banking. Secure checkout powered by Razorpay.'],
        ['n'=>'03','icon'=>'🎓','title'=>'Learn & Certify', 'desc'=>'Get instant access, complete at your own pace, and earn a verified certificate on completion.'],
      ]; @endphp
      @foreach($steps as $s)
      <div class="bg-gray-50 rounded-3xl p-7 text-center border border-gray-100 card-hover">
        <div class="w-12 h-12 bg-gradient-to-br from-brand-500 to-accent-500 rounded-2xl flex items-center justify-center text-white font-black text-lg mx-auto mb-4 shadow-md">{{ $s['n'] }}</div>
        <div class="text-3xl mb-3">{{ $s['icon'] }}</div>
        <h3 class="text-base font-bold text-gray-900 mb-2">{{ $s['title'] }}</h3>
        <p class="text-gray-500 text-sm leading-relaxed">{{ $s['desc'] }}</p>
      </div>
      @endforeach
    </div>

    <!-- Access types -->
    <div class="text-center mb-10">
      <h3 class="text-2xl font-black text-gray-900 mb-3">Two Access Types</h3>
      <p class="text-gray-500">Each course clearly states which access type it offers</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">

      <!-- Lifetime -->
      <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-3xl p-7 card-hover">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center text-2xl shadow-md">♾️</div>
          <div>
            <h3 class="font-black text-gray-900 text-lg">Lifetime Access</h3>
            <span class="text-xs bg-green-100 text-green-700 font-bold px-2.5 py-0.5 rounded-full">Most Popular</span>
          </div>
        </div>
        <ul class="space-y-2.5 text-sm text-gray-600">
          @foreach([
            'Pay once — access forever',
            'All future course updates included',
            'Watch at your own pace, anytime',
            'Re-watch lessons unlimited times',
            'Certificate valid for life',
            'No renewal fees ever',
          ] as $f)
          <li class="flex items-center gap-2">
            <span class="w-5 h-5 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xs font-black flex-shrink-0">✓</span>
            {{ $f }}
          </li>
          @endforeach
        </ul>
      </div>

      <!-- Limited -->
      <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-3xl p-7 card-hover">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-2xl shadow-md">📅</div>
          <div>
            <h3 class="font-black text-gray-900 text-lg">Limited Access</h3>
            <span class="text-xs bg-blue-100 text-blue-700 font-bold px-2.5 py-0.5 rounded-full">Lower Price</span>
          </div>
        </div>
        <ul class="space-y-2.5 text-sm text-gray-600">
          @foreach([
            'Access for a defined period (e.g. 6 months, 1 year)',
            'Lower one-time price',
            'Full course content during access window',
            'Certificate earned is valid permanently',
            'Downloadable resources kept forever',
            'Option to extend access if needed',
          ] as $f)
          <li class="flex items-center gap-2">
            <span class="w-5 h-5 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-black flex-shrink-0">✓</span>
            {{ $f }}
          </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- What's always included -->
<section class="py-16 bg-gray-50">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10">
      <h2 class="text-2xl font-black text-gray-900 mb-2">Included in Every Course</h2>
      <p class="text-gray-500 text-sm">Regardless of course price or access type</p>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      @php $included = [
        ['icon'=>'📹','label'=>'HD Video Lessons'],
        ['icon'=>'📄','label'=>'Study Materials'],
        ['icon'=>'✅','label'=>'Practice Quizzes'],
        ['icon'=>'📜','label'=>'Completion Certificate'],
        ['icon'=>'💬','label'=>'Community Access'],
        ['icon'=>'📱','label'=>'Mobile Access'],
        ['icon'=>'🔄','label'=>'Progress Tracking'],
        ['icon'=>'🏆','label'=>'Verified Badge'],
      ]; @endphp
      @foreach($included as $item)
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
        <span class="text-2xl flex-shrink-0">{{ $item['icon'] }}</span>
        <span class="text-sm font-semibold text-gray-700">{{ $item['label'] }}</span>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- Payment methods -->
<section class="py-16 bg-white">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10">
      <h2 class="text-2xl font-black text-gray-900 mb-2">Secure & Flexible Payments</h2>
      <p class="text-gray-500 text-sm">Powered by Razorpay — India's most trusted payment gateway</p>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      @php $methods = [
        ['icon'=>'💳','label'=>'Credit Card',  'sub'=>'Visa, Mastercard, Amex'],
        ['icon'=>'🏧','label'=>'Debit Card',   'sub'=>'All Indian bank cards'],
        ['icon'=>'📱','label'=>'UPI',          'sub'=>'GPay, PhonePe, Paytm'],
        ['icon'=>'🏦','label'=>'Net Banking',  'sub'=>'All major banks'],
      ]; @endphp
      @foreach($methods as $m)
      <div class="bg-gray-50 rounded-2xl border border-gray-100 p-5 text-center card-hover">
        <div class="text-3xl mb-2">{{ $m['icon'] }}</div>
        <div class="font-bold text-gray-900 text-sm">{{ $m['label'] }}</div>
        <div class="text-xs text-gray-400 mt-0.5">{{ $m['sub'] }}</div>
      </div>
      @endforeach
    </div>

    <!-- Guarantee -->
    <div class="bg-green-50 border border-green-200 rounded-2xl p-6 flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
      <span class="text-5xl flex-shrink-0">🛡️</span>
      <div>
        <div class="font-black text-gray-900 text-lg">7-Day Money-Back Guarantee</div>
        <div class="text-sm text-gray-500 mt-1">Not satisfied within 7 days of purchase? Email us at <a href="mailto:info@Skillspot.in" class="text-brand-600 font-semibold hover:underline">info@Skillspot.in</a> for a full refund — no questions asked.</div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="py-20 bg-gray-50">
  <div class="max-w-3xl mx-auto px-4 sm:px-6">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-black text-gray-900 mb-3">Frequently Asked Questions</h2>
    </div>
    @php $faqs = [
      ['q'=>'Do I need to pay a monthly subscription?',
       'a'=>'No. Skillspot.in uses a simple pay-per-course model. You pay once for a course and get access based on the access type (lifetime or limited period). No recurring fees.'],
      ['q'=>'What is the difference between Lifetime and Limited access?',
       'a'=>'Lifetime access means you can watch the course forever — including all future updates. Limited access gives you access for a set period (e.g. 6 months or 1 year) at a lower price. Your certificate is valid permanently either way.'],
      ['q'=>'Can I buy multiple courses?',
       'a'=>'Yes! Each course is purchased independently. You can enroll in as many courses as you want. Each will be available in your student dashboard.'],
      ['q'=>'What happens after my limited access expires?',
       'a'=>'Your enrollment record and certificate remain. You can optionally re-purchase the course to regain access to the video content.'],
      ['q'=>'Is there a free trial?',
       'a'=>'You can register for free and preview intro lessons before purchasing any course. No payment required to browse.'],
      ['q'=>'Are prices inclusive of GST?',
       'a'=>'Prices shown on course pages are exclusive of GST. Applicable taxes (18% GST) are added at checkout.'],
      ['q'=>'How do I get my certificate?',
       'a'=>'Complete all lessons and pass the final quiz. Your certificate is auto-generated and available in your dashboard to download instantly.'],
      ['q'=>'Can I get a refund?',
       'a'=>'Yes. We offer a 7-day money-back guarantee. Contact info@Skillspot.in within 7 days of purchase. Courses with certificates already downloaded are not eligible.'],
    ]; @endphp
    <div class="space-y-3">
      @foreach($faqs as $i => $faq)
      <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <button onclick="toggleFaq({{ $i }})"
                class="w-full flex items-center justify-between px-5 py-4 text-left font-semibold text-gray-900 text-sm hover:bg-gray-50 transition">
          <span>{{ $faq['q'] }}</span>
          <svg id="faq-icon-{{ $i }}" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform ml-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="faq-{{ $i }}" class="hidden px-5 pb-4 text-sm text-gray-500 leading-relaxed border-t border-gray-100 pt-3">{{ $faq['a'] }}</div>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-20 gradient-hero text-center px-4">
  <div class="max-w-2xl mx-auto">
    <h2 class="text-3xl md:text-4xl font-black text-white mb-5">Ready to start learning?</h2>
    <p class="text-gray-300 mb-8 text-lg">Register free — browse courses and enroll in what interests you.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/register" class="bg-white text-brand-700 hover:bg-gray-100 px-8 py-4 rounded-2xl font-black transition shadow-xl active:scale-[0.98]">
        Register Free →
      </a>
      <a href="/contact" class="glass text-white hover:bg-white/10 px-8 py-4 rounded-2xl font-semibold transition active:scale-[0.98]">
        Have Questions?
      </a>
    </div>
  </div>
</section>

@endsection

@push('scripts')
<script>
  function toggleFaq(i) {
    const el   = document.getElementById('faq-' + i);
    const icon = document.getElementById('faq-icon-' + i);
    el.classList.toggle('hidden');
    icon.style.transform = el.classList.contains('hidden') ? '' : 'rotate(180deg)';
  }
</script>
@endpush
