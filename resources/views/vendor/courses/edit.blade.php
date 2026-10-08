@extends('layouts.vendor')
@section('title','Course Settings — Vendor Panel')
@section('header','Course Settings')
@section('content')
<div class="max-w-2xl">
  <div class="mb-4">
    <h2 class="text-lg font-bold text-gray-900">{{ $course->title }}</h2>
    <p class="text-sm text-gray-500">Configure batch type, seat limits, and registration</p>
  </div>

  @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-4 text-sm text-red-800"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ route('vendor.courses.update', $course) }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
    @csrf @method('PUT')

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Course Type</label>
      <select name="course_type" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <option value="open" {{ $course->course_type === 'open' ? 'selected' : '' }}>Open (anyone can join anytime)</option>
        <option value="one_time" {{ $course->course_type === 'one_time' ? 'selected' : '' }}>One-time (single batch, fixed)</option>
        <option value="batch" {{ $course->course_type === 'batch' ? 'selected' : '' }}>Batch (repeating batches with dates)</option>
      </select>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Max Students <span class="text-gray-400">(0 = unlimited)</span></label>
        <input type="number" name="max_students" value="{{ old('max_students', $course->max_students) }}" min="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Batch Name</label>
        <input type="text" name="batch_name" value="{{ old('batch_name', $course->batch_name) }}" placeholder="e.g. Batch 2025-A" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Batch Start Date</label>
        <input type="date" name="batch_start_date" value="{{ old('batch_start_date', $course->batch_start_date?->format('Y-m-d')) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Batch End Date</label>
        <input type="date" name="batch_end_date" value="{{ old('batch_end_date', $course->batch_end_date?->format('Y-m-d')) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
    </div>

    <div class="flex items-center gap-3">
      <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" name="registration_open" value="1" {{ $course->registration_open ? 'checked' : '' }} class="sr-only peer">
        <div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-brand-600 peer-focus:ring-2 peer-focus:ring-brand-300 transition"></div>
        <div class="absolute left-0.5 top-0.5 bg-white w-4 h-4 rounded-full shadow peer-checked:translate-x-5 transition"></div>
      </label>
      <span class="text-sm font-medium text-gray-700">Registration Open</span>
    </div>

    <div class="pt-2 flex items-center gap-3">
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition">Save Settings</button>
      <a href="{{ route('vendor.courses') }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
    </div>
  </form>
</div>
@endsection
