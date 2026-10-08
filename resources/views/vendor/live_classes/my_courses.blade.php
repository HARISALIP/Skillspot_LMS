@extends('layouts.vendor')
@section('title', 'My Courses — ' . $vendor->brand_name)
@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-5">

  <div>
    <h1 class="text-2xl font-black text-gray-900">📚 My Courses</h1>
    <p class="text-sm text-gray-500 mt-0.5">Completed live class history</p>
  </div>

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
  @endif

  @forelse($completed as $class)
  @php
    $sessCount = $class->sessions->count();
    $allFiles  = $class->sessions->flatMap(fn($s) => $s->files->where('is_visible', true));
    $recCount  = $allFiles->where('file_type','recording')->count();
    $noteCount = $allFiles->where('file_type','notes')->count();
    $resCount  = $allFiles->whereNotIn('file_type',['recording','notes'])->count();
  @endphp
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="h-1 bg-green-500"></div>
    <div class="p-5 flex flex-wrap items-center justify-between gap-4">
      <div class="flex-1 min-w-0">
        <div class="flex flex-wrap gap-2 items-center mb-1.5">
          <span class="font-bold text-gray-900 text-base">{{ $class->title }}</span>
          <span class="text-xs bg-green-100 text-green-700 font-semibold px-2.5 py-0.5 rounded-full">✅ Completed</span>
          @if($class->schedule_type === 'hours_based')
            <span class="text-xs bg-purple-100 text-purple-700 font-semibold px-2.5 py-0.5 rounded-full">⏱ {{ rtrim(rtrim(number_format((float)$class->completed_hours,2),'0'),'.') }}h</span>
          @else
            @if($class->batch_name)<span class="text-xs bg-amber-100 text-amber-700 font-semibold px-2.5 py-0.5 rounded-full">📅 {{ $class->batch_name }}</span>@endif
          @endif
        </div>
        <div class="flex flex-wrap gap-x-4 text-xs text-gray-400">
          <span>{{ $sessCount }} sessions</span>
          <span>{{ $class->enrollments_count }} students</span>
          @if($recCount)  <span>🎬 {{ $recCount }} recording{{ $recCount != 1 ? 's' : '' }}</span>  @endif
          @if($noteCount) <span>📄 {{ $noteCount }} note{{ $noteCount != 1 ? 's' : '' }}</span>      @endif
          @if($resCount)  <span>📦 {{ $resCount }} resource{{ $resCount != 1 ? 's' : '' }}</span>    @endif
          @if($class->schedule_type === 'date_based' && $class->batch_start_date)
            <span>{{ $class->batch_start_date->format('d M Y') }} → {{ $class->batch_end_date?->format('d M Y') ?? 'TBD' }}</span>
          @endif
        </div>
      </div>
      <div class="flex gap-2 flex-shrink-0">
        <a href="{{ route('vendor.live_classes.show', $class->id) }}"
           class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition">
          View History →
        </a>
      </div>
    </div>
  </div>
  @empty
  <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center text-gray-400">
    <div class="text-5xl mb-3">📚</div>
    <p class="font-semibold text-gray-600">No completed courses yet</p>
    <p class="text-sm mt-1">Completed live classes will appear here</p>
    <a href="{{ route('vendor.live_classes.index') }}" class="inline-block mt-4 bg-blue-600 text-white text-sm font-semibold px-5 py-2.5 rounded-xl hover:bg-blue-700 transition">
      Go to Live Classes →
    </a>
  </div>
  @endforelse

</div>
@endsection
