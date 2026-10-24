@extends('layouts.student')
@section('title', $vendor->brand_name.' — Skillspot')
@section('page-title', 'My learning')
@section('page-sub', 'Organization learning')
@section('student-content')
  @if(session('success'))<div role="status" class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>@endif
  <div class="mb-6 rounded-2xl bg-gradient-to-r from-brand-600 to-accent-600 p-6 text-white">
    <p class="text-sm text-white/80">{{ $vendor->brand_name }}</p>
    <h2 class="mt-1 text-2xl font-black">Welcome back, {{ auth()->user()->name }}</h2>
    <p class="mt-3 text-sm text-white/90">{{ $enrollments->count() }} courses · {{ $enrollments->where('status', 'completed')->count() }} completed · {{ $liveClassEnrollments->count() }} live classes</p>
  </div>
  <nav aria-label="Organization learning sections" class="mb-6 flex flex-wrap gap-2">
    @foreach(['dashboard'=>'Overview','courses'=>'My courses','catalog'=>'Explore courses','live'=>'Live classes'] as $section=>$label)
      <a href="{{ route('organization.dashboard', ['slug'=>$vendor->slug, ...($section === 'dashboard' ? [] : ['tab'=>$section])]) }}" @if($tab === $section) aria-current="page" @endif class="rounded-xl px-4 py-2.5 text-sm font-semibold {{ $tab === $section ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">{{ $label }}</a>
    @endforeach
  </nav>

  @if(in_array($tab, ['dashboard','courses']))
    <section aria-labelledby="organization-courses-heading" class="mb-8">
      <div class="mb-3 flex items-center justify-between gap-3"><h2 id="organization-courses-heading" class="text-lg font-bold text-gray-900">My courses</h2><span class="text-sm text-gray-500">{{ $enrollments->count() }} courses</span></div>
      @if($enrollments->isEmpty())
        <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center"><p class="text-sm text-gray-600">No organization courses yet.</p><a href="{{ route('organization.dashboard', ['slug'=>$vendor->slug,'tab'=>'catalog']) }}" class="mt-3 inline-flex min-h-11 items-center font-semibold text-brand-700">Explore courses →</a></div>
      @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach($enrollments as $enrollment)<x-learner-enrollment-card :enrollment="$enrollment" :organization="$vendor" />@endforeach</div>
      @endif
    </section>
  @endif

  @if(in_array($tab, ['dashboard','live']))
    <section aria-labelledby="organization-live-heading" class="mb-8">
      <h2 id="organization-live-heading" class="mb-3 text-lg font-bold text-gray-900">Live classes</h2>
      @forelse($liveClassEnrollments as $enrollment)
        @php($live = $enrollment->liveClass)
        <a href="{{ route('vendor.portal.live_class.detail', [$vendor->slug, $live->slug]) }}" class="mb-3 flex min-h-20 items-center justify-between gap-3 rounded-2xl border border-gray-100 bg-white p-4 hover:border-brand-300">
          <span><strong class="block text-sm text-gray-900">{{ $live->title }}</strong><small class="text-gray-500">{{ $live->status === 'live' ? 'Live now' : ucfirst($live->status) }} · {{ $live->sessions->count() }} sessions</small></span><span class="font-semibold text-brand-700">Open →</span>
        </a>
      @empty
        @if($tab === 'live')<p class="rounded-2xl border border-gray-100 bg-white p-6 text-sm text-gray-600">No standalone live classes are assigned to you.</p>@endif
      @endforelse
      @foreach($upcomingLive as $lesson)<x-live-class-card :lesson="$lesson" />@endforeach
    </section>
  @endif

  @if(in_array($tab, ['dashboard','catalog']))
    <section aria-labelledby="organization-catalog-heading">
      <h2 id="organization-catalog-heading" class="mb-3 text-lg font-bold text-gray-900">Explore {{ $vendor->brand_name }} courses</h2>
      @if($catalog->isEmpty())<p class="rounded-2xl border border-gray-100 bg-white p-6 text-sm text-gray-600">No courses are available for your classes yet.</p>
      @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
          @foreach($catalog as $course)
            <article class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
              <div class="relative flex h-28 items-center justify-center overflow-hidden bg-gradient-to-br from-brand-100 to-accent-100"><i class="fa-solid fa-book-open text-4xl text-brand-500" aria-hidden="true"></i>@if($course->thumbnail)<img src="{{ $course->thumbnail }}" class="absolute inset-0 h-full w-full object-cover" alt="" onerror="this.remove()">@endif</div>
              <div class="p-4"><p class="text-xs font-semibold text-brand-700">{{ $course->category ?: $vendor->brand_name }}</p><h3 class="mt-1 min-h-10 text-sm font-bold text-gray-900">{{ $course->title }}</h3><p class="mt-2 text-xs text-gray-500">{{ $course->is_free ? 'Free' : '₹'.number_format($course->price, 2) }} · {{ $course->enrollments_count ?? 0 }} learners</p><a href="{{ route('vendor.portal.courses.show', [$vendor->slug, $course]) }}" class="mt-4 block min-h-11 rounded-xl bg-brand-600 px-4 py-3 text-center text-sm font-bold text-white">View course</a></div>
            </article>
          @endforeach
        </div>
      @endif
    </section>
  @endif
@endsection
