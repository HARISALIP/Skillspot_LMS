@extends('layouts.student')
@section('title', $course->title.' — Skillspot.in')
@section('page-title', $course->title)
@section('page-sub', $course->category.' · '.ucfirst($course->level))

@section('student-content')

@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-4 py-3 text-sm font-semibold">
  ❌ {{ session('error') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-xl">×</button>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- ── Left: Course info ───────────────────────────────────────── --}}
  <div class="lg:col-span-2 space-y-5">

    {{-- Hero --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="h-48 md:h-64 bg-gradient-to-br from-brand-100 to-accent-100 relative overflow-hidden">
        @if($course->thumbnail)
          <img src="{{ $course->thumbnail }}" class="w-full h-full object-cover" alt="{{ $course->title }}">
        @else
          <div class="flex items-center justify-center h-full text-8xl">📚</div>
        @endif
        @if($hasAccess)
        <div class="absolute top-3 left-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">✅ Enrolled</div>
        @elseif($course->is_free)
        <div class="absolute top-3 left-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">🆓 Free</div>
        @endif
      </div>
      <div class="p-5 md:p-6">
        <div class="flex items-center gap-2 mb-2 flex-wrap">
          <span class="text-xs bg-brand-100 text-brand-700 font-semibold px-2.5 py-1 rounded-lg">{{ $course->category }}</span>
          <span class="text-xs bg-gray-100 text-gray-600 font-medium px-2.5 py-1 rounded-lg capitalize">{{ $course->level }}</span>
          @if($totalLessons > 0)
          <span class="text-xs bg-gray-100 text-gray-600 font-medium px-2.5 py-1 rounded-lg">📹 {{ $totalLessons }} lessons</span>
          @endif
          @if($totalDuration > 0)
          <span class="text-xs bg-gray-100 text-gray-600 font-medium px-2.5 py-1 rounded-lg">⏱ {{ round($totalDuration/60,1) }}h</span>
          @endif
          <span class="text-xs bg-gray-100 text-gray-600 font-medium px-2.5 py-1 rounded-lg">👥 {{ $course->enrollments_count }} enrolled</span>
        </div>
        <h1 class="text-xl md:text-2xl font-black text-gray-900 mb-3">{{ $course->title }}</h1>
        @if($course->description)
        <p class="text-gray-600 text-sm leading-relaxed">{{ $course->description }}</p>
        @endif
      </div>
    </div>

    {{-- What you'll learn --}}
    @if(count($outcomes))
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-black text-gray-900 mb-4 flex items-center gap-2">🎯 What You'll Learn</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        @foreach($outcomes as $o)
        <div class="flex items-start gap-2 text-sm text-gray-700">
          <span class="text-green-500 font-bold mt-0.5 flex-shrink-0">✓</span>
          <span>{{ $o }}</span>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Requirements --}}
    @if(count($requirements))
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-black text-gray-900 mb-4">📋 Requirements</h3>
      <ul class="space-y-2">
        @foreach($requirements as $r)
        <li class="flex items-start gap-2 text-sm text-gray-600">
          <span class="text-gray-400 mt-0.5">•</span>{{ $r }}
        </li>
        @endforeach
      </ul>
    </div>
    @endif

    {{-- Curriculum --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-black text-gray-900">📚 Course Curriculum</h3>
        <p class="text-xs text-gray-400 mt-1">{{ $course->sections->count() }} sections · {{ $totalLessons }} lessons</p>
      </div>
      <div class="divide-y divide-gray-50">
        @foreach($course->sections as $section)
        <div>
          <div class="flex items-center justify-between px-5 py-3 bg-gray-50 cursor-pointer"
               onclick="this.nextElementSibling.classList.toggle('hidden')">
            <span class="font-semibold text-gray-900 text-sm">{{ $section->title }}</span>
            <span class="text-xs text-gray-400">{{ $section->lessons->count() }} lessons</span>
          </div>
          <div class="divide-y divide-gray-50">
            @foreach($section->lessons as $lesson)
            <div class="flex items-center gap-3 px-5 py-2.5">
              <span class="text-base flex-shrink-0">
                @if($lesson->type==='video') 🎬
                @elseif($lesson->type==='quiz') 📝
                @elseif($lesson->type==='pdf') 📄
                @elseif($lesson->type==='live') 📡
                @else 📖 @endif
              </span>
              <span class="flex-1 text-sm text-gray-700">{{ $lesson->title }}</span>
              @if($lesson->is_preview && !$hasAccess)
              <span class="text-xs text-green-600 font-semibold flex-shrink-0">Free preview</span>
              @elseif(!$hasAccess)
              <span class="text-gray-300 flex-shrink-0">🔒</span>
              @endif
              @if($lesson->duration)
              <span class="text-xs text-gray-400 flex-shrink-0">{{ $lesson->duration }}m</span>
              @endif
            </div>
            @endforeach
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- ── Right: Enroll/Pay card ───────────────────────────────────── --}}
  <div class="lg:col-span-1">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden sticky top-20">

      @if($hasAccess && !$isExpired)
      {{-- Already enrolled --}}
      <div class="p-5">
        <div class="text-center mb-4">
          <div class="text-4xl mb-2">🎓</div>
          <div class="font-black text-green-700 text-lg">You're Enrolled!</div>
          @if($enrollment)
          <div class="text-sm text-gray-500 mt-1">Progress: <strong>{{ $enrollment->progress }}%</strong></div>
          <div class="bg-gray-100 rounded-full h-2 overflow-hidden mt-2">
            <div class="bg-gradient-to-r from-brand-500 to-accent-500 h-full rounded-full" style="width:{{ $enrollment->progress }}%"></div>
          </div>
          @if($enrollment->access_type==='limited' && $enrollment->expires_at)
          <div class="text-xs text-blue-600 font-semibold mt-2">📅 Access until {{ $enrollment->expires_at->format('d M Y') }}</div>
          @else
          <div class="text-xs text-green-600 font-semibold mt-2">♾️ Lifetime access</div>
          @endif
          @endif
        </div>
        <a href="{{ route('student.learn', $course->id) }}"
           class="block w-full text-center py-3.5 bg-brand-600 hover:bg-brand-700 text-white font-black rounded-2xl transition shadow-lg text-sm">
          ▶️ {{ $enrollment && $enrollment->progress > 0 ? 'Continue Learning' : 'Start Learning' }}
        </a>
      </div>

      @elseif($isExpired)
      {{-- Access expired --}}
      <div class="p-5 text-center">
        <div class="text-4xl mb-2">⏰</div>
        <div class="font-black text-red-700 mb-1">Access Expired</div>
        <div class="text-xs text-gray-500 mb-4">Your access expired on {{ $enrollment->expires_at->format('d M Y') }}</div>
        <a href="/contact" class="block w-full text-center py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl text-sm transition">
          📧 Contact to Renew
        </a>
      </div>

      @else
      {{-- Enroll / Pay --}}
      <div class="p-5">
        {{-- Price --}}
        <div class="text-center mb-4">
          @if($course->is_free)
            <div class="text-3xl font-black text-green-600 mb-1">Free</div>
            <div class="text-xs text-gray-400">No payment required</div>
          @else
            <div class="text-3xl font-black text-gray-900 mb-1">
              ₹{{ number_format($course->sale_price ?? $course->price) }}
            </div>
            @if($course->sale_price)
            <div class="text-sm text-gray-400">
              <span class="line-through">₹{{ number_format($course->price) }}</span>
              <span class="text-red-500 font-bold ml-1">{{ round((1 - $course->sale_price/$course->price)*100) }}% OFF</span>
            </div>
            @endif
          @endif
        </div>

        {{-- CTA --}}
        @auth
          @if($course->is_free)
          <form method="POST" action="{{ route('student.enroll-free', $course->id) }}">
            @csrf
            <button type="submit" class="w-full py-4 bg-green-600 hover:bg-green-700 text-white font-black rounded-2xl transition shadow-lg text-sm">
              🆓 Enroll Free Now
            </button>
          </form>
          @else
          <button id="payBtn" onclick="startPayment()"
                  class="w-full py-4 bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-700 hover:to-accent-700 text-white font-black rounded-2xl transition shadow-lg text-sm flex items-center justify-center gap-2">
            💳 Enroll Now — ₹{{ number_format($course->sale_price ?? $course->price) }}
          </button>
          @endif
        @else
          <a href="/login" class="block w-full text-center py-4 bg-brand-600 hover:bg-brand-700 text-white font-black rounded-2xl transition shadow-lg text-sm">
            Login to Enroll →
          </a>
          <a href="/register" class="block w-full text-center py-2.5 mt-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-sm transition">
            Create Free Account
          </a>
        @endauth

        {{-- Features --}}
        <div class="mt-5 space-y-2.5 text-xs text-gray-500">
          @foreach([
            ['🔒','Secure payment via Razorpay'],
            ['♾️','Lifetime access'],
            ['📜','Certificate on completion'],
            ['📱','Access on any device'],
            ['🛡️','7-day money-back guarantee'],
          ] as [$icon,$text])
          <div class="flex items-center gap-2"><span>{{ $icon }}</span>{{ $text }}</div>
          @endforeach
        </div>
      </div>
      @endif

    </div>
  </div>
</div>

{{-- Payment success toast --}}
<div id="paySuccess" class="hidden fixed bottom-6 right-6 bg-green-600 text-white px-6 py-4 rounded-2xl shadow-2xl z-50 text-sm font-semibold flex items-center gap-3">
  🎉 <span id="paySuccessMsg">Payment successful!</span>
</div>

@endsection

@push('scripts')
@if(!$course->is_free && !$hasAccess && auth()->check())
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
async function startPayment() {
  const btn = document.getElementById('payBtn');
  btn.disabled = true;
  btn.innerHTML = '⏳ Initializing…';

  try {
    const res  = await fetch('{{ route('student.create-order', $course->id) }}', {
      method: 'POST',
      headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
    });
    const data = await res.json();
    if (!res.ok) { alert(data.error || 'Failed to create order.'); btn.disabled=false; btn.innerHTML='💳 Enroll Now'; return; }

    const options = {
      key         : data.key_id,
      amount      : data.amount,
      currency    : data.currency,
      name        : '{{ \App\Models\Setting::get('site_name','Skillspot.in') }}',
      description : data.course,
      order_id    : data.order_id,
      prefill     : { name: data.user_name, email: data.user_email, contact: data.user_phone },
      theme       : { color: '#2563eb' },
      handler: async function(response) {
        // Verify on server
        const vres = await fetch('{{ route('student.verify-payment', $course->id) }}', {
          method: 'POST',
          headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
          body: JSON.stringify({
            razorpay_order_id  : response.razorpay_order_id,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature : response.razorpay_signature,
          }),
        });
        const vdata = await vres.json();
        if (vdata.success) {
          const toast = document.getElementById('paySuccess');
          document.getElementById('paySuccessMsg').textContent = vdata.message;
          toast.classList.remove('hidden');
          setTimeout(() => { window.location.href = vdata.redirect; }, 2000);
        } else {
          alert(vdata.error || 'Verification failed.');
          btn.disabled=false; btn.innerHTML='💳 Enroll Now';
        }
      },
      modal: { ondismiss: function() { btn.disabled=false; btn.innerHTML='💳 Enroll Now'; } },
    };
    new Razorpay(options).open();
  } catch(e) {
    alert('Error: '+e.message);
    btn.disabled=false;
    btn.innerHTML='💳 Enroll Now';
  }
}
</script>
@endif
@endpush
