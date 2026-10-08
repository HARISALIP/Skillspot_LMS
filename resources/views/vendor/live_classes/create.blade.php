@extends('layouts.vendor')
@section('title', 'Create Live Class — ' . $vendor->brand_name)
@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
  <div class="mb-6">
    <a href="{{ route('vendor.live_classes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Live Classes</a>
    <h1 class="text-2xl font-black text-gray-900 mt-2">Create Live Class</h1>
  </div>

  @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-5 text-sm text-red-700">
      <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ route('vendor.live_classes.store') }}" x-data="{ type: '{{ old('schedule_type','hours_based') }}' }" class="space-y-5">
    @csrf

    {{-- Vendor selector (only shown when teacher/admin has multiple vendors) --}}
    @if(($myVendors ?? collect())->count() > 1)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h2 class="font-bold text-gray-800">🏪 Assign to Vendor</h2>
      <div class="mt-3">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Vendor *</label>
        <select name="vendor_id" required
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          @foreach($myVendors as $v)
            <option value="{{ $v->id }}" {{ old('vendor_id', $vendor->id) == $v->id ? 'selected' : '' }}>
              {{ $v->brand_name }}
            </option>
          @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">Live class will be created under the selected vendor portal.</p>
      </div>
    </div>
    @elseif(($myVendors ?? collect())->count() === 1)
      <input type="hidden" name="vendor_id" value="{{ $myVendors->first()->id }}">
    @endif

    {{-- Schedule Type --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
      <h2 class="font-bold text-gray-800">Schedule Type</h2>
      <div class="grid grid-cols-2 gap-3">
        <label class="cursor-pointer">
          <input type="radio" name="schedule_type" value="hours_based" x-model="type" class="sr-only peer" {{ old('schedule_type','hours_based') === 'hours_based' ? 'checked' : '' }}>
          <div class="peer-checked:border-blue-500 peer-checked:bg-blue-50 border-2 border-gray-200 rounded-xl p-4 text-center transition">
            <div class="text-2xl mb-1">⏱</div>
            <div class="font-bold text-sm text-gray-800">Hours Based</div>
            <div class="text-xs text-gray-500 mt-1">Set total hours. Teacher starts & stops manually. Hours auto-tracked.</div>
          </div>
        </label>
        <label class="cursor-pointer">
          <input type="radio" name="schedule_type" value="date_based" x-model="type" class="sr-only peer" {{ old('schedule_type') === 'date_based' ? 'checked' : '' }}>
          <div class="peer-checked:border-amber-500 peer-checked:bg-amber-50 border-2 border-gray-200 rounded-xl p-4 text-center transition">
            <div class="text-2xl mb-1">📅</div>
            <div class="font-bold text-sm text-gray-800">Batch / Date Based</div>
            <div class="text-xs text-gray-500 mt-1">Fixed batch with start & end date. Students enroll by batch.</div>
          </div>
        </label>
      </div>
    </div>

    {{-- Basic Info --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
      <h2 class="font-bold text-gray-800">Class Details</h2>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Class Title *</label>
        <input type="text" name="title" value="{{ old('title') }}" required
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
               placeholder="e.g. Python Bootcamp, CCNA Batch 3">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
        <textarea name="description" rows="2"
                  class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Brief description...">{{ old('description') }}</textarea>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Scheduled Date & Time <span class="text-gray-400">(optional)</span></label>
        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <p class="text-xs text-gray-400 mt-1">Set a scheduled start time for this live class. Leave blank for no fixed schedule.</p>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Platform *</label>
          <select name="platform" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="zoom" {{ old('platform','zoom') === 'zoom' ? 'selected' : '' }}>Zoom</option>
            <option value="google_meet" {{ old('platform') === 'google_meet' ? 'selected' : '' }}>Google Meet</option>
            <option value="teams" {{ old('platform') === 'teams' ? 'selected' : '' }}>MS Teams</option>
            <option value="other" {{ old('platform') === 'other' ? 'selected' : '' }}>Other</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Max Students <span class="text-gray-400">(0 = unlimited)</span></label>
          <input type="number" name="max_students" value="{{ old('max_students',0) }}" min="0"
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Meeting URL</label>
        <input type="url" name="meeting_url" value="{{ old('meeting_url') }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
               placeholder="https://zoom.us/j/...">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Meeting ID</label>
          <input type="text" name="meeting_id" value="{{ old('meeting_id') }}"
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
          <input type="text" name="meeting_password" value="{{ old('meeting_password') }}"
                 class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
      </div>
    </div>

    {{-- Hours Based fields --}}
    <div x-show="type === 'hours_based'" class="bg-purple-50 rounded-2xl border border-purple-200 p-5 space-y-4">
      <h2 class="font-bold text-purple-800">⏱ Hours Based Settings</h2>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Total Hours *</label>
        <input type="number" name="total_hours" value="{{ old('total_hours') }}" step="0.5" min="0.5"
               class="w-full border border-purple-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
               placeholder="e.g. 20">
        <p class="text-xs text-purple-600 mt-1">Teacher starts & stops each session. Hours are auto-calculated. Class auto-completes when target is reached.</p>
      </div>
    </div>

    {{-- Date Based fields --}}
    <div x-show="type === 'date_based'" class="bg-amber-50 rounded-2xl border border-amber-200 p-5 space-y-4">
      <h2 class="font-bold text-amber-800">📅 Batch Settings</h2>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Batch Name *</label>
        <input type="text" name="batch_name" value="{{ old('batch_name') }}"
               class="w-full border border-amber-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
               placeholder="e.g. Batch 1 — June 2026">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Start Date *</label>
          <input type="date" name="batch_start_date" value="{{ old('batch_start_date') }}"
                 class="w-full border border-amber-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">End Date *</label>
          <input type="date" name="batch_end_date" value="{{ old('batch_end_date') }}"
                 class="w-full border border-amber-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>
      </div>
      <p class="text-xs text-amber-700">Teacher can still manually start & stop sessions within these dates.</p>
    </div>

    <div class="flex gap-3">
      <button type="submit"
              class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition">
        Create Live Class ✅
      </button>
      <a href="{{ route('vendor.live_classes.index') }}"
         class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition text-center">
        Cancel
      </a>
    </div>
  </form>
</div>
@endsection
