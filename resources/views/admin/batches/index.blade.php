@extends('layouts.admin')
@section('page-title','Batch Management')
@section('page-sub','Manage all vendor batches, students & attendance')
@section('admin-content')
<div class="space-y-6">

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif

  {{-- Filters --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">Vendor</label>
        <select name="vendor_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
          <option value="">All Vendors</option>
          @foreach($vendors as $v)
          <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->brand_name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">Type</label>
        <select name="type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
          <option value="">All Types</option>
          <option value="batch" {{ request('type')==='batch'?'selected':'' }}>Batch</option>
          <option value="open"  {{ request('type')==='open'?'selected':'' }}>Open</option>
        </select>
      </div>
      <div class="flex-1 min-w-40">
        <label class="block text-xs font-semibold text-gray-500 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Course title…" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2 rounded-xl text-sm font-semibold transition">Filter</button>
      <a href="{{ route('admin.batches') }}" class="text-sm text-gray-500 hover:text-gray-700 py-2">Clear</a>
      <a href="{{ route('admin.courses.create') }}" class="ml-auto bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-xl text-sm font-semibold transition">+ New Batch Course</a>
    </form>
  </div>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="text-left px-5 py-3 font-semibold text-gray-600">Course</th>
          <th class="text-left px-4 py-3 font-semibold text-gray-600">Vendor</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Type</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Students</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Status</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($batches as $course)
        <tr class="hover:bg-gray-50">
          <td class="px-5 py-3">
            <div class="font-semibold text-gray-900">{{ $course->title }}</div>
            @if($course->batch_name)<div class="text-xs text-purple-600 font-medium">{{ $course->batch_name }}</div>@endif
            @if($course->batch_start_date)<div class="text-xs text-gray-400">📅 {{ $course->batch_start_date->format('d M Y') }} → {{ $course->batch_end_date?->format('d M Y') ?? '—' }}</div>@endif
          </td>
          <td class="px-4 py-3 text-sm text-gray-600">{{ $course->vendor?->brand_name ?? '— Skillspot.in —' }}</td>
          <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">{{ ucfirst($course->course_type) }}</span></td>
          <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $course->enrollments_count }}</td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $course->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
              {{ $course->is_published ? 'Published' : 'Draft' }}
            </span>
          </td>
          <td class="px-4 py-3">
            <div class="flex gap-2 justify-end">
              <a href="{{ route('admin.batches.show', $course) }}" class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 px-3 py-1.5 rounded-lg font-semibold transition">Manage</a>
              <a href="{{ route('admin.batches.report', $course) }}" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition">📊 Report</a>
              <a href="{{ route('admin.courses.edit', $course) }}" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition">✏️ Edit</a>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No courses found. <a href="{{ route('admin.courses.create') }}" class="text-brand-600 hover:underline">Create one</a>.</td></tr>
        @endforelse
      </tbody>
    </table>
    @if($batches->hasPages())
    <div class="px-5 py-3 border-t border-gray-100">{{ $batches->links() }}</div>
    @endif
  </div>
</div>
@endsection
