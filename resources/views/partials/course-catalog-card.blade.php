@php
  $creator = $course->primaryInstructor?->name ?? $course->vendor?->brand_name ?? 'Skillspot';
  $coverColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $course->thumbnail_color) ? $course->thumbnail_color : '#4b5563';
  $rating = rtrim(rtrim(number_format((float) ($course->reviews_avg_rating ?? 0), 1), '0'), '.');
@endphp
<article class="course-card">
  <a href="{{ $href }}" aria-label="Open {{ $course->title }}" class="course-card-cover block">
    @if($course->thumbnail)
      <img src="{{ $course->thumbnail }}" alt="{{ $course->title }} course cover" loading="lazy" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden')">
      <div class="hidden h-full items-center justify-center bg-gradient-to-tr from-black px-5 text-center text-xl font-extrabold leading-6 text-white" style="--tw-gradient-to: {{ $coverColor }}">{{ $course->title }}</div>
    @else
      <div class="flex h-full items-center justify-center bg-gradient-to-tr from-black px-5 text-center font-extrabold leading-6 text-white {{ mb_strlen($course->title) > 32 ? 'text-lg' : (mb_strlen($course->title) > 20 ? 'text-2xl' : 'text-3xl') }}" style="--tw-gradient-to: {{ $coverColor }}">{{ $course->title }}</div>
    @endif
  </a>
  <div class="course-card-body">
    <div class="mb-2 flex min-h-5 items-center justify-between gap-2 text-sm text-gray-600">
      @if(($course->sections_count ?? 0) > 0)<span aria-label="{{ $course->sections_count }} sections"><i class="fa-solid fa-book-open mr-1.5" aria-hidden="true"></i>{{ $course->sections_count }}</span>@endif
      @if(($course->enrollments_count ?? 0) > 0)<span aria-label="{{ $course->enrollments_count }} learners"><i class="fa-solid fa-users mr-1.5" aria-hidden="true"></i>{{ $course->enrollments_count }}</span>@endif
      @if(!isset($enrollment))<span aria-label="Rating {{ $rating }}" class="text-amber-700"><i class="fa-solid fa-star mr-1.5" aria-hidden="true"></i>{{ $rating }}</span>@endif
      @if($course->enable_certification)<span aria-label="Get Certified" class="text-amber-600"><i class="fa-solid fa-award" aria-hidden="true"></i></span>@endif
    </div>
    @if(isset($manage) && $manage)<p class="mb-1 text-xs font-medium text-gray-500">{{ ucfirst(str_replace('_', ' ', $course->marketplace_status ?? 'draft')) }}</p>@endif
    @if(!isset($manage) && !isset($enrollment) && $course->visibility === 'restricted')<p class="mb-1 text-xs font-medium text-brand-700">Private course</p>@endif
    @if($course->thumbnail)<h2 class="text-lg font-semibold leading-6 text-gray-900"><a href="{{ $href }}">{{ $course->title }}</a></h2>@endif
    <p class="mb-4 mt-1 line-clamp-2 text-sm leading-5 text-gray-700">{{ $course->short_introduction ?: $course->category }}</p>
    @isset($enrollment)
      <div class="course-card-footer">
        <p class="mb-2 text-xs {{ $isExpired ? 'text-red-700' : 'text-gray-600' }}">
          @if($isExpired)Access expired
          @elseif($enrollment->access_type === 'limited' && $enrollment->expires_at)Access until {{ $enrollment->expires_at->format('d M Y') }}
          @else Lifetime access @endif
          @if($enrollment->status === 'completed') · Completed @endif
        </p>
        <div class="mb-2 flex justify-between text-xs text-gray-600"><span>{{ $completedLessons }}/{{ $totalLessons }} lessons</span><span>{{ $enrollment->progress }}%</span></div>
        <div role="progressbar" aria-label="Course progress" aria-valuenow="{{ $enrollment->progress }}" aria-valuemin="0" aria-valuemax="100" class="mb-3 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full bg-brand-600" style="width:{{ $enrollment->progress }}%"></div></div>
        @if($isExpired)<span class="text-sm font-semibold text-red-700">Access expired</span>
        @else<a href="{{ $href }}" class="inline-flex min-h-10 items-center rounded-md bg-brand-600 px-3 text-sm font-semibold text-white hover:bg-brand-700">{{ $enrollment->progress > 0 ? 'Continue learning' : 'Start learning' }}</a>@endif
      </div>
    @else
      <div class="course-card-footer flex items-center justify-between gap-2 text-sm text-gray-700">
        <span class="flex min-w-0 items-center gap-2"><span aria-hidden="true" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-medium">{{ mb_strtoupper(mb_substr($creator, 0, 1)) }}</span><span class="truncate">{{ $creator }}</span></span>
        @if(isset($manage) && $manage)
          <a href="{{ route('vendor.courses.students', $course) }}" class="shrink-0 text-brand-700 hover:underline">Students</a>
        @else
          <span class="shrink-0 font-semibold">{{ $course->is_free ? 'Free' : '₹' . number_format($course->sale_price ?? $course->price, 0) }}</span>
        @endif
      </div>
    @endisset
    @if(isset($manage) && $manage && !$isVendorOnly)
      <div class="mt-3 flex gap-3 border-t border-gray-100 pt-3 text-sm"><a href="{{ route('vendor.courses.edit', $course) }}" class="font-semibold text-brand-700 hover:underline">Edit</a><a href="{{ route('instructor.courses.preview', ['course' => $course, 'from' => 'vendor']) }}" class="text-gray-600 hover:underline">Preview</a></div>
    @endif
  </div>
</article>
