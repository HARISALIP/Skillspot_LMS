@extends('layouts.vendor')
@section('title','Batches — Vendor Panel')
@section('header','Batches')
@section('content')
<div class="space-y-6">

  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-lg font-bold text-gray-900">All Batches</h2>
      <p class="text-sm text-gray-500">Manage your batch courses, students & attendance</p>
    </div>
    
  </div>

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="bg-red-50 text-red-800 border border-red-200 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>@endif

  <div class="grid grid-cols-1 gap-4">
    @forelse($batches as $batch)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <span class="font-bold text-gray-900">{{ $batch->title }}</span>
            @if($batch->batch_name)
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">{{ $batch->batch_name }}</span>
            @endif
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $batch->registration_open ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
              {{ $batch->registration_open ? 'Registration Open' : 'Closed' }}
            </span>
          </div>
          <div class="text-xs text-gray-400 flex items-center gap-4 mt-1">
            @if($batch->batch_start_date)
              <span>📅 {{ $batch->batch_start_date->format('d M Y') }} → {{ $batch->batch_end_date?->format('d M Y') ?? '—' }}</span>
            @endif
            <span>👥 {{ $batch->enrollments_count }} students</span>
            <span>📡 {{ $batch->live_count }} live classes</span>
            @if($batch->max_students > 0)
              <span>🎯 Max {{ $batch->max_students }}</span>
            @endif
          </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <a href="{{ route('vendor.batches.show', $batch) }}" class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 px-3 py-1.5 rounded-lg font-semibold transition">Manage</a>
          <a href="{{ route('vendor.batches.report', $batch) }}" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition">📊 Report</a>
        </div>
      </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
      No batches yet. Create a course with type "Batch" to get started.
    </div>
    @endforelse
  </div>
</div>
@endsection
