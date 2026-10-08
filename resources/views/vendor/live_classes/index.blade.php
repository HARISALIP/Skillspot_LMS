@extends('layouts.vendor')
@section('title', 'Live Classes — ' . $vendor->brand_name)
@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-black text-gray-900">📡 Live Classes</h1>
      <p class="text-sm text-gray-500 mt-0.5">Hours-based & batch-date sessions</p>
    </div>
    @if(auth()->user()->hasRole(['teacher','admin','super-admin','vendor']))
    <a href="{{ route('vendor.live_classes.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
      + Create Live Class
    </a>
    @endif
  </div>

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
  @endif

  @forelse($classes as $class)
  @php
    $isLive = $class->status === 'live';
    $isDone = $class->status === 'completed';
    $activeSession = $class->sessions->where('status','live')->first();
  @endphp
  <div class="bg-white rounded-2xl border {{ $isLive ? 'border-red-300 shadow-red-100' : 'border-gray-100' }} shadow-sm overflow-hidden">
    {{-- Top bar --}}
    <div class="h-1.5 {{ $isLive ? 'bg-red-500 animate-pulse' : ($isDone ? 'bg-green-500' : 'bg-blue-500') }}"></div>
    <div class="p-5">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="font-bold text-gray-900 text-base">{{ $class->title }}</h3>
            @if($isLive)
              <span class="inline-flex items-center gap-1 bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">
                🔴 LIVE NOW
              </span>
            @elseif($isDone)
              <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">✅ Completed</span>
            @elseif($class->status === 'cancelled')
              <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full">Cancelled</span>
            @else
              <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">Active</span>
            @endif
            {{-- Schedule type badge --}}
            @if($class->schedule_type === 'hours_based')
              <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-2 py-0.5 rounded-full">⏱ Hours Based</span>
            @else
              <span class="bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">📅 Batch</span>
            @endif
          </div>

          {{-- Meta info --}}
          <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
            @if($class->schedule_type === 'hours_based')
              <span>🎯 {{ $class->completed_hours }}h / {{ $class->total_hours }}h total</span>
              <span>📊 {{ $class->progressPercent() }}% done</span>
            @else
              @if($class->batch_name)<span>🏷 {{ $class->batch_name }}</span>@endif
              @if($class->batch_start_date)
                <span>📅 {{ $class->batch_start_date->format('d M Y') }} → {{ $class->batch_end_date?->format('d M Y') ?? 'TBD' }}</span>
              @endif
            @endif
            <span>👥 {{ $class->enrollments_count }} enrolled{{ $class->max_students ? ' / '.$class->max_students.' max' : '' }}</span>
            <span>🖥 {{ ucfirst(str_replace('_',' ',$class->platform)) }}</span>
          </div>

          {{-- Hours progress bar --}}
          @if($class->schedule_type === 'hours_based' && $class->total_hours > 0)
          <div class="mt-3 flex items-center gap-2">
            <div class="flex-1 bg-gray-200 rounded-full h-2">
              <div class="h-2 rounded-full bg-blue-500 transition-all" style="width:{{ $class->progressPercent() }}%"></div>
            </div>
            <span class="text-xs text-gray-500 w-10 text-right">{{ $class->progressPercent() }}%</span>
          </div>
          @endif

          {{-- Active session info --}}
          @if($isLive && $activeSession)
          <div class="mt-2 text-xs text-red-600 font-semibold">
            🔴 Session started: {{ $activeSession->started_at->format('h:i A') }} ({{ $activeSession->started_at->diffForHumans() }})
          </div>
          @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2 flex-shrink-0">
          <a href="{{ route('vendor.live_classes.show', $class->id) }}"
             class="text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition">
            Manage →
          </a>
          @if($isLive)
          <form method="POST" action="{{ route('vendor.live_classes.stop', $class->id) }}">
            @csrf
            <button onclick="return confirm('Stop this session?')"
                    class="text-sm font-bold bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl transition">
              ⏹ Stop
            </button>
          </form>
          @endif
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center text-gray-400">
    <div class="text-5xl mb-3">📡</div>
    <p class="font-semibold text-gray-600">No live classes yet</p>
    <p class="text-sm mt-1">Create your first live class to get started</p>
    <a href="{{ route('vendor.live_classes.create') }}"
       class="inline-block mt-4 bg-blue-600 text-white text-sm font-semibold px-5 py-2.5 rounded-xl hover:bg-blue-700 transition">
      + Create Live Class
    </a>
  </div>
  @endforelse

</div>
@endsection
