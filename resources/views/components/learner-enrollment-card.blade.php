@props(['enrollment', 'organization' => null])
@php
  $course = $enrollment->course;
  $expired = $enrollment->access_type === 'limited' && $enrollment->expires_at?->isPast();
  $lessons = $course->sections->flatMap->lessons;
  $completed = \DB::table('lesson_progress')->where('user_id', auth()->id())
      ->whereIn('lesson_id', $lessons->pluck('id'))->where('completed', true)->count();
@endphp
<article class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm {{ $expired ? 'opacity-60' : '' }}">
  <div class="relative flex h-32 items-center justify-center overflow-hidden bg-gradient-to-br from-brand-100 to-accent-100">
    <i class="fa-solid fa-book-open text-4xl text-brand-500" aria-hidden="true"></i>
    @if($course->thumbnail)<img src="{{ $course->thumbnail }}" class="absolute inset-0 h-full w-full object-cover" alt="" onerror="this.remove()">@endif
    <span class="absolute right-2 top-2 rounded-lg bg-black/70 px-2 py-1 text-xs font-bold text-white">{{ $enrollment->progress }}%</span>
  </div>
  <div class="p-4">
    <p class="mb-1 text-xs font-semibold text-brand-700">{{ $course->category ?: ($organization?->brand_name ?? 'Skillspot') }}</p>
    <h3 class="line-clamp-2 min-h-10 text-sm font-bold text-gray-900">{{ $course->title }}</h3>
    <div class="mt-3 flex justify-between text-xs text-gray-500"><span>{{ $completed }}/{{ $lessons->count() }} lessons</span><span>{{ $enrollment->status === 'completed' ? 'Completed' : $enrollment->progress.'% complete' }}</span></div>
    <div role="progressbar" aria-label="{{ $course->title }} progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $enrollment->progress }}" class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500" style="width:{{ $enrollment->progress }}%"></div></div>
    @if($expired)<p class="mt-4 text-center text-sm font-semibold text-red-700">Access expired</p>
    @else
      <a href="{{ route('student.learn', [$course, ...($organization ? ['org' => $organization->slug] : [])]) }}" class="mt-4 block min-h-11 rounded-xl bg-brand-600 px-4 py-3 text-center text-sm font-bold text-white hover:bg-brand-700">{{ $enrollment->status === 'completed' ? 'Review lessons' : ($enrollment->progress ? 'Continue learning' : 'Start learning') }}</a>
      @if($organization)<a href="{{ route('vendor.portal.batch', [$organization->slug, $course]) }}" class="mt-2 block text-center text-xs font-semibold text-brand-700">Course materials</a>@endif
    @endif
  </div>
</article>
